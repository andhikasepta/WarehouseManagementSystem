<?php
// api/get_rack_utilisasi.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    // Ensure rack_utilisasi table exists
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

    $month = isset($_GET['month']) ? trim($_GET['month']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';
    $filter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all'; // all, used, available
    $action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : 'data';

    // Validate month against allow-list if provided
    $validMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    $monthOrderCase = "CASE month 
        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
        ELSE 0 END";

    // If no month/year is specified, find the latest period in rack_utilisasi
    if ($month === '' || $year === '') {
        $latestStmt = $pdo->query("SELECT month, year FROM rack_utilisasi ORDER BY year DESC, $monthOrderCase DESC LIMIT 1");
        $latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
        if ($latest) {
            $month = $latest['month'];
            $year = $latest['year'];
        }
    }

    if ($month !== '' && $year !== '' && in_array($month, $validMonths, true) && preg_match('/^\d{4}$/', $year)) {
        // Return ALL labels from rack_master, left-joined with rack_utilisasi for this period.
        $stmt = $pdo->prepare(
            "SELECT rm.label, rm.rack AS rack_group, rm.category,
                    ? AS month, ? AS year,
                    COALESCE(ru.qty, 0) AS qty,
                    COALESCE(ru.capacity, 0.00) AS capacity,
                    ru.id AS id
             FROM rack_master rm
             LEFT JOIN rack_utilisasi ru ON rm.label = ru.label AND ru.month = ? AND ru.year = ?
             ORDER BY rm.category, rm.rack, rm.label"
        );
        $stmt->execute([$month, $year, $month, $year]);
    } else {
        // Return from rack_master with latest rack_utilisasi
        if ($driver === 'pgsql') {
            $sql = "SELECT rm.label, rm.rack AS rack_group, rm.category,
                           COALESCE(ru.month, '') AS month, COALESCE(ru.year, '') AS year,
                           COALESCE(ru.qty, 0) AS qty,
                           COALESCE(ru.capacity, 0.00) AS capacity,
                           ru.id AS id
                    FROM rack_master rm
                    LEFT JOIN (
                        SELECT DISTINCT ON (label) label, month, year, qty, capacity, id
                        FROM rack_utilisasi ORDER BY label, id DESC
                    ) ru ON rm.label = ru.label
                    ORDER BY rm.category, rm.rack, rm.label";
        } else {
            $sql = "SELECT rm.label, rm.rack AS rack_group, rm.category,
                           COALESCE(ru.month, '') AS month, COALESCE(ru.year, '') AS year,
                           COALESCE(ru.qty, 0) AS qty,
                           COALESCE(ru.capacity, 0.00) AS capacity,
                           ru.id AS id
                    FROM rack_master rm
                    LEFT JOIN (
                        SELECT * FROM rack_utilisasi ORDER BY id DESC
                    ) ru ON rm.label = ru.label
                    GROUP BY rm.label, rm.rack, rm.category
                    ORDER BY rm.category, rm.rack, rm.label";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Summary Metrics
    $totalLocations = count($allResults);
    $usedLocations = 0;
    $availableLocations = 0;
    $totalCapSum = 0;
    $totalQtySum = 0;

    $filteredResults = [];
    foreach ($allResults as $row) {
        $cap = (float) $row['capacity'];
        $qty = (int) $row['qty'];
        $totalCapSum += $cap;
        $totalQtySum += $qty;

        $isUsed = ($cap > 0 || $qty > 0);
        if ($isUsed) {
            $usedLocations++;
        } else {
            $availableLocations++;
        }

        if ($filter === 'used') {
            if ($isUsed) $filteredResults[] = $row;
        } elseif ($filter === 'available') {
            if (!$isUsed || $cap < 100) $filteredResults[] = $row;
        } else {
            $filteredResults[] = $row;
        }
    }

    $avgUtilization = $totalLocations > 0 ? round($totalCapSum / $totalLocations, 1) : 0;

    echo json_encode([
        'status' => 'success',
        'period' => [
            'month' => $month,
            'year' => $year
        ],
        'summary' => [
            'total_capacity' => $totalLocations,
            'used' => $usedLocations,
            'available' => $availableLocations,
            'avg_utilization' => $avgUtilization,
            'total_qty' => $totalQtySum
        ],
        'count' => count($filteredResults),
        'data' => $filteredResults
    ]);
} catch (PDOException $e) {
    error_log("Database error fetching rack utilisasi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred while fetching data.']);
}
