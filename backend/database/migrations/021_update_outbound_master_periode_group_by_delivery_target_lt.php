<?php
// backend/database/migrations/021_update_outbound_master_periode_group_by_delivery_target_lt.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    try {
        if ($driver === 'pgsql') {
            // 1. Update delivery_target from raw_data if not set
            $sql1 = "
            UPDATE outbound_master
            SET delivery_target = (
                CASE 
                    WHEN raw_data->>'DELIVERY TARGET' IS NULL OR TRIM(raw_data->>'DELIVERY TARGET') = '' OR TRIM(raw_data->>'DELIVERY TARGET') = '-' THEN NULL
                    WHEN raw_data->>'DELIVERY TARGET' ~ '^\\d{4}-\\d{2}-\\d{2}' THEN SUBSTRING(raw_data->>'DELIVERY TARGET' FROM '^\\d{4}-\\d{2}-\\d{2}')::DATE
                    WHEN raw_data->>'DELIVERY TARGET' ~ '^\\d{1,2}[\\/\\-]\\d{1,2}[\\/\\-]\\d{4}' THEN 
                        CASE 
                            WHEN raw_data->>'DELIVERY TARGET' LIKE '%/%' THEN to_date(SUBSTRING(raw_data->>'DELIVERY TARGET' FROM '^\\d{1,2}\\/\\d{1,2}\\/\\d{4}'), 'DD/MM/YYYY')
                            ELSE to_date(SUBSTRING(raw_data->>'DELIVERY TARGET' FROM '^\\d{1,2}\\-\\d{1,2}\\-\\d{4}'), 'DD-MM-YYYY')
                        END
                    WHEN raw_data->>'DELIVERY TARGET' ~ '^\\d+(\\.\\d+)?$' AND (raw_data->>'DELIVERY TARGET')::numeric > 25000 AND (raw_data->>'DELIVERY TARGET')::numeric < 80000 THEN 
                        (DATE '1899-12-30' + (FLOOR((raw_data->>'DELIVERY TARGET')::numeric)::integer || ' days')::interval)::DATE
                    ELSE NULL
                END
            )
            WHERE raw_data->>'DELIVERY TARGET' IS NOT NULL;
            ";
            $pdo->exec($sql1);

            // 2. Update periode_group based on delivery_target - lt days
            $sql2 = "
            UPDATE outbound_master
            SET periode_group = (
                TRIM(TO_CHAR(
                    delivery_target - (COALESCE(NULLIF(REGEXP_REPLACE(lt, '[^0-9]', '', 'g'), ''), '0')::integer * interval '1 day'),
                    'FMMonth YYYY'
                )) || COALESCE(SUBSTRING(periode_group FROM '-Batch\\d+'), '-Batch1')
            )
            WHERE delivery_target IS NOT NULL;
            ";
            $pdo->exec($sql2);
        } else {
            // MySQL
            $sql1 = "
            UPDATE outbound_master
            SET delivery_target = (
                CASE 
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) IS NULL OR TRIM(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"'))) = '' OR TRIM(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"'))) = '-' THEN NULL
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN SUBSTRING(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')), 1, 10)
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}' THEN STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')), '%d/%m/%Y')
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) REGEXP '^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}' THEN STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')), '%d-%m-%Y')
                    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) AS DECIMAL(10,2)) > 25000 AND CAST(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) AS DECIMAL(10,2)) < 80000 THEN 
                        DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"')) AS DECIMAL(10,2))) DAY)
                    ELSE NULL
                END
            )
            WHERE JSON_EXTRACT(raw_data, '$.\"DELIVERY TARGET\"') IS NOT NULL;
            ";
            $pdo->exec($sql1);

            $sql2 = "
            UPDATE outbound_master
            SET periode_group = CONCAT(
                DATE_FORMAT(DATE_SUB(delivery_target, INTERVAL CAST(COALESCE(NULLIF(REGEXP_REPLACE(lt, '[^0-9]', ''), ''), '0') AS UNSIGNED) DAY), '%M %Y'),
                IF(periode_group REGEXP '-Batch[0-9]+', SUBSTRING(periode_group, LOCATE('-Batch', periode_group)), '-Batch1')
            )
            WHERE delivery_target IS NOT NULL;
            ";
            $pdo->exec($sql2);
        }
        echo "Migration 021 completed: outbound_master periode_group updated by Delivery Target - LT.\n";
    } catch (PDOException $e) {
        echo "Migration 021 warning: " . $e->getMessage() . "\n";
    }
};
