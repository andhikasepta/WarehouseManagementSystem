<?php
// api/delete_outbound_forwarder.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$canDelete = canDelete('master_data_outbound') || canDelete('outbound') || canDelete('master_data');
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $currentUser = getCurrentUser();
    $userRole = $currentUser['role'] ?? 'admin';

    try {
        if (!empty($data['clear_all'])) {
            if ($userRole !== 'superadmin' && $userRole !== 'head_warehouse_admin') {
                echo json_encode(['status' => 'error', 'message' => 'Request hapus data ke Superadmin']);
                exit;
            }
            $pdo->exec("TRUNCATE TABLE outbound_forwarder");
            echo json_encode(['status' => 'success', 'message' => 'Semua data PR Forwarder berhasil dihapus.']);
            exit;
        }

        $periode = trim($data['periode'] ?? '');
        $year = trim($data['year'] ?? '');

        if ($periode !== '') {
            $stmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group = ?");
            $stmt->execute([$periode]);
            $count = $stmt->rowCount();
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $count data PR Forwarder untuk periode $periode."]);
            exit;
        } elseif ($year !== '') {
            $stmt = $pdo->prepare("DELETE FROM outbound_forwarder WHERE periode_group LIKE ?");
            $stmt->execute(['%' . $year . '%']);
            $count = $stmt->rowCount();
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $count data PR Forwarder untuk tahun $year."]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Harap pilih periode atau tahun yang ingin dihapus.']);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
