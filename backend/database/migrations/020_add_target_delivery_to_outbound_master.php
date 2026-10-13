<?php
// backend/database/migrations/020_add_target_delivery_to_outbound_master.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $safeConvertColumnToDate = function ($table, $column) use ($pdo, $driver) {
        try {
            if ($driver === 'pgsql') {
                // Check if table exists
                $tCheck = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
                $tCheck->execute([$table]);
                if (!$tCheck->fetch()) {
                    return;
                }

                // Check if column exists
                $colCheck = $pdo->prepare("SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?");
                $colCheck->execute([$table, $column]);
                $colInfo = $colCheck->fetch(PDO::FETCH_ASSOC);

                if (!$colInfo) {
                    $pdo->exec("ALTER TABLE \"$table\" ADD COLUMN \"$column\" DATE");
                    echo "Added column '$column' as DATE to '$table'.\n";
                } elseif (strtolower($colInfo['data_type']) !== 'date') {
                    $sql = "ALTER TABLE \"$table\" ALTER COLUMN \"$column\" TYPE DATE USING (
                        CASE 
                            WHEN \"$column\" IS NULL OR TRIM(\"$column\"::text) = '' OR TRIM(\"$column\"::text) = '-' THEN NULL
                            WHEN \"$column\"::text ~ '^\\d{4}-\\d{2}-\\d{2}' THEN substring(\"$column\"::text from '^\\d{4}-\\d{2}-\\d{2}')::DATE
                            WHEN \"$column\"::text ~ '^\\d{1,2}[\\/\\-]\\d{1,2}[\\/\\-]\\d{4}' THEN 
                                CASE 
                                    WHEN \"$column\"::text LIKE '%/%' THEN to_date(substring(\"$column\"::text from '^\\d{1,2}\\/\\d{1,2}\\/\\d{4}'), 'DD/MM/YYYY')
                                    ELSE to_date(substring(\"$column\"::text from '^\\d{1,2}\\-\\d{1,2}\\-\\d{4}'), 'DD-MM-YYYY')
                                END
                            WHEN \"$column\"::text ~ '^\\d{5}$' THEN (DATE '1899-12-30' + (\"$column\"::integer || ' days')::interval)::DATE
                            ELSE NULL
                        END
                    )";
                    $pdo->exec($sql);
                    echo "Altered column '$column' in '$table' to DATE.\n";
                } else {
                    echo "Column '$column' in '$table' is already DATE.\n";
                }
            } else {
                // MySQL
                $tCheck = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
                $tCheck->execute([$table]);
                if (!$tCheck->fetch()) {
                    return;
                }

                $colCheck = $pdo->prepare("SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
                $colCheck->execute([$table, $column]);
                $colInfo = $colCheck->fetch(PDO::FETCH_ASSOC);

                if (!$colInfo) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` DATE NULL");
                    echo "Added column '$column' as DATE to '$table'.\n";
                } elseif (strtolower($colInfo['DATA_TYPE']) !== 'date') {
                    $pdo->exec("UPDATE `$table` SET `$column` = CASE 
                        WHEN `$column` IS NULL OR TRIM(`$column`) = '' OR TRIM(`$column`) = '-' THEN NULL
                        WHEN `$column` REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN SUBSTRING(`$column`, 1, 10)
                        WHEN `$column` REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}' THEN STR_TO_DATE(`$column`, '%d/%m/%Y')
                        WHEN `$column` REGEXP '^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}' THEN STR_TO_DATE(`$column`, '%d-%m-%Y')
                        WHEN `$column` REGEXP '^[0-9]{5}$' THEN DATE_ADD('1899-12-30', INTERVAL `$column` DAY)
                        ELSE NULL
                    END");
                    $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN `$column` DATE NULL");
                    echo "Altered column '$column' in '$table' to DATE.\n";
                } else {
                    echo "Column '$column' in '$table' is already DATE.\n";
                }
            }
        } catch (PDOException $e) {
            echo "Warning on table '$table', column '$column': " . $e->getMessage() . "\n";
        }
    };

    // 1. Outbound Master: delivery_target
    $safeConvertColumnToDate('outbound_master', 'delivery_target');

    // 2. Outbound Forwarder: tgl and date columns
    $safeConvertColumnToDate('outbound_forwarder', 'sr_tgl');
    $safeConvertColumnToDate('outbound_forwarder', 'pr_tgl');
    $safeConvertColumnToDate('outbound_forwarder', 'po_tgl');
    $safeConvertColumnToDate('outbound_forwarder', 'po_target_dlv');
    $safeConvertColumnToDate('outbound_forwarder', 'delivery_pickup');
    $safeConvertColumnToDate('outbound_forwarder', 'delivery_target_dlv');
    $safeConvertColumnToDate('outbound_forwarder', 'approval_date');

    // 3. Inbound GR: tgl and date columns
    $safeConvertColumnToDate('inbound_gr', 'pr_date');
    $safeConvertColumnToDate('inbound_gr', 'po_date');
    $safeConvertColumnToDate('inbound_gr', 'do_date');
    $safeConvertColumnToDate('inbound_gr', 'gr_date');
    $safeConvertColumnToDate('inbound_gr', 'artis_date');
    $safeConvertColumnToDate('inbound_gr', 'iips_date');

    // 4. Inbound Master: verify tgl and date columns
    $safeConvertColumnToDate('inbound_master', 'po_tgl_generate');
    $safeConvertColumnToDate('inbound_master', 'po_target_delivery');

    echo "Migration 020 completed: Date columns verified and altered to DATE.\n";
};
