<?php
// api/get_outbound_master.php
// Server-side DataTables processing for Outbound Master Data
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $userRole = $currentUser['role'] ?? 'admin';
    $userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

    $canAccess = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || $userRole === 'outbound_admin' || in_array('master_data_outbound', $userModules) || in_array('master_data', $userModules) || in_array('outbound', $userModules));
    if (!$canAccess) {
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    // Ensure table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS outbound_master (
        $idCol,
        mr_no TEXT,
        mr_type TEXT,
        mr_desc TEXT,
        mr_status TEXT,
        pck_no TEXT,
        pck_detail TEXT,
        pck_status TEXT,
        awb TEXT,
        dn_no TEXT,
        pr_no TEXT,
        po_no TEXT,
        origin_from TEXT,
        site_origin TEXT,
        site_origin_addr TEXT,
        destination_to TEXT,
        site_destination TEXT,
        site_destination_addr TEXT,
        pickup_type TEXT,
        via TEXT,
        lt TEXT,
        delivery_target TEXT,
        dn_status TEXT,
        last_log TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    // ─── DataTables Server-Side Parameters ───
    $draw    = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
    $start   = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length  = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

    // 24 Column mapping (must match DataTables column order)
    $columns = [
        'mr_no',                 // 0
        'mr_type',               // 1
        'mr_desc',               // 2
        'mr_status',             // 3
        'pck_no',                // 4
        'pck_detail',            // 5
        'pck_status',            // 6
        'awb',                   // 7
        'dn_no',                 // 8
        'pr_no',                 // 9
        'po_no',                 // 10
        'origin_from',           // 11
        'site_origin',           // 12
        'site_origin_addr',      // 13
        'destination_to',        // 14
        'site_destination',      // 15
        'site_destination_addr', // 16
        'pickup_type',           // 17
        'via',                   // 18
        'lt',                    // 19
        'delivery_target',       // 20
        'dn_status',             // 21
        'last_log',              // 22
        'periode_group'          // 23
    ];

    $orderColumn = isset($columns[$orderColIdx]) ? $columns[$orderColIdx] : 'id';
    if (!in_array($orderColumn, $columns)) {
        $orderColumn = 'id';
    }

    // ─── External filter parameters (from dropdown filters) ───
    $siteDestFilter  = $_GET['site_destination'] ?? null;
    $mrStatusFilter  = $_GET['mr_status'] ?? null;
    $dnStatusFilter  = $_GET['dn_status'] ?? null;
    $mrNoFilter      = $_GET['mr_no'] ?? null;
    $periodeFilter   = $_GET['periode'] ?? $_GET['periode_group'] ?? null;

    // ─── Build WHERE clause ───
    $whereConditions = [];
    $params = [];

    if (!empty($siteDestFilter)) {
        $whereConditions[] = "site_destination = ?";
        $params[] = $siteDestFilter;
    }
    if (!empty($mrStatusFilter)) {
        $whereConditions[] = "mr_status = ?";
        $params[] = $mrStatusFilter;
    }
    if (!empty($dnStatusFilter)) {
        $whereConditions[] = "dn_status = ?";
        $params[] = $dnStatusFilter;
    }
    if (!empty($periodeFilter)) {
        $whereConditions[] = "periode_group = ?";
        $params[] = $periodeFilter;
    }
    if (!empty($mrNoFilter)) {
        $whereConditions[] = "(mr_no LIKE ? OR pck_no LIKE ? OR dn_no LIKE ? OR pr_no LIKE ? OR po_no LIKE ?)";
        $mrTerm = '%' . $mrNoFilter . '%';
        $params[] = $mrTerm;
        $params[] = $mrTerm;
        $params[] = $mrTerm;
        $params[] = $mrTerm;
        $params[] = $mrTerm;
    }

    // DataTables global search
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $whereConditions[] = "(
            mr_no LIKE ? OR mr_desc LIKE ? OR mr_status LIKE ? OR
            pck_no LIKE ? OR pck_detail LIKE ? OR pck_status LIKE ? OR
            awb LIKE ? OR dn_no LIKE ? OR pr_no LIKE ? OR po_no LIKE ? OR
            origin_from LIKE ? OR site_origin LIKE ? OR
            destination_to LIKE ? OR site_destination LIKE ? OR
            pickup_type LIKE ? OR via LIKE ? OR dn_status LIKE ? OR last_log LIKE ? OR periode_group LIKE ?
        )";
        for ($i = 0; $i < 19; $i++) {
            $params[] = $searchTerm;
        }
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    // ─── Total count without filters ───
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM outbound_master");
    $recordsTotal = (int)$totalStmt->fetchColumn();

    // ─── Filtered count with WHERE conditions ───
    $filteredSql = "SELECT COUNT(*) FROM outbound_master" . $whereClause;
    $filteredStmt = $pdo->prepare($filteredSql);
    $filteredStmt->execute($params);
    $recordsFiltered = (int)$filteredStmt->fetchColumn();

    $orderColumnEscaped = "`$orderColumn`";
    $dataSql = "SELECT id, mr_no, mr_type, mr_desc, mr_status,
                       pck_no, pck_detail, pck_status,
                       awb, dn_no, pr_no, po_no,
                       origin_from, site_origin, site_origin_addr,
                       destination_to, site_destination, site_destination_addr,
                       pickup_type, via, lt, delivery_target,
                       dn_status, last_log, periode_group
                FROM outbound_master" . $whereClause . "
                ORDER BY " . $orderColumnEscaped . " " . $orderDir;

    if ($length > 0) {
        $dataSql .= " LIMIT ? OFFSET ?";
    }

    $dataStmt = $pdo->prepare($dataSql);
    
    // Bind WHERE condition parameters as strings
    $paramIndex = 1;
    foreach ($params as $val) {
        $dataStmt->bindValue($paramIndex, $val, PDO::PARAM_STR);
        $paramIndex++;
    }

    // Bind LIMIT and OFFSET explicitly as integers
    if ($length > 0) {
        $dataStmt->bindValue($paramIndex, (int)$length, PDO::PARAM_INT);
        $paramIndex++;
        $dataStmt->bindValue($paramIndex, (int)$start, PDO::PARAM_INT);
    }

    $dataStmt->execute();
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data
    ]);

} catch (Exception $e) {
    error_log("Database error in get_outbound_master.php: " . $e->getMessage());
    echo json_encode([
        'draw'            => intval($_GET['draw'] ?? 0),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => 'Terjadi kesalahan saat memuat data: ' . $e->getMessage()
    ]);
}
