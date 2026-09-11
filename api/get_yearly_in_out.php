<?php
// api/get_yearly_in_out.php
ini_set('memory_limit', '512M');
set_time_limit(0);
if (!ob_start('ob_gzhandler')) ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
session_write_close();

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

    $monthOrder = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
    ];

    // First, let's get all distinct periode_groups to establish chronological order
    $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL AND TRIM(periode_group) != ''");
    $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);

    if (!function_exists('parsePeriodGroupInfo')) {
        function parsePeriodGroupInfo($pg, $monthOrder) {
            if (!$pg || $pg === 'Unknown Period') return null;
            if (preg_match('/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/i', trim($pg), $m)) {
                $monthName = ucfirst(strtolower($m[1]));
                $monthNum = $monthOrder[strtolower($m[1])] ?? 0;
                $yr = intval($m[2]);
                $batch = isset($m[3]) ? intval($m[3]) : 1;
                if ($monthNum > 0 && $yr > 0) {
                    return [
                        'periode_group' => $pg,
                        'month' => $monthName,
                        'month_num' => $monthNum,
                        'year' => $yr,
                        'batch' => $batch,
                        'year_month_key' => sprintf('%04d-%02d', $yr, $monthNum),
                        'sort_val' => ($yr * 12 + $monthNum) * 100 + $batch
                    ];
                }
            }
            return null;
        }
    }

    $parsedPeriods = [];
    foreach ($allPeriods as $ap) {
        $info = parsePeriodGroupInfo($ap, $monthOrder);
        if ($info) {
            $parsedPeriods[] = $info;
        }
    }

    // Sort periods strictly chronologically ascending
    usort($parsedPeriods, function($a, $b) {
        return $a['sort_val'] - $b['sort_val'];
    });

    // Group periods by Year-Month ('YYYY-MM')
    $periodsByMonth = [];
    foreach ($parsedPeriods as $p) {
        $ym = $p['year_month_key'];
        if (!isset($periodsByMonth[$ym])) {
            $periodsByMonth[$ym] = [];
        }
        $periodsByMonth[$ym][] = $p;
    }

    // Chronologically sorted distinct YYYY-MM keys with data in DB
    $availableMonths = array_keys($periodsByMonth);
    sort($availableMonths);

    // Identify which periods need to be loaded into memory
    $periodsToLoadSet = [];
    foreach ($months as $i => $m) {
        $targetMonthNum = $i + 1;
        $targetYm = sprintf('%04d-%02d', intval($year), $targetMonthNum);

        if (isset($periodsByMonth[$targetYm])) {
            foreach ($periodsByMonth[$targetYm] as $tp) {
                $periodsToLoadSet[$tp['periode_group']] = true;
            }

            // Also find the immediate baseline month that precedes this month
            $ymPos = array_search($targetYm, $availableMonths);
            if ($ymPos !== false && $ymPos > 0) {
                $prevYm = $availableMonths[$ymPos - 1];
                foreach ($periodsByMonth[$prevYm] as $pp) {
                    $periodsToLoadSet[$pp['periode_group']] = true;
                }
            }
        }
    }

    $periodsToLoad = array_keys($periodsToLoadSet);

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
        $regNo = trim((string)($row['reg_no'] ?? ''));
        if ($regNo === '') {
            $regNo = 'REG_' . md5(($row['spec_code'] ?? '') . '_' . ($row['spec_name'] ?? '') . '_' . ($row['category'] ?? ''));
        }
        $assetsByPeriod[$row['periode_group']][$regNo] = $row;
    }

    // Compute IN/OUT for each month (aggregate all batches for that month)
    foreach ($months as $i => $month) {
        $targetMonthNum = $i + 1;
        $targetYm = sprintf('%04d-%02d', intval($year), $targetMonthNum);

        if (!isset($periodsByMonth[$targetYm])) {
            continue; // No data uploaded for this month -> 0
        }

        $currPeriodInfos = $periodsByMonth[$targetYm];
        $currAssetsMap = [];
        foreach ($currPeriodInfos as $cpi) {
            $pg = $cpi['periode_group'];
            if (isset($assetsByPeriod[$pg])) {
                $currAssetsMap = array_merge($currAssetsMap, $assetsByPeriod[$pg]);
            }
        }

        // Determine previous baseline month
        // (e.g. For January 2026, baseline is December 2025 / December 2025-Batch2)
        $ymPos = array_search($targetYm, $availableMonths);
        $prevPeriodInfos = ($ymPos !== false && $ymPos > 0) ? $periodsByMonth[$availableMonths[$ymPos - 1]] : null;

        $prevAssetsMap = [];
        $hasPrevBaseline = false;
        if ($prevPeriodInfos && !empty($prevPeriodInfos)) {
            $hasPrevBaseline = true;
            foreach ($prevPeriodInfos as $ppi) {
                $pg = $ppi['periode_group'];
                if (isset($assetsByPeriod[$pg])) {
                    $prevAssetsMap = array_merge($prevAssetsMap, $assetsByPeriod[$pg]);
                }
            }
        }

        $countIn = 0;
        $countOut = 0;
        $inDetails = [];
        $outDetails = [];

        $includeDetails = (isset($_GET['include_details']) && $_GET['include_details'] === '1');
        $maxDetails = $includeDetails ? 100000 : 50;

        // Count IN: Present in current month, but NOT in previous baseline
        // If there is NO previous baseline (e.g. December 2025 as the very first period in DB), ALL are IN!
        foreach ($currAssetsMap as $reg_no => $assetRow) {
            if (!$hasPrevBaseline || !isset($prevAssetsMap[$reg_no])) {
                $countIn++;
                if (count($inDetails) < $maxDetails) {
                    $inDetails[] = [
                        'spec_code' => $assetRow['spec_code'] ?? '-',
                        'reg_no' => $reg_no,
                        'spec_name' => $assetRow['spec_name'] ?? '-',
                        'category' => $assetRow['category'] ?? 'IN',
                        'status' => 'IN'
                    ];
                }
            }
        }

        // Count OUT: Present in previous baseline, but NOT in current month
        if ($hasPrevBaseline) {
            foreach ($prevAssetsMap as $reg_no => $assetRow) {
                if (!isset($currAssetsMap[$reg_no])) {
                    $countOut++;
                    if (count($outDetails) < $maxDetails) {
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
