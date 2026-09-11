<?php
// api/get_rack_data.php
@ini_set('memory_limit', '512M');
@set_time_limit(120);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // 12 standard month columns to always display for the selected year
    $standardMonths = [
        'January'   => 'CAP JAN',
        'February'  => 'CAP FEB',
        'March'     => 'CAP MAR',
        'April'     => 'CAP APR',
        'May'       => 'CAP MEI',
        'June'      => 'CAP JUN',
        'July'      => 'CAP JUL',
        'August'    => 'CAP AGU',
        'September' => 'CAP SEP',
        'October'   => 'CAP OKT',
        'November'  => 'CAP NOV',
        'December'  => 'CAP DES'
    ];

    // Ensure rack_utilisasi table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $pdo->exec("CREATE TABLE IF NOT EXISTS rack_utilisasi (
        $idCol,
        label VARCHAR(255) NOT NULL,
        month VARCHAR(20) NOT NULL,
        year VARCHAR(10) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        capacity DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol,
        CONSTRAINT unique_label_period UNIQUE (label, month, year)
    )");

    // Get available years from rack_utilisasi
    $yearStmt = $pdo->query("SELECT DISTINCT year FROM rack_utilisasi WHERE year IS NOT NULL AND year != '' ORDER BY year DESC");
    $dbYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

    $curYear = (string)date('Y');
    $availableYears = is_array($dbYears) ? $dbYears : [];
    if (!in_array($curYear, $availableYears)) {
        $availableYears[] = $curYear;
    }
    // Also include next year for convenience
    $nextYear = (string)((int)$curYear + 1);
    if (!in_array($nextYear, $availableYears)) {
        $availableYears[] = $nextYear;
    }
    rsort($availableYears, SORT_STRING);

    // Determine selected year
    $selectedYear = isset($_GET['year']) ? trim((string)$_GET['year']) : '';
    if (!preg_match('/^\d{4}$/', $selectedYear)) {
        $selectedYear = !empty($dbYears) ? (string)$dbYears[0] : $curYear;
    }

    // Fetch all racks from rack_master
    $stmt = $pdo->query("SELECT id, barcode, name, label, active, category, COALESCE(name, rack, label) AS rack FROM rack_master ORDER BY id ASC");
    $racks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch utilization data for the selected year
    $uStmt = $pdo->prepare("SELECT label, month, capacity, qty FROM rack_utilisasi WHERE year = ?");
    $uStmt->execute([$selectedYear]);
    $utilRows = $uStmt->fetchAll(PDO::FETCH_ASSOC);

    $utilMap = [];
    foreach ($utilRows as $u) {
        $lbl = trim($u['label']);
        $m = trim($u['month']);
        if ($lbl !== '' && $m !== '') {
            $cap = (float)$u['capacity'];
            if ($cap > 0 && $cap <= 1.0) {
                $cap = $cap * 100.0;
            }
            $utilMap[$lbl][$m] = round($cap, 2);
        }
    }

    // Attach all 12 monthly columns (null if not yet imported)
    $results = [];
    foreach ($racks as $r) {
        $lbl = trim($r['label'] ?? '');
        foreach ($standardMonths as $mName => $colKey) {
            if ($lbl !== '' && isset($utilMap[$lbl][$mName])) {
                $r[$colKey] = $utilMap[$lbl][$mName];
            } else {
                $r[$colKey] = null;
            }
        }
        $results[] = $r;
    }

    echo json_encode([
        'status' => 'success',
        'selected_year' => $selectedYear,
        'available_years' => array_values($availableYears),
        'data' => $results
    ]);
} catch(PDOException $e) {
    error_log('get_rack_data.php error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengambil data rak: ' . $e->getMessage()]);
}
