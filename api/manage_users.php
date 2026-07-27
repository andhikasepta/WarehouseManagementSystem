<?php
// api/manage_users.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'superadmin') {
    echo json_encode(['status' => 'error', 'message' => 'Hanya Super Admin yang diizinkan mengelola user.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, username, name, role, allowed_modules, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $user['allowed_modules'] = json_decode($user['allowed_modules'], true) ?? [];
        }

        echo json_encode(['status' => 'success', 'data' => $users]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        $data = $_POST;
    }

    $action = $data['action'] ?? 'save';

    if ($action === 'reset_password') {
        $userId = intval($data['id'] ?? 0);
        $newPassword = trim($data['new_password'] ?? '');

        if ($userId <= 0 || empty($newPassword)) {
            echo json_encode(['status' => 'error', 'message' => 'Password baru wajib diisi.']);
            exit;
        }

        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            echo json_encode(['status' => 'success', 'message' => 'Password user berhasil di-reset.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $userId = intval($data['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID User tidak valid.']);
            exit;
        }

        if ($userId === intval($currentUser['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            echo json_encode(['status' => 'success', 'message' => 'User berhasil dihapus.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Save (Create or Update)
    $userId = intval($data['id'] ?? 0);
    $username = trim($data['username'] ?? '');
    $name = trim($data['name'] ?? '');
    $password = trim($data['password'] ?? '');
    $role = trim($data['role'] ?? 'admin');
    $modules = is_array($data['allowed_modules'] ?? null) ? $data['allowed_modules'] : [];

    if (empty($username) || empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan nama wajib diisi.']);
        exit;
    }

    if ($role === 'superadmin') {
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Role Super Admin tidak dapat dipilih untuk user baru.']);
            exit;
        } else {
            $checkStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $checkStmt->execute([$userId]);
            $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser || $targetUser['role'] !== 'superadmin') {
                echo json_encode(['status' => 'error', 'message' => 'Role Super Admin tidak dapat diberikan kepada akun ini.']);
                exit;
            }
        }
    }

    $modulesJson = json_encode(array_values($modules));

    if ($userId > 0) {
        // UPDATE Existing User
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, password = ?, role = ?, allowed_modules = ? WHERE id = ?");
                $stmt->execute([$username, $name, $hash, $role, $modulesJson, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, role = ?, allowed_modules = ? WHERE id = ?");
                $stmt->execute([$username, $name, $role, $modulesJson, $userId]);
            }
            echo json_encode(['status' => 'success', 'message' => 'User berhasil diperbarui.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        // CREATE New User
        if (empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Password wajib diisi untuk user baru.']);
            exit;
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, allowed_modules) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $name, $role, $modulesJson]);
            echo json_encode(['status' => 'success', 'message' => 'User admin baru berhasil didaftarkan.']);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan. Silakan pilih username lain.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
    exit;
}
