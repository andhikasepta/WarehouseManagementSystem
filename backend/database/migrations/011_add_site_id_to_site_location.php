<?php
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Add site_id column for the site code from Excel
    try {
        $pdo->exec("ALTER TABLE site_location ADD COLUMN site_id TEXT AFTER id");
    } catch (PDOException $e) {
        // Column may already exist
        if (strpos($e->getMessage(), 'Duplicate column') === false && strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
    }
};
