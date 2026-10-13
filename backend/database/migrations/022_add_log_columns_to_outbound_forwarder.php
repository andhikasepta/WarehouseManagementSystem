<?php
// database/migrations/022_add_log_columns_to_outbound_forwarder.php
// Adds 'created_by' and 'updated_by' columns to outbound_forwarder for audit log tracking.

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    try {
        // 1. Check & add created_by
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'created_by'");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'created_by'");
            $stmt->execute();
        }
        $existsCreatedBy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existsCreatedBy) {
            $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN created_by TEXT NULL");
            echo "Added 'created_by' column to outbound_forwarder table.\n";
        } else {
            echo "Column 'created_by' already exists on outbound_forwarder, skipping.\n";
        }

        // 2. Check & add updated_by
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'updated_by'");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'updated_by'");
            $stmt->execute();
        }
        $existsUpdatedBy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existsUpdatedBy) {
            $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN updated_by TEXT NULL");
            echo "Added 'updated_by' column to outbound_forwarder table.\n";
        } else {
            echo "Column 'updated_by' already exists on outbound_forwarder, skipping.\n";
        }
    } catch (PDOException $e) {
        echo "Error adding log columns to outbound_forwarder: " . $e->getMessage() . "\n";
        throw $e;
    }
};
