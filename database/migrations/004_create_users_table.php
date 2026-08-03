<?php
// database/migrations/004_create_users_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $tableOpt = ($driver === 'pgsql') ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        $idCol,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(150) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        allowed_modules TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $tableOpt;";
    
    $pdo->exec($sql);

    // 2. Seed default Super Admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $defaultPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $defaultModules = json_encode(['inbound', 'warehouse', 'outbound', 'master_data']);
        
        $insert = $pdo->prepare("INSERT INTO users (username, password, name, role, allowed_modules) VALUES (?, ?, ?, ?, ?)");
        $insert->execute(['admin', $defaultPasswordHash, 'Super Admin', 'superadmin', $defaultModules]);
        echo "Seeded default Super Admin user (admin / admin123)\n";
    }
};
