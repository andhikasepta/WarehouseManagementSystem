<?php
// backend/config/database.php

require_once __DIR__ . '/../paths.php';

// Auto-load .env file if available
if (!function_exists('loadEnvFile')) {
    function loadEnvFile($envPath) {
        if (!file_exists($envPath)) return;
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (getenv($key) === false) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}
loadEnvFile(ROOT_PATH . '.env');

$driver   = getenv('DB_DRIVER')   ?: 'mysql'; // 'mysql' or 'pgsql'
$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$port     = getenv('DB_PORT')     ?: ($driver === 'pgsql' ? '5432' : '3306');
$user     = getenv('DB_USER')     ?: ($driver === 'pgsql' ? 'postgres' : 'root');
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$dbname   = getenv('DB_NAME')     ?: 'dashboard_db';

// Mac (MAMP) default override if on mysql
if ($driver === 'mysql' && (strtoupper(substr(PHP_OS, 0, 3)) === 'MAC' || PHP_OS === 'Darwin')) {
    if (getenv('DB_PORT') === false) $port = '8889';
    if (getenv('DB_PASSWORD') === false) $password = 'root';
}

try {
    if ($driver === 'pgsql') {
        // PostgreSQL connection handling
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $pdo = new PDO($dsn, $user, $password);
        } catch (PDOException $ex) {
            // Create database if not exists
            $initDsn = "pgsql:host=$host;port=$port;dbname=postgres";
            $initPdo = new PDO($initDsn, $user, $password);
            $initPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $initPdo->exec("CREATE DATABASE \"$dbname\"");
            
            $pdo = new PDO($dsn, $user, $password);
        }
    } else {
        // MySQL connection handling
        $dsn = "mysql:host=$host;port=$port";
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $pdo->exec("USE `$dbname`");
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => "Connection failed: " . $e->getMessage()]));
}

if (!function_exists('getSystemAppVersion')) {
    function getSystemAppVersion($pdo = null) {
        $defaultVer = 'Beta-v1.0.0';
        if (!$pdo) return $defaultVer;
        try {
            $stmt = $pdo->query("SELECT version, title, description FROM announcements WHERE type = 'update' AND is_active = 1 AND (version IS NOT NULL AND version != '') ORDER BY id DESC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['version'])) {
                $ver = trim($row['version']);
                if (preg_match('/beta/i', $ver)) {
                    return $ver;
                }
                if (preg_match('/^[0-9]/', $ver)) {
                    return 'Beta-v' . $ver;
                }
                return 'Beta-' . $ver;
            }
        } catch (Exception $e) {}
        return $defaultVer;
    }
}
