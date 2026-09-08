<?php
// api/get_outbound_forwarder_filters.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Ensure table exists
    $pdo->query("SELECT 1 FROM outbound_forwarder LIMIT 1");

    // Fetch unique periods
    $periodsStmt = $pdo->query("SELECT DISTINCT periode_group FROM outbound_forwarder WHERE periode_group IS NOT NULL AND periode_group != '' ORDER BY periode_group DESC");
    $periods = $periodsStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'periods' => $periods
        ],
        'filters' => [
            'periode' => $periods
        ]
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'periods' => []
        ],
        'filters' => [
            'periode' => []
        ]
    ]);
}
