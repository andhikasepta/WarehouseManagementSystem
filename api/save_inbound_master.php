<?php
// api/save_inbound_master.php
ini_set('memory_limit', '512M');
set_time_limit(0);
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

    // Safety net: create table if migrations haven't run yet
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
}

function getValCI($row, $keys) {
    if (!is_array($keys)) $keys = [$keys];
    foreach ($row as $k => $v) {
        foreach ($keys as $targetKey) {
            $cleanK = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $k));
            $cleanTarget = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $targetKey));
            if ($cleanK === $cleanTarget) {
                return (string)$v;
            }
        }
    }
    return null;
}

function parseDateVal($val) {
    if (!$val) return null;
    $str = trim((string)$val);
    if (empty($str)) return null;
    
    // Check if numeric (Excel serial date number)
    if (is_numeric($str) && (float)$str > 25000 && (float)$str < 80000) {
        $unixTimestamp = ((float)$str - 25569) * 86400;
        return date('Y-m-d', $unixTimestamp);
    }
    
    // Handle DD/MM/YYYY or DD-MM-YYYY formats
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }

    // Handle YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
        return $str;
    }
    
    $timestamp = strtotime($str);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    return null;
}

function parseNumberVal($val) {
    if ($val === null || $val === '') return 0;
    if (is_numeric($val)) return (float)$val;
    $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string)$val));
    return is_numeric($clean) ? (float)$clean : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            ensureInboundMasterTableExists($pdo);
            $action = $data['action'] ?? 'single';

            if ($action === 'init') {
                $clearAll = !empty($data['clear_all']);
                if ($clearAll) {
                    $pdo->exec("TRUNCATE TABLE inbound_master");
                }
                echo json_encode(['status' => 'success', 'message' => 'Inbound master batch initialized']);
                exit;
            } elseif ($action === 'append' || $action === 'batch') {
                $rows = $data['data'] ?? [];
                if (!is_array($rows)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                    exit;
                }

                // Determine Periode Group
                $month = trim((string)($data['month'] ?? ''));
                $year = trim((string)($data['year'] ?? ''));
                $periodeGroup = !empty($data['periode_group']) ? trim((string)$data['periode_group']) : null;
                if (!$periodeGroup && !empty($month) && !empty($year)) {
                    $periodeGroup = $month . ' ' . $year;
                }

                // Check if data for this period already exists in database
                if (!empty($periodeGroup) && empty($data['clear_existing_period'])) {
                    $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM inbound_master WHERE periode_group = ?");
                    $chkStmt->execute([$periodeGroup]);
                    if ((int)$chkStmt->fetchColumn() > 0) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => "Data untuk Periode '$periodeGroup' sudah ada di database."
                        ]);
                        exit;
                    }
                }

                if (!empty($rows)) {
                    $pdo->beginTransaction();

                    // Delete existing records for this period if requested
                    if (!empty($periodeGroup) && !empty($data['clear_existing_period'])) {
                        $delStmt = $pdo->prepare("DELETE FROM inbound_master WHERE periode_group = ?");
                        $delStmt->execute([$periodeGroup]);
                    }

                    $stmt = $pdo->prepare("INSERT INTO inbound_master (
                        pr_nomor, pr_kode_site, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama,
                        pr_nama_bagian, pr_nama_divisi, pr_regional, pr_jenis_ma, po_nomor,
                        po_deskripsi, po_vendor, po_tgl_generate, po_nama_item, po_qty_item,
                        po_uom_item, po_target_delivery, project_id, periode_group, raw_data
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )");

                    $insertedCount = 0;
                    foreach ($rows as $row) {
                        if (!is_array($row)) continue;

                        $prNomor = getValCI($row, ['PR Nomor', 'pr_nomor', 'PR_Nomor']);
                        $prKodeSite = getValCI($row, ['PR Kode Site', 'pr_kode_site', 'Kode Site']);
                        $prNamaSite = getValCI($row, ['PR Nama Site', 'pr_nama_site', 'Nama Site']);
                        $prItemKategori = getValCI($row, ['PR Item Kategori', 'pr_item_kategori', 'Item Kategori']);
                        $prPicTeknis = getValCI($row, ['PR PIC Teknis Nama', 'pr_pic_teknis_nama', 'PIC Teknis', 'PIC Teknis Nama']);
                        $prNamaBagian = getValCI($row, ['PR Nama Bagian', 'pr_nama_bagian', 'Nama Bagian', 'Bagian']);
                        $prNamaDivisi = getValCI($row, ['PR Nama Divisi', 'pr_nama_divisi', 'Nama Divisi', 'Divisi']);
                        $prRegional = getValCI($row, ['PR Regional', 'pr_regional', 'Regional']);
                        $prJenisMa = getValCI($row, ['PR Jenis MA', 'pr_jenis_ma', 'Jenis MA']);
                        $poNomor = getValCI($row, ['PO Nomor', 'po_nomor', 'PO_Nomor']);
                        $poDeskripsi = getValCI($row, ['PO Deskripsi', 'po_deskripsi', 'Deskripsi']);
                        $poVendor = getValCI($row, ['PO Vendor', 'po_vendor', 'Vendor']);
                        $poTglGen = parseDateVal(getValCI($row, ['PO Tgl. Generate', 'PO Tgl Generate', 'po_tgl_generate', 'Tgl Generate']));
                        $poNamaItem = getValCI($row, ['PO Nama Item', 'po_nama_item', 'Nama Item', 'Item']);
                        $poQty = parseNumberVal(getValCI($row, ['PO Qty Item', 'po_qty_item', 'Qty Item', 'Qty']));
                        $poUomItem = getValCI($row, ['PO UoM Item', 'po_uom_item', 'UoM Item', 'UoM', 'Satuan']);
                        $poTargetDel = parseDateVal(getValCI($row, ['PO Target Delivery', 'po_target_delivery', 'Target Delivery']));
                        $projectId = getValCI($row, ['Project ID', 'project_id', 'Project_ID', 'ProjectID']);

                        // Skip entirely empty rows
                        if (!$prNomor && !$poNomor && !$poNamaItem && !$prNamaSite) {
                            continue;
                        }

                        // Fallback row period if not explicitly set
                        $rowPeriod = $periodeGroup;
                        if (!$rowPeriod) {
                            $rowPeriod = getValCI($row, ['Periode Group', 'periode_group', 'Periode', 'periode']);
                        }

                        $stmt->execute([
                            $prNomor, $prKodeSite, $prNamaSite, $prItemKategori, $prPicTeknis,
                            $prNamaBagian, $prNamaDivisi, $prRegional, $prJenisMa, $poNomor,
                            $poDeskripsi, $poVendor, $poTglGen, $poNamaItem, $poQty,
                            $poUomItem, $poTargetDel, $projectId, $rowPeriod, json_encode($row)
                        ]);
                        $insertedCount++;
                    }
                    $pdo->commit();
                }
                echo json_encode(['status' => 'success', 'message' => "$insertedCount data berhasil disimpan" . ($periodeGroup ? " untuk periode $periodeGroup" : "") . "!"]);
                exit;
            } elseif ($action === 'single_save') {
                $id = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : null;

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE inbound_master SET 
                        pr_nomor=?, pr_kode_site=?, pr_nama_site=?, pr_item_kategori=?, pr_pic_teknis_nama=?,
                        pr_nama_bagian=?, pr_nama_divisi=?, pr_regional=?, pr_jenis_ma=?, po_nomor=?,
                        po_deskripsi=?, po_vendor=?, po_tgl_generate=?, po_nama_item=?, po_qty_item=?,
                        po_uom_item=?, po_target_delivery=?, project_id=?, periode_group=?
                        WHERE id=?");
                    $stmt->execute([
                        $data['pr_nomor'] ?? null, $data['pr_kode_site'] ?? null, $data['pr_nama_site'] ?? null,
                        $data['pr_item_kategori'] ?? null, $data['pr_pic_teknis_nama'] ?? null, $data['pr_nama_bagian'] ?? null,
                        $data['pr_nama_divisi'] ?? null, $data['pr_regional'] ?? null, $data['pr_jenis_ma'] ?? null,
                        $data['po_nomor'] ?? null, $data['po_deskripsi'] ?? null, $data['po_vendor'] ?? null,
                        parseDateVal($data['po_tgl_generate'] ?? null), $data['po_nama_item'] ?? null,
                        parseNumberVal($data['po_qty_item'] ?? 0),
                        $data['po_uom_item'] ?? null, parseDateVal($data['po_target_delivery'] ?? null),
                        $data['project_id'] ?? null, $data['periode_group'] ?? null, $id
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO inbound_master (
                        pr_nomor, pr_kode_site, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama,
                        pr_nama_bagian, pr_nama_divisi, pr_regional, pr_jenis_ma, po_nomor,
                        po_deskripsi, po_vendor, po_tgl_generate, po_nama_item, po_qty_item,
                        po_uom_item, po_target_delivery, project_id, periode_group
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $data['pr_nomor'] ?? null, $data['pr_kode_site'] ?? null, $data['pr_nama_site'] ?? null,
                        $data['pr_item_kategori'] ?? null, $data['pr_pic_teknis_nama'] ?? null, $data['pr_nama_bagian'] ?? null,
                        $data['pr_nama_divisi'] ?? null, $data['pr_regional'] ?? null, $data['pr_jenis_ma'] ?? null,
                        $data['po_nomor'] ?? null, $data['po_deskripsi'] ?? null, $data['po_vendor'] ?? null,
                        parseDateVal($data['po_tgl_generate'] ?? null), $data['po_nama_item'] ?? null,
                        parseNumberVal($data['po_qty_item'] ?? 0),
                        $data['po_uom_item'] ?? null, parseDateVal($data['po_target_delivery'] ?? null),
                        $data['project_id'] ?? null, $data['periode_group'] ?? null
                    ]);
                    echo json_encode(['status' => 'success', 'message' => 'Record created successfully']);
                }
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Database error in save_inbound_master.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("General error in save_inbound_master.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
