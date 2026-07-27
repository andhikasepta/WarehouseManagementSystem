<?php
// api/get_data.php
ini_set('memory_limit', '512M');
set_time_limit(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $periodeGroup = $_GET['periode'] ?? null;
    
    if (!$periodeGroup) {
        $latestStmt = $pdo->query("SELECT periode_group FROM assets WHERE periode_group != 'Unknown Period' ORDER BY periode DESC LIMIT 1");
        $periodeGroup = $latestStmt->fetchColumn();
    }
    
    if ($periodeGroup) {
        // Find the actual 'periode' (YYYY-MM) for this periode_group
        $stmtP = $pdo->prepare("SELECT periode FROM assets WHERE periode_group = ? LIMIT 1");
        $stmtP->execute([$periodeGroup]);
        $currentPeriode = $stmtP->fetchColumn();

        // Get current period assets
        $stmtCurr = $pdo->prepare("SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode, periode_group FROM assets WHERE periode_group = ?");
        $stmtCurr->execute([$periodeGroup]);
        $currAssetsRaw = $stmtCurr->fetchAll(PDO::FETCH_ASSOC);

        $currAssetsMap = [];
        foreach ($currAssetsRaw as $row) {
            $currAssetsMap[$row['reg_no']] = $row;
        }

        // Find the chronological previous period using PHP to avoid slow SQL function scans
        $prevPGroup = null;
        if ($currentPeriode) {
            $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
            $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);
            
            // Sort periods chronologically
            usort($allPeriods, function($a, $b) {
                $da = strtotime("01 " . $a);
                $db = strtotime("01 " . $b);
                return $da - $db;
            });
            
            // Find the period right before the current one
            $currentIndex = array_search($periodeGroup, $allPeriods);
            if ($currentIndex !== false && $currentIndex > 0) {
                $prevPGroup = $allPeriods[$currentIndex - 1];
            }
        }

        $prevAssetsMap = [];
        if ($prevPGroup) {
            $stmtPrev = $pdo->prepare("SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode, periode_group FROM assets WHERE periode_group = ?");
            $stmtPrev->execute([$prevPGroup]);
            $prevAssetsRaw = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);
            foreach ($prevAssetsRaw as $row) {
                $prevAssetsMap[$row['reg_no']] = $row;
            }
        }

        $results = [];

        // Determine IN and -
        foreach ($currAssetsMap as $reg_no => $asset) {
            if (!$prevPGroup) {
                $asset['status'] = 'IN';
            } else {
                if (isset($prevAssetsMap[$reg_no])) {
                    $asset['status'] = '-';
                } else {
                    $asset['status'] = 'IN';
                }
            }
            $results[] = $asset;
        }

        // Determine OUT
        if ($prevPGroup) {
            foreach ($prevAssetsMap as $reg_no => $prevAsset) {
                if (!isset($currAssetsMap[$reg_no])) {
                    $outAsset = $prevAsset;
                    $outAsset['status'] = 'OUT';
                    $outAsset['periode'] = $currentPeriode;
                    $outAsset['periode_group'] = $periodeGroup;
                    $results[] = $outAsset;
                }
            }
        }

    } else {
        $stmt = $pdo->query("SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode FROM assets");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Convert numeric fields properly if necessary, PDO might return strings for numbers
    foreach($results as &$row) {
        if (isset($row['nbv'])) {
            $row['nbv'] = (float)$row['nbv'];
        }
    }
    
    $json = json_encode([
        'status' => 'success',
        'data' => $results
    ]);
    
    if ($json === false) {
        error_log("JSON Encode Error in get_data.php: " . json_last_error_msg());
        echo json_encode([
            'status' => 'error',
            'message' => 'JSON Encoding Error: ' . json_last_error_msg()
        ]);
    } else {
        echo $json;
    }
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while retrieving data.']);
}
