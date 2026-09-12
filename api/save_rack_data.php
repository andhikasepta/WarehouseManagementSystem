<?php
// api/save_rack_data.php
@ini_set('memory_limit', '512M');
@set_time_limit(300);
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
function parseRackRowData($row, $defaultYear = null) {
    if (!is_array($row)) return null;

    $curYear = $defaultYear ?: (string)date('Y');
    $barcode = null;
    $name = null;
    $label = null;
    $active = null;
    $category = null;
    $monthlyData = [];

    $monthMap = [
        'JAN' => 'January', 'JANUARI' => 'January', 'JANUARY' => 'January',
        'FEB' => 'February', 'FEBRUARI' => 'February', 'FEBRUARY' => 'February',
        'MAR' => 'March', 'MARET' => 'March', 'MARCH' => 'March',
        'APR' => 'April', 'APRIL' => 'April',
        'MAY' => 'May', 'MEI' => 'May',
        'JUN' => 'June', 'JUNI' => 'June', 'JUNE' => 'June',
        'JUL' => 'July', 'JULI' => 'July', 'JULY' => 'July',
        'AUG' => 'August', 'AGU' => 'August', 'AGUSTUS' => 'August', 'AUGUST' => 'August',
        'SEP' => 'September', 'SEPT' => 'September', 'SEPTEMBER' => 'September',
        'OCT' => 'October', 'OKT' => 'October', 'OKTOBER' => 'October', 'OCTOBER' => 'October',
        'NOV' => 'November', 'NOP' => 'November', 'NOVEMBER' => 'November',
        'DEC' => 'December', 'DES' => 'December', 'DESEMBER' => 'December', 'DECEMBER' => 'December'
    ];

    // 1. Try named header matching
    foreach ($row as $k => $v) {
        $val = is_string($v) ? trim($v) : $v;
        if ($val === '' || $val === null) continue;

        $rawKey = trim((string)$k);
        $matchedMonthMetric = false;

        // Check for monthly metrics like QTY JAN, CAP JAN, CAP MEI, CAP MEI 2026, etc.
        if (preg_match('/^(QTY|CAP|CAPACITY)[_\s\-]*([A-Za-z]+)(?:[_\s\-]*(\d{2,4}))?$/i', $rawKey, $mMatches)) {
            $prefix = strtoupper($mMatches[1]);
            $monthCandidate = strtoupper($mMatches[2]);
            $yearCandidate = !empty($mMatches[3]) ? $mMatches[3] : $curYear;
            if (strlen($yearCandidate) === 2) {
                $yearCandidate = '20' . $yearCandidate;
            }

            if (isset($monthMap[$monthCandidate])) {
                $mFull = $monthMap[$monthCandidate];
                $periodKey = $mFull . '_' . $yearCandidate;
                if (!isset($monthlyData[$periodKey])) {
                    $monthlyData[$periodKey] = [
                        'month' => $mFull,
                        'year' => (string)$yearCandidate
                    ];
                }

                if ($prefix === 'QTY') {
                    $monthlyData[$periodKey]['qty'] = (int)$val;
                } else {
                    $strVal = is_string($val) ? trim($val) : (string)$val;
                    if ($strVal === '' || $strVal === '-' || strcasecmp($strVal, 'n/a') === 0 || strcasecmp($strVal, 'null') === 0) {
                        // Excel behavior: blank or '-' is unmeasured, NOT 0%
                        continue;
                    }
                    $cleanVal = str_replace(['%', ' '], '', $strVal);
                    $cleanVal = str_replace(',', '.', $cleanVal);
                    if (!is_numeric($cleanVal)) {
                        continue;
                    }
                    $numVal = (float)$cleanVal;
                    // Excel percentage cells store 100% as 1.0, 50% as 0.5, etc.
                    // If between 0 and 1.0 (inclusive), convert to percentage scale (1.0 -> 100%, 0.85 -> 85%)
                    if ($numVal > 0 && $numVal <= 1.0) {
                        $numVal = $numVal * 100;
                    }
                    $numVal = max(0.0, min(100.0, round($numVal, 2)));
                    $monthlyData[$periodKey]['capacity'] = $numVal;
                }
                $matchedMonthMetric = true;
            }
        }

        // Fallback for tight keys like CAPMEI without separator
        if (!$matchedMonthMetric) {
            $cleanKey = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $rawKey));
            foreach ($monthMap as $mCode => $mFull) {
                if ($cleanKey === 'QTY' . $mCode) {
                    $periodKey = $mFull . '_' . $curYear;
                    if (!isset($monthlyData[$periodKey])) {
                        $monthlyData[$periodKey] = ['month' => $mFull, 'year' => $curYear];
                    }
                    $monthlyData[$periodKey]['qty'] = (int)$val;
                    $matchedMonthMetric = true;
                    break;
                } elseif ($cleanKey === 'CAP' . $mCode || $cleanKey === 'CAPACITY' . $mCode) {
                    $strVal = is_string($val) ? trim($val) : (string)$val;
                    if ($strVal === '' || $strVal === '-' || strcasecmp($strVal, 'n/a') === 0 || strcasecmp($strVal, 'null') === 0) {
                        $matchedMonthMetric = true;
                        break;
                    }
                    $cleanVal = str_replace(['%', ' '], '', $strVal);
                    $cleanVal = str_replace(',', '.', $cleanVal);
                    if (!is_numeric($cleanVal)) {
                        $matchedMonthMetric = true;
                        break;
                    }
                    if (!isset($monthlyData[$periodKey])) {
                        $monthlyData[$periodKey] = ['month' => $mFull, 'year' => $curYear];
                    }
                    $numVal = (float)$cleanVal;
                    if ($numVal > 0 && $numVal <= 1.0) {
                        $numVal = $numVal * 100;
                    }
                    $numVal = max(0.0, min(100.0, round($numVal, 2)));
                    $monthlyData[$periodKey]['capacity'] = $numVal;
                    $matchedMonthMetric = true;
                    break;
                }
            }
        }

        if ($matchedMonthMetric) continue;

        $cleanKey = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $rawKey));
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

