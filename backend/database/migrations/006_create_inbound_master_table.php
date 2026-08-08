<?php
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS inbound_master (
        $idCol,
        pr_nomor TEXT,
        pr_kode_site TEXT,
        pr_nama_site TEXT,
        pr_item_kategori TEXT,
        pr_pic_teknis_nama TEXT,
        pr_nama_bagian TEXT,
        pr_nama_divisi TEXT,
        pr_regional TEXT,
        pr_jenis_ma TEXT,
        po_nomor TEXT,
        po_deskripsi TEXT,
        po_vendor TEXT,
        po_tgl_generate DATE,
        po_nama_item TEXT,
        po_qty_item DECIMAL(15,2) DEFAULT 0,
        po_uom_item TEXT,
        po_target_delivery DATE,
        project_id TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);
};
