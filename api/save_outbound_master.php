<?php
// api/save_outbound_master.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$canAdd = canAdd('master_data_outbound') || canAdd('outbound') || canAdd('master_data');
if (!$canAdd) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah/mengimpor Master Data Outbound.']);
    exit;
}

function ensureOutboundMasterTableExists($pdo)
{
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            ensureOutboundMasterTableExists($pdo);
            $action = $data['action'] ?? 'batch';

            if ($action === 'init') {
                $clearAll = !empty($data['clear_all']);
                if ($clearAll) {
                    if ($userRole !== 'superadmin' && $userRole !== 'head_warehouse_admin') {
                        echo json_encode(['status' => 'error', 'message' => 'Request hapus data ke Superadmin']);
                        exit;
                    }
                    $pdo->exec("TRUNCATE TABLE outbound_master");
                }
                echo json_encode(['status' => 'success', 'message' => 'Outbound master batch initialized']);
                exit;
            } elseif ($action === 'append' || $action === 'batch') {
                $rows = $data['data'] ?? [];
                if (!is_array($rows)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                    exit;
                }

                $month = trim((string) ($data['month'] ?? ''));
                $year = trim((string) ($data['year'] ?? ''));
                $periodeGroup = !empty($data['periode_group']) ? trim((string) $data['periode_group']) : null;
                if (!$periodeGroup && !empty($month) && !empty($year)) {
                    $periodeGroup = $month . ' ' . $year;
                }

                if (!empty($data['clear_all'])) {
                    $pdo->exec("TRUNCATE TABLE outbound_master");
                }

                if (!empty($rows)) {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO outbound_master (
                        mr_no, mr_type, mr_desc, mr_status,
                        pck_no, pck_detail, pck_status,
                        awb, dn_no, pr_no, po_no,
                        origin_from, site_origin, site_origin_addr,
                        destination_to, site_destination, site_destination_addr,
                        pickup_type, via, lt, delivery_target,
                        dn_status, last_log, periode_group, raw_data
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?
                    )");

                    $insertedCount = 0;
                    foreach ($rows as $row) {
                        if (!is_array($row))
                            continue;

                        $mrNo = isset($row['mr_no']) ? trim((string) $row['mr_no']) : '';
                        $mrType = isset($row['mr_type']) ? trim((string) $row['mr_type']) : '';
                        $mrDesc = isset($row['mr_desc']) ? trim((string) $row['mr_desc']) : '';
                        $mrStatus = isset($row['mr_status']) ? trim((string) $row['mr_status']) : '';
                        $pckNo = isset($row['pck_no']) ? trim((string) $row['pck_no']) : '';
                        $pckDetail = isset($row['pck_detail']) ? trim((string) $row['pck_detail']) : '';
                        $pckStatus = isset($row['pck_status']) ? trim((string) $row['pck_status']) : '';
                        $awb = isset($row['awb']) ? trim((string) $row['awb']) : '';
                        $dnNo = isset($row['dn_no']) ? trim((string) $row['dn_no']) : '';
                        $prNo = isset($row['pr_no']) ? trim((string) $row['pr_no']) : '';
                        $poNo = isset($row['po_no']) ? trim((string) $row['po_no']) : '';
                        $originFrom = isset($row['origin_from']) ? trim((string) $row['origin_from']) : (isset($row['from']) ? trim((string) $row['from']) : '');
                        $siteOrigin = isset($row['site_origin']) ? trim((string) $row['site_origin']) : '';
                        $siteOriginAddr = isset($row['site_origin_addr']) ? trim((string) $row['site_origin_addr']) : '';
                        $destinationTo = isset($row['destination_to']) ? trim((string) $row['destination_to']) : (isset($row['to']) ? trim((string) $row['to']) : '');
                        $siteDestination = isset($row['site_destination']) ? trim((string) $row['site_destination']) : '';
                        $siteDestAddr = isset($row['site_destination_addr']) ? trim((string) $row['site_destination_addr']) : '';
                        $pickupType = isset($row['pickup_type']) ? trim((string) $row['pickup_type']) : '';
                        $via = isset($row['via']) ? trim((string) $row['via']) : '';
                        $lt = isset($row['lt']) ? trim((string) $row['lt']) : '';
                        $deliveryTarget = isset($row['delivery_target']) ? trim((string) $row['delivery_target']) : '';
                        $dnStatus = isset($row['dn_status']) ? trim((string) $row['dn_status']) : '';
                        $lastLog = isset($row['last_log']) ? trim((string) $row['last_log']) : '';

                        $rawData = isset($row['_raw']) ? $row['_raw'] : $row;
                        $rawJson = json_encode($rawData, JSON_UNESCAPED_UNICODE);

                        $allEmpty = empty($mrNo) && empty($pckNo) && empty($awb) && empty($dnNo) && empty($prNo) && empty($poNo) && empty($siteOrigin) && empty($siteDestination);
                        if ($allEmpty)
                            continue;

                        $stmt->execute([
                            $mrNo,
                            $mrType,
                            $mrDesc,
                            $mrStatus,
                            $pckNo,
                            $pckDetail,
                            $pckStatus,
                            $awb,
                            $dnNo,
                            $prNo,
                            $poNo,
                            $originFrom,
                            $siteOrigin,
                            $siteOriginAddr,
                            $destinationTo,
                            $siteDestination,
                            $siteDestAddr,
                            $pickupType,
                            $via,
                            $lt,
                            $deliveryTarget,
                            $dnStatus,
                            $lastLog,
                            $periodeGroup,
                            $rawJson
                        ]);
                        $insertedCount++;
                    }

                    $pdo->commit();

                    echo json_encode([
                        'status' => 'success',
                        'message' => "Berhasil menyimpan $insertedCount baris data Outbound Master.",
                        'inserted_count' => $insertedCount
                    ]);
                    exit;
                } else {
                    echo json_encode(['status' => 'success', 'message' => 'Tidak ada data untuk disimpan', 'inserted_count' => 0]);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Database error in save_outbound_master.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("General error in save_outbound_master.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
