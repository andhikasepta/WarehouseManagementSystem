<?php
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $doubleCol = ($driver === 'pgsql') ? "nbv DOUBLE PRECISION DEFAULT 0" : "nbv DOUBLE DEFAULT 0";

    $sql = "CREATE TABLE IF NOT EXISTS assets (
        $idCol,
        spec_code VARCHAR(255),
        spec_name VARCHAR(255),
        reg_no VARCHAR(255),
        asset_planner_organization VARCHAR(255),
        $doubleCol,
        so_result VARCHAR(255),
        so_location VARCHAR(255),
        range_val VARCHAR(255),
        sub_location VARCHAR(255),
        category VARCHAR(255),
        periode VARCHAR(255),
        periode_group VARCHAR(255),
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
};
