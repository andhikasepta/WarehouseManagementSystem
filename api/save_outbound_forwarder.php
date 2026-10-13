<?php
// api/save_outbound_forwarder.php
@ini_set('memory_limit', '512M');
@set_time_limit(300);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/cache_helper.php';

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
        sr_tgl DATE,
        pr_no TEXT,
        pr_tgl DATE,
        valuation_price TEXT,
        suggestion TEXT,
        purpose TEXT,
        po_no TEXT,
        po_tgl DATE,
        po_price TEXT,
        po_vendor TEXT,
        po_target_dlv DATE,
        po_buyer TEXT,
        doc TEXT,
        note TEXT,
        delivery_type TEXT,
        delivery_via TEXT,
        delivery_nama TEXT,
        delivery_awb TEXT,
        delivery_pickup DATE,
        delivery_lead_time TEXT,
        delivery_target_dlv DATE,
        approval_status TEXT,
        approval_approver TEXT,
        approval_date DATE,
        periode_group TEXT,
        created_date DATE,
        created_by TEXT,
        updated_date DATE,
        updated_by TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    // Ensure created_by, updated_by, created_date, and updated_date exist if table was previously created
    try {
        $colsToAdd = [
            'created_by' => 'TEXT',
            'updated_by' => 'TEXT',
            'created_date' => 'DATE',
            'updated_date' => 'DATE'
        ];

        foreach ($colsToAdd as $cName => $cType) {
            if ($driver === 'pgsql') {
                $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = ?");
                $stmt->execute([$cName]);
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN {$cName} {$cType}");
                }
            } else {
                $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = ?");
                $stmt->execute([$cName]);
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN {$cName} {$cType} NULL");
                }
            }
        }
    } catch (Exception $e) {}
}

function parseDateVal($val, $includeTime = false)
{
    if (!$val)
        return null;
    $str = trim((string) $val);
    if (empty($str) || $str === '-' || strtolower($str) === 'null')
        return null;

    // Check if numeric (Excel serial date number)
    if (is_numeric($str) && (float) $str > 25000 && (float) $str < 80000) {
        $unixTimestamp = (int) round(((float) $str - 25569) * 86400);
        return $includeTime ? date('Y-m-d H:i:s', $unixTimestamp) : date('Y-m-d', $unixTimestamp);
    }

    // Handle DD/MM/YYYY or DD-MM-YYYY with optional HH:mm:ss (e.g. 13/10/2026 15:31:52)
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/', $str, $m)) {
        $h = isset($m[4]) ? (int) $m[4] : 0;
        $i = isset($m[5]) ? (int) $m[5] : 0;
        $s = isset($m[6]) ? (int) $m[6] : 0;
        if ($includeTime && isset($m[4])) {
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1], $h, $i, $s);
        }
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }

    // Handle YYYY-MM-DD with optional HH:mm:ss
    if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/', $str, $m)) {
        $h = isset($m[4]) ? (int) $m[4] : 0;
        $i = isset($m[5]) ? (int) $m[5] : 0;
        $s = isset($m[6]) ? (int) $m[6] : 0;
        if ($includeTime && isset($m[4])) {
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $h, $i, $s);
        }
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    $timestamp = strtotime($str);
    if ($timestamp !== false) {
        return $includeTime ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d', $timestamp);
    }
    return null;
}

