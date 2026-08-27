<?php
/**
 * SPA Page Loader — AJAX endpoint
 * 
 * Loads page content fragments for the SPA shell.
 * Defines SPA_MODE constant so page files skip header/navbar/footer output.
 * Returns only the page content HTML (with inline styles and scripts).
 * 
 * Usage: GET api/spa_load.php?page=dashboard
 */

// Define SPA_MODE before anything else — page files check this constant
define('SPA_MODE', true);

require_once __DIR__ . '/../backend/paths.php';
require_once BACKEND_PATH . 'auth.php';

// Must be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Session expired', 'action' => 'reload']);
    exit;
}

$page = $_GET['page'] ?? '';

// Load route map
$routes = include BACKEND_PATH . 'spa_routes.php';

if (empty($page) || !isset($routes[$page])) {
    http_response_code(404);
    echo '<div class="container-fluid" style="padding-top: 100px;">';
    echo '<div class="text-center py-5">';
    echo '<div class="mb-3"><i class="fas fa-exclamation-triangle fa-3x text-warning"></i></div>';
    echo '<h4 class="text-gray-800">Halaman Tidak Ditemukan</h4>';
    echo '<p class="text-muted">Halaman yang Anda cari tidak tersedia.</p>';
    echo '</div></div>';
    exit;
}

$route = $routes[$page];
$pageFile = FRONTEND_PATH . 'pages/' . $route['file'];

if (!file_exists($pageFile)) {
    http_response_code(404);
    echo '<div class="container-fluid" style="padding-top: 100px;">';
    echo '<div class="text-center py-5">';
    echo '<h4 class="text-gray-800">Page file not found</h4>';
    echo '</div></div>';
    exit;
}

// Set active page for any component that might need it
$activePage = $route['active_page'] ?? '';

// Include the page file — in SPA_MODE it outputs only content
include $pageFile;
