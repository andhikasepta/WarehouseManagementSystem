<?php
// api/save_inbound_gr.php
@ini_set('memory_limit', '512M');
@set_time_limit(300);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$canAdd = canAdd('master_data_inbound') || canAdd('inbound') || canAdd('master_data');
if (!$canAdd) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah/mengimpor Master Data GR.']);
    exit;
}

function ensureInboundGrTableExists($pdo)
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS inbound_gr (
        $idCol,
        no_reg TEXT,
        kd_spec TEXT,
        sn TEXT,
        pn TEXT,
        product_name TEXT,
        price TEXT,
        pr_no TEXT,
        pr_date TEXT,
        po_no TEXT,
        po_date TEXT,
        do_no TEXT,
        do_date TEXT,
        gr_no TEXT,
        gr_date TEXT,
        po_value TEXT,
        nama_project TEXT,
        term_of_payment TEXT,
        kode_site_penerimaan TEXT,
        qty TEXT,
        uom TEXT,
        is_unique_item TEXT,
        warranty TEXT,
        warranty_unit TEXT,
        manufacturer TEXT,
        vendor_name TEXT,
        vendor_address TEXT,
        jenis_kepemilikan TEXT,
        pemilik TEXT,
        capex_opex TEXT,
        loi_no TEXT,
        is_sent_to_artis_code TEXT,
        artis_date TEXT,
        artis_message TEXT,
        is_sent_to_iips_code TEXT,
        iips_date TEXT,
        iips_message TEXT,
        pic_submit_gr TEXT,
        pic_registration TEXT,
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
            ensureInboundGrTableExists($pdo);
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
                    $pdo->exec("TRUNCATE TABLE inbound_gr");
                } elseif (!empty($periodeGroup)) {
                    $delStmt = $pdo->prepare("DELETE FROM inbound_gr WHERE periode_group = ?");
                    $delStmt->execute([$periodeGroup]);
                }
                echo json_encode(['status' => 'success', 'message' => 'Data GR master batch initialized']);
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
                    $pdo->exec("TRUNCATE TABLE inbound_gr");
                }

                $insertedCount = 0;
                if (!empty($rows)) {
                    $pdo->beginTransaction();

                    $chunkSize = 150;
                    $chunks = array_chunk($rows, $chunkSize);

                    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

                    foreach ($chunks as $chunk) {
                        $placeholders = [];
                        $params = [];

                        foreach ($chunk as $r) {
                            if (!is_array($r)) continue;
                            $pGroup = !empty($r['periode_group']) ? $r['periode_group'] : $periodeGroup;

                            // Build normalized key map for case-insensitive and space-insensitive matching
                            $norm = [];
                            foreach ($r as $k => $v) {
                                $cleanK = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$k));
                                if ($cleanK !== '') {
                                    $norm[$cleanK] = $v;
                                }
                            }

                            $getVal = function ($keys, $default = null) use ($r, $norm) {
                                if (!is_array($keys)) $keys = [$keys];
                                foreach ($keys as $k) {
                                    if (isset($r[$k]) && $r[$k] !== '') return $r[$k];
                                    $cleanK = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$k));
                                    if (isset($norm[$cleanK]) && $norm[$cleanK] !== '') return $norm[$cleanK];
                                }
                                return $default;
                            };

                            // 38 column placeholders + periode_group + raw_data = 40
                            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $params[] = $getVal(['no_reg', 'noreg', 'no_registrasi']);
                            $params[] = $getVal(['kd_spec', 'kdspec', 'kode_spec']);
                            $params[] = $getVal(['sn', 'serial_number', 'serial_no']);
                            $params[] = $getVal(['pn', 'part_number', 'part_no']);
                            $params[] = $getVal(['product_name', 'productname', 'nama_produk']);
                            $params[] = $getVal(['price', 'harga']);
                            $params[] = $getVal(['pr_no', 'prno', 'pr_nomor', 'nomor_pr']);
                            $params[] = $getVal(['pr_date', 'prdate', 'tanggal_pr', 'tgl_pr']);
                            $params[] = $getVal(['po_no', 'pono', 'po_nomor', 'nomor_po']);
                            $params[] = $getVal(['po_date', 'podate', 'tanggal_po', 'tgl_po']);
                            $params[] = $getVal(['do_no', 'dono', 'do_nomor', 'nomor_do', 'dn_no']);
                            $params[] = $getVal(['do_date', 'dodate', 'tanggal_do', 'tgl_do', 'dn_date']);
                            $params[] = $getVal(['gr_no', 'grno', 'gr_nomor', 'nomor_gr']);
                            $params[] = $getVal(['gr_date', 'grdate', 'tanggal_gr', 'tgl_gr']);
                            $params[] = $getVal(['po_value', 'povalue', 'nilai_po']);
                            $params[] = $getVal(['nama_project', 'namaproject', 'project_name']);
                            $params[] = $getVal(['term_of_payment', 'termofpayment', 'top']);
                            $params[] = $getVal(['kode_site_penerimaan', 'kodesitepenerimaan', 'site_penerimaan']);
                            $params[] = $getVal(['qty', 'quantity', 'jumlah']);
                            $params[] = $getVal(['uom', 'satuan']);
                            $params[] = $getVal(['is_unique_item', 'isuniqueitem']);
                            $params[] = $getVal(['warranty', 'garansi']);
                            $params[] = $getVal(['warranty_unit', 'warrantyUnit', 'satuan_garansi']);
                            $params[] = $getVal(['manufacturer', 'brand', 'merk']);
                            $params[] = $getVal(['vendor_name', 'vendorname', 'nama_vendor']);
                            $params[] = $getVal(['vendor_address', 'vendoraddress', 'alamat_vendor']);
                            $params[] = $getVal(['jenis_kepemilikan', 'jeniskepemilikan']);
                            $params[] = $getVal(['pemilik', 'owner']);
                            $params[] = $getVal(['capex_opex', 'capexopex']);
                            $params[] = $getVal(['loi_no', 'loino', 'nomor_loi']);
                            $params[] = $getVal(['is_sent_to_artis_code', 'IsSentToARTISCode', 'issenttoartiscode']);
                            $params[] = $getVal(['artis_date', 'ARTIS_Date', 'artisdate']);
                            $params[] = $getVal(['artis_message', 'ARTIS_Message', 'artismessage']);
                            $params[] = $getVal(['is_sent_to_iips_code', 'IsSentToIIPSCode', 'issenttoiipscode']);
                            $params[] = $getVal(['iips_date', 'IIPS_Date', 'iipsdate']);
                            $params[] = $getVal(['iips_message', 'IIPS_Message', 'iipsmessage']);
                            $params[] = $getVal(['pic_submit_gr', 'PIC Submit GR', 'picsubmitgr']);
                            $params[] = $getVal(['pic_registration', 'PIC Registration', 'picregistration']);
                            $params[] = $pGroup;
                            $params[] = json_encode($r, JSON_UNESCAPED_UNICODE);

                            $insertedCount++;
                        }

                        if (!empty($placeholders)) {
                            $sql = "INSERT INTO inbound_gr (
                                no_reg, kd_spec, sn, pn, product_name, price,
                                pr_no, pr_date, po_no, po_date, do_no, do_date,
                                gr_no, gr_date, po_value, nama_project, term_of_payment,
                                kode_site_penerimaan, qty, uom, is_unique_item,
                                warranty, warranty_unit, manufacturer, vendor_name,
                                vendor_address, jenis_kepemilikan, pemilik, capex_opex,
                                loi_no, is_sent_to_artis_code, artis_date, artis_message,
                                is_sent_to_iips_code, iips_date, iips_message,
                                pic_submit_gr, pic_registration, periode_group, raw_data
                            ) VALUES " . implode(', ', $placeholders);

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                        }
                    }

                    $pdo->commit();
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "Successfully imported $insertedCount rows into Data GR.",
                    'count' => $insertedCount
                ]);
                exit;
            } elseif ($action === 'finalize') {
                echo json_encode(['status' => 'success', 'message' => 'Data GR upload finalized successfully.']);
                exit;
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in save_inbound_gr.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
