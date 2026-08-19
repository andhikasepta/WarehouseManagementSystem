<?php
// api/get_yearly_in_out.php
ini_set('memory_limit', '512M');
set_time_limit(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $year = isset($_GET['year']) ? trim($_GET['year']) : null;
    
    if (!$year || !preg_match('/^\d{4}$/', $year)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid year provided']);
        exit;
    }
    
    // We need to fetch IN/OUT for each month of the year
    $months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    $results = [
        'in' => array_fill(0, 12, 0),
        'out' => array_fill(0, 12, 0),
        'in_details' => array_fill(0, 12, []),
        'out_details' => array_fill(0, 12, [])
    ];

    // First, let's get all distinct periode_groups to establish chronological order
    $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
    $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);
    
    // Sort periods chronologically
    usort($allPeriods, function($a, $b) {
        // Parse "Month Year-BatchN" format
        $pa = preg_match('/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/i', $a, $ma);
        $pb = preg_match('/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/i', $b, $mb);
        if (!$pa && !$pb) return 0;
        if (!$pa) return 1;
        if (!$pb) return -1;
        $da = strtotime("01 " . $ma[1] . " " . $ma[2]);
        $db = strtotime("01 " . $mb[1] . " " . $mb[2]);
        if ($da !== $db) return $da - $db;
        $ba = isset($ma[3]) ? intval($ma[3]) : 1;
        $bb = isset($mb[3]) ? intval($mb[3]) : 1;
        return $ba - $bb;
    });
    
    // We will preload all assets for the required periods to avoid 24 separate queries
    // Required periods: all months in the target year, PLUS the period immediately preceding the earliest month in the target year (for January diff)
    $periodsToLoad = [];
    foreach ($months as $m) {
        // Match all batch variants for this month+year (e.g. "January 2026-Batch1", "January 2026-Batch2")
        foreach ($allPeriods as $ap) {
            if (preg_match('/^' . preg_quote($m, '/') . '\s+' . preg_quote($year, '/') . '(?:-Batch\d+)?$/i', $ap)) {
                if (!in_array($ap, $periodsToLoad)) {
                    $periodsToLoad[] = $ap;
                }
                // Add the preceding period for diff
                $idx = array_search($ap, $allPeriods);
                if ($idx !== false && $idx > 0) {
                    $prevP = $allPeriods[$idx - 1];
                    if (!in_array($prevP, $periodsToLoad)) {
                        $periodsToLoad[] = $prevP;
                    }
                }
            }
        }
    }
    
    if (empty($periodsToLoad)) {
        // No data at all for this year
        echo json_encode(['status' => 'success', 'data' => $results]);
        exit;
    }

    // Load all required assets grouped by periode_group
    $inClause = implode(',', array_fill(0, count($periodsToLoad), '?'));
    $stmtAssets = $pdo->prepare("SELECT reg_no, spec_code, spec_name, category, periode_group FROM assets WHERE periode_group IN ($inClause)");
    $stmtAssets->execute($periodsToLoad);
    
    $assetsByPeriod = [];
    foreach ($periodsToLoad as $p) {
        $assetsByPeriod[$p] = [];
    }
    
    while ($row = $stmtAssets->fetch(PDO::FETCH_ASSOC)) {
        $assetsByPeriod[$row['periode_group']][$row['reg_no']] = $row;
    }
    
    // Now compute IN/OUT for each month (aggregate all batches for that month)
    foreach ($months as $i => $month) {
        // Find all period entries for this month+year (could be Batch1, Batch2)
        $monthPeriods = [];
        foreach ($allPeriods as $ap) {
            if (preg_match('/^' . preg_quote($month, '/') . '\s+' . preg_quote($year, '/') . '(?:-Batch\d+)?$/i', $ap)) {
                $monthPeriods[] = $ap;
            }
        }
        
        if (empty($monthPeriods)) {
            continue; // No data uploaded for this month yet -> 0
        }

        // Use the last batch for this month as the "current" for comparison
        $targetPeriod = end($monthPeriods);
        
        if (!isset($assetsByPeriod[$targetPeriod])) {
            continue;
        }
        
        $idx = array_search($targetPeriod, $allPeriods);
        $prevPeriod = ($idx !== false && $idx > 0) ? $allPeriods[$idx - 1] : null;
        
        // Merge all batches of this month for the current period
        $currAssetsMap = [];
        foreach ($monthPeriods as $mp) {
            if (isset($assetsByPeriod[$mp])) {
                $currAssetsMap = array_merge($currAssetsMap, $assetsByPeriod[$mp]);
            }
        }
        $prevAssetsMap = $prevPeriod ? ($assetsByPeriod[$prevPeriod] ?? []) : [];
        
        $countIn = 0;
        $countOut = 0;
        $inDetails = [];
        $outDetails = [];
        
        // Count IN
        foreach ($currAssetsMap as $reg_no => $assetRow) {
            if (!$prevPeriod || !isset($prevAssetsMap[$reg_no])) {
                $countIn++;
                $inDetails[] = [
                    'spec_code' => $assetRow['spec_code'] ?? '-',
                    'reg_no' => $reg_no,
                    'spec_name' => $assetRow['spec_name'] ?? '-',
                    'category' => $assetRow['category'] ?? 'IN',
                    'status' => 'IN'
                ];
            }
        }
        
        // Count OUT
        if ($prevPeriod) {
            foreach ($prevAssetsMap as $reg_no => $assetRow) {
                if (!isset($currAssetsMap[$reg_no])) {
                    $countOut++;
                    $outDetails[] = [
                        'spec_code' => $assetRow['spec_code'] ?? '-',
                        'reg_no' => $reg_no,
                        'spec_name' => $assetRow['spec_name'] ?? '-',
                        'category' => $assetRow['category'] ?? 'OUT',
                        'status' => 'OUT'
                    ];
                }
            }
        }
        
        $results['in'][$i] = $countIn;
        $results['out'][$i] = $countOut;
        $results['in_details'][$i] = $inDetails;
        $results['out_details'][$i] = $outDetails;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);

} catch(PDOException $e) {
    error_log("Database error in get_yearly_in_out: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while computing yearly data.']);
}
