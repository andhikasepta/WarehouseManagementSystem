<?php
// api/get_inbound_gr_filters.php
// Lightweight endpoint to fetch distinct filter options for Data GR table.
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $periodes = $pdo->query("SELECT DISTINCT periode_group FROM inbound_gr WHERE periode_group IS NOT NULL AND periode_group != ''")->fetchAll(PDO::FETCH_COLUMN);
    usort($periodes, function($a, $b) {
        $da = strtotime("01 " . $a);
        $db = strtotime("01 " . $b);
        return $db - $da;
    });

    $vendors = $pdo->query("SELECT DISTINCT vendor_name FROM inbound_gr WHERE vendor_name IS NOT NULL AND TRIM(vendor_name) != '' ORDER BY vendor_name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $projects = $pdo->query("SELECT DISTINCT nama_project FROM inbound_gr WHERE nama_project IS NOT NULL AND TRIM(nama_project) != '' ORDER BY nama_project ASC")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'filters' => [
            'periode' => $periodes,
            'vendor'  => $vendors,
            'project' => $projects
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_inbound_gr_filters.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
