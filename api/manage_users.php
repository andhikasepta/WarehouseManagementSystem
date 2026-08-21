<?php
// api/manage_users.php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

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
        $stmt = $pdo->query("SELECT id, username, name, role, employment_type, job_title, allowed_modules, permissions, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $user['employment_type'] = $user['employment_type'] ?? 'Karyawan Tetap';
            $user['job_title'] = $user['job_title'] ?? '';
            $user['allowed_modules'] = json_decode($user['allowed_modules'], true) ?? [];
            $user['permissions'] = json_decode($user['permissions'] ?? '{}', true) ?? [];
        }

        echo json_encode(['status' => 'success', 'data' => $users]);
    } catch (PDOException $e) {
        error_log('manage_users.php GET error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat memuat data user.']);
    }
    exit;
}

if ($method === 'POST') {
    validateCsrf();
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
            error_log('manage_users.php reset_password error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mereset password.']);
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
            error_log('manage_users.php delete error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus user.']);
        }
        exit;
    }

    // Save (Create or Update)
    $userId = intval($data['id'] ?? 0);
    $username = trim($data['username'] ?? '');
    $name = trim($data['name'] ?? '');
    $password = trim($data['password'] ?? '');
    $role = trim($data['role'] ?? 'admin');
    $employmentType = trim($data['employment_type'] ?? 'Karyawan Tetap');
    if ($employmentType !== 'Outsourcing' && $employmentType !== 'Karyawan Tetap') {
        $employmentType = 'Karyawan Tetap';
    }
    $jobTitle = trim($data['job_title'] ?? '');
    $modules = is_array($data['allowed_modules'] ?? null) ? $data['allowed_modules'] : [];
    $permissions = is_array($data['permissions'] ?? null) ? $data['permissions'] : [];

    // Validate role is an allowed value
    $validRoles = ['superadmin', 'head_asset_warehouse_admin', 'head_warehouse_admin', 'inbound_admin', 'outbound_admin', 'warehouse_admin', 'repository_admin', 'outsourcing'];
    if (!in_array($role, $validRoles)) {
        $role = 'warehouse_admin';
    }

    // No forced modules - superadmin decides all access via RBAC checkboxes

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

    // Build sanitized permissions: only include modules that are in allowed_modules
    $sanitizedPermissions = [];
    foreach ($modules as $mod) {
        $modKey = is_string($mod) ? trim($mod) : '';
        if (empty($modKey)) continue;

        if (isset($permissions[$modKey]) && is_array($permissions[$modKey])) {
            $sanitizedPermissions[$modKey] = [
                'view'   => !empty($permissions[$modKey]['view']),
                'add'    => !empty($permissions[$modKey]['add']),
                'delete' => !empty($permissions[$modKey]['delete']),
            ];
        } else {
            // Default: view only for modules without explicit permissions
            $sanitizedPermissions[$modKey] = ['view' => true, 'add' => false, 'delete' => false];
        }
    }

    // Superadmin always gets full permissions
    if ($role === 'superadmin') {
        $sanitizedPermissions = [];
    }

    $modulesJson = json_encode(array_values($modules));
    $permissionsJson = json_encode($sanitizedPermissions);

    if ($userId > 0) {
        // UPDATE Existing User
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, password = ?, role = ?, employment_type = ?, job_title = ?, allowed_modules = ?, permissions = ? WHERE id = ?");
                $stmt->execute([$username, $name, $hash, $role, $employmentType, $jobTitle, $modulesJson, $permissionsJson, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, role = ?, employment_type = ?, job_title = ?, allowed_modules = ?, permissions = ? WHERE id = ?");
                $stmt->execute([$username, $name, $role, $employmentType, $jobTitle, $modulesJson, $permissionsJson, $userId]);
            }
            echo json_encode(['status' => 'success', 'message' => 'User berhasil diperbarui.']);
        } catch (PDOException $e) {
            error_log('manage_users.php update error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui user.']);
        }
    } else {
        // CREATE New User
        if (empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Password wajib diisi untuk user baru.']);
            exit;
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, employment_type, job_title, allowed_modules, permissions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $name, $role, $employmentType, $jobTitle, $modulesJson, $permissionsJson]);
            echo json_encode(['status' => 'success', 'message' => 'User admin baru berhasil didaftarkan.']);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan. Silakan pilih username lain.']);
            } else {
                error_log('manage_users.php create error: ' . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat membuat user baru.']);
            }
        }
    }
    exit;
}
