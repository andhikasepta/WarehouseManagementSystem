<?php
// api/get_dashboard_totals.php
// Returns aggregate totals from ALL asset data (no period filter)
// - total_asset: COUNT(*) of all assets
// - total_nbv: SUM(nbv) of all assets
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
session_write_close();

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_asset, COALESCE(SUM(nbv), 0) AS total_nbv FROM assets");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_asset' => (int) $row['total_asset'],
            'total_nbv' => (float) $row['total_nbv']
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_dashboard_totals: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
