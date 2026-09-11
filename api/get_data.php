<?php
// api/get_data.php
ini_set('memory_limit', '512M');
set_time_limit(120);
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
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $q = ($driver === 'pgsql') ? '"' : '`';

    $periodeGroup = $_GET['periode'] ?? null;
    $siteFilter   = $_GET['site'] ?? null;
    $isFull       = (isset($_GET['full']) && $_GET['full'] === '1');
    
    if (!$periodeGroup) {
        $latestStmt = $pdo->query("SELECT periode_group FROM assets WHERE periode_group != 'Unknown Period' ORDER BY periode DESC LIMIT 1");
        $periodeGroup = $latestStmt->fetchColumn();
    }
    
    if ($periodeGroup) {
        $isBatchSpecific = (bool)preg_match('/-Batch\d+$/i', $periodeGroup);
        $currWhere = $isBatchSpecific ? "periode_group = ?" : "(periode_group = ? OR periode_group LIKE ?)";
        $currPeriodParams = $isBatchSpecific ? [$periodeGroup] : [$periodeGroup, $periodeGroup . '%'];

        if ($siteFilter) {
            $currWhere .= " AND so_location = ?";
            $currPeriodParams[] = $siteFilter;
        }

        if (!$isFull) {
            // High-speed aggregation directly from SQL (<0.5s for 65k+ rows)
            // 1. Total qty & Total NBV
            $stmtTot = $pdo->prepare("SELECT COUNT(*) AS total_qty, COALESCE(SUM(nbv), 0) AS total_nbv FROM assets WHERE $currWhere");
            $stmtTot->execute($currPeriodParams);
            $totRow = $stmtTot->fetch(PDO::FETCH_ASSOC);

            // 2. Aging group
            $stmtAging = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN {$q}range{$q} IS NULL OR TRIM({$q}range{$q}) = '' THEN 'Unassigned' 
                        ELSE TRIM({$q}range{$q}) 
                    END AS label,
                    CASE 
                        WHEN {$q}range{$q} IS NULL OR TRIM({$q}range{$q}) = '' THEN 'Unassigned' 
                        ELSE TRIM({$q}range{$q}) 
                    END AS {$q}range{$q},
                    COUNT(*) AS qty, 
                    COALESCE(SUM(nbv), 0) AS nbv 
                FROM assets 
                WHERE $currWhere 
                GROUP BY 
                    CASE 
                        WHEN {$q}range{$q} IS NULL OR TRIM({$q}range{$q}) = '' THEN 'Unassigned' 
                        ELSE TRIM({$q}range{$q}) 
                    END 
                ORDER BY qty DESC
            ");
            $stmtAging->execute($currPeriodParams);
            $agingRows = $stmtAging->fetchAll(PDO::FETCH_ASSOC);

            // 3. Organization group
            $stmtOrg = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN asset_planner_organization IS NULL OR TRIM(asset_planner_organization) = '' THEN 'Tanpa Organization' 
                        ELSE TRIM(asset_planner_organization) 
                    END AS label,
                    CASE 
                        WHEN asset_planner_organization IS NULL OR TRIM(asset_planner_organization) = '' THEN 'Tanpa Organization' 
                        ELSE TRIM(asset_planner_organization) 
                    END AS org,
                    COUNT(*) AS qty, 
                    COALESCE(SUM(nbv), 0) AS nbv 
                FROM assets 
                WHERE $currWhere 
                GROUP BY 
                    CASE 
                        WHEN asset_planner_organization IS NULL OR TRIM(asset_planner_organization) = '' THEN 'Tanpa Organization' 
                        ELSE TRIM(asset_planner_organization) 
                    END 
                ORDER BY qty DESC
            ");
            $stmtOrg->execute($currPeriodParams);
            $orgRows = $stmtOrg->fetchAll(PDO::FETCH_ASSOC);

            // 4. Aging overview metrics
            $stmtCards = $pdo->prepare("
                SELECT
                    COUNT(CASE WHEN LOWER({$q}range{$q}) LIKE '%<3%' OR LOWER({$q}range{$q}) LIKE '%< 3%' OR LOWER({$q}range{$q}) LIKE '%< 1%' OR LOWER({$q}range{$q}) LIKE '%<1%' OR LOWER({$q}range{$q}) LIKE '%<%' THEN 1 END) AS less_3m,
                    COUNT(CASE WHEN LOWER({$q}range{$q}) LIKE '%3-12%' OR LOWER({$q}range{$q}) LIKE '%3 - 12%' OR LOWER({$q}range{$q}) LIKE '%1-2%' OR LOWER({$q}range{$q}) LIKE '%1 - 2%' OR LOWER({$q}range{$q}) LIKE '%1 tahun%' THEN 1 END) AS m3_to_12,
                    COUNT(CASE WHEN LOWER({$q}range{$q}) LIKE '%>2%' OR LOWER({$q}range{$q}) LIKE '%> 2%' OR LOWER({$q}range{$q}) LIKE '%2 - 3%' OR LOWER({$q}range{$q}) LIKE '%2-3%' OR LOWER({$q}range{$q}) LIKE '%>%' THEN 1 END) AS more_12m,
                    COUNT(CASE WHEN LOWER(category) LIKE '%re-use%' OR LOWER(category) LIKE '%reuse%' OR LOWER(category) LIKE '%need to utilize%' OR LOWER(category) LIKE '%slow moving%' THEN 1 END) AS reuse
                FROM assets WHERE $currWhere
            ");
            $stmtCards->execute($currPeriodParams);
            $cardsRow = $stmtCards->fetch(PDO::FETCH_ASSOC);

            // 5. Lightweight preview rows (100 rows)
            $stmtSample = $pdo->prepare("SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, {$q}range{$q}, sub_location, category, periode, periode_group, 'IN' AS status FROM assets WHERE $currWhere LIMIT 100");
            $stmtSample->execute($currPeriodParams);
            $sampleRows = $stmtSample->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'summary' => [
                    'total_asset' => (int)($totRow['total_qty'] ?? 0),
                    'total_nbv' => (float)($totRow['total_nbv'] ?? 0),
                    'cards' => [
                        'less_3m' => (int)($cardsRow['less_3m'] ?? 0),
                        'm3_to_12' => (int)($cardsRow['m3_to_12'] ?? 0),
                        'more_12m' => (int)($cardsRow['more_12m'] ?? 0),
                        'reuse' => (int)($cardsRow['reuse'] ?? 0)
                    ],
                    'aging_chart' => $agingRows,
                    'org_chart' => $orgRows
                ],
                'data' => $sampleRows
            ]);
            exit;
        }

        // Full dataset request (if full=1 explicitly requested)
        $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
        $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);
        
        usort($allPeriods, function($a, $b) {
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
        
        $currentIndex = array_search($periodeGroup, $allPeriods);
        if ($currentIndex === false && !$isBatchSpecific) {
            foreach ($allPeriods as $idx => $ap) {
                if (strpos($ap, $periodeGroup) === 0) {
                    $currentIndex = $idx;
                    break;
                }
            }
        }
        $prevPGroup = ($currentIndex !== false && $currentIndex > 0) ? $allPeriods[$currentIndex - 1] : null;

        // Get current period's actual 'periode' value (for OUT assets)
        $stmtP = $pdo->prepare("SELECT periode FROM assets WHERE (periode_group = ? OR periode_group LIKE ?) LIMIT 1");
        $stmtP->execute([$periodeGroup, $periodeGroup . '%']);
        $currentPeriode = $stmtP->fetchColumn();

        if ($prevPGroup) {
            $sqlCurr = "SELECT c.spec_code, c.spec_name, c.reg_no, c.asset_planner_organization,
                               c.nbv, c.so_result, c.so_location, c.{$q}range{$q}, c.sub_location,
                               c.category, c.periode, c.periode_group,
                               CASE WHEN p.reg_no IS NULL THEN 'IN' ELSE '-' END AS status
                        FROM assets c
                        LEFT JOIN assets p ON p.reg_no = c.reg_no AND p.periode_group = ?
                        WHERE $currWhere";
            $paramsCurr = array_merge([$prevPGroup], $currPeriodParams);

            $sqlOut = "SELECT p.spec_code, p.spec_name, p.reg_no, p.asset_planner_organization,
                              p.nbv, p.so_result, p.so_location, p.{$q}range{$q}, p.sub_location,
                              p.category, ? AS periode, ? AS periode_group,
                              'OUT' AS status
                       FROM assets p
                       LEFT JOIN assets c ON c.reg_no = p.reg_no AND ($currWhere)
                       WHERE p.periode_group = ? AND c.reg_no IS NULL";
            $paramsOut = array_merge([$currentPeriode, $periodeGroup], $currPeriodParams, [$prevPGroup]);

            $sql = "($sqlCurr) UNION ALL ($sqlOut)";
            $params = array_merge($paramsCurr, $paramsOut);
        } else {
            $sql = "SELECT spec_code, spec_name, reg_no, asset_planner_organization,
                           nbv, so_result, so_location, {$q}range{$q}, sub_location,
                           category, periode, periode_group,
                           'IN' AS status
                    FROM assets WHERE $currWhere";
            $params = $currPeriodParams;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['nbv'] = (float)$row['nbv'];
            $results[] = $row;
        }

    } else {
        $sql = "SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, {$q}range{$q}, sub_location, category, periode FROM assets";
        $params = [];
        if ($siteFilter) {
            $sql .= " WHERE so_location = ?";
            $params[] = $siteFilter;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$row) {
            if (isset($row['nbv'])) {
                $row['nbv'] = (float)$row['nbv'];
            }
        }
        unset($row);
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
