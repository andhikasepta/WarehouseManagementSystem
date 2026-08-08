<?php
// api/get_inbound_filters.php
// Lightweight endpoint to fetch distinct filter options for the Inbound Master Data table.
// Called once on page load, not on every DataTables redraw.
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch distinct filter options using indexed columns
    $periodes = $pdo->query("SELECT DISTINCT periode_group FROM inbound_master WHERE periode_group IS NOT NULL AND periode_group != ''")->fetchAll(PDO::FETCH_COLUMN);
    usort($periodes, function($a, $b) {
        $da = strtotime("01 " . $a);
        $db = strtotime("01 " . $b);
        return $db - $da; // newest first
    });

    $bagians = $pdo->query("SELECT DISTINCT pr_nama_bagian FROM inbound_master WHERE pr_nama_bagian IS NOT NULL AND pr_nama_bagian != '' ORDER BY pr_nama_bagian ASC")->fetchAll(PDO::FETCH_COLUMN);
    $pics = $pdo->query("SELECT DISTINCT pr_pic_teknis_nama FROM inbound_master WHERE pr_pic_teknis_nama IS NOT NULL AND pr_pic_teknis_nama != '' ORDER BY pr_pic_teknis_nama ASC")->fetchAll(PDO::FETCH_COLUMN);
    $kategoriList = $pdo->query("SELECT DISTINCT pr_item_kategori FROM inbound_master WHERE pr_item_kategori IS NOT NULL AND pr_item_kategori != '' ORDER BY pr_item_kategori ASC")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'filters' => [
            'periode' => $periodes,
            'bagian' => $bagians,
            'pic' => $pics,
            'kategori' => $kategoriList
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_inbound_filters.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
