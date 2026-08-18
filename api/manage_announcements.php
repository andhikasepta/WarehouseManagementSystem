<?php
// api/manage_announcements.php - Announcement CRUD Handler for Super Admin
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
if (($currentUser['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Khusus Super Admin']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Validate CSRF on state-changing actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
}

try {
    // Ensure 'type' and 'version' columns exist
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'maintenance'");
            $pdo->exec("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS version VARCHAR(100) DEFAULT NULL");
        } else {
            try { $pdo->exec("ALTER TABLE announcements ADD COLUMN type VARCHAR(50) DEFAULT 'maintenance'"); } catch (Exception $ex) {}
            try { $pdo->exec("ALTER TABLE announcements ADD COLUMN version VARCHAR(100) DEFAULT NULL"); } catch (Exception $ex) {}
        }
    } catch (Exception $ex) {}

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, title, description, type, version, start_datetime, end_datetime, is_active, created_by, created_at FROM announcements ORDER BY id ASC");
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $announcements]);
        exit;
    }

    if ($action === 'create' || $action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $id = isset($input['id']) ? intval($input['id']) : 0;
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $type = trim($input['type'] ?? 'maintenance');
        if ($type !== 'update') {
            $type = 'maintenance';
        }
        $version = trim($input['version'] ?? '');
        $start_datetime = trim($input['start_datetime'] ?? '');
        $end_datetime = trim($input['end_datetime'] ?? '');
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

        // Format date string if date-only (YYYY-MM-DD) or datetime-local (YYYY-MM-DDTHH:mm)
        $start_datetime = str_replace('T', ' ', $start_datetime);
        $end_datetime = str_replace('T', ' ', $end_datetime);
        if (strlen($start_datetime) === 10) $start_datetime .= ' 00:00:00';
        if (strlen($end_datetime) === 10) $end_datetime .= ' 00:00:00';
        if (strlen($start_datetime) === 16) $start_datetime .= ':00';
        if (strlen($end_datetime) === 16) $end_datetime .= ':00';

        if (empty($title) || empty($description) || empty($start_datetime) || empty($end_datetime)) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi (Judul, Deskripsi, Tanggal/Waktu Mulai & Selesai).']);
            exit;
        }

        date_default_timezone_set('Asia/Jakarta');
        $todayMidnight = strtotime(date('Y-m-d 00:00:00'));
        if (($id === 0 || $action === 'create') && strtotime($start_datetime) < $todayMidnight) {
            echo json_encode(['success' => false, 'message' => 'Tanggal pengumuman tidak boleh tanggal yang sudah lewat (backdate).']);
            exit;
        }

        if (strtotime($end_datetime) <= strtotime($start_datetime)) {
            echo json_encode(['success' => false, 'message' => 'Waktu selesai harus lebih besar dari waktu mulai.']);
            exit;
        }

        if ($action === 'create' || $id === 0) {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, description, type, version, start_datetime, end_datetime, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $type, $version, $start_datetime, $end_datetime, $is_active, $currentUser['name']]);
            echo json_encode(['success' => true, 'message' => 'Pengumuman baru berhasil ditambahkan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, description = ?, type = ?, version = ?, start_datetime = ?, end_datetime = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $description, $type, $version, $start_datetime, $end_datetime, $is_active, $id]);
            echo json_encode(['success' => true, 'message' => 'Pengumuman berhasil diperbarui!']);
        }
        exit;
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE announcements SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Status Pengumuman berhasil diubah.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Pengumuman berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
} catch (PDOException $e) {
    error_log('manage_announcements.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database pada pengumuman.']);
}


