<?php
// api/delete_outbound_master.php
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

$canDelete = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || canDelete('master_data_outbound') || canDelete('outbound') || canDelete('master_data'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data Outbound. Silakan request hak akses Delete ke Superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    try {
        $action = $data['action'] ?? 'truncate_all';

        if ($action === 'delete_single') {
            $id = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null;
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM outbound_master WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Data Outbound berhasil dihapus']);
            exit;
        }

        if ($action === 'truncate_all' || $action === 'delete_all' || empty($action)) {
            $pdo->exec("TRUNCATE TABLE outbound_master");
            echo json_encode(['status' => 'success', 'message' => 'Semua data Outbound Master berhasil dihapus']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error in delete_outbound_master.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete outbound master data: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
