<?php
// api/save_outbound_forwarder.php
@ini_set('memory_limit', '512M');
@set_time_limit(300);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$canAdd = canAdd('master_data_outbound') || canAdd('outbound') || canAdd('master_data');
if (!$canAdd) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah/mengimpor Master Data PR Forwarder.']);
    exit;
}

function ensureOutboundForwarderTableExists($pdo)
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS outbound_forwarder (
        $idCol,
        no_dn TEXT,
        print_status TEXT,
        dn_status TEXT,
        asal_pengirim TEXT,
        asal_code TEXT,
        asal_site TEXT,
        asal_alamat TEXT,
        tujuan_penerima TEXT,
        tujuan_code TEXT,
        tujuan_site TEXT,
        tujuan_alamat TEXT,
        proc_vendor_mode TEXT,
        proc_vendor_name TEXT,
        koli TEXT,
        mata_anggaran TEXT,
        sr_no TEXT,
        sr_tgl TEXT,
        pr_no TEXT,
        pr_tgl TEXT,
        valuation_price TEXT,
        suggestion TEXT,
        purpose TEXT,
        po_no TEXT,
        po_tgl TEXT,
        po_price TEXT,
        po_vendor TEXT,
        po_target_dlv TEXT,
        po_buyer TEXT,
        doc TEXT,
        note TEXT,
        delivery_type TEXT,
        delivery_via TEXT,
        delivery_nama TEXT,
        delivery_awb TEXT,
        delivery_pickup TEXT,
        delivery_lead_time TEXT,
        delivery_target_dlv TEXT,
        approval_status TEXT,
        approval_approver TEXT,
        approval_date TEXT,
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
            ensureOutboundForwarderTableExists($pdo);
            $action = $data['action'] ?? 'batch';

            if ($action === 'init') {
                $month = trim((string) ($data['month'] ?? ''));
                $year = trim((string) ($data['year'] ?? ''));
                $batch = trim((string) ($data['batch'] ?? '1'));
                $periodeGroup = !empty($data['periode_group']) ? trim((string) $data['periode_group']) : null;
                if (!$periodeGroup && !empty($month) && !empty($year)) {
                    $periodeGroup = $month . ' ' . $year . '-Batch' . intval($batch);
                }

                $clearAll = !empty($data['clear_all']);
                if ($clearAll) {
                    $currentUser = getCurrentUser();
                    $userRole = $currentUser['role'] ?? 'admin';
                    if ($userRole !== 'superadmin' && $userRole !== 'head_warehouse_admin') {
                        echo json_encode(['status' => 'error', 'message' => 'Request hapus data ke Superadmin']);
                        exit;
                    }
                    $pdo->exec("TRUNCATE TABLE outbound_forwarder");
                } elseif (!empty($periodeGroup)) {
                    $delStmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group = ?");
                    $delStmt->execute([$periodeGroup]);
                }
                echo json_encode(['status' => 'success', 'message' => 'PR Forwarder master batch initialized']);
                exit;
            } elseif ($action === 'append' || $action === 'batch') {
                $rows = $data['data'] ?? [];
                if (!is_array($rows)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                    exit;
                }

                $month = trim((string) ($data['month'] ?? ''));
                $year = trim((string) ($data['year'] ?? ''));
                $batch = trim((string) ($data['batch'] ?? '1'));
                $periodeGroup = !empty($data['periode_group']) ? trim((string) $data['periode_group']) : null;
                if (!$periodeGroup && !empty($month) && !empty($year)) {
                    $periodeGroup = $month . ' ' . $year . '-Batch' . intval($batch);
                }

                if (!empty($data['clear_all'])) {
                    $pdo->exec("TRUNCATE TABLE outbound_forwarder");
                }

                $insertedCount = 0;
                if (!empty($rows)) {
                    $pdo->beginTransaction();

                    $chunkSize = 150;
                    $chunks = array_chunk($rows, $chunkSize);

                    foreach ($chunks as $chunk) {
                        $placeholders = [];
                        $params = [];

                        foreach ($chunk as $r) {
                            if (!is_array($r)) continue;
                            $pGroup = !empty($r['periode_group']) ? $r['periode_group'] : $periodeGroup;

                            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $params[] = $r['no_dn'] ?? null;
                            $params[] = $r['print_status'] ?? null;
                            $params[] = $r['dn_status'] ?? null;
                            $params[] = $r['asal_pengirim'] ?? null;
                            $params[] = $r['asal_code'] ?? null;
                            $params[] = $r['asal_site'] ?? null;
                            $params[] = $r['asal_alamat'] ?? null;
                            $params[] = $r['tujuan_penerima'] ?? null;
                            $params[] = $r['tujuan_code'] ?? null;
                            $params[] = $r['tujuan_site'] ?? null;
                            $params[] = $r['tujuan_alamat'] ?? null;
                            $params[] = $r['proc_vendor_mode'] ?? null;
                            $params[] = $r['proc_vendor_name'] ?? null;
                            $params[] = $r['koli'] ?? null;
                            $params[] = $r['mata_anggaran'] ?? null;
                            $params[] = $r['sr_no'] ?? null;
                            $params[] = $r['sr_tgl'] ?? null;
                            $params[] = $r['pr_no'] ?? null;
                            $params[] = $r['pr_tgl'] ?? null;
                            $params[] = $r['valuation_price'] ?? null;
                            $params[] = $r['suggestion'] ?? null;
                            $params[] = $r['purpose'] ?? null;
                            $params[] = $r['po_no'] ?? null;
                            $params[] = $r['po_tgl'] ?? null;
                            $params[] = $r['po_price'] ?? null;
                            $params[] = $r['po_vendor'] ?? null;
                            $params[] = $r['po_target_dlv'] ?? null;
                            $params[] = $r['po_buyer'] ?? null;
                            $params[] = $r['doc'] ?? null;
                            $params[] = $r['note'] ?? null;
                            $params[] = $r['delivery_type'] ?? null;
                            $params[] = $r['delivery_via'] ?? null;
                            $params[] = $r['delivery_nama'] ?? null;
                            $params[] = $r['delivery_awb'] ?? null;
                            $params[] = $r['delivery_pickup'] ?? null;
                            $params[] = $r['delivery_lead_time'] ?? null;
                            $params[] = $r['delivery_target_dlv'] ?? null;
                            $params[] = $r['approval_status'] ?? null;
                            $params[] = $r['approval_approver'] ?? null;
                            $params[] = $r['approval_date'] ?? null;
                            $params[] = $pGroup;
                            $params[] = json_encode($r);
                            $insertedCount++;
                        }

                        if (!empty($placeholders)) {
                            $sql = "INSERT INTO outbound_forwarder (
                                no_dn, print_status, dn_status,
                                asal_pengirim, asal_code, asal_site, asal_alamat,
                                tujuan_penerima, tujuan_code, tujuan_site, tujuan_alamat,
                                proc_vendor_mode, proc_vendor_name, koli, mata_anggaran,
                                sr_no, sr_tgl, pr_no, pr_tgl,
                                valuation_price, suggestion, purpose,
                                po_no, po_tgl, po_price, po_vendor, po_target_dlv, po_buyer,
                                doc, note,
                                delivery_type, delivery_via, delivery_nama, delivery_awb,
                                delivery_pickup, delivery_lead_time, delivery_target_dlv,
                                approval_status, approval_approver, approval_date,
                                periode_group, raw_data
                            ) VALUES " . implode(', ', $placeholders);
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                        }
                    }

                    $pdo->commit();
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "Berhasil menyimpan $insertedCount data PR Forwarder."
                ]);
                exit;
            } elseif ($action === 'finalize') {
                echo json_encode(['status' => 'success', 'message' => 'PR Forwarder master batch finalized']);
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
