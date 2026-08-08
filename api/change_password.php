<?php
// api/change_password.php - Secure User Password Change Endpoint
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!$data) {
    $data = $_POST;
}

$currentPassword = trim($data['current_password'] ?? '');
$newPassword = trim($data['new_password'] ?? '');
$confirmPassword = trim($data['confirm_password'] ?? '');

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom password wajib diisi.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password baru tidak cocok.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password baru minimal harus 6 karakter.']);
    exit;
}

$currentUser = getCurrentUser();
$userId = intval($currentUser['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi pengguna tidak valid. Silakan login kembali.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRecord) {
        echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan dalam sistem.']);
        exit;
    }

    $passwordValid = false;
    if (password_verify($currentPassword, $userRecord['password'])) {
        $passwordValid = true;
    } elseif ($currentPassword === $userRecord['password']) {
        // Auto-migrate legacy plaintext password if any
        $passwordValid = true;
    }

    if (!$passwordValid) {
        echo json_encode(['status' => 'error', 'message' => 'Password saat ini yang Anda masukkan salah.']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$newHash, $userId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Password Anda berhasil diperbarui.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
    ]);
}
