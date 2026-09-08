<?php
// api/save_rack_data.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Safe CSRF verification for authenticated requests
if (!empty($_SESSION['csrf_token'])) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!empty($token) && !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }
}

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$isSuperAdmin = ($userRole === 'superadmin');
$canManageRack = ($isSuperAdmin || canAdd('master_data_storage') || canAdd('warehouse') || canAdd('master_data') || in_array($userRole, ['admin', 'superadmin', 'warehouse_admin', 'head_asset_warehouse_admin']));

if (!$canManageRack) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengunggah atau mengubah master layout rak.']);
    exit;
}

/**
 * Smart row parser that handles:
 * 1. Standard named headers: BARCODE, NAME, LABEL, ACTIVE, CATEGORY
 * 2. Header variants: Shelf, Sub Location, Status, Checked, Project, Lokasi, etc.
 * 3. Fallback to positional columns (0 => Barcode, 1 => Name, 2 => Label, 3 => Active, 4 => Category)
 * 4. Header rows inside data (automatically skipped)
 * 5. Optional monthly metrics (QTY JAN, CAP JAN, etc.)
 */
function parseRackRowData($row) {
    if (!is_array($row)) return null;

    $barcode = null;
    $name = null;
    $label = null;
    $active = null;
    $category = null;
    $monthlyData = [];

    $monthMap = [
        'JAN' => 'January', 'FEB' => 'February', 'MAR' => 'March',
        'APR' => 'April', 'MAY' => 'May', 'MEI' => 'May',
        'JUN' => 'June', 'JUL' => 'July', 'AUG' => 'August', 'AGU' => 'August',
        'SEP' => 'September', 'OCT' => 'October', 'OKT' => 'October',
        'NOV' => 'November', 'DEC' => 'December', 'DES' => 'December'
    ];

    // 1. Try named header matching
    foreach ($row as $k => $v) {
        $val = is_string($v) ? trim($v) : $v;
        if ($val === '' || $val === null) continue;

        $rawKey = trim((string)$k);
        $cleanKey = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $rawKey));

        // Check for monthly metrics like QTY JAN, CAP JAN, etc.
        $matchedMonthMetric = false;
        foreach ($monthMap as $mCode => $mFull) {
            if ($cleanKey === 'QTY' . $mCode) {
                $monthlyData[$mFull]['qty'] = (int)$val;
                $matchedMonthMetric = true;
                break;
            } elseif ($cleanKey === 'CAP' . $mCode || $cleanKey === 'CAPACITY' . $mCode) {
                $monthlyData[$mFull]['capacity'] = (float)$val;
                $matchedMonthMetric = true;
                break;
            }
        }
        if ($matchedMonthMetric) continue;

        if (in_array($cleanKey, ['BARCODE', 'BARCODECODE', 'KODE', 'CODE', 'ID', 'NOBARCODE', 'KODEBARANG'])) {
            $barcode = $val;
        } elseif (in_array($cleanKey, ['NAME', 'RACK', 'SHELF', 'RACKNAME', 'SHELFNAME', 'NAMARACK', 'NAMASHELF', 'RACKGROUP', 'NAMA', 'NAMARAK'])) {
            $name = $val;
        } elseif (in_array($cleanKey, ['LABEL', 'SUBLOCATION', 'LOKASI', 'SUBLOKASI', 'LOCATION', 'PATH', 'LABELSUBLOCATION'])) {
            $label = $val;
        } elseif (in_array($cleanKey, ['ACTIVE', 'STATUS', 'CHECKED', 'AKTIF', 'ISACTIVE', 'STATE'])) {
            $active = $val;
        } elseif (in_array($cleanKey, ['CATEGORY', 'KATEGORI', 'CAT', 'PROJECT', 'TIPE', 'TYPE'])) {
            $category = $val;
        }
    }

    // 2. If essential fields are missing (e.g. keys are __EMPTY, numeric, or non-matching)
    if (!$label && !$name && !$barcode) {
        $vals = [];
        foreach ($row as $v) {
            $vals[] = is_string($v) ? trim($v) : $v;
        }

        // Check if this row is a header row that leaked into data
        if (isset($vals[0]) && in_array(strtoupper((string)$vals[0]), ['BARCODE', 'KODE', 'BARCODE CODE'])) {
            return null; // Skip header row
        }

        if (isset($vals[0]) && $vals[0] !== '') $barcode = $vals[0];
        if (isset($vals[1]) && $vals[1] !== '') $name = $vals[1];
        if (isset($vals[2]) && $vals[2] !== '') $label = $vals[2];
        if (isset($vals[3]) && $vals[3] !== '') $active = $vals[3];
        if (isset($vals[4]) && $vals[4] !== '') $category = $vals[4];

        // Smart value-based heuristics: hierarchical path with '/' is always the label
        foreach ($vals as $v) {
            if (is_string($v) && strpos($v, '/') !== false && strpos($v, ' ') === false) {
                $label = $v;
                break;
            }
        }
    }

    if (!$barcode && !$name && !$label) {
        return null;
    }

    if (!$label) $label = $name ?: ($barcode ? (string)$barcode : '');
    if (!$name) $name = $label;
    if (!$active) $active = 'ACTIVE';

    return [
        'barcode' => $barcode ? (string)$barcode : null,
        'name' => (string)$name,
        'label' => (string)$label,
        'active' => (string)$active,
        'category' => $category ? (string)$category : null,
        'monthly' => $monthlyData
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $truncateSql = ($driver === 'pgsql') ? "TRUNCATE TABLE rack_master RESTART IDENTITY" : "TRUNCATE TABLE rack_master";
            $action = isset($data['action']) ? $data['action'] : null;

            if ($action) {
                // Direct Single Batch Upload Protocol (used by dedicated modal)
                if ($action === 'batch') {
                    $rows = isset($data['data']) ? $data['data'] : [];
                    if (!is_array($rows)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                        exit;
                    }

                    $pdo->beginTransaction();
                    $pdo->exec($truncateSql);

                    $stmt = $pdo->prepare("INSERT INTO rack_master (barcode, name, label, active, category, rack) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertedCount = 0;
                    $curYear = (string)date('Y');

                    foreach ($rows as $row) {
                        $parsed = parseRackRowData($row);
                        if (!$parsed) continue;

                        $stmt->execute([
                            $parsed['barcode'],
                            $parsed['name'],
                            $parsed['label'],
                            $parsed['active'],
                            $parsed['category'],
                            $parsed['name']
                        ]);
                        $insertedCount++;

                        if (!empty($parsed['monthly'])) {
                            foreach ($parsed['monthly'] as $mName => $mMetrics) {
                                $mQty = isset($mMetrics['qty']) ? (int)$mMetrics['qty'] : 0;
                                $mCap = isset($mMetrics['capacity']) ? (float)$mMetrics['capacity'] : 0.0;
                                if ($driver === 'pgsql') {
                                    $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (label, month, year, qty, capacity)
                                                            VALUES (?, ?, ?, ?, ?)
                                                            ON CONFLICT (label, month, year) DO UPDATE SET 
                                                            qty = EXCLUDED.qty, capacity = EXCLUDED.capacity, updated_at = CURRENT_TIMESTAMP");
                                } else {
                                    $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
                                                            VALUES (?, ?, ?, ?, ?)
                                                            ON DUPLICATE KEY UPDATE `qty` = VALUES(`qty`), `capacity` = VALUES(`capacity`), `updated_at` = CURRENT_TIMESTAMP");
                                }
                                $uStmt->execute([$parsed['label'], $mName, $curYear, $mQty, $mCap]);
                            }
                        }
                    }

                    $pdo->commit();

                    echo json_encode([
                        'status' => 'success',
                        'message' => "Master Data Utilisasi Rack berhasil disimpan ($insertedCount rak).",
                        'total' => $insertedCount
                    ]);
                    exit;
                } elseif ($action === 'init') {
                    $pdo->exec($truncateSql);
                    echo json_encode(['status' => 'success', 'message' => 'Rack Master truncated successfully']);
                } elseif ($action === 'append') {
                    $rows = isset($data['data']) ? $data['data'] : [];
                    if (!is_array($rows)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                        exit;
                    }

                    $insertedCount = 0;
                    if (!empty($rows)) {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("INSERT INTO rack_master (barcode, name, label, active, category, rack) VALUES (?, ?, ?, ?, ?, ?)");
                        $curYear = (string)date('Y');

                        foreach ($rows as $row) {
                            $parsed = parseRackRowData($row);
                            if (!$parsed) continue;

                            $stmt->execute([
                                $parsed['barcode'],
                                $parsed['name'],
                                $parsed['label'],
                                $parsed['active'],
                                $parsed['category'],
                                $parsed['name']
                            ]);
                            $insertedCount++;

                            // If row has monthly metrics (QTY JAN, CAP JAN, etc.), also upsert into rack_utilisasi
                            if (!empty($parsed['monthly'])) {
                                foreach ($parsed['monthly'] as $mName => $mMetrics) {
                                    $mQty = isset($mMetrics['qty']) ? (int)$mMetrics['qty'] : 0;
                                    $mCap = isset($mMetrics['capacity']) ? (float)$mMetrics['capacity'] : 0.0;
                                    if ($driver === 'pgsql') {
                                        $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (label, month, year, qty, capacity)
                                                                VALUES (?, ?, ?, ?, ?)
                                                                ON CONFLICT (label, month, year) DO UPDATE SET 
                                                                qty = EXCLUDED.qty, capacity = EXCLUDED.capacity, updated_at = CURRENT_TIMESTAMP");
                                    } else {
                                        $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
                                                                VALUES (?, ?, ?, ?, ?)
                                                                ON DUPLICATE KEY UPDATE `qty` = VALUES(`qty`), `capacity` = VALUES(`capacity`), `updated_at` = CURRENT_TIMESTAMP");
                                    }
                                    $uStmt->execute([$parsed['label'], $mName, $curYear, $mQty, $mCap]);
                                }
                            }
                        }
                        $pdo->commit();
                    }
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Batch processed successfully',
                        'inserted' => $insertedCount
                    ]);
                } elseif ($action === 'finalize') {
                    $totalRacks = (int)$pdo->query("SELECT COUNT(*) FROM rack_master")->fetchColumn();
                    echo json_encode([
                        'status' => 'success',
                        'message' => "Master Data Utilisasi Rack berhasil disimpan ($totalRacks rak).",
                        'total' => $totalRacks
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
                }
            } else {
                // Single Request Upload
                $pdo->beginTransaction();
                $pdo->exec($truncateSql);

                $stmt = $pdo->prepare("INSERT INTO rack_master (barcode, name, label, active, category, rack) VALUES (?, ?, ?, ?, ?, ?)");
                $insertedCount = 0;
                $curYear = (string)date('Y');

                foreach ($data as $row) {
                    $parsed = parseRackRowData($row);
                    if (!$parsed) continue;

                    $stmt->execute([
                        $parsed['barcode'],
                        $parsed['name'],
                        $parsed['label'],
                        $parsed['active'],
                        $parsed['category'],
                        $parsed['name']
                    ]);
                    $insertedCount++;

                    if (!empty($parsed['monthly'])) {
                        foreach ($parsed['monthly'] as $mName => $mMetrics) {
                            $mQty = isset($mMetrics['qty']) ? (int)$mMetrics['qty'] : 0;
                            $mCap = isset($mMetrics['capacity']) ? (float)$mMetrics['capacity'] : 0.0;
                            if ($driver === 'pgsql') {
                                $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (label, month, year, qty, capacity)
                                                        VALUES (?, ?, ?, ?, ?)
                                                        ON CONFLICT (label, month, year) DO UPDATE SET 
                                                        qty = EXCLUDED.qty, capacity = EXCLUDED.capacity, updated_at = CURRENT_TIMESTAMP");
                            } else {
                                $uStmt = $pdo->prepare("INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
                                                        VALUES (?, ?, ?, ?, ?)
                                                        ON DUPLICATE KEY UPDATE `qty` = VALUES(`qty`), `capacity` = VALUES(`capacity`), `updated_at` = CURRENT_TIMESTAMP");
                            }
                            $uStmt->execute([$parsed['label'], $mName, $curYear, $mQty, $mCap]);
                        }
                    }
                }
                
                $pdo->commit();
                echo json_encode([
                    'status' => 'success',
                    'message' => "Master Data Utilisasi Rack berhasil disimpan ($insertedCount baris).",
                    'total' => $insertedCount
                ]);
            }
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Database error during rack upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'A database error occurred while saving the rack data: ' . $e->getMessage()]);
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("General error during rack upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving the rack data: ' . $e->getMessage()]);
        }
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
