<?php
// api/save_rack_utilisasi.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
if (($currentUser['role'] ?? '') === 'head_warehouse_admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Hak akses Head Warehouse Admin hanya untuk melihat data (Read-Only).']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$validMonths = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Check if this is a bulk save (array of rows)
if (isset($data['rows']) && is_array($data['rows'])) {
    $month = isset($data['month']) ? trim($data['month']) : '';
    $year = isset($data['year']) ? trim($data['year']) : '';

    // Validate month
    if (!in_array($month, $validMonths, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid month value']);
        exit;
    }

    // Validate year
    if (!preg_match('/^\d{4}$/', $year) || (int)$year < 2000 || (int)$year > 2099) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid year value']);
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

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `qty` = VALUES(`qty`), `capacity` = VALUES(`capacity`), `updated_at` = CURRENT_TIMESTAMP"
        );

        $savedCount = 0;
        foreach ($data['rows'] as $row) {
            $label = isset($row['label']) ? trim($row['label']) : '';
            $qty = isset($row['qty']) ? $row['qty'] : 0;
            $capacity = isset($row['capacity']) ? $row['capacity'] : 0;

            if ($label === '') continue;

            // Validate qty
            if (!is_numeric($qty) || (int)$qty < 0) {
                $qty = 0;
            }
            $qty = (int)$qty;

            // Validate capacity
            if (!is_numeric($capacity) || (float)$capacity < 0 || (float)$capacity > 100) {
                $capacity = 0;
            }
            $capacity = round((float)$capacity, 2);

            $stmt->execute([$label, $month, $year, $qty, $capacity]);
            $savedCount++;
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => $savedCount . ' rows saved successfully']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error saving rack utilisasi bulk: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred while saving data.']);
    }
} else {
    // Single row save (legacy support)
    $label = isset($data['label']) ? trim($data['label']) : '';
    $month = isset($data['month']) ? trim($data['month']) : '';
    $year = isset($data['year']) ? trim($data['year']) : '';
    $qty = isset($data['qty']) ? $data['qty'] : null;
    $capacity = isset($data['capacity']) ? $data['capacity'] : null;

    if ($label === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Label (Sub Location) is required']);
        exit;
    }

    if (!in_array($month, $validMonths, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid month value']);
        exit;
    }

    if (!preg_match('/^\d{4}$/', $year) || (int)$year < 2000 || (int)$year > 2099) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid year value']);
        exit;
    }

    if (!is_numeric($qty) || (int)$qty < 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Qty must be a non-negative number']);
        exit;
    }
    $qty = (int)$qty;

    if (!is_numeric($capacity) || (float)$capacity < 0 || (float)$capacity > 100) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Capacity must be between 0 and 100']);
        exit;
    }
    $capacity = round((float)$capacity, 2);

    try {
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

        $stmt = $pdo->prepare(
            "INSERT INTO rack_utilisasi (`label`, `month`, `year`, `qty`, `capacity`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `qty` = VALUES(`qty`), `capacity` = VALUES(`capacity`), `updated_at` = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$label, $month, $year, $qty, $capacity]);

        echo json_encode(['status' => 'success', 'message' => 'Data saved successfully']);
    } catch (PDOException $e) {
        error_log("Database error saving rack utilisasi: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred while saving data.']);
    }
}
