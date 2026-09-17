<?php
// api/get_inbound_gr.php
// Server-side DataTables processing for Data GR Master Data
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
        || $userRole === 'inbound_admin'
        || hasPermission('master_data_inbound', 'view')
        || hasPermission('inbound', 'view')
        || hasPermission('master_data', 'view')
        || in_array('master_data_inbound', $userModules)
        || in_array('master_data', $userModules)
        || in_array('inbound', $userModules)
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

    // DataTables Server-Side Parameters
    $start       = isset($_GET['start']) ? max(0, intval($_GET['start'])) : 0;
    $length      = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

    // 39 Column mapping matching DataTables columns
    $columns = [
        'no_reg',                // 0
        'kd_spec',               // 1
        'sn',                    // 2
        'pn',                    // 3
        'product_name',          // 4
        'price',                 // 5
        'pr_no',                 // 6
        'pr_date',               // 7
        'po_no',                 // 8
        'po_date',               // 9
        'do_no',                 // 10
        'do_date',               // 11
        'gr_no',                 // 12
        'gr_date',               // 13
        'po_value',              // 14
        'nama_project',          // 15
        'term_of_payment',       // 16
        'kode_site_penerimaan',  // 17
        'qty',                   // 18
        'uom',                   // 19
        'is_unique_item',        // 20
        'warranty',              // 21
        'warranty_unit',         // 22
        'manufacturer',          // 23
        'vendor_name',           // 24
        'vendor_address',        // 25
        'jenis_kepemilikan',     // 26
        'pemilik',               // 27
        'capex_opex',            // 28
        'loi_no',                // 29
        'is_sent_to_artis_code', // 30
        'artis_date',            // 31
        'artis_message',         // 32
        'is_sent_to_iips_code',  // 33
        'iips_date',             // 34
        'iips_message',          // 35
        'pic_submit_gr',         // 36
        'pic_registration',      // 37
        'periode_group'          // 38
    ];

    $orderColumn = isset($columns[$orderColIdx]) ? $columns[$orderColIdx] : 'id';

    // Build filter conditions
    $whereConditions = [];
    $params = [];

    // Global search
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $searchFields = ['no_reg', 'sn', 'pn', 'product_name', 'pr_no', 'po_no', 'do_no', 'gr_no', 'nama_project', 'vendor_name', 'periode_group'];
        $clauses = [];
        foreach ($searchFields as $f) {
            $clauses[] = "{$q}{$f}{$q} $likeOp ?";
            $params[] = $searchTerm;
        }
        $whereConditions[] = '(' . implode(' OR ', $clauses) . ')';
    }

    // Per-column search
    if (isset($_GET['columns']) && is_array($_GET['columns'])) {
        foreach ($_GET['columns'] as $colIdx => $colData) {
            if (!empty($colData['search']['value']) && isset($columns[$colIdx])) {
                $colName = $columns[$colIdx];
                $colSearchVal = $colData['search']['value'];
                if (preg_match('/^\^(.*)\$$/', $colSearchVal, $m)) {
                    $whereConditions[] = "{$q}{$colName}{$q} = ?";
                    $params[] = $m[1];
                } else {
                    $whereConditions[] = "{$q}{$colName}{$q} $likeOp ?";
                    $params[] = '%' . $colSearchVal . '%';
                }
            }
        }
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    // Total records
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM inbound_gr");
    $recordsTotal = (int)$totalStmt->fetchColumn();

    // Filtered count
    $filteredSql = "SELECT COUNT(*) FROM inbound_gr" . $whereClause;
    $filteredStmt = $pdo->prepare($filteredSql);
    $filteredStmt->execute($params);
    $recordsFiltered = (int)$filteredStmt->fetchColumn();

    // Fetch data
    $orderColumnEscaped = "{$q}{$orderColumn}{$q}";
    $dataSql = "SELECT id, no_reg, kd_spec, sn, pn, product_name, price,
                       pr_no, pr_date, po_no, po_date, do_no, do_date,
                       gr_no, gr_date, po_value, nama_project, term_of_payment,
                       kode_site_penerimaan, qty, uom, is_unique_item,
                       warranty, warranty_unit, manufacturer, vendor_name,
                       vendor_address, jenis_kepemilikan, pemilik, capex_opex,
                       loi_no, is_sent_to_artis_code, artis_date, artis_message,
                       is_sent_to_iips_code, iips_date, iips_message,
                       pic_submit_gr, pic_registration, periode_group
                FROM inbound_gr" . $whereClause .
               " ORDER BY $orderColumnEscaped $orderDir LIMIT ? OFFSET ?";

    $dataParams = array_merge($params, [$length, $start]);
    $dataStmt = $pdo->prepare($dataSql);

    foreach ($dataParams as $i => $val) {
        $paramIndex = $i + 1;
        if ($i >= count($params)) {
            $dataStmt->bindValue($paramIndex, (int)$val, PDO::PARAM_INT);
        } else {
            $dataStmt->bindValue($paramIndex, $val, PDO::PARAM_STR);
        }
    }

    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $rows
    ]);
} catch (Exception $e) {
    error_log("Database error in get_inbound_gr.php: " . $e->getMessage());
    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => $e->getMessage()
    ]);
}
