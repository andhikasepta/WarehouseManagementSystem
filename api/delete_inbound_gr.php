<?php
// api/delete_inbound_gr.php
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

$canDelete = ($userRole === 'superadmin' || canDelete('master_data_inbound') || canDelete('inbound'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus Data GR. Silakan request hak akses Delete ke Superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    try {
        $action = $data['action'] ?? 'delete_period';

        if ($action === 'delete_single') {
            $id = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null;
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM inbound_gr WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Data GR berhasil dihapus']);
            exit;
        }

        $periode = $data['periode'] ?? $data['periode_group'] ?? null;
        if (!$periode && !empty($data['month']) && !empty($data['year'])) {
            $batch = !empty($data['batch']) ? intval($data['batch']) : '';
            $periode = trim($data['month']) . ' ' . trim($data['year']);
            if ($batch) {
                $periode .= '-Batch' . $batch;
            }
        }

        if (!empty($periode)) {
            $stmt = $pdo->prepare("DELETE FROM inbound_gr WHERE periode_group = ?");
            $stmt->execute([$periode]);
            echo json_encode(['status' => 'success', 'message' => "Data GR periode $periode berhasil dihapus"]);
            exit;
        } elseif ($action === 'truncate_all' || empty($periode)) {
            $pdo->exec("TRUNCATE TABLE inbound_gr");
            echo json_encode(['status' => 'success', 'message' => 'Semua Data GR berhasil dihapus']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Periode tidak ditemukan']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error in delete_inbound_gr.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
