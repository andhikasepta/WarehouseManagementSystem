<?php
// api/delete_data.php
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
$canDelete = ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin' || canDelete('master_data_storage') || canDelete('warehouse') || canDelete('master_data'));
if (!$canDelete) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data Storage. Silakan request hak akses Delete ke Superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $periode = $data['periode'] ?? $data['periode_group'] ?? null;
    $month = trim((string)($data['month'] ?? ''));
    $year = trim((string)($data['year'] ?? ''));
    $batch = trim((string)($data['batch'] ?? ''));

    if (!$periode && !empty($month) && !empty($year)) {
        if (!empty($batch)) {
            $periode = $month . ' ' . $year . '-Batch' . intval($batch);
        } else {
            $periode = $month . ' ' . $year . '-Batch%';
        }
    }

    if ($periode) {
        try {
            if (strpos($periode, '%') !== false) {
                $stmt = $pdo->prepare("DELETE FROM assets WHERE periode_group LIKE ?");
                $stmt->execute([$periode]);
            } else {
                // Delete exact match or legacy match
                $stmt = $pdo->prepare("DELETE FROM assets WHERE periode_group = ? OR (periode_group = ? AND ? NOT LIKE '%-Batch%')");
                $legacyPeriode = preg_replace('/-Batch\d+$/i', '', $periode);
                $stmt->execute([$periode, $legacyPeriode, $periode]);
            }
            
            $deletedRows = $stmt->rowCount();
            
            // Rebuild IN/OUT since a period was completely removed
            require_once 'rebuild_in_out.php';
            rebuildInOutStatus($pdo);
            
            echo json_encode([
                'status' => 'success', 
                'message' => "Data for $periode deleted successfully.",
                'deleted_rows' => $deletedRows
            ]);
        } catch(PDOException $e) {
            error_log('delete_data.php error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No period specified for deletion.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
