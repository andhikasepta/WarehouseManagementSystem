<?php
// api/save_kpi_master.php
ini_set('memory_limit', '512M');
set_time_limit(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
validateCsrf();

$canAdd = canAdd('master_data_kpi') || canAdd('kpi_monitoring') || canAdd('master_data');
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
if ($userRole === 'superadmin') $canAdd = true;

if (!$canAdd) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah/mengimpor KPI Master Data.']);
    exit;
}

// Static KPI target values
$KPI_TARGETS = [
    'gr' => 98.0,
    'registrasi' => 98.0,
    'slow_moving' => 85.0,
    'utilisasi_space' => 90.0,
    'stok_opname' => 85.0,
    'delivery_effectiveness' => 97.0,
    'mr_closing' => 90.0,
    'efisiensi_delivery' => 10.0,
];

function ensureKpiMasterTableExists($pdo)
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS kpi_master (
        $idCol,
        bulan VARCHAR(20) NOT NULL,
        gr_target DECIMAL(10,2) DEFAULT 0,
        gr_achievement DECIMAL(10,2) DEFAULT 0,
        registrasi_target DECIMAL(10,2) DEFAULT 0,
        registrasi_achievement DECIMAL(10,2) DEFAULT 0,
        slow_moving_target DECIMAL(10,2) DEFAULT 0,
        slow_moving_achievement DECIMAL(10,2) DEFAULT 0,
        utilisasi_space_target DECIMAL(10,2) DEFAULT 0,
        utilisasi_space_achievement DECIMAL(10,2) DEFAULT 0,
        stok_opname_target DECIMAL(10,2) DEFAULT 0,
        stok_opname_achievement DECIMAL(10,2) DEFAULT 0,
        delivery_effectiveness_target DECIMAL(10,2) DEFAULT 0,
        delivery_effectiveness_achievement DECIMAL(10,2) DEFAULT 0,
        mr_closing_target DECIMAL(10,2) DEFAULT 0,
        mr_closing_achievement DECIMAL(10,2) DEFAULT 0,
        efisiensi_delivery_target DECIMAL(10,2) DEFAULT 0,
        efisiensi_delivery_achievement DECIMAL(10,2) DEFAULT 0,
        periode_tahun INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);
}

/**
 * Case-insensitive column value extractor
 */
function getKpiVal($row, $keys)
{
    if (!is_array($keys)) $keys = [$keys];
    foreach ($row as $k => $v) {
        foreach ($keys as $targetKey) {
            $cleanK = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $k));
            $cleanTarget = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $targetKey));
            if ($cleanK === $cleanTarget) {
                return trim((string)$v);
            }
        }
    }
    return null;
}