/**
 * Helper to safely upsert monthly utilisasi records without wiping out existing qty
 */
function upsertMonthlyUtilisasi($pdo, $driver, $label, $monthlyData) {
    if (empty($monthlyData) || empty($label)) return 0;
    static $cachedStmt = null;
    if (!$cachedStmt) {
        if ($driver === 'pgsql') {
            $cachedStmt = $pdo->prepare("INSERT INTO rack_utilisasi (label, month, year, qty, capacity)
                                    VALUES (?, ?, ?, ?, ?)
                                    ON CONFLICT (label, month, year) DO UPDATE SET 
                                    capacity = EXCLUDED.capacity,
                                    qty = CASE WHEN EXCLUDED.qty > 0 THEN EXCLUDED.qty ELSE rack_utilisasi.qty END,
                                    updated_at = CURRENT_TIMESTAMP");
        } else {
            $cachedStmt = $pdo->prepare("INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
                                    VALUES (?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE 
                                    `capacity` = VALUES(`capacity`),
                                    `qty` = CASE WHEN VALUES(`qty`) > 0 THEN VALUES(`qty`) ELSE `qty` END,
                                    `updated_at` = CURRENT_TIMESTAMP");
        }
    }
    $count = 0;
    foreach ($monthlyData as $mMetrics) {
        $mName = $mMetrics['month'] ?? '';
        $mYear = $mMetrics['year'] ?? (string)date('Y');
        if (empty($mName) || empty($mYear)) continue;

        $hasQty = array_key_exists('qty', $mMetrics);
        $hasCap = array_key_exists('capacity', $mMetrics);
        $mQty = $hasQty ? (int)$mMetrics['qty'] : 0;
        $mCap = $hasCap ? (float)$mMetrics['capacity'] : 0.0;

        $cachedStmt->execute([$label, $mName, $mYear, $mQty, $mCap]);
        $count++;
    }
    return $count;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $truncateSql = ($driver === 'pgsql') ? "TRUNCATE TABLE rack_master RESTART IDENTITY" : "TRUNCATE TABLE rack_master";
            $action = isset($data['action']) ? $data['action'] : null;
            $targetYear = (!empty($data['year']) && preg_match('/^\d{4}$/', trim((string)$data['year']))) ? trim((string)$data['year']) : (string)date('Y');

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
                    $monthlyUpdatedCount = 0;

                    foreach ($rows as $row) {
                        $parsed = parseRackRowData($row, $targetYear);
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
                            $monthlyUpdatedCount += upsertMonthlyUtilisasi($pdo, $driver, $parsed['label'], $parsed['monthly']);
                        }
                    }

                    $pdo->commit();

                    $respMsg = "Master Data Utilisasi Rack berhasil disimpan ($insertedCount rak)";
                    if ($monthlyUpdatedCount > 0) {
                        $respMsg .= ", termasuk data utilisasi bulanan yang otomatis diperbarui";
                    }
                    $respMsg .= ".";

                    echo json_encode([
                        'status' => 'success',
                        'message' => $respMsg,
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
                        
                        $parsedList = [];
                        foreach ($rows as $row) {
                            $parsed = parseRackRowData($row, $targetYear);
                            if ($parsed) {
                                $parsedList[] = $parsed;
                            }
                        }

                        $chunkSize = 250;
                        $chunks = array_chunk($parsedList, $chunkSize);
                        foreach ($chunks as $chunk) {
                            $rowPlaceholders = [];
                            $params = [];
                            foreach ($chunk as $p) {
                                $rowPlaceholders[] = "(?, ?, ?, ?, ?, ?)";
                                $params[] = $p['barcode'];
                                $params[] = $p['name'];
                                $params[] = $p['label'];
                                $params[] = $p['active'];
                                $params[] = $p['category'];
                                $params[] = $p['name'];
                                $insertedCount++;
                            }
                            if (!empty($rowPlaceholders)) {
                                $sql = "INSERT INTO rack_master (barcode, name, label, active, category, rack) VALUES " . implode(', ', $rowPlaceholders);
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute($params);
                            }
                        }

                        // Upsert monthly utilisasi using cached prepared statement
                        foreach ($parsedList as $p) {
                            if (!empty($p['monthly'])) {
                                upsertMonthlyUtilisasi($pdo, $driver, $p['label'], $p['monthly']);
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

                foreach ($data as $row) {
                    $parsed = parseRackRowData($row, $targetYear);
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
                        upsertMonthlyUtilisasi($pdo, $driver, $parsed['label'], $parsed['monthly']);
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
