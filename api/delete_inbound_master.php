<?php
// api/delete_inbound_master.php
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
$userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

$canAccess = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || $userRole === 'inbound_admin' || in_array('master_data_inbound', $userModules) || in_array('master_data', $userModules));
if (!$canAccess) {
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    try {
        $action = $data['action'] ?? 'delete_period';

        if ($action === 'delete_single') {
            $id = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : null;
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM inbound_master WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Data successfully deleted']);
            exit;
        }

        $periode = $data['periode'] ?? $data['periode_group'] ?? null;
        if (!$periode && !empty($data['month']) && !empty($data['year'])) {
            $periode = trim($data['month']) . ' ' . trim($data['year']);
        }

        if (!empty($periode)) {
            $stmt = $pdo->prepare("DELETE FROM inbound_master WHERE periode_group = ?");
            $stmt->execute([$periode]);
            echo json_encode(['status' => 'success', 'message' => "Data Inbound periode $periode berhasil dihapus"]);
            exit;
        } elseif ($action === 'truncate_all') {
            if ($userRole !== 'superadmin' && $userRole !== 'head_warehouse_admin') {
                echo json_encode(['status' => 'error', 'message' => 'Hanya Super Admin atau Head Warehouse yang diizinkan mengosongkan seluruh tabel.']);
                exit;
            }
            $pdo->exec("TRUNCATE TABLE inbound_master");
            echo json_encode(['status' => 'success', 'message' => 'Semua data Inbound berhasil dihapus']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Periode tidak ditentukan untuk penghapusan data.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error in delete_inbound_master.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete inbound master data']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
