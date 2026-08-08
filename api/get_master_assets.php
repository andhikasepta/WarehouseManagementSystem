<?php
// api/get_master_assets.php
// Server-side DataTables processing for Master Assets table
ini_set('memory_limit', '256M');
set_time_limit(60);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit;
}

try {
    // ─── DataTables Server-Side Parameters ───
    $draw    = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
    $start   = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length  = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

    // Column mapping (must match DataTables column order)
    $columns = [
        'spec_code',                  // 0
        'spec_name',                  // 1
        'reg_no',                     // 2
        'asset_planner_organization', // 3
        'nbv',                        // 4
        'so_result',                  // 5
        'so_location',                // 6
        '`range`',                    // 7
        'sub_location',               // 8
        'category',                   // 9
        'periode_group',              // 10
        'status'                      // 11 (computed)
    ];

    // Sanitize order column
    $orderColumn = isset($columns[$orderColIdx]) ? $columns[$orderColIdx] : 'spec_code';

    // ─── Extract Filters Early (GET params + DataTables per-column search) ───
    $periodeFilter = $_GET['filterPeriode'] ?? $_GET['periode'] ?? $_GET['periode_group'] ?? null;
    $subLocFilter  = $_GET['filterSubLocation'] ?? $_GET['sub_location'] ?? null;

    if (!$periodeFilter && isset($_GET['columns'][10]['search']['value']) && !empty($_GET['columns'][10]['search']['value'])) {
        $rawVal = $_GET['columns'][10]['search']['value'];
        $periodeFilter = preg_replace('/^\^|\$$/', '', $rawVal);
    }

    if (!$subLocFilter && isset($_GET['columns'][8]['search']['value']) && !empty($_GET['columns'][8]['search']['value'])) {
        $rawVal = $_GET['columns'][8]['search']['value'];
        $subLocFilter = preg_replace('/^\^|\$$/', '', $rawVal);
    }

    // ─── Step 1: Get all distinct periods for chronological ordering ───
    $stmtPeriods = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
    $allPeriods = $stmtPeriods->fetchAll(PDO::FETCH_COLUMN);

    $months = ['JAN'=>1, 'FEB'=>2, 'MAR'=>3, 'APR'=>4, 'MAY'=>5, 'MEI'=>5, 'JUN'=>6, 'JUL'=>7, 'AUG'=>8, 'AGU'=>8, 'SEP'=>9, 'OCT'=>10, 'OKT'=>10, 'NOV'=>11, 'DEC'=>12, 'DES'=>12];
    usort($allPeriods, function($a, $b) use ($months) {
        $da = strtotime("01 " . $a);
        $db = strtotime("01 " . $b);
        if ($da && $db) return $da - $db;
        $ma = 0; $mb = 0;
        foreach (['JAN','FEB','MAR','APR','MAY','MEI','JUN','JUL','AUG','AGU','SEP','OCT','OKT','NOV','DEC','DES'] as $m) {
            if (stripos($a, $m) !== false) $ma = $months[$m] ?? 0;
            if (stripos($b, $m) !== false) $mb = $months[$m] ?? 0;
        }
        return $ma - $mb;
    });

    if (empty($allPeriods)) {
        echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        exit;
    }

    // ─── Step 2: Determine target period(s) to query ───
    // If a specific period is requested, ONLY query that period & its previous period!
    // If no period is requested, default to the latest period for instant load.
    $targetPeriods = [];
    if (!empty($periodeFilter) && $periodeFilter !== 'ALL') {
        $targetPeriods = [$periodeFilter];
    } else {
        // Default to latest period to keep queries fast and prevent full history scans
        $latestPeriod = end($allPeriods);
        $targetPeriods = [$latestPeriod];
        $periodeFilter = $latestPeriod;
    }

    // ─── Step 3: Build high-performance targeted UNION ALL query ───
    $unionParts = [];
    $unionParams = [];

    foreach ($targetPeriods as $pg) {
        $idx = array_search($pg, $allPeriods);
        if ($idx === false || $idx === 0) {
            // First period (no previous period) — all IN
            $unionParts[] = "SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, 
                                    so_result, so_location, `range`, sub_location, category, 
                                    periode_group, 'IN' AS status
                             FROM assets WHERE periode_group = ?";
            $unionParams[] = $pg;
        } else {
            $prevPg = $allPeriods[$idx - 1];

            // Current assets: IN or '-'
            $unionParts[] = "SELECT c.spec_code, c.spec_name, c.reg_no, c.asset_planner_organization, c.nbv,
                                    c.so_result, c.so_location, c.`range`, c.sub_location, c.category,
                                    c.periode_group,
                                    CASE WHEN p.reg_no IS NULL THEN 'IN' ELSE '-' END AS status
                             FROM assets c
                             LEFT JOIN assets p ON p.reg_no = c.reg_no AND p.periode_group = ?
                             WHERE c.periode_group = ?";
            $unionParams[] = $prevPg;
            $unionParams[] = $pg;

            // OUT assets (in previous period but not in current)
            $unionParts[] = "SELECT p.spec_code, p.spec_name, p.reg_no, p.asset_planner_organization, p.nbv,
                                    p.so_result, p.so_location, p.`range`, p.sub_location, p.category,
                                    ? AS periode_group,
                                    'OUT' AS status
                             FROM assets p
                             LEFT JOIN assets c ON c.reg_no = p.reg_no AND c.periode_group = ?
                             WHERE p.periode_group = ? AND c.reg_no IS NULL";
            $unionParams[] = $pg;
            $unionParams[] = $pg;
            $unionParams[] = $prevPg;
        }
    }

    $baseQuery = implode(" UNION ALL ", $unionParts);

    // ─── Step 4: Apply remaining filters (SubLocation, Global Search, Per-Column) ───
    $whereConditions = [];
    $filterParams = [];

    if (!empty($subLocFilter)) {
        $whereConditions[] = "sub_location = ?";
        $filterParams[] = $subLocFilter;
    }
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $whereConditions[] = "(spec_code LIKE ? OR spec_name LIKE ? OR reg_no LIKE ? OR asset_planner_organization LIKE ? OR so_location LIKE ? OR periode_group LIKE ?)";
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
        $filterParams[] = $searchTerm;
    }

    // Per-column search from DataTables (excluding period column 10 which is already scoped)
    $colNames = ['spec_code', 'spec_name', 'reg_no', 'asset_planner_organization', 'nbv',
                 'so_result', 'so_location', '`range`', 'sub_location', 'category', 'periode_group', 'status'];
    if (isset($_GET['columns']) && is_array($_GET['columns'])) {
        foreach ($_GET['columns'] as $colIdx => $colData) {
            if ($colIdx === 10) continue; // Periode handled at query scoping level
            if (!empty($colData['search']['value']) && isset($colNames[$colIdx])) {
                $colName = $colNames[$colIdx];
                $colSearchVal = $colData['search']['value'];
                if (preg_match('/^\^(.*)\$$/', $colSearchVal, $m)) {
                    $whereConditions[] = "$colName = ?";
                    $filterParams[] = $m[1];
                } else {
                    $whereConditions[] = "$colName LIKE ?";
                    $filterParams[] = '%' . $colSearchVal . '%';
                }
            }
        }
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    // ─── Step 5: Count total records (unfiltered) ───
    $totalSql = "SELECT COUNT(*) FROM ($baseQuery) AS t";
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute($unionParams);
    $recordsTotal = (int)$totalStmt->fetchColumn();

    // ─── Step 6: Count filtered records ───
    $filteredSql = "SELECT COUNT(*) FROM ($baseQuery) AS t" . $whereClause;
    $allFilterParams = array_merge($unionParams, $filterParams);
    $filteredStmt = $pdo->prepare($filteredSql);
    $filteredStmt->execute($allFilterParams);
    $recordsFiltered = (int)$filteredStmt->fetchColumn();

    // ─── Step 7: Fetch paginated data ───
    $orderColumnSafe = ($orderColumn === 'status') ? 'status' : $orderColumn;
    $dataSql = "SELECT * FROM ($baseQuery) AS t" . $whereClause . " ORDER BY $orderColumnSafe $orderDir LIMIT ? OFFSET ?";
    $dataParams = array_merge($allFilterParams, [$length, $start]);
    $dataStmt = $pdo->prepare($dataSql);

    foreach ($dataParams as $i => $val) {
        $paramIndex = $i + 1;
        if ($i >= count($allFilterParams)) {
            $dataStmt->bindValue($paramIndex, (int)$val, PDO::PARAM_INT);
        } else {
            $dataStmt->bindValue($paramIndex, $val, PDO::PARAM_STR);
        }
    }

    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['nbv'] = (float)($row['nbv'] ?? 0);
    }
    unset($row);

    // ─── Step 8: Return distinct filter values for dropdowns ───
    $subLocations = $pdo->query("SELECT DISTINCT sub_location FROM assets WHERE sub_location IS NOT NULL AND sub_location != '' ORDER BY sub_location")->fetchAll(PDO::FETCH_COLUMN);

    // Reverse periods array so newest is at the top of dropdown
    $reversePeriods = array_reverse($allPeriods);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $rows,
        'filters' => [
            'periodes' => $reversePeriods,
            'subLocations' => $subLocations
        ]
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch(PDOException $e) {
    error_log("Database error in get_master_assets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'draw' => $draw ?? 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred while retrieving data.'
    ]);
}
