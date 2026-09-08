<?php
// api/get_data.php
ini_set('memory_limit', '512M');
set_time_limit(120);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $q = ($driver === 'pgsql') ? '"' : '`';

    $periodeGroup = $_GET['periode'] ?? null;
    $siteFilter   = $_GET['site'] ?? null;
    
    if (!$periodeGroup) {
        $latestStmt = $pdo->query("SELECT periode_group FROM assets WHERE periode_group != 'Unknown Period' ORDER BY periode DESC LIMIT 1");
        $periodeGroup = $latestStmt->fetchColumn();
    }
    
    if ($periodeGroup) {
        // 1. Find the chronological previous period using a lightweight indexed query
        $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
        $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);
        
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
        
        $currentIndex = array_search($periodeGroup, $allPeriods);
        $isBatchSpecific = (bool)preg_match('/-Batch\d+$/i', $periodeGroup);
        if ($currentIndex === false && !$isBatchSpecific) {
            foreach ($allPeriods as $idx => $ap) {
                if (strpos($ap, $periodeGroup) === 0) {
                    $currentIndex = $idx;
                    break;
                }
            }
        }
        $prevPGroup = ($currentIndex !== false && $currentIndex > 0) ? $allPeriods[$currentIndex - 1] : null;
        $currWhere = $isBatchSpecific ? "c.periode_group = ?" : "(c.periode_group = ? OR c.periode_group LIKE ?)";
        $currPeriodParams = $isBatchSpecific ? [$periodeGroup] : [$periodeGroup, $periodeGroup . '%'];

        // 2. Get current period's actual 'periode' value (for OUT assets)
        $stmtP = $pdo->prepare("SELECT periode FROM assets WHERE (periode_group = ? OR periode_group LIKE ?) LIMIT 1");
        $stmtP->execute([$periodeGroup, $periodeGroup . '%']);
        $currentPeriode = $stmtP->fetchColumn();

        // 3. Use SQL LEFT JOINs to compute IN/OUT status at the database level
        //    This avoids loading two full period datasets into PHP memory.
        $siteCondCurr = '';
        $siteCondPrev = '';
        $params = [];

        if ($prevPGroup) {
            // ── Current period assets: status = IN (not in prev) or '-' (in prev) ──
            $sqlCurr = "SELECT c.spec_code, c.spec_name, c.reg_no, c.asset_planner_organization,
                               c.nbv, c.so_result, c.so_location, c.{$q}range{$q}, c.sub_location,
                               c.category, c.periode, c.periode_group,
                               CASE WHEN p.reg_no IS NULL THEN 'IN' ELSE '-' END AS status
                        FROM assets c
                        LEFT JOIN assets p ON p.reg_no = c.reg_no AND p.periode_group = ?
                        WHERE $currWhere";
            $paramsCurr = array_merge([$prevPGroup], $currPeriodParams);

            if ($siteFilter) {
                $sqlCurr .= " AND c.so_location = ?";
                $paramsCurr[] = $siteFilter;
            }

            // ── OUT assets: in previous period but NOT in current ──
            $sqlOut = "SELECT p.spec_code, p.spec_name, p.reg_no, p.asset_planner_organization,
                              p.nbv, p.so_result, p.so_location, p.{$q}range{$q}, p.sub_location,
                              p.category, ? AS periode, ? AS periode_group,
                              'OUT' AS status
                       FROM assets p
                       LEFT JOIN assets c ON c.reg_no = p.reg_no AND ($currWhere)
                       WHERE p.periode_group = ? AND c.reg_no IS NULL";
            $paramsOut = array_merge([$currentPeriode, $periodeGroup], $currPeriodParams, [$prevPGroup]);

            if ($siteFilter) {
                $sqlOut .= " AND p.so_location = ?";
                $paramsOut[] = $siteFilter;
            }

            // UNION ALL both queries for a single result set
            $sql = "($sqlCurr) UNION ALL ($sqlOut)";
            $params = array_merge($paramsCurr, $paramsOut);
        } else {
            // No previous period — all assets are 'IN'
            $sql = "SELECT spec_code, spec_name, reg_no, asset_planner_organization,
                           nbv, so_result, so_location, {$q}range{$q}, sub_location,
                           category, periode, periode_group,
                           'IN' AS status
                    FROM assets WHERE " . ($isBatchSpecific ? "periode_group = ?" : "(periode_group = ? OR periode_group LIKE ?)");
            $params = $currPeriodParams;

            if ($siteFilter) {
                $sql .= " AND so_location = ?";
                $params[] = $siteFilter;
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Stream results to minimize peak memory usage
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
