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
if (!function_exists('parsePeriodeGroup')) {
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
}

if (!function_exists('sortPeriodeGroups')) {
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
}

try {
    $periodTables = ['assets', 'inbound_master', 'inbound_gr', 'outbound_master', 'outbound_forwarder'];
    $periodQueries = [];
    foreach ($periodTables as $t) {
        try {
            $pdo->query("SELECT 1 FROM $t LIMIT 1");
            $periodQueries[] = "SELECT periode_group FROM $t WHERE periode_group IS NOT NULL AND TRIM(periode_group) != ''";
        } catch (Exception $e) {
            // Table doesn't exist yet, skip
        }
    }
    if (empty($periodQueries)) {
        $periodQueries[] = "SELECT periode_group FROM assets WHERE periode_group IS NOT NULL";
    }
    $unionSql = "SELECT DISTINCT periode_group FROM (" . implode(" UNION ", $periodQueries) . ") all_periods";
    $stmt = $pdo->query($unionSql);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Sort periods chronologically (newest first)
    $results = sortPeriodeGroups($results);

    // Filter out baseline/reference periods (e.g. December 2025) from the public period selector
    // unless explicitly requested with ?all=1 or ?include_reference=1 (e.g. in Master Data management)
    $includeAll = !empty($_GET['all']) || !empty($_GET['include_reference']);
    if (!$includeAll) {
        $results = array_values(array_filter($results, function($pg) {
            if (preg_match('/^December\s+2025(?:-Batch\d+)?$/i', trim((string)$pg))) {
                return false;
            }
            return true;
        }));
    }

    // Query distinct sites from so_location or sub_location
    $sites = [];
    try {
        $stmtSites = $pdo->query("SELECT DISTINCT so_location FROM assets WHERE so_location IS NOT NULL AND so_location != '' ORDER BY so_location ASC");
        $sites = $stmtSites->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $sites = [];
    }

    // Extract unique years from the period data
    $years = [];
    foreach ($results as $pg) {
        $parsed = parsePeriodeGroup($pg);
        if ($parsed) {
            $years[(string)$parsed['year']] = true;
        }
    }
    $years = array_keys($years);
    sort($years);
    if (empty($years)) {
        $years = [(string)date('Y')];
    }
    $years = array_values(array_map('strval', $years));

    // Retrieve unique grouping years strictly from kpi_master table
    $kpiYears = [];
    try {
        $rawKpi = $pdo->query("SELECT DISTINCT periode_tahun FROM kpi_master WHERE periode_tahun IS NOT NULL ORDER BY periode_tahun ASC")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rawKpi as $ky) {
            if (!empty($ky)) {
                $kpiYears[] = (string)$ky;
            }
        }
        $kpiYears = array_values(array_unique(array_map('strval', $kpiYears)));
        sort($kpiYears);
    } catch (Exception $e) {
        $kpiYears = [];
    }

    $type = strtolower($_GET['type'] ?? '');
    if ($type === 'kpi') {
        echo json_encode([
            'status' => 'success', 
            'data' => [],
            'sites' => [],
            'years' => $kpiYears,
            'kpi_years' => $kpiYears
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $results,
        'sites' => $sites,
        'years' => $years,
        'kpi_years' => $kpiYears
    ]);
} catch(PDOException $e) {
    error_log('get_periods.php error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengambil data periode.']);
}
?>
