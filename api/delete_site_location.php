<?php
// api/delete_site_location.php
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
$canDelete = ($userRole === 'superadmin' || canDelete('site_location'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data Site Location. Silakan request hak akses Delete ke Superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$action = $data['action'] ?? '';

try {
    if ($action === 'delete_all') {
        // Check table exists
        try {
            $pdo->query("SELECT 1 FROM site_location LIMIT 1");
        } catch (PDOException $e) {
            echo json_encode(['status' => 'success', 'message' => 'Tabel site_location belum ada atau sudah kosong.', 'deleted' => 0]);
            exit;
        }

        $countStmt = $pdo->query("SELECT COUNT(*) FROM site_location");
        $count = (int)$countStmt->fetchColumn();

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $pdo->exec("TRUNCATE TABLE site_location RESTART IDENTITY");
        } else {
            $pdo->exec("TRUNCATE TABLE site_location");
        }

        echo json_encode([
            'status' => 'success',
            'message' => $count . ' record(s) berhasil dihapus dari Site Location.',
            'deleted' => $count
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . $action]);
    }
} catch (PDOException $e) {
    error_log("delete_site_location error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
