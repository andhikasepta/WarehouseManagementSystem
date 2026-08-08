<?php
// api/download_template_inbound.php
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

$filename = 'Template_Import_Master_Data_Inbound.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 18 Header columns
$headers = [
    'PR Nomor',
    'PR Kode Site',
    'PR Nama Site',
    'PR Item Kategori',
    'PR PIC Teknis Nama',
    'PR Nama Bagian',
    'PR Nama Divisi',
    'PR Regional',
    'PR Jenis MA',
    'PO Nomor',
    'PO Deskripsi',
    'PO Vendor',
    'PO Tgl. Generate',
    'PO Nama Item',
    'PO Qty Item',
    'PO UoM Item',
    'PO Target Delivery',
    'Project ID'
];

fputcsv($output, $headers);

// Real Sample Row
fputcsv($output, [
    'PR-70900/3020/1000/2025',
    '50002003304',
    'INBOUND WAREHOUSE T TEKNO',
    'Service',
    'SOFIAN ARISSA PUTRO',
    'PROJECT MANAGEMENT',
    '',
    'Wilayah Pusat (PUSAT)',
    'OPEX',
    '21780/I/PO-LA/2025',
    'Pertamina EP - Containment Data Center Zona 1 Jambi - Penarikan FO DC Containment IT Room',
    'TRIGUNA AKSES TEKNOLOGI',
    '03/12/2025',
    'Retensi 5% selama 1 bulan',
    '1',
    'Lots',
    '02/03/2026',
    'PID-02160-04-2024'
]);

fclose($output);
exit;