function determineForwarderPeriodeGroup($createdDate, $fallback = null)
{
    $dateToUse = $createdDate ?: $fallback;
    if (!$dateToUse) {
        $dateToUse = date('Y-m-d');
    }

    $ts = strtotime($dateToUse);
    if ($ts !== false) {
        $month = date('F', $ts);     // e.g. "October", "February"
        $year = date('Y', $ts);      // e.g. "2026"
        $day = (int) date('j', $ts); // e.g. 13
        $batch = ($day <= 15) ? 1 : 2;
        return "$month $year-Batch$batch";
    }

    if (!empty($fallback)) {
        return $fallback;
    }

    return date('F Y') . '-Batch1';
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
                    clearWmsBackendCache();
                } elseif (!empty($periodeGroup)) {
                    $delStmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group = ?");
                    $delStmt->execute([$periodeGroup]);
                    clearWmsBackendCache();
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
                $autoGroup = !empty($data['auto_group']) || (empty($month) && empty($year));
                $periodeGroup = !empty($data['periode_group']) ? trim((string) $data['periode_group']) : null;
                if (!$periodeGroup && !empty($month) && !empty($year)) {
                    $periodeGroup = $month . ' ' . $year . '-Batch' . intval($batch);
                }

                if (!empty($data['clear_all'])) {
                    $pdo->exec("TRUNCATE TABLE outbound_forwarder");
                }

                $insertedCount = 0;
                if (!empty($rows)) {
                    $currentUser = getCurrentUser();
                    $creatorName = $currentUser['username'] ?? ($currentUser['name'] ?? 'System');
                    $nowTimestamp = date('Y-m-d H:i:s');

                    $pdo->beginTransaction();

                    $chunkSize = 150;
                    $chunks = array_chunk($rows, $chunkSize);

                    foreach ($chunks as $chunk) {
                        $placeholders = [];
                        $params = [];

                        foreach ($chunk as $r) {
                            if (!is_array($r)) continue;

                            // 1. Extract LOG created date & datetime
                            $logCreatedDate = null;
                            $logCreatedAt = null;
                            $dateKeys = ['created_date', 'created_at', 'log_created_date', 'LOG Created Date', 'Created Date', 'CREATED DATE', 'Tgl Created', 'log_date', 'created_tgl'];
                            foreach ($dateKeys as $dk) {
                                if (!empty($r[$dk])) {
                                    $parsedDate = parseDateVal($r[$dk], false);
                                    $parsedDateTime = parseDateVal($r[$dk], true);
                                    if ($parsedDate) {
                                        $logCreatedDate = $parsedDate;
                                        $logCreatedAt = $parsedDateTime ?: ($parsedDate . ' ' . date('H:i:s'));
                                        break;
                                    }
                                }
                            }
                            if (!$logCreatedDate) {
                                $fallbackDateKeys = ['po_tgl', 'pr_tgl', 'sr_tgl', 'approval_date', 'delivery_pickup'];
                                foreach ($fallbackDateKeys as $fdk) {
                                    if (!empty($r[$fdk])) {
                                        $parsed = parseDateVal($r[$fdk], false);
                                        if ($parsed) {
                                            $logCreatedDate = $parsed;
                                            $logCreatedAt = $parsed . ' ' . date('H:i:s');
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$logCreatedDate) {
                                $logCreatedDate = date('Y-m-d');
                                $logCreatedAt = date('Y-m-d H:i:s');
                            }

                            // 2. Extract LOG updated date & datetime
                            $logUpdatedDate = null;
                            $logUpdatedAt = null;
                            $updateDateKeys = ['updated_date', 'updated_at', 'log_updated_date', 'LOG Updated Date', 'Updated Date', 'UPDATED DATE', 'Tgl Updated', 'updated_tgl'];
                            foreach ($updateDateKeys as $udk) {
                                if (!empty($r[$udk])) {
                                    $parsedUDate = parseDateVal($r[$udk], false);
                                    $parsedUTime = parseDateVal($r[$udk], true);
                                    if ($parsedUDate) {
                                        $logUpdatedDate = $parsedUDate;
                                        $logUpdatedAt = $parsedUTime ?: ($parsedUDate . ' ' . date('H:i:s'));
                                        break;
                                    }
                                }
                            }
                            if (!$logUpdatedDate) {
                                $logUpdatedDate = $logCreatedDate;
                                $logUpdatedAt = $logCreatedAt;
                            }

                            // 3. Grouping period on PR Forwarder (based on created date)
                            if (!empty($periodeGroup)) {
                                $pGroup = $periodeGroup;
                            } elseif (!empty($r['periode_group'])) {
                                $pGroup = $r['periode_group'];
                            } else {
                                $pGroup = determineForwarderPeriodeGroup($logCreatedDate);
                            }

                            // 4. Audit Log Users
                            $rowCreatedBy = !empty($r['created_by']) ? trim((string) $r['created_by']) : (!empty($r['CREATED BY']) ? trim((string) $r['CREATED BY']) : $creatorName);
                            $rowUpdatedBy = !empty($r['updated_by']) ? trim((string) $r['updated_by']) : (!empty($r['UPDATED BY']) ? trim((string) $r['UPDATED BY']) : $creatorName);

                            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                            $params[] = parseDateVal($r['sr_tgl'] ?? null);
                            $params[] = $r['pr_no'] ?? null;
                            $params[] = parseDateVal($r['pr_tgl'] ?? null);
                            $params[] = $r['valuation_price'] ?? null;
                            $params[] = $r['suggestion'] ?? null;
                            $params[] = $r['purpose'] ?? null;
                            $params[] = $r['po_no'] ?? null;
                            $params[] = parseDateVal($r['po_tgl'] ?? null);
                            $params[] = $r['po_price'] ?? null;
                            $params[] = $r['po_vendor'] ?? null;
                            $params[] = parseDateVal($r['po_target_dlv'] ?? null);
                            $params[] = $r['po_buyer'] ?? null;
                            $params[] = $r['doc'] ?? null;
                            $params[] = $r['note'] ?? null;
                            $params[] = $r['delivery_type'] ?? null;
                            $params[] = $r['delivery_via'] ?? null;
                            $params[] = $r['delivery_nama'] ?? null;
                            $params[] = $r['delivery_awb'] ?? null;
                            $params[] = parseDateVal($r['delivery_pickup'] ?? null);
                            $params[] = $r['delivery_lead_time'] ?? null;
                            $params[] = parseDateVal($r['delivery_target_dlv'] ?? null);
                            $params[] = $r['approval_status'] ?? null;
                            $params[] = $r['approval_approver'] ?? null;
                            $params[] = parseDateVal($r['approval_date'] ?? null);
                            $params[] = $pGroup;
                            $params[] = $logCreatedDate;
                            $params[] = $rowCreatedBy;
                            $params[] = $logUpdatedDate;
                            $params[] = $rowUpdatedBy;
                            $params[] = $logCreatedAt;
                            $params[] = $logUpdatedAt;
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
                                periode_group, created_date, created_by, updated_date, updated_by, created_at, updated_at, raw_data
                            ) VALUES " . implode(', ', $placeholders);
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                        }
                    }

                    $pdo->commit();
                    clearWmsBackendCache();
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "Berhasil menyimpan $insertedCount data PR Forwarder."
                ]);
                exit;
            } elseif ($action === 'finalize') {
                clearWmsBackendCache();
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
