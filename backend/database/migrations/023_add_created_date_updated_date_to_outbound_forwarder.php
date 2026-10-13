<?php
// database/migrations/023_add_created_date_updated_date_to_outbound_forwarder.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    try {
        // 1. Check & add created_date (DATE)
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'created_date'");
        } else {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'created_date'");
        }
        $stmt->execute();
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN created_date DATE NULL");
            echo "Added 'created_date' (DATE) column to outbound_forwarder.\n";
        }

        // 2. Check & add updated_date (DATE)
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'outbound_forwarder' AND column_name = 'updated_date'");
        } else {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbound_forwarder' AND COLUMN_NAME = 'updated_date'");
        }
        $stmt->execute();
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE outbound_forwarder ADD COLUMN updated_date DATE NULL");
            echo "Added 'updated_date' (DATE) column to outbound_forwarder.\n";
        }
    } catch (PDOException $e) {
        echo "Error adding date columns to outbound_forwarder: " . $e->getMessage() . "\n";
    }
};
