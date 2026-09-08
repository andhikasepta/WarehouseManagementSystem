<?php
// api/delete_kpi_master.php
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

$canDelete = ($userRole === 'superadmin' || canDelete('master_data_kpi') || canDelete('kpi_monitoring') || canDelete('master_data'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus KPI Master Data.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    try {
        $action = $data['action'] ?? 'delete_by_year';
        $year = isset($data['year']) ? trim((string)$data['year']) : '';

        if ($action === 'delete_by_year') {
            if (empty($year) || !preg_match('/^\d{4}$/', $year)) {
                echo json_encode(['status' => 'error', 'message' => 'Tahun Periode tidak valid.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM kpi_master WHERE periode_tahun = ?");
            $stmt->execute([(int)$year]);
            $deleted = $stmt->rowCount();

            echo json_encode([
                'status' => 'success',
                'message' => "Berhasil menghapus $deleted data KPI untuk tahun $year.",
                'deleted' => $deleted
            ]);
            exit;
        }

        if ($action === 'delete_all') {
            // Check if table exists before truncating
            try {
                $pdo->exec("TRUNCATE TABLE kpi_master");
            } catch (PDOException $e) {
                $pdo->exec("DELETE FROM kpi_master");
            }
            echo json_encode(['status' => 'success', 'message' => 'Semua data KPI Master berhasil dihapus.']);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
        exit;

    } catch (PDOException $e) {
        error_log("Database error in delete_kpi_master.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data KPI: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
