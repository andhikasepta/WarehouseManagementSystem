<?php
// Migration: Update existing periode_group values to include Batch 1 suffix
// Old format: "January 2026" → New format: "January 2026-Batch1"
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Update assets table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM assets WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $pdo->exec("UPDATE assets SET periode_group = CONCAT(periode_group, '-Batch1') WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        }
    } catch (Exception $e) {
        // Table might not exist yet, skip silently
    }

    // Update inbound_master table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM inbound_master WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $pdo->exec("UPDATE inbound_master SET periode_group = CONCAT(periode_group, '-Batch1') WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        }
    } catch (Exception $e) {
        // Table might not exist yet, skip silently
    }

    // Update outbound_master table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM outbound_master WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $pdo->exec("UPDATE outbound_master SET periode_group = CONCAT(periode_group, '-Batch1') WHERE periode_group IS NOT NULL AND periode_group != '' AND periode_group NOT LIKE '%-Batch%'");
        }
    } catch (Exception $e) {
        // Table might not exist yet, skip silently
    }
};
