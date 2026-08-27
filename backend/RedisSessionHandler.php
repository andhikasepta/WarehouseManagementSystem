<?php
// backend/RedisSessionHandler.php — Secure Redis Session Handler for WMS
// Target Middleware VM: 103.123.100.11:6379 (Timeout: 20 minutes / 1200s)
// Supports: Native PECL Redis, Direct Socket (RESP), Redis AUTH, Redis 6+ ACL, and TLS/SSL encryption

if (!class_exists('RedisSessionHandler')) {
    class RedisSessionHandler implements SessionHandlerInterface {
        private $host;
        private $port;
        private $password;
        private $username;
        private $useTls;
        private $timeout;
        private $prefix;
        private $ttl;
        private $socket = null;
        private $redisExtObj = null;

        public function __construct(
            $host = '103.123.100.11',
            $port = 6379,
            $timeout = 2.5,
            $prefix = 'PHPREDIS_SESSION:',
            $ttl = 1200,
            $password = null,
            $username = null,
            $useTls = false
        ) {
            $this->host = $host;
            $this->port = (int)$port;
            $this->timeout = (float)$timeout;
            $this->prefix = $prefix;
            $this->ttl = (int)$ttl;
            $this->password = !empty($password) ? (string)$password : null;
            $this->username = !empty($username) ? (string)$username : null;
            $this->useTls = (bool)$useTls;
        }

        private function sanitizeKey($sessionId) {
            if (!is_string($sessionId) || !preg_match('/^[a-zA-Z0-9,-]+$/', $sessionId)) {
                return null;
            }
            return $this->prefix . $sessionId;
        }

        private function connect() {
            if ($this->redisExtObj !== null) {
                return true;
            }
            if ($this->socket && is_resource($this->socket) && !feof($this->socket)) {
                return true;
            }

            // 1. Try PHP Redis C-Extension if available
            if (extension_loaded('redis') && class_exists('Redis')) {
                try {
                    $redis = new \Redis();
                    $host = $this->useTls ? 'tls://' . $this->host : $this->host;
                    $connected = @$redis->connect($host, $this->port, $this->timeout);
                    if ($connected) {
                        if ($this->password !== null) {
                            if ($this->username !== null) {
                                $authed = @$redis->auth([$this->username, $this->password]);
                            } else {
                                $authed = @$redis->auth($this->password);
                            }
                            if (!$authed) {
                                error_log("RedisSessionHandler: Redis AUTH failed for {$this->host}:{$this->port}");
                                $redis->close();
                                return false;
                            }
                        }
                        $this->redisExtObj = $redis;
                        return true;
                    }
                } catch (\Throwable $e) {
                    $this->redisExtObj = null;
                }
            }

            // 2. Direct TCP / TLS Socket (RESP protocol) fallback (Works everywhere without PECL)
            $errno = 0;
            $errstr = '';
            $protocol = $this->useTls ? 'tls://' : 'tcp://';
            $remoteSocket = "{$protocol}{$this->host}:{$this->port}";
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $this->socket = @stream_socket_client(
                $remoteSocket,
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($this->socket) {
                $sec = (int)$this->timeout;
                $usec = (int)(($this->timeout - $sec) * 1000000);
                stream_set_timeout($this->socket, $sec, $usec);

                // Authenticate if password is provided
                if ($this->password !== null) {
                    $authArgs = ($this->username !== null) 
                        ? ['AUTH', $this->username, $this->password] 
                        : ['AUTH', $this->password];
                    
                    $authRes = $this->sendRawSocketCommand($authArgs);
                    if ($authRes !== 'OK' && $authRes !== '+OK') {
                        error_log("RedisSessionHandler: Redis socket AUTH failed for {$this->host}:{$this->port}");
                        $this->close();
                        return false;
                    }
                }
                return true;
            }

            error_log("RedisSessionHandler: Failed to connect to Redis at {$this->host}:{$this->port} - {$errstr}");
            return false;
        }

        private function sendRawSocketCommand(array $args) {
            if (!$this->socket) return false;
            $cmd = '*' . count($args) . "\r\n";
            foreach ($args as $arg) {
                $argStr = (string)$arg;
                $cmd .= '$' . strlen($argStr) . "\r\n" . $argStr . "\r\n";
            }
            if (@fwrite($this->socket, $cmd) === false) {
                return false;
            }
            return $this->readResponse();
        }

        private function sendCommand(array $args) {
            if ($this->redisExtObj !== null) {
                $cmd = strtoupper($args[0]);
                try {
                    if ($cmd === 'GET') {
                        return $this->redisExtObj->get($args[1]);
                    } elseif ($cmd === 'SETEX') {
                        return $this->redisExtObj->setex($args[1], (int)$args[2], $args[3]);
                    } elseif ($cmd === 'DEL') {
                        return $this->redisExtObj->del($args[1]);
                    }
                } catch (\Throwable $e) {
                    error_log("RedisSessionHandler extension error: " . $e->getMessage());
                }
            }

            if (!$this->connect()) {
                return false;
            }

            return $this->sendRawSocketCommand($args);
        }

        private function readResponse() {
            if (!$this->socket) return null;
            $line = fgets($this->socket);
            if ($line === false) {
                $this->close();
                return null;
            }
            $line = trim($line);
            if ($line === '') return null;
            $type = $line[0];
            $payload = substr($line, 1);

            switch ($type) {
                case '+': // Simple string
                    return $payload;
                case '-': // Error
                    return false;
                case ':': // Integer
                    return (int)$payload;
                case '$': // Bulk string
                    $len = (int)$payload;
                    if ($len === -1) return null;
                    $data = '';
                    $remaining = $len;
                    while ($remaining > 0 && !feof($this->socket)) {
                        $chunk = fread($this->socket, min($remaining, 8192));
                        if ($chunk === false) break;
                        $data .= $chunk;
                        $remaining -= strlen($chunk);
                    }
                    fread($this->socket, 2); // Read trailing \r\n
                    return $data;
                case '*': // Array
                    $count = (int)$payload;
                    if ($count === -1) return null;
                    $arr = [];
                    for ($i = 0; $i < $count; $i++) {
                        $arr[] = $this->readResponse();
                    }
                    return $arr;
                default:
                    return null;
            }
        }

        #[\ReturnTypeWillChange]
        public function open($savePath, $sessionName) {
            return $this->connect();
        }

        #[\ReturnTypeWillChange]
        public function close() {
            if ($this->redisExtObj !== null) {
                try {
                    $this->redisExtObj->close();
                } catch (\Throwable $e) {}
                $this->redisExtObj = null;
            }
            if ($this->socket && is_resource($this->socket)) {
                @fclose($this->socket);
                $this->socket = null;
            }
            return true;
        }

        #[\ReturnTypeWillChange]
        public function read($sessionId) {
            $key = $this->sanitizeKey($sessionId);
            if ($key === null) return '';
            $data = $this->sendCommand(['GET', $key]);
            return is_string($data) ? $data : '';
        }

        #[\ReturnTypeWillChange]
        public function write($sessionId, $data) {
            $key = $this->sanitizeKey($sessionId);
            if ($key === null) return false;
            $res = $this->sendCommand(['SETEX', $key, (string)$this->ttl, $data]);
            return ($res === 'OK' || $res === '+OK' || $res === true || $res === 1);
        }

        #[\ReturnTypeWillChange]
        public function destroy($sessionId) {
            $key = $this->sanitizeKey($sessionId);
            if ($key === null) return false;
            $this->sendCommand(['DEL', $key]);
            return true;
        }

        #[\ReturnTypeWillChange]
        public function gc($maxlifetime) {
            return true; // Redis automatically manages key expiry using TTL (SETEX)
        }
    }
}