function parseNumeric($val)
{
    if ($val === null || $val === '') return 0;
    // Remove % sign if present
    $val = str_replace('%', '', (string)$val);
    $val = str_replace(',', '.', $val);
    $val = trim($val);
    $num = is_numeric($val) ? (float)$val : 0;
    // Auto-scale decimal percentage from Excel (e.g. 0.98 -> 98.0, 1.00 -> 100.0)
    if ($num > 0 && $num <= 1.0) {
        $num = round($num * 100, 2);
    }
    return $num;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (is_array($data)) {
        try {
            ensureKpiMasterTableExists($pdo);
            $action = $data['action'] ?? 'batch';

            if ($action === 'batch') {
                $rows = $data['data'] ?? [];
                if (!is_array($rows)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                    exit;
                }

                $year = trim((string)($data['year'] ?? ''));
                if (empty($year) || !preg_match('/^\d{4}$/', $year)) {
                    echo json_encode(['status' => 'error', 'message' => 'Tahun Periode tidak valid.']);
                    exit;
                }

                // Validate year is not in the past (2026 minimum)
                $minYear = 2026;
                if ((int)$year < $minYear) {
                    echo json_encode(['status' => 'error', 'message' => 'Tahun Periode tidak boleh kurang dari ' . $minYear . '.']);
                    exit;
                }

                $validMonths = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                $pdo->beginTransaction();

                // Delete existing data for this year before inserting
                $delStmt = $pdo->prepare("DELETE FROM kpi_master WHERE periode_tahun = ?");
                $delStmt->execute([(int)$year]);

                $insertStmt = $pdo->prepare("INSERT INTO kpi_master (
                    bulan,
                    gr_target, gr_achievement,
                    registrasi_target, registrasi_achievement,
                    slow_moving_target, slow_moving_achievement,
                    utilisasi_space_target, utilisasi_space_achievement,
                    stok_opname_target, stok_opname_achievement,
                    delivery_effectiveness_target, delivery_effectiveness_achievement,
                    mr_closing_target, mr_closing_achievement,
                    efisiensi_delivery_target, efisiensi_delivery_achievement,
                    periode_tahun
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                global $KPI_TARGETS;
                $insertedCount = 0;

                foreach ($rows as $row) {
                    if (!is_array($row)) continue;

                    $bulan = getKpiVal($row, ['Bulan', 'bulan', 'Month', 'month']);
                    if (empty($bulan)) continue;

                    // Normalize month name
                    $bulan = ucfirst(strtolower(trim($bulan)));
                    if (!in_array($bulan, $validMonths)) continue;

                    // Extract achievement values from Excel
                    $grAchievement = parseNumeric(getKpiVal($row, ['GR Achievement', 'GRAchievement', 'gr_achievement']));
                    $regAchievement = parseNumeric(getKpiVal($row, ['Registrasi Achievement', 'RegistrasiAchievement', 'registrasi_achievement', 'Registration Achievement']));
                    $slowAchievement = parseNumeric(getKpiVal($row, ['Slow Moving Achievement', 'SlowMovingAchievement', 'slow_moving_achievement']));
                    $utilAchievement = parseNumeric(getKpiVal($row, ['Utilisasi Space Achievement', 'UtilisasiSpaceAchievement', 'utilisasi_space_achievement', 'Capacity Achievement']));
                    $stokAchievement = parseNumeric(getKpiVal($row, ['Stok Opname Hub & Outlet Achievement', 'StokOpnameHubOutletAchievement', 'stok_opname_achievement', 'Stok Opname Achievement']));
                    $delEffAchievement = parseNumeric(getKpiVal($row, ['Delivery Effectiveness Achievement', 'DeliveryEffectivenessAchievement', 'delivery_effectiveness_achievement']));
                    $mrAchievement = parseNumeric(getKpiVal($row, ['MR Closing Achievement', 'MRClosingAchievement', 'mr_closing_achievement', 'MR Closing (Akumulatif) Achievement']));
                    $efisiensiAchievement = parseNumeric(getKpiVal($row, ['Efisiensi Delivery Achievement', 'EfisiensiDeliveryAchievement', 'efisiensi_delivery_achievement']));

                    // Also check if targets are provided in the Excel (override defaults)
                    $grTarget = parseNumeric(getKpiVal($row, ['GR Target', 'GRTarget', 'gr_target']));
                    $regTarget = parseNumeric(getKpiVal($row, ['Registrasi Target', 'RegistrasiTarget', 'registrasi_target', 'Registration Target']));
                    $slowTarget = parseNumeric(getKpiVal($row, ['Slow Moving Target', 'SlowMovingTarget', 'slow_moving_target']));
                    $utilTarget = parseNumeric(getKpiVal($row, ['Utilisasi Space Target', 'UtilisasiSpaceTarget', 'utilisasi_space_target', 'Capacity Target']));
                    $stokTarget = parseNumeric(getKpiVal($row, ['Stok Opname Hub & Outlet Target', 'StokOpnameHubOutletTarget', 'stok_opname_target', 'Stok Opname Target']));
                    $delEffTarget = parseNumeric(getKpiVal($row, ['Delivery Effectiveness Target', 'DeliveryEffectivenessTarget', 'delivery_effectiveness_target']));
                    $mrTarget = parseNumeric(getKpiVal($row, ['MR Closing Target', 'MRClosingTarget', 'mr_closing_target', 'MR Closing (Akumulatif) Target']));
                    $efisiensiTarget = parseNumeric(getKpiVal($row, ['Efisiensi Delivery Target', 'EfisiensiDeliveryTarget', 'efisiensi_delivery_target']));

                    // Use static defaults if target not provided in Excel
                    if ($grTarget == 0) $grTarget = $KPI_TARGETS['gr'];
                    if ($regTarget == 0) $regTarget = $KPI_TARGETS['registrasi'];
                    if ($slowTarget == 0) $slowTarget = $KPI_TARGETS['slow_moving'];
                    if ($utilTarget == 0) $utilTarget = $KPI_TARGETS['utilisasi_space'];
                    if ($stokTarget == 0) $stokTarget = $KPI_TARGETS['stok_opname'];
                    if ($delEffTarget == 0) $delEffTarget = $KPI_TARGETS['delivery_effectiveness'];
                    if ($mrTarget == 0) $mrTarget = $KPI_TARGETS['mr_closing'];
                    if ($efisiensiTarget == 0) $efisiensiTarget = $KPI_TARGETS['efisiensi_delivery'];

                    $insertStmt->execute([
                        $bulan,
                        $grTarget, $grAchievement,
                        $regTarget, $regAchievement,
                        $slowTarget, $slowAchievement,
                        $utilTarget, $utilAchievement,
                        $stokTarget, $stokAchievement,
                        $delEffTarget, $delEffAchievement,
                        $mrTarget, $mrAchievement,
                        $efisiensiTarget, $efisiensiAchievement,
                        (int)$year
                    ]);
                    $insertedCount++;
                }

                $pdo->commit();
                echo json_encode([
                    'status' => 'success',
                    'message' => "Berhasil mengimpor $insertedCount data KPI untuk tahun $year.",
                    'inserted' => $insertedCount
                ]);
                exit;
            }

            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Database error in save_kpi_master.php: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
