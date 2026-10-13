<?php
// api/get_outbound_forwarder.php
// Server-side DataTables processing for Outbound PR Forwarder Master Data
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

$draw = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0;

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

    // Ensure log columns exist on outbound_forwarder
    try {
        if ($driver === 'pgsql') {
            $checkStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'created_by'");
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN created_by TEXT");
            }
            $checkStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'updated_by'");
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN updated_by TEXT");
            }
        } else {
            $checkStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'created_by'");
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN created_by TEXT NULL");
            }
            $checkStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'updated_by'");
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN updated_by TEXT NULL");
            }
        }
    } catch (Exception $e) {}

    // ─── DataTables Server-Side Parameters ───
    $start       = isset($_REQUEST['start']) ? max(0, intval($_REQUEST['start'])) : 0;
    $length      = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 25;
    $searchValue = isset($_REQUEST['search']['value']) ? trim($_REQUEST['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_REQUEST['order'][0]['column']) ? intval($_REQUEST['order'][0]['column']) : 0;
    $orderDir    = (isset($_REQUEST['order'][0]['dir']) && strtolower($_REQUEST['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

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
        'periode_group',         // 40
        'created_at',            // 41 (created date)
        'created_by',            // 42
        'updated_at',            // 43 (updated date)
        'updated_by'             // 44
    ];

    $orderColumn = (isset($columns[$orderColIdx])) ? $columns[$orderColIdx] : 'id';
    $orderColumnEscaped = "{$q}{$orderColumn}{$q}";

    // ─── Filter Parameters ───
    $filterPeriode        = isset($_REQUEST['periode']) ? trim($_REQUEST['periode']) : '';
    $filterDnStatus       = isset($_REQUEST['dn_status']) ? trim($_REQUEST['dn_status']) : '';
    $filterApprovalStatus = isset($_REQUEST['approval_status']) ? trim($_REQUEST['approval_status']) : '';
    $filterDoc            = isset($_REQUEST['search_general']) ? trim($_REQUEST['search_general']) : (isset($_REQUEST['filter_doc']) ? trim($_REQUEST['filter_doc']) : '');

    $whereClauses = [];
    $params = [];

    if ($filterPeriode !== '') {
        $cleanPeriode = trim(preg_replace('/\s*\((?:ALL\s+)?BATCH(?:\s+ALL)?\)\s*$/i', '', $filterPeriode));
        if (preg_match('/-Batch\d+$/i', $cleanPeriode)) {
            $whereClauses[] = "periode_group = ?";
            $params[] = $cleanPeriode;
        } else {
            $whereClauses[] = "(periode_group = ? OR periode_group $likeOp ?)";
            $params[] = $cleanPeriode;
            $params[] = $cleanPeriode . '%';
        }
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
        $searchSqlParts = [];
        foreach ($columns as $c) {
            $searchSqlParts[] = "{$q}{$c}{$q}::text $likeOp ?";
            $params[] = $searchWildcard;
        }
        $whereClauses[] = "(" . implode(" OR ", $searchSqlParts) . ")";
    }

    // Per-column search
    if (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) {
        foreach ($_REQUEST['columns'] as $colIdx => $colData) {
            if (!empty($colData['search']['value']) && isset($columns[$colIdx])) {
                $colName = $columns[$colIdx];
                $colSearchVal = $colData['search']['value'];
                if (preg_match('/^\^(.*)\$$/', $colSearchVal, $m)) {
                    $whereClauses[] = "{$q}{$colName}{$q}::text = ?";
                    $params[] = $m[1];
                } else {
                    $whereClauses[] = "{$q}{$colName}{$q}::text $likeOp ?";
                    $params[] = '%' . $colSearchVal . '%';
                }
            }
        }
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
