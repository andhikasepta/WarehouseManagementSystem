<?php
// database/migrations/007_add_performance_indexes.php
// Adds indexes to assets and inbound_master tables for query performance.
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Helper: safely create an index, ignoring "already exists" errors
    $createIndex = function ($sql) use ($pdo) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Index already exists – skip silently
            // MySQL error 1061: Duplicate key name
            // PostgreSQL: relation already exists
            if (strpos($e->getMessage(), '1061') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    };

    // ─── assets table indexes ───
    // Assets columns are VARCHAR(255) — no prefix length needed
    $createIndex("CREATE INDEX idx_assets_periode_group ON assets(periode_group)");
    $createIndex("CREATE INDEX idx_assets_periode ON assets(periode)");
    $createIndex("CREATE INDEX idx_assets_so_location ON assets(so_location)");
    $createIndex("CREATE INDEX idx_assets_reg_no ON assets(reg_no)");
    $createIndex("CREATE INDEX idx_assets_category ON assets(category)");
    $createIndex("CREATE INDEX idx_assets_sub_location ON assets(sub_location)");

    if ($driver === 'mysql') {
        $createIndex("CREATE INDEX idx_assets_range_val ON assets(`range`)");
    } else {
        $createIndex("CREATE INDEX idx_assets_range_val ON assets(\"range\")");
    }

    // Composite index for the most common query pattern (periode_group lookup + periode sort)
    $createIndex("CREATE INDEX idx_assets_pg_periode ON assets(periode_group, periode)");

    // ─── inbound_master table indexes ───
    // Inbound columns are TEXT — prefix length required on MySQL
    if ($driver === 'mysql') {
        $createIndex("CREATE INDEX idx_inbound_periode_group ON inbound_master(periode_group(191))");
        $createIndex("CREATE INDEX idx_inbound_pr_nama_bagian ON inbound_master(pr_nama_bagian(191))");
        $createIndex("CREATE INDEX idx_inbound_pr_pic_teknis ON inbound_master(pr_pic_teknis_nama(191))");
        $createIndex("CREATE INDEX idx_inbound_pr_item_kategori ON inbound_master(pr_item_kategori(191))");
        $createIndex("CREATE INDEX idx_inbound_po_nomor ON inbound_master(po_nomor(191))");
        $createIndex("CREATE INDEX idx_inbound_pr_nomor ON inbound_master(pr_nomor(191))");
        $createIndex("CREATE INDEX idx_inbound_project_id ON inbound_master(project_id(191))");
    } else {
        $createIndex("CREATE INDEX idx_inbound_periode_group ON inbound_master(periode_group)");
        $createIndex("CREATE INDEX idx_inbound_pr_nama_bagian ON inbound_master(pr_nama_bagian)");
        $createIndex("CREATE INDEX idx_inbound_pr_pic_teknis ON inbound_master(pr_pic_teknis_nama)");
        $createIndex("CREATE INDEX idx_inbound_pr_item_kategori ON inbound_master(pr_item_kategori)");
        $createIndex("CREATE INDEX idx_inbound_po_nomor ON inbound_master(po_nomor)");
        $createIndex("CREATE INDEX idx_inbound_pr_nomor ON inbound_master(pr_nomor)");
        $createIndex("CREATE INDEX idx_inbound_project_id ON inbound_master(project_id)");
    }
};
