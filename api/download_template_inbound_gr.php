<?php
// api/download_template_inbound_gr.php
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

$filename = 'Template_Import_Master_Data_GR.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 38 Header columns as requested
$headers = [
    'no_reg',
    'kd_spec',
    'sn',
    'pn',
    'product_name',
    'price',
    'pr_no',
    'pr_date',
    'po_no',
    'po_date',
    'do_no',
    'do_date',
    'gr_no',
    'gr_date',
    'po_value',
    'nama_project',
    'term_of_payment',
    'kode_site_penerimaan',
    'qty',
    'uom',
    'is_unique_item',
    'warranty',
    'warranty_Unit',
    'manufacturer',
    'vendor_name',
    'vendor_address',
    'jenis_kepemilikan',
    'pemilik',
    'capex_opex',
    'loi_no',
    'IsSentToARTISCode',
    'ARTIS_Date',
    'ARTIS_Message',
    'IsSentToIIPSCode',
    'IIPS_Date',
    'IIPS_Message',
    'PIC Submit GR',
    'PIC Registration'
];

fputcsv($output, $headers);

// Sample row
fputcsv($output, [
    'REG-001',
    'SPEC-001',
    'SN12345678',
    'PN-8877',
    'ROUTER CISCO 2911',
    '15000000',
    'PR-2026/001',
    '2026-01-10',
    'PO-2026/001',
    '2026-01-15',
    'DO-2026/001',
    '2026-01-20',
    'GR-2026/001',
    '2026-01-22',
    '15000000',
    'PROJECT NETWORK EXPANSION',
    '30 Days',
    'SITE-JKT-01',
    '1',
    'Unit',
    'Yes',
    '12',
    'Month',
    'Cisco',
    'PT. VENDOR TEKNOLOGI',
    'Jl. Sudirman No. 10 Jakarta',
    'Sewa Beli',
    'PT TELEKOMUNIKASI',
    'CAPEX',
    'LOI-001',
    '200',
    '2026-01-22',
    'SUCCESS',
    '200',
    '2026-01-22',
    'SUCCESS',
    'JOHN DOE',
    'JANE DOE'
]);

fclose($output);
exit;
