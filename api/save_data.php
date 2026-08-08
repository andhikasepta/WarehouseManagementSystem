<?php
// api/save_data.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

function formatPeriode($rawDate) {
    if (!$rawDate) return 'Unknown Period';
    
    $str = strtoupper(trim((string)$rawDate));
    $currentYear = date('Y');
    
    $months = [
        'JAN' => 'January',
        'FEB' => 'February',
        'MAR' => 'March',
        'APR' => 'April',
        'MEI' => 'May',
        'JUN' => 'June',
        'JUL' => 'July',
        'AGU' => 'August',
        'SEP' => 'September',
        'OKT' => 'October',
        'NOV' => 'November',
        'DES' => 'December'
    ];
    
    foreach ($months as $abbr => $full) {
        if (strpos($str, $abbr) !== false) {
            return $full . ' ' . $currentYear;
        }
    }
    
    return 'Unknown Period';
}

function getValCI($row, $key) {
    foreach ($row as $k => $v) {
        if (strcasecmp($k, $key) === 0) {
            return $v;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            $action = isset($data['action']) ? $data['action'] : null;

            if ($action) {
                // Batch Upload Protocol
                if ($action === 'init') {
                    $periods = isset($data['periods']) ? $data['periods'] : [];
                    if (!is_array($periods)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid periods parameter']);
                        exit;
                    }
                    // Delete existing records for these periods
                    if (!empty($periods)) {
                        $placeholders = implode(',', array_fill(0, count($periods), '?'));
                        $delStmt = $pdo->prepare("DELETE FROM assets WHERE periode_group IN ($placeholders)");
                        $delStmt->execute($periods);
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Periods initialized successfully']);
                } elseif ($action === 'append') {
                    $rows = isset($data['data']) ? $data['data'] : [];
                    if (!is_array($rows)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                        exit;
                    }

                    if (!empty($rows)) {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("INSERT INTO assets 
                            (spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode, periode_group, raw_data) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        foreach ($rows as $row) {
                            $periodeRaw = getValCI($row, 'periode');
                            $periodeGroup = formatPeriode($periodeRaw);
                            $rawData = json_encode($row);

                            $nbv = getValCI($row, 'nbv');
                            $nbv = is_numeric($nbv) ? (float)$nbv : 0;

                            $stmt->execute([
                                getValCI($row, 'spec_code'),
                                getValCI($row, 'spec_name'),
                                getValCI($row, 'reg_no'),
                                getValCI($row, 'asset_planner_organization'),
                                $nbv,
                                getValCI($row, 'so_result'),
                                getValCI($row, 'so_location'),
                                getValCI($row, 'range'),
                                getValCI($row, 'sub_location'),
                                getValCI($row, 'category'),
                                $periodeRaw,
                                $periodeGroup,
                                $rawData
                            ]);
                        }
                        $pdo->commit();
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Batch appended successfully']);
                } elseif ($action === 'finalize') {
                    // Get all periods for response
                    $periodStmt = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
                    $periods = $periodStmt->fetchAll(PDO::FETCH_COLUMN);

                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'Data saved and finalized successfully',
                        'periods' => $periods
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
                }
            } else {
                // Legacy Single Request Upload
                $pdo->beginTransaction();
                
                // First pass: identify unique periods in the uploaded data
                $uploadedPeriods = [];
                foreach ($data as $row) {
                    $periodeRaw = null;
                    foreach ($row as $k => $v) {
                        if (strcasecmp($k, 'periode') === 0) {
                            $periodeRaw = $v;
                            break;
                        }
                    }
                    
                    $periodeGroup = formatPeriode($periodeRaw);
                    $uploadedPeriods[$periodeGroup] = true;
                }
                
                // Delete existing records for these periods to avoid duplicates
                if (!empty($uploadedPeriods)) {
                    $placeholders = implode(',', array_fill(0, count($uploadedPeriods), '?'));
                    $delStmt = $pdo->prepare("DELETE FROM assets WHERE periode_group IN ($placeholders)");
                    $delStmt->execute(array_keys($uploadedPeriods));
                }

                $stmt = $pdo->prepare("INSERT INTO assets 
                    (spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, `range`, sub_location, category, periode, periode_group, raw_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($data as $row) {
                    $periodeRaw = getValCI($row, 'periode');
                    $periodeGroup = formatPeriode($periodeRaw);
                    $rawData = json_encode($row);

                    $nbv = getValCI($row, 'nbv');
                    $nbv = is_numeric($nbv) ? (float)$nbv : 0;

                    $stmt->execute([
                        getValCI($row, 'spec_code'),
                        getValCI($row, 'spec_name'),
                        getValCI($row, 'reg_no'),
                        getValCI($row, 'asset_planner_organization'),
                        $nbv,
                        getValCI($row, 'so_result'),
                        getValCI($row, 'so_location'),
                        getValCI($row, 'range'),
                        getValCI($row, 'sub_location'),
                        getValCI($row, 'category'),
                        $periodeRaw,
                        $periodeGroup,
                        $rawData
                    ]);
                }
                $pdo->commit();
                
                // Get all periods for response
                $periodStmt = $pdo->query("SELECT DISTINCT periode_group FROM assets WHERE periode_group IS NOT NULL");
                $periods = $periodStmt->fetchAll(PDO::FETCH_COLUMN);

                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Data saved successfully',
                    'periods' => $periods
                ]);
            }
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // TODO(security): Log detailed error server-side and show generic message to the client
            error_log("Database error during upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'A database error occurred while saving the data.']);
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("General error during upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving the data.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
