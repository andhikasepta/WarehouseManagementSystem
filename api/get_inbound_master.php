<?php
// api/get_inbound_master.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

function ensureInboundMasterTableExists($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS inbound_master (
        $idCol,
        pr_nomor TEXT,
        pr_kode_site TEXT,
        pr_nama_site TEXT,
        pr_item_kategori TEXT,
        pr_pic_teknis_nama TEXT,
        pr_nama_bagian TEXT,
        pr_nama_divisi TEXT,
        pr_regional TEXT,
        pr_jenis_ma TEXT,
        po_nomor TEXT,
        po_deskripsi TEXT,
        po_vendor TEXT,
        po_tgl_generate DATE,
        po_nama_item TEXT,
        po_qty_item DECIMAL(15,2) DEFAULT 0,
        po_uom_item TEXT,
        po_target_delivery DATE,
        project_id TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    try {
        if ($driver === 'mysql') {
            $pdo->exec("ALTER TABLE inbound_master ADD COLUMN periode_group TEXT");
        } elseif ($driver === 'pgsql') {
            $pdo->exec("ALTER TABLE inbound_master ADD COLUMN IF NOT EXISTS periode_group TEXT");
        }
    } catch (Exception $ex) {}
}

try {
    ensureInboundMasterTableExists($pdo);

    $currentUser = getCurrentUser();
    $userRole = $currentUser['role'] ?? 'admin';
    $userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

    $canAccess = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || $userRole === 'inbound_admin' || in_array('master_data_inbound', $userModules) || in_array('master_data', $userModules));
    if (!$canAccess) {
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    $periodeFilter = $_GET['periode'] ?? $_GET['periode_group'] ?? null;
    $bagianFilter = $_GET['bagian'] ?? null;
    $picFilter = $_GET['pic'] ?? null;
    $kategoriFilter = $_GET['kategori'] ?? null;
    $searchQuery = $_GET['search'] ?? null;

    $sql = "SELECT id, pr_nomor, pr_kode_site, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama, 
                   pr_nama_bagian, pr_nama_divisi, pr_regional, pr_jenis_ma, po_nomor, po_deskripsi, 
                   po_vendor, po_tgl_generate, po_nama_item, po_qty_item, po_uom_item, po_target_delivery, 
                   project_id, periode_group, created_at, updated_at 
            FROM inbound_master WHERE 1=1";
    $params = [];

    if (!empty($periodeFilter)) {
        $sql .= " AND periode_group = ?";
        $params[] = $periodeFilter;
    }
    if (!empty($bagianFilter)) {
        $sql .= " AND pr_nama_bagian = ?";
        $params[] = $bagianFilter;
    }
    if (!empty($picFilter)) {
        $sql .= " AND pr_pic_teknis_nama = ?";
        $params[] = $picFilter;
    }
    if (!empty($kategoriFilter)) {
        $sql .= " AND pr_item_kategori = ?";
        $params[] = $kategoriFilter;
    }
    if (!empty($searchQuery)) {
        $sql .= " AND (po_nomor LIKE ? OR pr_nomor LIKE ? OR po_nama_item LIKE ? OR project_id LIKE ? OR periode_group LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['po_qty_item'] = (float)$r['po_qty_item'];
        if (empty($r['periode_group'])) {
            $r['periode_group'] = 'Unknown Period';
        }
    }
    unset($r);

    // Fetch distinct filter options
    $periodes = $pdo->query("SELECT DISTINCT periode_group FROM inbound_master WHERE periode_group IS NOT NULL AND periode_group != ''")->fetchAll(PDO::FETCH_COLUMN);
    usort($periodes, function($a, $b) {
        $da = strtotime("01 " . $a);
        $db = strtotime("01 " . $b);
        return $db - $da; // newest first
    });

    $bagians = $pdo->query("SELECT DISTINCT pr_nama_bagian FROM inbound_master WHERE pr_nama_bagian IS NOT NULL AND pr_nama_bagian != '' ORDER BY pr_nama_bagian ASC")->fetchAll(PDO::FETCH_COLUMN);
    $pics = $pdo->query("SELECT DISTINCT pr_pic_teknis_nama FROM inbound_master WHERE pr_pic_teknis_nama IS NOT NULL AND pr_pic_teknis_nama != '' ORDER BY pr_pic_teknis_nama ASC")->fetchAll(PDO::FETCH_COLUMN);
    $kategoriList = $pdo->query("SELECT DISTINCT pr_item_kategori FROM inbound_master WHERE pr_item_kategori IS NOT NULL AND pr_item_kategori != '' ORDER BY pr_item_kategori ASC")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'data' => $rows,
        'filters' => [
            'periode' => $periodes,
            'bagian' => $bagians,
            'pic' => $pics,
            'kategori' => $kategoriList
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_inbound_master.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
