<?php
// api/delete_outbound_forwarder.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/cache_helper.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';

$canDelete = ($userRole === 'superadmin' || canDelete('master_data_outbound') || canDelete('outbound') || canDelete('master_data'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data PR Forwarder. Silakan hubungi Superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: [];

    try {
        $action = $data['action'] ?? 'truncate_all';

        // Check if specific single ID deletion
        if ($action === 'delete_single' && !empty($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE id = ?");
            $stmt->execute([(int)$data['id']]);
            clearWmsBackendCache();
            echo json_encode(['status' => 'success', 'message' => 'Data PR Forwarder berhasil dihapus.']);
            exit;
        }

        // Specific period deletion if explicitly requested
        $periode = trim($data['periode'] ?? '');
        $year = trim($data['year'] ?? '');

        if ($action === 'delete_period' && $periode !== '') {
            $stmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group = ?");
            $stmt->execute([$periode]);
            $count = $stmt->rowCount();
            clearWmsBackendCache();
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $count data PR Forwarder untuk periode $periode."]);
            exit;
        } elseif ($action === 'delete_period' && $year !== '') {
            $stmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group LIKE ?");
            $stmt->execute(['%' . $year . '%']);
            $count = $stmt->rowCount();
            clearWmsBackendCache();
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $count data PR Forwarder untuk tahun $year."]);
            exit;
        }

        // Default: Hapus SEMUA data PR Forwarder
        $pdo->exec("TRUNCATE TABLE outbound_forwarder");
        clearWmsBackendCache();
        echo json_encode(['status' => 'success', 'message' => 'Semua data Master PR Forwarder berhasil dihapus.']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
