<?php
// backend/database/migrations/014_add_division_bagian_sub_bagian_to_repository_documents.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Helper to check if column exists in table
    $columnExists = function ($table, $column) use ($pdo, $driver) {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        }
    };

    if (!$columnExists('repository_documents', 'division')) {
        $pdo->exec("ALTER TABLE repository_documents ADD COLUMN division VARCHAR(150) NULL DEFAULT 'Supply Chain Management'");
        echo "Added column 'division' to repository_documents.\n";
    }

    if (!$columnExists('repository_documents', 'bagian')) {
        $pdo->exec("ALTER TABLE repository_documents ADD COLUMN bagian VARCHAR(150) NULL");
        echo "Added column 'bagian' to repository_documents.\n";
    }

    if (!$columnExists('repository_documents', 'sub_bagian')) {
        $pdo->exec("ALTER TABLE repository_documents ADD COLUMN sub_bagian VARCHAR(150) NULL");
        echo "Added column 'sub_bagian' to repository_documents.\n";
    }

    // Update existing rows that have NULL division to default 'Supply Chain Management'
    $pdo->exec("UPDATE repository_documents SET division = 'Supply Chain Management' WHERE division IS NULL OR division = ''");
    
    // Set default bagian for existing documents if NULL
    $pdo->exec("UPDATE repository_documents SET bagian = 'Asset And Warehouse Management' WHERE bagian IS NULL OR bagian = ''");
    $pdo->exec("UPDATE repository_documents SET sub_bagian = 'Warehouse Management' WHERE sub_bagian IS NULL OR sub_bagian = ''");

    echo "Migration 014 completed successfully.\n";
};
