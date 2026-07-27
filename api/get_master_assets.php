<?php
// api/get_master_assets.php
ini_set('memory_limit', '2048M');
set_time_limit(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

if (!isLoggedIn()) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit;
}

try {
    // We want to fetch data for the Master Data table.
    // Exclude 'raw_data' because it can make the JSON response too large and cause "Invalid JSON" errors on large datasets.
    $stmt = $pdo->query("SELECT id, spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode, periode_group, created_at FROM assets ORDER BY periode ASC");
    $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by periode
    $groupedByPeriod = [];
    foreach ($rawResults as $row) {
        $p = $row['periode'];
        if (!isset($groupedByPeriod[$p])) {
            $groupedByPeriod[$p] = [];
        }
        $groupedByPeriod[$p][$row['reg_no']] = $row;
    }

    $sortedPeriods = array_keys($groupedByPeriod);
    usort($sortedPeriods, function($a, $b) {
        $months = ['JAN'=>1, 'FEB'=>2, 'MAR'=>3, 'APR'=>4, 'MAY'=>5, 'MEI'=>5, 'JUN'=>6, 'JUL'=>7, 'AUG'=>8, 'AGU'=>8, 'SEP'=>9, 'OCT'=>10, 'OKT'=>10, 'NOV'=>11, 'DEC'=>12, 'DES'=>12];
        $ma = isset($months[strtoupper($a)]) ? $months[strtoupper($a)] : 0;
        $mb = isset($months[strtoupper($b)]) ? $months[strtoupper($b)] : 0;
        return $ma - $mb;
    });
    
    unset($rawResults); // Free memory

    $finalResults = [];
    
    for ($i = 0; $i < count($sortedPeriods); $i++) {
        $currentPeriod = $sortedPeriods[$i];
        $currentAssets = $groupedByPeriod[$currentPeriod];
        
        $prevAssets = [];
        if ($i > 0) {
            $prevPeriod = $sortedPeriods[$i - 1];
            $prevAssets = $groupedByPeriod[$prevPeriod];
        }

        // Check IN and -
        foreach ($currentAssets as $reg_no => $asset) {
            if ($i === 0) {
                $asset['status'] = 'IN';
            } else {
                if (isset($prevAssets[$reg_no])) {
                    $asset['status'] = '-';
                } else {
                    $asset['status'] = 'IN';
                }
            }
            $finalResults[] = $asset;
        }

        // Check OUT (Assets in previous period but not in current)
        if ($i > 0) {
            // Get an arbitrary row from the current period to copy its periode_group string
            // In case the period has no real assets, we might not have a periode_group, but typically we do.
            $sampleCurrAsset = reset($currentAssets);
            $currPeriodeGroup = $sampleCurrAsset ? $sampleCurrAsset['periode_group'] : "";

            foreach ($prevAssets as $reg_no => $prevAsset) {
                if (!isset($currentAssets[$reg_no])) {
                    $outAsset = $prevAsset;
                    $outAsset['status'] = 'OUT';
                    $outAsset['periode'] = $currentPeriod;
                    if ($currPeriodeGroup) {
                        $outAsset['periode_group'] = $currPeriodeGroup;
                    }
                    $finalResults[] = $outAsset;
                }
            }
        }
    }
    // Stream JSON to avoid memory exhaustion
    echo '{"data":[';
    $first = true;
    foreach ($finalResults as $asset) {
        if (!$first) {
            echo ',';
        }
        // Encode with UTF-8 substitute to prevent failures on malformed characters
        $encoded = json_encode($asset, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($encoded) {
            echo $encoded;
        } else {
            // Fallback if encode still fails (very rare with those flags)
            echo '{}';
        }
        $first = false;
    }
    echo ']}';
    
} catch(PDOException $e) {
    error_log("Database error in get_master_assets: " . $e->getMessage());
    // Use proper header for error
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while retrieving data.']);
}
