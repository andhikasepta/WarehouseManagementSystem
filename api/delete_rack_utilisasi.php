<?php
// api/delete_rack_utilisasi.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Safe CSRF verification
if (!empty($_SESSION['csrf_token'])) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!empty($token) && !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }
}

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$canDelete = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || canDelete('master_data_storage') || canDelete('warehouse') || canDelete('master_data'));
if (!$canDelete) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data Utilisasi Rack.']);
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

$action = isset($data['action']) ? trim($data['action']) : '';

try {
    if ($action === 'delete_year') {
        $year = isset($data['year']) ? trim((string)$data['year']) : '';
        if (empty($year) || !preg_match('/^\d{4}$/', $year)) {
            echo json_encode(['status' => 'error', 'message' => 'Tahun tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM rack_utilisasi WHERE year = ?");
        $stmt->execute([$year]);
        $deletedCount = $stmt->rowCount();

        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil menghapus data utilisasi rack untuk tahun $year ($deletedCount baris).",
            'deleted' => $deletedCount
        ]);
        exit;
    }

    if ($action === 'delete_all') {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $pdo->beginTransaction();
        
        $pdo->exec("DELETE FROM rack_utilisasi");
        $pdo->exec("DELETE FROM rack_master");
        
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Semua data master layout dan utilisasi rack berhasil dihapus.'
        ]);
        exit;
    }

    if ($action === 'delete_single' || isset($data['id'])) {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM rack_utilisasi WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Record not found']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error deleting rack utilisasi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General error deleting rack utilisasi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
