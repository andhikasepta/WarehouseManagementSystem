<?php
// api/get_rack_utilisasi.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Ensure rack_utilisasi table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rack_utilisasi` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `label` VARCHAR(255) NOT NULL,
        `month` VARCHAR(20) NOT NULL,
        `year` VARCHAR(10) NOT NULL,
        `qty` INT NOT NULL DEFAULT 0,
        `capacity` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_label_period` (`label`, `month`, `year`)
    )");

    $month = isset($_GET['month']) ? trim($_GET['month']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';

    // Validate month against allow-list if provided
    $validMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    if ($month !== '' && $year !== '') {
        if (!in_array($month, $validMonths, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid month value']);
            exit;
        }
        if (!preg_match('/^\d{4}$/', $year)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid year value']);
            exit;
        }

        // Return ALL labels from rack_master, left-joined with rack_utilisasi for this period.
        // Labels without data get qty=0, capacity=0.
        $stmt = $pdo->prepare(
            "SELECT rm.label, rm.rack AS rack_group, rm.category,
                    ? AS `month`, ? AS `year`,
                    COALESCE(ru.qty, 0) AS qty,
                    COALESCE(ru.capacity, 0.00) AS capacity,
                    ru.id AS id
             FROM rack_master rm
             LEFT JOIN rack_utilisasi ru ON rm.label = ru.label AND ru.month = ? AND ru.year = ?
             ORDER BY rm.category, rm.rack, rm.label"
        );
        $stmt->execute([$month, $year, $month, $year]);
    } else {
        // Return only saved data (for dashboard or unfiltered views)
        $stmt = $pdo->prepare(
            "SELECT ru.id, ru.label, ru.month, ru.year, ru.qty, ru.capacity,
                    COALESCE(rm.rack, '') AS rack_group, COALESCE(rm.category, '') AS category
             FROM rack_utilisasi ru
             LEFT JOIN rack_master rm ON ru.label = rm.label
             ORDER BY ru.year DESC, FIELD(ru.month, 'January','February','March','April','May','June','July','August','September','October','November','December') DESC, rm.category, rm.rack, ru.label"
        );
        $stmt->execute();
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $results]);
} catch (PDOException $e) {
    error_log("Database error fetching rack utilisasi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred while fetching data.']);
}
