<?php
// database/migrations/009_create_repository_documents_table.php

return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $tableOpt = ($driver === 'pgsql') ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. Create repository_documents table
    $sql = "CREATE TABLE IF NOT EXISTS repository_documents (
        $idCol,
        title VARCHAR(255) NOT NULL,
        document_code VARCHAR(100) NULL,
        category VARCHAR(100) NOT NULL DEFAULT 'General',
        description TEXT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL DEFAULT 0,
        uploaded_by VARCHAR(150) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $tableOpt;";

    $pdo->exec($sql);
    echo "Created table repository_documents.\n";

    // Ensure uploads directory exists at project root
    $uploadDir = dirname(__DIR__, 3) . '/uploads/repository';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Helper to generate minimal valid PDF bytes
    $generateSimplePdf = function ($title, $docCode) {
        $content = "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n"
            . "4 0 obj\n<< /Length 120 >>\nstream\n"
            . "BT\n/F1 18 Tf\n50 720 Td\n($title) Tj\n0 -30 Td\n/F1 12 Tf\n(Document Code: $docCode) Tj\n0 -20 Td\n(Work Instruction - PT. Aplikanusa Lintasarta) Tj\nET\n"
            . "endstream\nendobj\n"
            . "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n"
            . "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000244 00000 n \n0000000415 00000 n \ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n492\n%%EOF";
        return $content;
    };

    // 2. Seed sample Work Instruction (WI) documents if empty
    $count = $pdo->query("SELECT COUNT(*) FROM repository_documents")->fetchColumn();
    if ($count == 0) {
        $samples = [
            [
                'title' => 'WI - Penerimaan Barang & Staging (GR)',
                'code' => 'WI-INB-001',
                'category' => 'Inbound',
                'description' => 'Petunjuk kerja operasional untuk proses penerimaan barang, pencocokan PO, verifikasi fisik perangkat, dan staging area.',
                'filename' => 'WI_Penerimaan_Barang_Inbound.pdf'
            ],
            [
                'title' => 'WI - Penataan Rak & Penyimpanan Warehouse',
                'code' => 'WI-STR-002',
                'category' => 'Storage',
                'description' => 'Standar operasional tata letak penempatan barang pada rak penyimpanan, tagging barcode, dan manajemen utilisasi kapasitas.',
                'filename' => 'WI_Penataan_Rak_Storage.pdf'
            ],
            [
                'title' => 'WI - Pemenuhan Material Request (MR) & Outbound Shipping',
                'code' => 'WI-OUT-003',
                'category' => 'Outbound',
                'description' => 'Panduan alur picking, packing, pembuatan surat jalan, dan serah terima pengiriman perangkat kepada ekspedisi / logistik.',
                'filename' => 'WI_Pemenuhan_MR_Outbound.pdf'
            ],
            [
                'title' => 'WI - Perhitungan Utilisasi Kapasitas Rak & Space Gudang',
                'code' => 'WI-KAP-004',
                'category' => 'General',
                'description' => 'Metodologi dan formulasi perhitungan kapasitas terpakai rak (meter kubik & slot), audit inventory berkala, dan penanganan relokasi.',
                'filename' => 'WI_Perhitungan_Kapasitas_Gudang.pdf'
            ]
        ];

        $insertStmt = $pdo->prepare("INSERT INTO repository_documents (title, document_code, category, description, file_path, file_name, original_name, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($samples as $sample) {
            $uniqueName = 'wi_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $targetPath = $uploadDir . '/' . $uniqueName;
            $pdfBytes = $generateSimplePdf($sample['title'], $sample['code']);
            file_put_contents($targetPath, $pdfBytes);
            $fileSize = filesize($targetPath);

            $insertStmt->execute([
                $sample['title'],
                $sample['code'],
                $sample['category'],
                $sample['description'],
                'uploads/repository/' . $uniqueName,
                $uniqueName,
                $sample['filename'],
                $fileSize,
                'Super Admin'
            ]);
        }
        echo "Seeded default Work Instruction (WI) PDF documents.\n";
    }
};
