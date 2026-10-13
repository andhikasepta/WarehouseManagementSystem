<?php
// api/get_inbound_master.php
// Server-side DataTables processing for Inbound Master Data
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

    $canAccess = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || $userRole === 'inbound_admin' || in_array('master_data_inbound', $userModules) || in_array('master_data', $userModules));
    if (!$canAccess) {
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    // ─── DataTables Server-Side Parameters ───
    $draw    = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0;
    $start   = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
    $length  = isset($_REQUEST['length']) ? intval($_REQUEST['length']) : 25;
    $searchValue = isset($_REQUEST['search']['value']) ? trim($_REQUEST['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_REQUEST['order'][0]['column']) ? intval($_REQUEST['order'][0]['column']) : 0;
    $orderDir    = (isset($_REQUEST['order'][0]['dir']) && strtolower($_REQUEST['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

    // Column mapping (must match DataTables column order)
    $columns = [
        'pr_nomor',           // 0
        'pr_kode_site',       // 1
        'pr_nama_site',       // 2
        'pr_item_kategori',   // 3
        'pr_pic_teknis_nama', // 4
        'pr_nama_bagian',     // 5
        'pr_nama_divisi',     // 6
        'pr_regional',        // 7
        'pr_jenis_ma',        // 8
        'po_nomor',           // 9
        'po_deskripsi',       // 10
        'po_vendor',          // 11
        'po_tgl_generate',    // 12
        'po_nama_item',       // 13
        'po_qty_item',        // 14
        'po_uom_item',        // 15
        'po_target_delivery', // 16
        'project_id',         // 17
        'periode_group'       // 18
    ];

    $orderColumn = isset($columns[$orderColIdx]) ? $columns[$orderColIdx] : 'id';
    // Sanitize: only allow known column names
    if (!in_array($orderColumn, $columns)) {
        $orderColumn = 'id';
    }

    // ─── External filter parameters (from dropdown filters) ───
    $periodeFilter  = $_REQUEST['periode'] ?? $_REQUEST['periode_group'] ?? null;
    $bagianFilter   = $_REQUEST['bagian'] ?? null;
    $picFilter      = $_REQUEST['pic'] ?? null;
    $kategoriFilter = $_REQUEST['kategori'] ?? null;

    // ─── Build WHERE clause ───
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $q = ($driver === 'pgsql') ? '"' : '`';
    $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
    $castText = ($driver === 'pgsql') ? '::text' : '';

    $whereConditions = [];
    $params = [];

    if (!empty($periodeFilter)) {
        $cleanPeriode = trim(preg_replace('/\s*\((?:ALL\s+)?BATCH(?:\s+ALL)?\)\s*$/i', '', $periodeFilter));
        if (preg_match('/-Batch\d+$/i', $cleanPeriode)) {
            $whereConditions[] = "periode_group = ?";
            $params[] = $cleanPeriode;
        } else {
            $whereConditions[] = "(periode_group = ? OR periode_group $likeOp ?)";
            $params[] = $cleanPeriode;
            $params[] = $cleanPeriode . '%';
        }
    }
    if (!empty($bagianFilter)) {
        $whereConditions[] = "pr_nama_bagian = ?";
        $params[] = $bagianFilter;
    }
    if (!empty($picFilter)) {
        $whereConditions[] = "pr_pic_teknis_nama = ?";
        $params[] = $picFilter;
    }
    if (!empty($kategoriFilter)) {
        $whereConditions[] = "pr_item_kategori = ?";
        $params[] = $kategoriFilter;
    }

    // DataTables global search (searches across ALL columns)
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $clauses = [];
        foreach ($columns as $c) {
            $clauses[] = "{$q}{$c}{$q}{$castText} $likeOp ?";
            $params[] = $searchTerm;
        }
        $whereConditions[] = '(' . implode(' OR ', $clauses) . ')';
    }

    // Per-column search from DataTables
    if (isset($_REQUEST['columns']) && is_array($_REQUEST['columns'])) {
        foreach ($_REQUEST['columns'] as $colIdx => $colData) {
            if (!empty($colData['search']['value']) && isset($columns[$colIdx])) {
                $colName = $columns[$colIdx];
                $colSearchVal = $colData['search']['value'];
                // Check if it looks like a regex exact match (^...$)
                if (preg_match('/^\^(.*)\$$/', $colSearchVal, $m)) {
                    $whereConditions[] = "{$q}{$colName}{$q}{$castText} = ?";
                    $params[] = $m[1];
                } else {
                    $whereConditions[] = "{$q}{$colName}{$q}{$castText} $likeOp ?";
                    $params[] = '%' . $colSearchVal . '%';
                }
            }
        }
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    // ─── Get total record count (unfiltered) ───
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM inbound_master");
    $recordsTotal = (int)$totalStmt->fetchColumn();

    // ─── Get filtered record count ───
    $filteredSql = "SELECT COUNT(*) FROM inbound_master" . $whereClause;
    $filteredStmt = $pdo->prepare($filteredSql);
    $filteredStmt->execute($params);
    $recordsFiltered = (int)$filteredStmt->fetchColumn();

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $q = ($driver === 'pgsql') ? '"' : '`';
    $orderColumnEscaped = "{$q}{$orderColumn}{$q}";
    $dataSql = "SELECT id, pr_nomor, pr_kode_site, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama,
                       pr_nama_bagian, pr_nama_divisi, pr_regional, pr_jenis_ma, po_nomor, po_deskripsi,
                       po_vendor, po_tgl_generate, po_nama_item, po_qty_item, po_uom_item, po_target_delivery,
                       project_id, periode_group
                FROM inbound_master" . $whereClause .
               " ORDER BY $orderColumnEscaped $orderDir LIMIT ? OFFSET ?";

    $dataParams = array_merge($params, [$length, $start]);
    $dataStmt = $pdo->prepare($dataSql);
    
    // Bind parameters — PDO needs explicit int type for LIMIT/OFFSET
    foreach ($dataParams as $i => $val) {
        $paramIndex = $i + 1;
        if ($i >= count($params)) {
            // LIMIT and OFFSET params — must be int
            $dataStmt->bindValue($paramIndex, (int)$val, PDO::PARAM_INT);
        } else {
            $dataStmt->bindValue($paramIndex, $val, PDO::PARAM_STR);
        }
    }

    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['po_qty_item'] = (float)$r['po_qty_item'];
        if (empty($r['periode_group'])) {
            $r['periode_group'] = 'Unknown Period';
        }
    }
    unset($r);

    // ─── Server-side DataTables response format ───
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $rows
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_inbound_master.php: " . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error occurred'
    ]);
}
