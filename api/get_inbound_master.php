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
    $draw    = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
    $start   = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length  = isset($_GET['length']) ? intval($_GET['length']) : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order parameters
    $orderColIdx = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir    = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

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
    $periodeFilter  = $_GET['periode'] ?? $_GET['periode_group'] ?? null;
    $bagianFilter   = $_GET['bagian'] ?? null;
    $picFilter      = $_GET['pic'] ?? null;
    $kategoriFilter = $_GET['kategori'] ?? null;

    // ─── Build WHERE clause ───
    $whereConditions = [];
    $params = [];

    if (!empty($periodeFilter)) {
        $whereConditions[] = "periode_group = ?";
        $params[] = $periodeFilter;
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

    // DataTables global search (searches across key columns)
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $whereConditions[] = "(po_nomor LIKE ? OR pr_nomor LIKE ? OR po_nama_item LIKE ? OR project_id LIKE ? OR periode_group LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Per-column search from DataTables
    if (isset($_GET['columns']) && is_array($_GET['columns'])) {
        foreach ($_GET['columns'] as $colIdx => $colData) {
            if (!empty($colData['search']['value']) && isset($columns[$colIdx])) {
                $colName = $columns[$colIdx];
                $colSearchVal = $colData['search']['value'];
                // Check if it looks like a regex exact match (^...$)
                if (preg_match('/^\^(.*)\$$/', $colSearchVal, $m)) {
                    $whereConditions[] = "$colName = ?";
                    $params[] = $m[1];
                } else {
                    $whereConditions[] = "$colName LIKE ?";
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

    // ─── Fetch paginated data ───
    // TEXT columns need backtick quoting for ORDER BY
    $orderColumnEscaped = "`$orderColumn`";
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
