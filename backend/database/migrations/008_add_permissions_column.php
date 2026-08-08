<?php
// database/migrations/008_add_permissions_column.php
// Adds a 'permissions' TEXT column to the users table for granular RBAC.
// Stores a JSON object mapping module names to {view, add, delete} flags.

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Check if column already exists
    try {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'permissions'");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'permissions'");
            $stmt->execute();
        }
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT NOT NULL DEFAULT '{}'");
            echo "Added 'permissions' column to users table.\n";

            // Backfill existing users: for each user's allowed_modules, set default permissions (view=true, add=true, delete=true)
            $users = $pdo->query("SELECT id, allowed_modules, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
            $updateStmt = $pdo->prepare("UPDATE users SET permissions = ? WHERE id = ?");

            foreach ($users as $user) {
                $modules = json_decode($user['allowed_modules'], true);
                if (!is_array($modules)) $modules = [];

                $permissions = [];
                foreach ($modules as $mod) {
                    // Superadmin and head roles get full permissions, others get view+add+delete (preserving current behavior)
                    $permissions[$mod] = ['view' => true, 'add' => true, 'delete' => true];
                }

                $updateStmt->execute([json_encode($permissions), $user['id']]);
            }
            echo "Backfilled permissions for existing users.\n";
        } else {
            echo "Column 'permissions' already exists, skipping.\n";
        }
    } catch (PDOException $e) {
        echo "Error adding permissions column: " . $e->getMessage() . "\n";
        throw $e;
    }
};
