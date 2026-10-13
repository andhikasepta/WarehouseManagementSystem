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
    
    $monthOrder = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
    ];

    usort($periodes, function($a, $b) use ($monthOrder) {
        $pa = null;
        $pb = null;
        if (preg_match('/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/i', $a, $ma)) {
            $pa = ['month' => strtolower($ma[1]), 'year' => intval($ma[2]), 'batch' => isset($ma[3]) ? intval($ma[3]) : 1];
        }
        if (preg_match('/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/i', $b, $mb)) {
            $pb = ['month' => strtolower($mb[1]), 'year' => intval($mb[2]), 'batch' => isset($mb[3]) ? intval($mb[3]) : 1];
        }
        if (!$pa && !$pb) return 0;
        if (!$pa) return 1;
        if (!$pb) return -1;

        if ($pb['year'] !== $pa['year']) return $pb['year'] - $pa['year'];
        $mDiff = ($monthOrder[$pb['month']] ?? 0) - ($monthOrder[$pa['month']] ?? 0);
        if ($mDiff !== 0) return $mDiff;
        return $pb['batch'] - $pa['batch'];
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
