<?php
// database/migrations/016_create_kpi_master_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS kpi_master (
        $idCol,
        bulan VARCHAR(20) NOT NULL,
        gr_target DECIMAL(10,2) DEFAULT 0,
        gr_achievement DECIMAL(10,2) DEFAULT 0,
        registrasi_target DECIMAL(10,2) DEFAULT 0,
        registrasi_achievement DECIMAL(10,2) DEFAULT 0,
        slow_moving_target DECIMAL(10,2) DEFAULT 0,
        slow_moving_achievement DECIMAL(10,2) DEFAULT 0,
        utilisasi_space_target DECIMAL(10,2) DEFAULT 0,
        utilisasi_space_achievement DECIMAL(10,2) DEFAULT 0,
        stok_opname_target DECIMAL(10,2) DEFAULT 0,
        stok_opname_achievement DECIMAL(10,2) DEFAULT 0,
        delivery_effectiveness_target DECIMAL(10,2) DEFAULT 0,
        delivery_effectiveness_achievement DECIMAL(10,2) DEFAULT 0,
        mr_closing_target DECIMAL(10,2) DEFAULT 0,
        mr_closing_achievement DECIMAL(10,2) DEFAULT 0,
        efisiensi_delivery_target DECIMAL(10,2) DEFAULT 0,
        efisiensi_delivery_achievement DECIMAL(10,2) DEFAULT 0,
        periode_tahun INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    // Add unique index on bulan + periode_tahun to support upsert
    try {
        if ($driver === 'pgsql') {
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_kpi_bulan_tahun ON kpi_master (bulan, periode_tahun)");
        } else {
            $pdo->exec("ALTER TABLE kpi_master ADD UNIQUE INDEX idx_kpi_bulan_tahun (bulan, periode_tahun)");
        }
    } catch (PDOException $e) {
        // Index may already exist, safe to ignore
    }

    echo "Created table 'kpi_master'.\n";
};
