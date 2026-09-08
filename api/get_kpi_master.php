<?php
// api/get_kpi_master.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Check if table exists
    try {
        $pdo->query("SELECT 1 FROM kpi_master LIMIT 1");
    } catch (PDOException $e) {
        // Table does not exist, return empty
        echo json_encode([
            'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
        exit;
    }

    // DataTables server-side parameters
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';

    // Build WHERE clause
    $where = [];
    $params = [];

    if (!empty($year) && preg_match('/^\d{4}$/', $year)) {
        $where[] = "periode_tahun = ?";
        $params[] = (int)$year;
    }

    if (!empty($searchValue)) {
        $where[] = "(bulan LIKE ?)";
        $params[] = "%$searchValue%";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Count total records
    $countSql = "SELECT COUNT(*) FROM kpi_master";
    $totalStmt = $pdo->query($countSql);
    $totalRecords = (int)$totalStmt->fetchColumn();

    // Count filtered records
    $filteredSql = "SELECT COUNT(*) FROM kpi_master $whereClause";
    $filteredStmt = $pdo->prepare($filteredSql);
    $filteredStmt->execute($params);
    $filteredRecords = (int)$filteredStmt->fetchColumn();

    // Order by month number for natural ordering
    $monthOrder = "CASE bulan
        WHEN 'January' THEN 1
        WHEN 'February' THEN 2
        WHEN 'March' THEN 3
        WHEN 'April' THEN 4
        WHEN 'May' THEN 5
        WHEN 'June' THEN 6
        WHEN 'July' THEN 7
        WHEN 'August' THEN 8
        WHEN 'September' THEN 9
        WHEN 'October' THEN 10
        WHEN 'November' THEN 11
        WHEN 'December' THEN 12
        ELSE 99
    END";

    // Fetch data
    $dataSql = "SELECT * FROM kpi_master $whereClause ORDER BY $monthOrder ASC LIMIT $length OFFSET $start";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for DataTables
    $formattedData = [];
    $formatPercent = function ($v) {
        $num = (float)$v;
        if ($num > 0 && $num <= 1.0) {
            $num *= 100;
        }
        return number_format($num, 2) . '%';
    };

    foreach ($rows as $row) {
        $formattedData[] = [
            'id' => $row['id'],
            'bulan' => $row['bulan'],
            'gr_target' => $formatPercent($row['gr_target']),
            'gr_achievement' => $formatPercent($row['gr_achievement']),
            'registrasi_target' => $formatPercent($row['registrasi_target']),
            'registrasi_achievement' => $formatPercent($row['registrasi_achievement']),
            'slow_moving_target' => $formatPercent($row['slow_moving_target']),
            'slow_moving_achievement' => $formatPercent($row['slow_moving_achievement']),
            'utilisasi_space_target' => $formatPercent($row['utilisasi_space_target']),
            'utilisasi_space_achievement' => $formatPercent($row['utilisasi_space_achievement']),
            'stok_opname_target' => $formatPercent($row['stok_opname_target']),
            'stok_opname_achievement' => $formatPercent($row['stok_opname_achievement']),
            'delivery_effectiveness_target' => $formatPercent($row['delivery_effectiveness_target']),
            'delivery_effectiveness_achievement' => $formatPercent($row['delivery_effectiveness_achievement']),
            'mr_closing_target' => $formatPercent($row['mr_closing_target']),
            'mr_closing_achievement' => $formatPercent($row['mr_closing_achievement']),
            'efisiensi_delivery_target' => $formatPercent($row['efisiensi_delivery_target']),
            'efisiensi_delivery_achievement' => $formatPercent($row['efisiensi_delivery_achievement']),
            'periode_tahun' => $row['periode_tahun']
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $formattedData
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_kpi_master.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
