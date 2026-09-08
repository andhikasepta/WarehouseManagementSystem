<?php
// api/get_outbound_forwarder.php
// Server-side DataTables processing for Outbound PR Forwarder Master Data
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;

if (!isLoggedIn()) {
    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'status'          => 'error',
        'message'         => 'Unauthorized'
    ]);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $userRole = $currentUser['role'] ?? 'admin';
    $userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

    $canAccess = ($userRole === 'head_warehouse_admin'
        || $userRole === 'head_asset_warehouse_admin'
        || $userRole === 'superadmin'
        || $userRole === 'outbound_admin'
        || hasPermission('master_data_outbound', 'view')
        || hasPermission('outbound', 'view')
        || hasPermission('master_data', 'view')
        || in_array('master_data_outbound', $userModules)
        || in_array('master_data', $userModules)
        || in_array('outbound', $userModules)
    );

    if (!$canAccess) {
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'data'            => [],
            'status'          => 'error',
            'message'         => 'Forbidden'
        ]);
        exit;
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
    $q = ($driver === 'pgsql') ? '"' : '`';

    // ─── DataTables Server-Side Parameters ───
    $start       = isset($_GET['start']) ? max(0, intval($_GET['start'])) : 0;
    $length      = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

    // 41 Column mapping matching DataTables columns
    $columns = [
        'no_dn',                 // 0
        'print_status',          // 1
        'dn_status',             // 2
        'asal_pengirim',         // 3
        'asal_code',             // 4
        'asal_site',             // 5
        'asal_alamat',           // 6
        'tujuan_penerima',       // 7
        'tujuan_code',           // 8
        'tujuan_site',           // 9
        'tujuan_alamat',         // 10
        'proc_vendor_mode',      // 11
        'proc_vendor_name',      // 12
        'koli',                  // 13
        'mata_anggaran',         // 14
        'sr_no',                 // 15
        'sr_tgl',                // 16
        'pr_no',                 // 17
        'pr_tgl',                // 18
        'valuation_price',       // 19
        'suggestion',            // 20
        'purpose',               // 21
        'po_no',                 // 22
        'po_tgl',                // 23
        'po_price',              // 24
        'po_vendor',             // 25
        'po_target_dlv',         // 26
        'po_buyer',              // 27
        'doc',                   // 28
        'note',                  // 29
        'delivery_type',         // 30
        'delivery_via',          // 31
        'delivery_nama',         // 32
        'delivery_awb',          // 33
        'delivery_pickup',       // 34
        'delivery_lead_time',    // 35
        'delivery_target_dlv',   // 36
        'approval_status',       // 37
        'approval_approver',     // 38
        'approval_date',         // 39
        'periode_group'          // 40
    ];

    $orderColumn = (isset($columns[$orderColIdx])) ? $columns[$orderColIdx] : 'id';
    $orderColumnEscaped = "{$q}{$orderColumn}{$q}";

    // ─── Filter Parameters ───
    $filterPeriode        = isset($_GET['periode']) ? trim($_GET['periode']) : '';
    $filterDnStatus       = isset($_GET['dn_status']) ? trim($_GET['dn_status']) : '';
    $filterApprovalStatus = isset($_GET['approval_status']) ? trim($_GET['approval_status']) : '';
    $filterDoc            = isset($_GET['search_general']) ? trim($_GET['search_general']) : (isset($_GET['filter_doc']) ? trim($_GET['filter_doc']) : '');

    $whereClauses = [];
    $params = [];

    if ($filterPeriode !== '') {
        $whereClauses[] = "periode_group = ?";
        $params[] = $filterPeriode;
    }

    if ($filterDnStatus !== '') {
        $whereClauses[] = "dn_status = ?";
        $params[] = $filterDnStatus;
    }

    if ($filterApprovalStatus !== '') {
        $whereClauses[] = "approval_status = ?";
        $params[] = $filterApprovalStatus;
    }

    if ($filterDoc !== '') {
        $whereClauses[] = "(no_dn $likeOp ? OR pr_no $likeOp ? OR po_no $likeOp ? OR sr_no $likeOp ? OR delivery_awb $likeOp ?)";
        $docWildcard = '%' . $filterDoc . '%';
        $params[] = $docWildcard;
        $params[] = $docWildcard;
        $params[] = $docWildcard;
        $params[] = $docWildcard;
        $params[] = $docWildcard;
    }

    if ($searchValue !== '') {
        $searchWildcard = '%' . $searchValue . '%';
        $searchableCols = [
            'no_dn', 'dn_status', 'asal_pengirim', 'asal_site',
            'tujuan_penerima', 'tujuan_site', 'proc_vendor_name',
            'sr_no', 'pr_no', 'po_no', 'po_vendor', 'delivery_awb',
            'delivery_nama', 'approval_status', 'approval_approver', 'periode_group'
        ];

        $searchSqlParts = [];
        foreach ($searchableCols as $c) {
            $searchSqlParts[] = "$c $likeOp ?";
            $params[] = $searchWildcard;
        }
        $whereClauses[] = "(" . implode(" OR ", $searchSqlParts) . ")";
    }

    $whereSql = "";
    if (count($whereClauses) > 0) {
        $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    }

    // ─── Total Records without filtering ───
    $totalRecordsStmt = $pdo->query("SELECT COUNT(*) FROM outbound_forwarder");
    $recordsTotal = intval($totalRecordsStmt->fetchColumn());

    // ─── Filtered Records count ───
    $filteredStmt = $pdo->prepare("SELECT COUNT(*) FROM outbound_forwarder $whereSql");
    $filteredStmt->execute($params);
    $recordsFiltered = intval($filteredStmt->fetchColumn());

    // ─── Fetch Data ───
    $dataSql = "SELECT * FROM outbound_forwarder $whereSql ORDER BY $orderColumnEscaped $orderDir";
    if ($length > 0) {
        $dataSql .= " LIMIT ? OFFSET ?";
    }

    $dataStmt = $pdo->prepare($dataSql);

    $paramIndex = 1;
    foreach ($params as $val) {
        $dataStmt->bindValue($paramIndex, $val, PDO::PARAM_STR);
        $paramIndex++;
    }

    if ($length > 0) {
        $dataStmt->bindValue($paramIndex, (int)$length, PDO::PARAM_INT);
        $paramIndex++;
        $dataStmt->bindValue($paramIndex, (int)$start, PDO::PARAM_INT);
    }

    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        unset($r['raw_data']);
    }
    unset($r);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $rows
    ]);

} catch (\Throwable $e) {
    error_log("Error in get_outbound_forwarder.php: " . $e->getMessage());
    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'status'          => 'error',
        'message'         => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
