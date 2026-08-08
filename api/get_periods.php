<?php
// api/get_periods.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Sort periods nicely if possible, but distinct string might suffice.
    // Assuming format "June 2026"
    usort($results, function($a, $b) {
        if ($a === 'Unknown Period') return 1;
        if ($b === 'Unknown Period') return -1;
        return strtotime($b) - strtotime($a); // Descending order
    });

    // Query distinct sites from so_location or sub_location
    $stmtSites = $pdo->query("SELECT DISTINCT so_location FROM assets WHERE so_location IS NOT NULL AND so_location != '' ORDER BY so_location ASC");
    $sites = $stmtSites->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success', 
        'data' => $results,
        'sites' => $sites
    ]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
