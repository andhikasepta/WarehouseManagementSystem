<?php
// api/save_rack_data.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
validateCsrf();

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$isSuperAdmin = ($userRole === 'superadmin');
$userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];
$canManageRack = ($isSuperAdmin || canAdd('master_data_storage') || canAdd('warehouse'));

if (!$canManageRack) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengunggah atau mengubah master layout rak.']);
    exit;
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
                    // Clear existing rack data
                    $pdo->exec("TRUNCATE TABLE rack_master");
                    echo json_encode(['status' => 'success', 'message' => 'Rack Master truncated successfully']);
                } elseif ($action === 'append') {
                    $rows = isset($data['data']) ? $data['data'] : [];
                    if (!is_array($rows)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid data parameter']);
                        exit;
                    }

                    if (!empty($rows)) {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("INSERT INTO rack_master (label, rack, category) VALUES (?, ?, ?)");
                        foreach ($rows as $row) {
                            $label = null;
                            $rack = null;
                            $category = null;

                            foreach ($row as $k => $v) {
                                if (strcasecmp($k, 'label') === 0) $label = $v;
                                else if (strcasecmp($k, 'rack') === 0) $rack = $v;
                                else if (strcasecmp($k, 'category') === 0) $category = $v;
                            }

                            if ($label && $rack) {
                                $stmt->execute([$label, $rack, $category]);
                            }
                        }
                        $pdo->commit();
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Batch appended successfully']);
                } elseif ($action === 'finalize') {
                    echo json_encode(['status' => 'success', 'message' => 'Rack Master data finalized successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
                }
            } else {
                // Legacy Single Request Upload
                $pdo->beginTransaction();
                
                // Clear existing rack data since we are uploading a new master layout
                $pdo->exec("TRUNCATE TABLE rack_master");

                $stmt = $pdo->prepare("INSERT INTO rack_master (label, rack, category) VALUES (?, ?, ?)");
                
                foreach ($data as $row) {
                    $label = null;
                    $rack = null;
                    $category = null;

                    foreach ($row as $k => $v) {
                        if (strcasecmp($k, 'label') === 0) $label = $v;
                        else if (strcasecmp($k, 'rack') === 0) $rack = $v;
                        else if (strcasecmp($k, 'category') === 0) $category = $v;
                    }

                    if ($label && $rack) {
                        $stmt->execute([$label, $rack, $category]);
                    }
                }
                
                $pdo->commit();
                
                echo json_encode(['status' => 'success', 'message' => 'Rack Master data saved successfully']);
            }
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // TODO(security): Log detailed error server-side and show generic message to the client
            error_log("Database error during rack upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'A database error occurred while saving the rack data.']);
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("General error during rack upload: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving the rack data.']);
        }
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
