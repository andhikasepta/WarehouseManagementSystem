<?php
// api/get_periods.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

/**
 * Parse a periode_group string like "January 2026-Batch1" into components.
 * Returns ['month' => 'January', 'year' => '2026', 'batch' => '1'] or null on failure.
 */
function parsePeriodeGroup($pg) {
    if (!$pg || $pg === 'Unknown Period') return null;
    // Expected format: "Month Year-BatchN"
    if (preg_match('/^(\w+)\s+(\d{4})-Batch(\d+)$/i', $pg, $m)) {
        return ['month' => $m[1], 'year' => $m[2], 'batch' => $m[3]];
    }
    // Legacy format: "Month Year" (no batch)
    if (preg_match('/^(\w+)\s+(\d{4})$/', $pg, $m)) {
        return ['month' => $m[1], 'year' => $m[2], 'batch' => '1'];
    }
    return null;
}

/**
 * Sort periode_group strings chronologically.
 * Format: "Month Year-BatchN" — sorted by year, month order, then batch number.
 */
function sortPeriodeGroups($periods) {
    $monthOrder = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
    ];

    usort($periods, function($a, $b) use ($monthOrder) {
        $pa = parsePeriodeGroup($a);
        $pb = parsePeriodeGroup($b);
        if (!$pa && !$pb) return 0;
        if (!$pa) return 1;
        if (!$pb) return -1;

        // Sort by year descending, then month descending, then batch descending
        $yearCmp = intval($pb['year']) - intval($pa['year']);
        if ($yearCmp !== 0) return $yearCmp;

        $monthA = $monthOrder[strtolower($pa['month'])] ?? 0;
        $monthB = $monthOrder[strtolower($pb['month'])] ?? 0;
        $monthCmp = $monthB - $monthA;
        if ($monthCmp !== 0) return $monthCmp;

        return intval($pb['batch']) - intval($pa['batch']);
    });

    return $periods;
}

try {
    $stmt = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Sort periods chronologically (newest first)
    $results = sortPeriodeGroups($results);

    // Query distinct sites from so_location or sub_location
    $stmtSites = $pdo->query("SELECT DISTINCT so_location FROM assets WHERE so_location IS NOT NULL AND so_location != '' ORDER BY so_location ASC");
    $sites = $stmtSites->fetchAll(PDO::FETCH_COLUMN);

    // Extract unique years from the period data
    $years = [];
    foreach ($results as $pg) {
        $parsed = parsePeriodeGroup($pg);
        if ($parsed) {
            $years[$parsed['year']] = true;
        }
    }
    $years = array_keys($years);
    sort($years);

    echo json_encode([
        'status' => 'success', 
        'data' => $results,
        'sites' => $sites,
        'years' => $years
    ]);
} catch(PDOException $e) {
    error_log('get_periods.php error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengambil data periode.']);
}
?>
