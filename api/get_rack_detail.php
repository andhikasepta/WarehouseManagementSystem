<?php
// api/get_rack_detail.php
// Returns all individual labels belonging to a specific rack name,
// with their CAP, barcode, active status, and category.
// Used by the rack detail modal on the Storage Tekno dashboard.
//
// Query params:
//   name  - The rack name (e.g. "RACK-A")
//   month - Month name (e.g. "June")
//   year  - Year (e.g. "2026")
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $month = isset($_GET['month']) ? trim($_GET['month']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';

    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing rack name parameter.']);
        exit;
    }

    // Validate month against allow-list
    $validMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    if ($month !== '' && !in_array($month, $validMonths, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid month parameter.']);
        exit;
    }

    if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid year parameter.']);
        exit;
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Check if rack_master exists and has data
    $rackMasterCount = 0;
    try {
        $rackMasterCount = (int) $pdo->query("SELECT COUNT(*) FROM rack_master")->fetchColumn();
    } catch (PDOException $e) {
        // Table might not exist
        $rackMasterCount = 0;
    }

    $results = [];

    if ($rackMasterCount > 0) {
        // Query rack_master for all labels under this rack name,
        // left-joined with rack_utilisasi for the specified period
        if ($month !== '' && $year !== '') {
            $stmt = $pdo->prepare(
                "SELECT rm.barcode, rm.label, rm.name, rm.active, rm.category,
                        COALESCE(ru.capacity, 0.00) AS capacity,
                        COALESCE(ru.qty, 0) AS qty,
                        ? AS period_month, ? AS period_year
                 FROM rack_master rm
                 LEFT JOIN rack_utilisasi ru ON rm.label = ru.label AND ru.month = ? AND ru.year = ?
                 WHERE rm.name = ?
                 ORDER BY rm.label"
            );
            $stmt->execute([$month, $year, $month, $year, $name]);
        } else {
            // No period specified — just return rack_master labels with latest capacity
            if ($driver === 'pgsql') {
                $stmt = $pdo->prepare(
                    "SELECT rm.barcode, rm.label, rm.name, rm.active, rm.category,
                            COALESCE(ru.capacity, 0.00) AS capacity,
                            COALESCE(ru.qty, 0) AS qty,
                            COALESCE(ru.month, '') AS period_month,
                            COALESCE(ru.year, '') AS period_year
                     FROM rack_master rm
                     LEFT JOIN (
                         SELECT DISTINCT ON (label) label, month, year, qty, capacity
                         FROM rack_utilisasi ORDER BY label, id DESC
                     ) ru ON rm.label = ru.label
                     WHERE rm.name = ?
                     ORDER BY rm.label"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT rm.barcode, rm.label, rm.name, rm.active, rm.category,
                            COALESCE(ru.capacity, 0.00) AS capacity,
                            COALESCE(ru.qty, 0) AS qty,
                            COALESCE(ru.month, '') AS period_month,
                            COALESCE(ru.year, '') AS period_year
                     FROM rack_master rm
                     LEFT JOIN (
                         SELECT label, month, year, qty, capacity
                         FROM rack_utilisasi ORDER BY id DESC
                     ) ru ON rm.label = ru.label
                     WHERE rm.name = ?
                     GROUP BY rm.label, rm.barcode, rm.name, rm.active, rm.category
                     ORDER BY rm.label"
                );
            }
            $stmt->execute([$name]);
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$r) {
            $cap = (float)($r['capacity'] ?? 0);
            if ($cap > 0 && $cap <= 1.0) {
                $cap = $cap * 100.0;
            }
            $r['capacity'] = number_format($cap, 2, '.', '');
        }
        unset($r);
    }

    echo json_encode([
        'status' => 'success',
        'rack_name' => $name,
        'count' => count($results),
        'data' => $results
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_rack_detail: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
