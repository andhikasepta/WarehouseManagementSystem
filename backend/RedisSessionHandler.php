<?php
// backend/RedisSessionHandler.php — Redis Session Handler for WMS
// Target Middleware VM: 103.123.100.11:6379 (Timeout: 15 minutes / 900s)

if (!class_exists('RedisSessionHandler')) {
    class RedisSessionHandler implements SessionHandlerInterface {
        private $host;
        private $port;
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
            $ttl = 900
        ) {
            $this->host = $host;
            $this->port = (int)$port;
            $this->timeout = (float)$timeout;
            $this->prefix = $prefix;
            $this->ttl = (int)$ttl;
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
                    $connected = @$redis->connect($this->host, $this->port, $this->timeout);
                    if ($connected) {
                        $this->redisExtObj = $redis;
                        return true;
                    }
                } catch (\Throwable $e) {
                    $this->redisExtObj = null;
                }
            }

            // 2. Direct TCP Socket (RESP protocol) fallback (Works everywhere without PECL)
            $errno = 0;
            $errstr = '';
            $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            if ($this->socket) {
                $sec = (int)$this->timeout;
                $usec = (int)(($this->timeout - $sec) * 1000000);
                stream_set_timeout($this->socket, $sec, $usec);
                return true;
            }

            error_log("RedisSessionHandler: Failed to connect to Redis at {$this->host}:{$this->port} - {$errstr}");
            return false;
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

            $cmd = '*' . count($args) . "\r\n";
            foreach ($args as $arg) {
                $argStr = (string)$arg;
                $cmd .= '$' . strlen($argStr) . "\r\n" . $argStr . "\r\n";
            }

            if (@fwrite($this->socket, $cmd) === false) {
                $this->close();
                return false;
            }

            return $this->readResponse();
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
            $key = $this->prefix . $sessionId;
            $data = $this->sendCommand(['GET', $key]);
            return is_string($data) ? $data : '';
        }

        #[\ReturnTypeWillChange]
        public function write($sessionId, $data) {
            $key = $this->prefix . $sessionId;
            $res = $this->sendCommand(['SETEX', $key, (string)$this->ttl, $data]);
            return ($res === 'OK' || $res === '+OK' || $res === true || $res === 1);
        }

        #[\ReturnTypeWillChange]
        public function destroy($sessionId) {
            $key = $this->prefix . $sessionId;
            $this->sendCommand(['DEL', $key]);
            return true;
        }

        #[\ReturnTypeWillChange]
        public function gc($maxlifetime) {
            return true; // Redis automatically manages key expiry using TTL (SETEX)
        }
    }
}
