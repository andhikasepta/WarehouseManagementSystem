<?php
// api/get_data.php
ini_set('memory_limit', '256M');
set_time_limit(60);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
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
            $da = strtotime("01 " . $a);
            $db = strtotime("01 " . $b);
            return $da - $db;
        });
        
        $currentIndex = array_search($periodeGroup, $allPeriods);
        $prevPGroup = ($currentIndex !== false && $currentIndex > 0) ? $allPeriods[$currentIndex - 1] : null;

        // 2. Get current period's actual 'periode' value (for OUT assets)
        $stmtP = $pdo->prepare("SELECT periode FROM assets WHERE periode_group = ? LIMIT 1");
        $stmtP->execute([$periodeGroup]);
        $currentPeriode = $stmtP->fetchColumn();

        // 3. Use SQL LEFT JOINs to compute IN/OUT status at the database level
        //    This avoids loading two full period datasets into PHP memory.
        $siteCondCurr = '';
        $siteCondPrev = '';
        $params = [];

        if ($prevPGroup) {
            // ── Current period assets: status = IN (not in prev) or '-' (in prev) ──
            $sqlCurr = "SELECT c.spec_code, c.spec_name, c.reg_no, c.asset_planner_organization,
                               c.nbv, c.so_result, c.so_location, c.`range`, c.sub_location,
                               c.category, c.periode, c.periode_group,
                               CASE WHEN p.reg_no IS NULL THEN 'IN' ELSE '-' END AS status
                        FROM assets c
                        LEFT JOIN assets p ON p.reg_no = c.reg_no AND p.periode_group = ?
                        WHERE c.periode_group = ?";
            $paramsCurr = [$prevPGroup, $periodeGroup];

            if ($siteFilter) {
                $sqlCurr .= " AND c.so_location = ?";
                $paramsCurr[] = $siteFilter;
            }

            // ── OUT assets: in previous period but NOT in current ──
            $sqlOut = "SELECT p.spec_code, p.spec_name, p.reg_no, p.asset_planner_organization,
                              p.nbv, p.so_result, p.so_location, p.`range`, p.sub_location,
                              p.category, ? AS periode, ? AS periode_group,
                              'OUT' AS status
                       FROM assets p
                       LEFT JOIN assets c ON c.reg_no = p.reg_no AND c.periode_group = ?
                       WHERE p.periode_group = ? AND c.reg_no IS NULL";
            $paramsOut = [$currentPeriode, $periodeGroup, $periodeGroup, $prevPGroup];

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
                           nbv, so_result, so_location, `range`, sub_location,
                           category, periode, periode_group,
                           'IN' AS status
                    FROM assets WHERE periode_group = ?";
            $params = [$periodeGroup];

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
        $sql = "SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode FROM assets";
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
