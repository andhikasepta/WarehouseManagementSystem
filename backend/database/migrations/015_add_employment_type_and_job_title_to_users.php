<?php
// backend/database/migrations/015_add_employment_type_and_job_title_to_users.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Helper to check if column exists in table
    $columnExists = function ($table, $column) use ($pdo, $driver) {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        }
    };

    if (!$columnExists('users', 'employment_type')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN employment_type VARCHAR(50) NULL DEFAULT 'Karyawan Tetap'");
        echo "Added column 'employment_type' to users.\n";
    }

    if (!$columnExists('users', 'job_title')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN job_title VARCHAR(150) NULL DEFAULT ''");
        echo "Added column 'job_title' to users.\n";
    }

    // Set defaults for existing rows if NULL
    $pdo->exec("UPDATE users SET employment_type = 'Karyawan Tetap' WHERE employment_type IS NULL OR employment_type = ''");
    $pdo->exec("UPDATE users SET job_title = '' WHERE job_title IS NULL");

    echo "Migration 015 completed successfully.\n";
};
