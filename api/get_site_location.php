<?php
// api/get_site_location.php — Server-side DataTables endpoint for site_location
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Ensure table exists
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_location (
        $idCol,
        category TEXT,
        intan TEXT,
        regional TEXT,
        region TEXT,
        area_cluster TEXT,
        address TEXT,
        province TEXT,
        city TEXT,
        sub_district TEXT,
        village TEXT,
        postal_code TEXT,
        latitude TEXT,
        longitude TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )");
} catch (Exception $e) {
    // Table might already exist, ignore
}

// DataTables server-side columns
$columns = [
    'site_id',
    'category',
    'intan',
    'region',
    'area_cluster',
    'address',
    'province',
    'city',
    'sub_district',
    'village',
    'postal_code',
    'latitude',
    'longitude'
];

try {
    // DataTables parameters
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Order
    $orderCol = 0;
    $orderDir = 'ASC';
    if (isset($_GET['order'][0]['column'])) {
        $orderCol = (int)$_GET['order'][0]['column'];
    }
    if (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'desc') {
        $orderDir = 'DESC';
    }
    $orderColumn = isset($columns[$orderCol]) ? $columns[$orderCol] : 'id';

    // Total records (unfiltered)
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM site_location");
    $recordsTotal = (int)$stmtTotal->fetchColumn();

    // Build WHERE clause for search
    $whereClause = '';
    $params = [];
    if (!empty($searchValue)) {
        $searchCols = [];
        foreach ($columns as $col) {
            if ($driver === 'pgsql') {
                $searchCols[] = "CAST($col AS TEXT) ILIKE ?";
            } else {
                $searchCols[] = "$col LIKE ?";
            }
            $params[] = '%' . $searchValue . '%';
        }
        $whereClause = 'WHERE (' . implode(' OR ', $searchCols) . ')';
    }

    // Filtered count
    $sqlFiltered = "SELECT COUNT(*) FROM site_location $whereClause";
    $stmtFiltered = $pdo->prepare($sqlFiltered);
    $stmtFiltered->execute($params);
    $recordsFiltered = (int)$stmtFiltered->fetchColumn();

    // Fetch data
    $sqlData = "SELECT " . implode(', ', $columns) . " FROM site_location $whereClause ORDER BY $orderColumn $orderDir LIMIT $length OFFSET $start";
    $stmtData = $pdo->prepare($sqlData);
    $stmtData->execute($params);
    $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);

} catch (PDOException $e) {
    error_log("get_site_location error: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
