<?php
// backend/paths.php — Central path constants for WMS project
// Include this file once at the entry point to resolve all project paths.

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    define('BACKEND_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    define('FRONTEND_PATH', ROOT_PATH . 'frontend' . DIRECTORY_SEPARATOR);
    define('CONFIG_PATH', BACKEND_PATH . 'config' . DIRECTORY_SEPARATOR);
    define('API_PATH', ROOT_PATH . 'api' . DIRECTORY_SEPARATOR);
}
