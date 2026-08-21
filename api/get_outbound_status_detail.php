<?php
// api/get_outbound_status_detail.php
// Returns summary counts and detail rows for Outbound Status Flow (Alur Pemenuhan MR)
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
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

    $action = isset($_GET['action']) ? trim($_GET['action']) : 'detail';
    $status = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : 'TOTAL MR';
    $periode = isset($_GET['periode']) ? trim($_GET['periode']) : (isset($_GET['periode_group']) ? trim($_GET['periode_group']) : '');

    // Normalize status names
    if ($status === 'SHIPPED') {
        $status = 'TOTAL SHIPPED';
    }

    // Build base period condition
    $periodWhere = "1=1";
    $periodParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $periodWhere = "(periode_group = ? OR periode_group LIKE ?)";
        $periodParams = [$periode, $periode . '%'];
    }

    if ($action === 'counts') {
        // If no period is specified and user requested empty default, return 0s
        if (empty($periode) || $periode === 'PILIH PERIODE DATA' || $periode === '-') {
            echo json_encode([
                'status' => 'success',
                'counts' => [
                    'total_mr' => 0,
                    'total_packed' => 0,
                    'total_shipped' => 0,
                    'dalam_perjalanan' => 0,
                    'tiba_di_lokasi' => 0,
                    'segments' => [
                        'internal_delivery' => 0,
                        'internal_pickup' => 0,
                        'internal_handcarry' => 0,
                        'external_mover' => 0
                    ]
                ]
            ]);
            exit;
        }

        // Return summary metrics for top cards with period filter
        $stmtMr = $pdo->prepare("SELECT COUNT(DISTINCT mr_no) FROM outbound_master WHERE $periodWhere AND mr_no IS NOT NULL AND TRIM(mr_no) != ''");
        $stmtMr->execute($periodParams);
        $totalMr = (int) $stmtMr->fetchColumn();
        
        $stmtPck = $pdo->prepare("SELECT COUNT(DISTINCT pck_no) FROM outbound_master WHERE $periodWhere AND pck_no IS NOT NULL AND TRIM(pck_no) != ''");
        $stmtPck->execute($periodParams);
        $totalPacked = (int) $stmtPck->fetchColumn();
        
        $stmtJalan = $pdo->prepare("SELECT COUNT(DISTINCT dn_no) FROM outbound_master WHERE $periodWhere AND (dn_no IS NOT NULL AND TRIM(dn_no) != '') AND (
            LOWER(dn_status) LIKE '%jalan%' OR LOWER(mr_status) LIKE '%jalan%' 
            OR LOWER(dn_status) LIKE '%perjalanan%' OR LOWER(mr_status) LIKE '%perjalanan%'
            OR LOWER(dn_status) LIKE '%transit%' OR LOWER(mr_status) LIKE '%transit%'
            OR LOWER(dn_status) LIKE '%on delivery%' OR LOWER(mr_status) LIKE '%on delivery%'
        )");
        $stmtJalan->execute($periodParams);
        $dalamPerjalanan = (int) $stmtJalan->fetchColumn();

        $stmtTiba = $pdo->prepare("SELECT COUNT(DISTINCT dn_no) FROM outbound_master WHERE $periodWhere AND (dn_no IS NOT NULL AND TRIM(dn_no) != '') AND (
            LOWER(dn_status) LIKE '%tiba%' OR LOWER(mr_status) LIKE '%tiba%' 
            OR LOWER(dn_status) LIKE '%delivered%' OR LOWER(mr_status) LIKE '%delivered%'
            OR LOWER(dn_status) LIKE '%close%' OR LOWER(mr_status) LIKE '%close%'
            OR LOWER(dn_status) LIKE '%selesai%' OR LOWER(mr_status) LIKE '%selesai%'
        )");
        $stmtTiba->execute($periodParams);
        $tibaLokasi = (int) $stmtTiba->fetchColumn();

        $stmtPt = $pdo->prepare("SELECT pickup_type, COUNT(*) as cnt FROM outbound_master WHERE $periodWhere AND pickup_type IS NOT NULL AND TRIM(pickup_type) != '' GROUP BY pickup_type");
        $stmtPt->execute($periodParams);
        $allPickupTypes = $stmtPt->fetchAll(PDO::FETCH_ASSOC);

        $deliveryCount = 0;
        $pickupCount = 0;
        $handcarryCount = 0;
        $moverCount = 0;

        foreach ($allPickupTypes as $pt) {
            $name = trim($pt['pickup_type']);
            $norm = strtolower(str_replace([' ', '-', '_'], '', $name));
            $cnt = (int)$pt['cnt'];

            if (strpos($norm, 'delivery') !== false) {
                $deliveryCount += $cnt;
            } elseif (strpos($norm, 'pickup') !== false) {
                $pickupCount += $cnt;
            } elseif (strpos($norm, 'handcarry') !== false) {
                $handcarryCount += $cnt;
            } elseif (strpos($norm, 'mover') !== false || strpos($norm, 'forwarder') !== false || strpos($norm, 'ekspedisi') !== false || strpos($norm, 'external') !== false) {
                $moverCount += $cnt;
            } else {
                $deliveryCount += $cnt;
            }
        }

        $totalShipped = $deliveryCount + $pickupCount + $handcarryCount + $moverCount;

        echo json_encode([
            'status' => 'success',
            'counts' => [
                'total_mr' => $totalMr,
                'total_packed' => $totalPacked,
                'total_shipped' => $totalShipped,
                'dalam_perjalanan' => $dalamPerjalanan,
                'tiba_di_lokasi' => $tibaLokasi,
                'segments' => [
                    'internal_delivery' => $deliveryCount,
                    'internal_pickup' => $pickupCount,
                    'internal_handcarry' => $handcarryCount,
                    'external_mover' => $moverCount
                ]
            ]
        ]);
        exit;
    }

    // Detail Action: fetch rows for the selected status
    if (empty($periode) || $periode === 'PILIH PERIODE DATA' || $periode === '-') {
        echo json_encode([
            'status' => 'success',
            'requested_status' => $status,
            'count' => 0,
            'data' => []
        ]);
        exit;
    }

    $where = $periodWhere;
    $params = $periodParams;

    if ($status === 'TOTAL PACKED') {
        $where .= " AND ((pck_no IS NOT NULL AND TRIM(pck_no) != '') OR (pck_status IS NOT NULL AND TRIM(pck_status) != ''))";
    } elseif ($status === 'TOTAL SHIPPED') {
        $where .= " AND (pickup_type IS NOT NULL AND TRIM(pickup_type) != '')";
    } elseif ($status === 'DALAM PERJALANAN') {
        $where .= " AND (LOWER(dn_status) LIKE '%jalan%' OR LOWER(mr_status) LIKE '%jalan%' OR LOWER(dn_status) LIKE '%perjalanan%' OR LOWER(mr_status) LIKE '%perjalanan%' OR LOWER(dn_status) LIKE '%transit%' OR LOWER(mr_status) LIKE '%transit%' OR LOWER(dn_status) LIKE '%on delivery%' OR LOWER(mr_status) LIKE '%on delivery%')";
    } elseif ($status === 'TIBA DI LOKASI') {
        $where .= " AND (LOWER(dn_status) LIKE '%tiba%' OR LOWER(mr_status) LIKE '%tiba%' OR LOWER(dn_status) LIKE '%delivered%' OR LOWER(mr_status) LIKE '%delivered%' OR LOWER(dn_status) LIKE '%close%' OR LOWER(mr_status) LIKE '%close%' OR LOWER(dn_status) LIKE '%selesai%' OR LOWER(mr_status) LIKE '%selesai%')";
    }

    $stmt = $pdo->prepare("SELECT mr_no, mr_type, mr_desc, mr_status, pck_no, pck_detail, pck_status, awb, dn_no, pr_no, po_no, origin_from, site_origin, site_origin_addr, destination_to, site_destination, site_destination_addr, pickup_type, via, lt, delivery_target, dn_status, last_log FROM outbound_master WHERE $where ORDER BY id DESC LIMIT 500");
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format rows according to the requested status headers
    $formattedRows = [];
    foreach ($rawRows as $r) {
        $userVal = !empty($r['origin_from']) ? $r['origin_from'] : (!empty($r['site_origin']) ? $r['site_origin'] : '-');
        $tujuanVal = !empty($r['site_destination']) ? $r['site_destination'] : (!empty($r['destination_to']) ? $r['destination_to'] : '-');

        if ($status === 'TOTAL MR') {
            $pickupBy = !empty($r['pickup_type']) ? $r['pickup_type'] : (!empty($r['via']) ? $r['via'] : '-');
            $ketVal = !empty($r['mr_desc']) ? $r['mr_desc'] : (!empty($r['mr_status']) ? $r['mr_status'] : (!empty($r['dn_status']) ? $r['dn_status'] : '-'));
            $formattedRows[] = [
                'no_mr'     => $r['mr_no'] ?: '-',
                'user'      => $userVal,
                'tujuan'    => $tujuanVal,
                'pickup_by' => $pickupBy,
                'ket'       => $ketVal
            ];
        } elseif ($status === 'TOTAL PACKED') {
            $formattedRows[] = [
                'no_pck'     => $r['pck_no'] ?: '-',
                'pck_detail' => $r['pck_detail'] ?: '-',
                'user'       => $userVal,
                'tujuan'     => $tujuanVal,
                'no_mr'      => $r['mr_no'] ?: '-',
                'no_dn'      => $r['dn_no'] ?: '-'
            ];
        } elseif ($status === 'TOTAL SHIPPED') {
            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'pickup_type'     => $r['pickup_type'] ?: '-',
                'via'             => $r['via'] ?: '-',
                'lt'              => $r['lt'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-',
                'last_log'        => $r['last_log'] ?: '-'
            ];
        } elseif ($status === 'DALAM PERJALANAN' || $status === 'TIBA DI LOKASI') {
            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'status_mr'       => $r['mr_status'] ?: '-',
                'lt'              => $r['lt'] ?: '-',
                'status_dn'       => $r['dn_status'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-',
                'last_log'        => $r['last_log'] ?: '-'
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'requested_status' => $status,
        'count' => count($formattedRows),
        'data' => $formattedRows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
