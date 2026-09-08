<?php
// database/migrations/018_update_rack_master_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS barcode VARCHAR(255)");
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS name VARCHAR(255)");
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS active VARCHAR(50) DEFAULT 'ACTIVE'");
        $pdo->exec("ALTER TABLE rack_master ALTER COLUMN rack DROP NOT NULL");
        try {
            $pdo->exec("ALTER TABLE rack_master DROP CONSTRAINT IF EXISTS rack_master_label_key");
        } catch (Exception $e) {}
    } else {
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS barcode VARCHAR(255)");
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS name VARCHAR(255)");
        $pdo->exec("ALTER TABLE rack_master ADD COLUMN IF NOT EXISTS active VARCHAR(50) DEFAULT 'ACTIVE'");
        $pdo->exec("ALTER TABLE rack_master MODIFY COLUMN rack VARCHAR(255) NULL");
    }

    echo "Updated 'rack_master' table with barcode, name, active columns.\n";
};
