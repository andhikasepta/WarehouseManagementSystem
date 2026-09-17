<?php
// database/migrations/019_create_inbound_gr_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS inbound_gr (
        $idCol,
        no_reg TEXT,
        kd_spec TEXT,
        sn TEXT,
        pn TEXT,
        product_name TEXT,
        price TEXT,
        pr_no TEXT,
        pr_date TEXT,
        po_no TEXT,
        po_date TEXT,
        do_no TEXT,
        do_date TEXT,
        gr_no TEXT,
        gr_date TEXT,
        po_value TEXT,
        nama_project TEXT,
        term_of_payment TEXT,
        kode_site_penerimaan TEXT,
        qty TEXT,
        uom TEXT,
        is_unique_item TEXT,
        warranty TEXT,
        warranty_unit TEXT,
        manufacturer TEXT,
        vendor_name TEXT,
        vendor_address TEXT,
        jenis_kepemilikan TEXT,
        pemilik TEXT,
        capex_opex TEXT,
        loi_no TEXT,
        is_sent_to_artis_code TEXT,
        artis_date TEXT,
        artis_message TEXT,
        is_sent_to_iips_code TEXT,
        iips_date TEXT,
        iips_message TEXT,
        pic_submit_gr TEXT,
        pic_registration TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    // Create performance indexes
    $createIndex = function ($indexSql) use ($pdo) {
        try {
            $pdo->exec($indexSql);
        } catch (Exception $e) {
            // Index might already exist
        }
    };

    if ($driver === 'mysql') {
        $createIndex("CREATE INDEX idx_inbound_gr_periode_group ON inbound_gr(periode_group(191))");
        $createIndex("CREATE INDEX idx_inbound_gr_po_no ON inbound_gr(po_no(191))");
        $createIndex("CREATE INDEX idx_inbound_gr_gr_no ON inbound_gr(gr_no(191))");
        $createIndex("CREATE INDEX idx_inbound_gr_vendor_name ON inbound_gr(vendor_name(191))");
        $createIndex("CREATE INDEX idx_inbound_gr_nama_project ON inbound_gr(nama_project(191))");
    } else {
        $createIndex("CREATE INDEX IF NOT EXISTS idx_inbound_gr_periode_group ON inbound_gr(periode_group)");
        $createIndex("CREATE INDEX IF NOT EXISTS idx_inbound_gr_po_no ON inbound_gr(po_no)");
        $createIndex("CREATE INDEX IF NOT EXISTS idx_inbound_gr_gr_no ON inbound_gr(gr_no)");
        $createIndex("CREATE INDEX IF NOT EXISTS idx_inbound_gr_vendor_name ON inbound_gr(vendor_name)");
        $createIndex("CREATE INDEX IF NOT EXISTS idx_inbound_gr_nama_project ON inbound_gr(nama_project)");
    }

    echo "Created table 'inbound_gr'.\n";
};
