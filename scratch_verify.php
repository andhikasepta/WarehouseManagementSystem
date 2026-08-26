<?php
require_once __DIR__ . '/backend/auth.php';

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'deutsch';
$_SESSION['role'] = 'superadmin';
$_SESSION['last_activity'] = time();

$sid = session_id();
echo "Active Session ID: " . $sid . "\n";

session_write_close();

// Now check directly with socket
$fp = fsockopen('103.123.100.11', 6379, $errno, $errstr, 2.5);
if ($fp) {
    $key = 'PHPREDIS_SESSION:' . $sid;
    $cmd = "*2\r\n$3\r\nTTL\r\n$" . strlen($key) . "\r\n" . $key . "\r\n";
    fwrite($fp, $cmd);
    $ttl = fgets($fp);
    echo "Redis Key: " . $key . "\n";
    echo "Redis TTL: " . trim($ttl) . " seconds remaining\n";

    $cmd2 = "*2\r\n$3\r\nGET\r\n$" . strlen($key) . "\r\n" . $key . "\r\n";
    fwrite($fp, $cmd2);
    $lenLine = fgets($fp);
    $val = fread($fp, 2048);
    echo "Redis Stored Session Data: " . trim($val) . "\n";
    fclose($fp);
}