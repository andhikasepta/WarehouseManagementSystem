<?php
// database/migrations/017_create_outbound_forwarder_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS outbound_forwarder (
        $idCol,
        no_dn TEXT,
        print_status TEXT,
        dn_status TEXT,
        asal_pengirim TEXT,
        asal_code TEXT,
        asal_site TEXT,
        asal_alamat TEXT,
        tujuan_penerima TEXT,
        tujuan_code TEXT,
        tujuan_site TEXT,
        tujuan_alamat TEXT,
        proc_vendor_mode TEXT,
        proc_vendor_name TEXT,
        koli TEXT,
        mata_anggaran TEXT,
        sr_no TEXT,
        sr_tgl TEXT,
        pr_no TEXT,
        pr_tgl TEXT,
        valuation_price TEXT,
        suggestion TEXT,
        purpose TEXT,
        po_no TEXT,
        po_tgl TEXT,
        po_price TEXT,
        po_vendor TEXT,
        po_target_dlv TEXT,
        po_buyer TEXT,
        doc TEXT,
        note TEXT,
        delivery_type TEXT,
        delivery_via TEXT,
        delivery_nama TEXT,
        delivery_awb TEXT,
        delivery_pickup TEXT,
        delivery_lead_time TEXT,
        delivery_target_dlv TEXT,
        approval_status TEXT,
        approval_approver TEXT,
        approval_date TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);
    echo "Created table 'outbound_forwarder'.\n";
};
