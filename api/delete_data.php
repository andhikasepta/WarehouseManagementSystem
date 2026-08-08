<?php
// api/delete_data.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $periode = $data['periode'] ?? null;

    if ($periode) {
        try {
            $stmt = $pdo->prepare("DELETE FROM assets WHERE periode_group = ?");
            $stmt->execute([$periode]);
            
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
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No period specified for deletion.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
