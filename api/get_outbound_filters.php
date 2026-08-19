<?php
// api/get_outbound_filters.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
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

    $destinations = $pdo->query("SELECT DISTINCT site_destination FROM outbound_master WHERE site_destination IS NOT NULL AND site_destination != '' ORDER BY site_destination ASC")->fetchAll(PDO::FETCH_COLUMN);
    $mrStatuses   = $pdo->query("SELECT DISTINCT mr_status FROM outbound_master WHERE mr_status IS NOT NULL AND mr_status != '' ORDER BY mr_status ASC")->fetchAll(PDO::FETCH_COLUMN);
    $dnStatuses   = $pdo->query("SELECT DISTINCT dn_status FROM outbound_master WHERE dn_status IS NOT NULL AND dn_status != '' ORDER BY dn_status ASC")->fetchAll(PDO::FETCH_COLUMN);
    $pickupTypes  = $pdo->query("SELECT DISTINCT pickup_type FROM outbound_master WHERE pickup_type IS NOT NULL AND pickup_type != '' ORDER BY pickup_type ASC")->fetchAll(PDO::FETCH_COLUMN);
    $periods      = $pdo->query("SELECT DISTINCT periode_group FROM outbound_master WHERE periode_group IS NOT NULL AND periode_group != '' ORDER BY periode_group DESC")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'site_destinations' => $destinations,
            'mr_statuses'       => $mrStatuses,
            'dn_statuses'       => $dnStatuses,
            'pickup_types'      => $pickupTypes,
            'periods'           => $periods
        ]
    ]);

} catch (Exception $e) {
    error_log("Database error in get_outbound_filters.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memuat filter options: ' . $e->getMessage(),
        'data' => [
            'site_destinations' => [],
            'mr_statuses'       => [],
            'dn_statuses'       => [],
            'pickup_types'      => []
        ]
    ]);
}
