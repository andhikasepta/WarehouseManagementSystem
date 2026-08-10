<?php
// api/get_kpi_data.php
// Returns real or dummy KPI Monitoring metrics, targets, and evaluation details
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // ── Input Parameters & Validation ──
    $month = isset($_GET['month']) ? trim($_GET['month']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';
    $site = isset($_GET['site']) ? trim($_GET['site']) : '';
    $periode = isset($_GET['periode']) ? trim($_GET['periode']) : '';

    $validMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    $monthsIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // Check if user explicitly selected "DATA DUMMY"
    $isDummy = (
        stripos($periode, 'dummy') !== false ||
        stripos($periode, 'tester') !== false ||
        stripos($month, 'dummy') !== false ||
        stripos($month, 'tester') !== false ||
        stripos($year, 'dummy') !== false
    );

    // If periode is provided like "June 2026", parse into month and year
    if (!$isDummy && !empty($periode) && $periode !== '-' && $periode !== 'PILIH PERIODE DATA') {
        $parts = explode(' ', $periode);
        if (count($parts) >= 2) {
            $month = ucfirst(strtolower($parts[0]));
            $year = $parts[1];
        }
    }

    $hasPeriod = (!empty($month) && !empty($year) && preg_match('/^\d{4}$/', $year));

    $monthIdx = array_search($month, $validMonths, true);
    if ($monthIdx === false) {
        $monthIdx = array_search($month, $monthsIndo, true);
    }
    if ($monthIdx !== false) {
        $month = $validMonths[$monthIdx];
        $monthIndoName = $monthsIndo[$monthIdx];
        $monthNumber = $monthIdx + 1;
    } else {
        $monthIndoName = $month;
        $monthNumber = 0;
    }

    $selectedPeriodGroup = $month . ' ' . $year;

    // ── Targets Definition ──
    $receivingSlaTarget = 95.0;
    $registrationSlaTarget = 98.0;
    $stockOpnameTarget = 99.5;
    $slowMovingTarget = 15.0; // Max threshold
    $capacityTarget = 80.0;
    $deliveryEffectivenessTarget = 95.0;
    $deliveryEfficiencyTarget = 130000000; // Rp 130.000.000 Target

    // ── Calculation / Evaluation Logic ──
    if ($isDummy) {
        // Dummy / Tester Data
        $receivingSlaVal = 96.5;
        $registrationSlaVal = 98.2;
        $stockOpnameVal = 99.8;
        $slowMovingVal = 12.8;
        $capacityVal = 76.4;
        $deliveryEffectivenessVal = 97.4;
        $deliveryEfficiencyVal = 148500000;
        $hasDataInPeriod = true;
        $month = 'DATA DUMMY';
        $monthIndoName = 'DATA DUMMY';
        $year = 'TESTER';
        $selectedPeriodGroup = 'DATA DUMMY';

        $kpiList = [
            [
                'id' => 'receiving_sla',
                'code' => 'KPI-IN-01',
                'name' => 'Receiving (GR) SLA',
                'category' => 'Inbound Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $receivingSlaTarget,
                'target_display' => '≥ 95.0%',
                'actual' => $receivingSlaVal,
                'actual_display' => '96,5%',
                'achievement' => 101.6,
                'status' => 'Achieved',
                'description' => 'Ketepatan waktu penerbitan Goods Receipt (GR) terhadap PO masuk sesuai Service Level Agreement (≤ 14 hari).',
                'formula' => '(Jumlah PO Terbit GR Tepat Waktu / Total PO Diterima) × 100%',
                'icon' => 'fa-clipboard-check',
                'color' => '#4e73df'
            ],
            [
                'id' => 'registration_sla',
                'code' => 'KPI-IN-02',
                'name' => 'Registration SLA',
                'category' => 'Inbound Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $registrationSlaTarget,
                'target_display' => '≥ 98.0%',
                'actual' => $registrationSlaVal,
                'actual_display' => '98,2%',
                'achievement' => 100.2,
                'status' => 'Achieved',
                'description' => 'Kecepatan dan kepatuhan registrasi serial number & tagging barcode perangkat pasca Goods Receipt (≤ 3 hari kerja).',
                'formula' => '(Jumlah Perangkat Diregistrasi Tepat Waktu / Total Perangkat GR) × 100%',
                'icon' => 'fa-barcode',
                'color' => '#36b9cc'
            ],
            [
                'id' => 'stock_opname',
                'code' => 'KPI-ST-01',
                'name' => 'Stock Opname',
                'category' => 'Storage & Warehouse Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $stockOpnameTarget,
                'target_display' => '≥ 99.5%',
                'actual' => $stockOpnameVal,
                'actual_display' => '99,8%',
                'achievement' => 100.3,
                'status' => 'Achieved',
                'description' => 'Akurasi kecocokan fisik inventori perangkat warehouse terhadap pencatatan sistem WMS saat audit berkala.',
                'formula' => '(Jumlah Item Fisik Match Sistem / Total Item Diaudit) × 100%',
                'icon' => 'fa-boxes',
                'color' => '#1cc88a'
            ],
            [
                'id' => 'slow_moving',
                'code' => 'KPI-ST-02',
                'name' => 'Slow Moving',
                'category' => 'Storage & Warehouse Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $slowMovingTarget,
                'target_display' => '≤ 15.0%',
                'actual' => $slowMovingVal,
                'actual_display' => '12,8%',
                'achievement' => 100.0,
                'status' => 'Achieved',
                'description' => 'Rasio perbandingan jumlah item perangkat mengendap/aging > 12 bulan terhadap total keseluruhan inventori.',
                'formula' => '(Total Qty Perangkat Aging > 12 Bulan / Total Qty Inventory on Hand) × 100%',
                'icon' => 'fa-hourglass-half',
                'color' => '#f6c23e'
            ],
            [
                'id' => 'capacity',
                'code' => 'KPI-ST-03',
                'name' => 'Capacity',
                'category' => 'Storage & Warehouse Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $capacityTarget,
                'target_display' => '70.0% - 80.0%',
                'actual' => $capacityVal,
                'actual_display' => '76,4%',
                'achievement' => 95.5,
                'status' => 'Achieved',
                'description' => 'Tingkat utilisasi kapasitas ruang penyimpanan rak dan staging area warehouse utama serta HUB regional.',
                'formula' => '(Kapasitas Ruang Terpakai / Total Kapasitas Maksimal Ruang) × 100%',
                'icon' => 'fa-warehouse',
                'color' => '#6f42c1'
            ],
            [
                'id' => 'delivery_effectiveness',
                'code' => 'KPI-OB-01',
                'name' => 'Delivery Effectiveness',
                'category' => 'Outbound Management',
                'unit' => '%',
                'is_currency' => false,
                'target' => $deliveryEffectivenessTarget,
                'target_display' => '≥ 95.0%',
                'actual' => $deliveryEffectivenessVal,
                'actual_display' => '97,4%',
                'achievement' => 102.5,
                'status' => 'Achieved',
                'description' => 'Efektivitas dan ketepatan pemenuhan Material Request (MR) & Delivery Order (DO) sampai di site tujuan tepat waktu.',
                'formula' => '(Jumlah Pengiriman On-Time & Sempurna / Total Permintaan Pengiriman) × 100%',
                'icon' => 'fa-truck-fast',
                'color' => '#e83e8c'
            ],
            [
                'id' => 'delivery_efficiency',
                'code' => 'KPI-OB-02',
                'name' => 'Efisiensi Delivery',
                'category' => 'Outbound Management',
                'unit' => 'IDR',
                'is_currency' => true,
                'target' => $deliveryEfficiencyTarget,
                'target_display' => 'Rp ' . number_format($deliveryEfficiencyTarget, 0, ',', '.'),
                'actual' => $deliveryEfficiencyVal,
                'actual_display' => 'Rp ' . number_format($deliveryEfficiencyVal, 0, ',', '.'),
                'achievement' => 114.2,
                'status' => 'Achieved',
                'description' => 'Total penghematan biaya logistik pengiriman melalui konsolidasi muatan armada dan optimasi rute regional.',
                'formula' => 'Estimasi Biaya Logistik Standar - Aktual Realisasi Pengeluaran Logistik',
                'icon' => 'fa-money-bill-wave',
                'color' => '#20c997'
            ]
        ];

        echo json_encode([
            'status' => 'success',
            'is_dummy' => true,
            'has_data' => true,
            'period' => [
                'month' => 'DATA DUMMY',
                'month_indo' => 'DATA DUMMY',
                'year' => 'TESTER',
                'group' => 'DATA DUMMY',
                'site' => $site
            ],
            'cards' => [
                'receiving_sla' => [
                    'name' => 'Receiving (GR) SLA',
                    'value' => $receivingSlaVal,
                    'value_formatted' => '96,5%',
                    'target' => $receivingSlaTarget,
                    'unit' => '%'
                ],
                'registration_sla' => [
                    'name' => 'Registration SLA',
                    'value' => $registrationSlaVal,
                    'value_formatted' => '98,2%',
                    'target' => $registrationSlaTarget,
                    'unit' => '%'
                ],
                'stock_opname' => [
                    'name' => 'Stock Opname',
                    'value' => $stockOpnameVal,
                    'value_formatted' => '99,8%',
                    'target' => $stockOpnameTarget,
                    'unit' => '%'
                ],
                'slow_moving' => [
                    'name' => 'Slow Moving',
                    'value' => $slowMovingVal,
                    'value_formatted' => '12,8%',
                    'target' => $slowMovingTarget,
                    'unit' => '%'
                ],
                'capacity' => [
                    'name' => 'Capacity',
                    'value' => $capacityVal,
                    'value_formatted' => '76,4%',
                    'target' => $capacityTarget,
                    'unit' => '%'
                ],
                'delivery_effectiveness' => [
                    'name' => 'Delivery Effectiveness',
                    'value' => $deliveryEffectivenessVal,
                    'value_formatted' => '97,4%',
                    'target' => $deliveryEffectivenessTarget,
                    'unit' => '%'
                ],
                'delivery_efficiency' => [
                    'name' => 'Efisiensi Delivery',
                    'value' => $deliveryEfficiencyVal,
                    'value_formatted' => 'Rp ' . number_format($deliveryEfficiencyVal, 0, ',', '.'),
                    'target' => $deliveryEfficiencyTarget,
                    'unit' => 'IDR'
                ]
            ],
            'kpi_list' => $kpiList
        ]);
        exit;
    }

    // ── Real Data Calculations strictly from Database ──
    $totalAssetsCount = 0;
    $slowMovingCount = 0;
    $soAuditedCount = 0;
    $soMatchedCount = 0;

    $inboundTotal = 0;
    $inboundOnTimeGr = 0;
    $inboundRegistered = 0;

    $avgCapacity = 0;
    $deliveryEffectivenessVal = 0.0;
    $deliveryEfficiencyVal = 0;

    $hasDataInPeriod = false;

    if ($hasPeriod && $monthNumber > 0) {
        // A. Assets Table Calculations (for Slow Moving & Stock Opname)
        $assetWhere = "WHERE (periode_group = ? OR periode_group = ?)";
        $assetParams = [$selectedPeriodGroup, $monthIndoName . ' ' . $year];
        if (!empty($site)) {
            $assetWhere .= " AND so_location = ?";
            $assetParams[] = $site;
        }

        $stmtAsset = $pdo->prepare("
            SELECT 
                COUNT(*) as total_count,
                SUM(CASE 
                    WHEN LOWER(category) LIKE '%slow moving%' 
                      OR LOWER(category) LIKE '%need to utilize%' 
                      OR LOWER(category) LIKE '%re-use%'
                      OR `range` LIKE '%> 1%'
                      OR `range` LIKE '%>1%'
                      OR `range` LIKE '%> 2%'
                      OR `range` LIKE '%>2%'
                      OR `range` LIKE '%> 3%'
                      OR `range` LIKE '%>3%'
                    THEN 1 ELSE 0 END) as slow_moving_count,
                SUM(CASE WHEN so_result IS NOT NULL AND so_result != '' THEN 1 ELSE 0 END) as so_audited,
                SUM(CASE WHEN LOWER(so_result) LIKE '%match%' OR LOWER(so_result) LIKE '%sesuai%' OR LOWER(so_result) LIKE '%ok%' THEN 1 ELSE 0 END) as so_matched
            FROM assets
            $assetWhere
        ");
        $stmtAsset->execute($assetParams);
        $assetSummary = $stmtAsset->fetch(PDO::FETCH_ASSOC);

        if ($assetSummary && (int)$assetSummary['total_count'] > 0) {
            $totalAssetsCount = (int)$assetSummary['total_count'];
            $slowMovingCount = (int)$assetSummary['slow_moving_count'];
            $soAuditedCount = (int)$assetSummary['so_audited'];
            $soMatchedCount = (int)$assetSummary['so_matched'];
            $hasDataInPeriod = true;
        }

        // B. Inbound Table Calculations (for Receiving GR SLA & Registration SLA)
        $stmtInbound = $pdo->prepare("
            SELECT 
                COUNT(*) as total_po,
                SUM(CASE 
                    WHEN po_target_delivery IS NOT NULL AND po_target_delivery >= po_tgl_generate THEN 1 
                    WHEN po_target_delivery IS NULL THEN 1
                    ELSE 0 END) as on_time_gr,
                SUM(CASE WHEN po_qty_item > 0 THEN 1 ELSE 0 END) as registered_count
            FROM inbound_master
            WHERE YEAR(po_tgl_generate) = ? AND MONTH(po_tgl_generate) = ?
        ");
        $stmtInbound->execute([$year, $monthNumber]);
        $inboundSummary = $stmtInbound->fetch(PDO::FETCH_ASSOC);
        if ($inboundSummary && (int)$inboundSummary['total_po'] > 0) {
            $inboundTotal = (int)$inboundSummary['total_po'];
            $inboundOnTimeGr = (int)$inboundSummary['on_time_gr'];
            $inboundRegistered = (int)$inboundSummary['registered_count'];
            $hasDataInPeriod = true;
        }

        // C. Rack Capacity Calculation from rack_utilisasi
        $stmtUtil = $pdo->prepare("
            SELECT AVG(capacity) as avg_cap, SUM(qty) as total_qty
            FROM rack_utilisasi
            WHERE (month = ? OR month = ?) AND year = ? AND capacity > 0
        ");
        $stmtUtil->execute([$month, $monthIndoName, $year]);
        $utilRow = $stmtUtil->fetch(PDO::FETCH_ASSOC);
        if ($utilRow && $utilRow['avg_cap'] !== null && (float)$utilRow['avg_cap'] > 0) {
            $avgCapacity = round((float)$utilRow['avg_cap'], 1);
            $hasDataInPeriod = true;
        }
    }

    // ── Compute Primary KPI Values (0 if no data) ──
    $receivingSlaVal = ($inboundTotal > 0) ? round(($inboundOnTimeGr / $inboundTotal) * 100, 1) : 0.0;
    if ($receivingSlaVal > 100) $receivingSlaVal = 100.0;

    $registrationSlaVal = ($inboundTotal > 0) ? round(($inboundRegistered / $inboundTotal) * 100, 1) : 0.0;
    if ($registrationSlaVal > 100) $registrationSlaVal = 100.0;

    $stockOpnameVal = ($soAuditedCount > 0) ? round(($soMatchedCount / $soAuditedCount) * 100, 1) : 0.0;
    if ($stockOpnameVal > 100) $stockOpnameVal = 100.0;

    $slowMovingVal = ($totalAssetsCount > 0) ? round(($slowMovingCount / $totalAssetsCount) * 100, 1) : 0.0;
    if ($slowMovingVal > 100) $slowMovingVal = 100.0;

    $capacityVal = ($avgCapacity > 0) ? $avgCapacity : 0.0;
    if ($capacityVal > 100) $capacityVal = 100.0;

    // Helper to format status
    function getStatus($hasData, $actual, $target, $isLowerBetter = false) {
        if (!$hasData || $actual == 0) return 'NoData';
        if ($isLowerBetter) {
            return ($actual <= $target) ? 'Achieved' : 'Critical';
        }
        return ($actual >= $target) ? 'Achieved' : 'Critical';
    }

    // Detailed KPI Performance Matrix Items
    $kpiList = [
        [
            'id' => 'receiving_sla',
            'code' => 'KPI-IN-01',
            'name' => 'Receiving (GR) SLA',
            'category' => 'Inbound Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $receivingSlaTarget,
            'target_display' => '≥ 95.0%',
            'actual' => $receivingSlaVal,
            'actual_display' => ($inboundTotal > 0) ? number_format($receivingSlaVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($inboundTotal > 0) ? round(($receivingSlaVal / $receivingSlaTarget) * 100, 1) : 0,
            'status' => getStatus($inboundTotal > 0, $receivingSlaVal, $receivingSlaTarget),
            'description' => 'Ketepatan waktu penerbitan Goods Receipt (GR) terhadap PO masuk sesuai Service Level Agreement (≤ 14 hari).',
            'formula' => '(Jumlah PO Terbit GR Tepat Waktu / Total PO Diterima) × 100%',
            'icon' => 'fa-clipboard-check',
            'color' => '#4e73df'
        ],
        [
            'id' => 'registration_sla',
            'code' => 'KPI-IN-02',
            'name' => 'Registration SLA',
            'category' => 'Inbound Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $registrationSlaTarget,
            'target_display' => '≥ 98.0%',
            'actual' => $registrationSlaVal,
            'actual_display' => ($inboundTotal > 0) ? number_format($registrationSlaVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($inboundTotal > 0) ? round(($registrationSlaVal / $registrationSlaTarget) * 100, 1) : 0,
            'status' => getStatus($inboundTotal > 0, $registrationSlaVal, $registrationSlaTarget),
            'description' => 'Kecepatan dan kepatuhan registrasi serial number & tagging barcode perangkat pasca Goods Receipt (≤ 3 hari kerja).',
            'formula' => '(Jumlah Perangkat Diregistrasi Tepat Waktu / Total Perangkat GR) × 100%',
            'icon' => 'fa-barcode',
            'color' => '#36b9cc'
        ],
        [
            'id' => 'stock_opname',
            'code' => 'KPI-ST-01',
            'name' => 'Stock Opname',
            'category' => 'Storage & Warehouse Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $stockOpnameTarget,
            'target_display' => '≥ 99.5%',
            'actual' => $stockOpnameVal,
            'actual_display' => ($soAuditedCount > 0) ? number_format($stockOpnameVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($soAuditedCount > 0) ? round(($stockOpnameVal / $stockOpnameTarget) * 100, 1) : 0,
            'status' => getStatus($soAuditedCount > 0, $stockOpnameVal, $stockOpnameTarget),
            'description' => 'Akurasi kecocokan fisik inventori perangkat warehouse terhadap pencatatan sistem WMS saat audit berkala.',
            'formula' => '(Jumlah Item Fisik Match Sistem / Total Item Diaudit) × 100%',
            'icon' => 'fa-boxes',
            'color' => '#1cc88a'
        ],
        [
            'id' => 'slow_moving',
            'code' => 'KPI-ST-02',
            'name' => 'Slow Moving',
            'category' => 'Storage & Warehouse Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $slowMovingTarget,
            'target_display' => '≤ 15.0%',
            'actual' => $slowMovingVal,
            'actual_display' => ($totalAssetsCount > 0) ? number_format($slowMovingVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($totalAssetsCount > 0) ? round((1 - max(0, ($slowMovingVal - $slowMovingTarget) / 100)) * 100, 1) : 0,
            'status' => getStatus($totalAssetsCount > 0, $slowMovingVal, $slowMovingTarget, true),
            'description' => 'Rasio perbandingan jumlah item perangkat mengendap/aging > 12 bulan terhadap total keseluruhan inventori.',
            'formula' => '(Total Qty Perangkat Aging > 12 Bulan / Total Qty Inventory on Hand) × 100%',
            'icon' => 'fa-hourglass-half',
            'color' => '#f6c23e'
        ],
        [
            'id' => 'capacity',
            'code' => 'KPI-ST-03',
            'name' => 'Capacity',
            'category' => 'Storage & Warehouse Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $capacityTarget,
            'target_display' => '70.0% - 80.0%',
            'actual' => $capacityVal,
            'actual_display' => ($avgCapacity > 0) ? number_format($capacityVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($avgCapacity > 0) ? round(($capacityVal / $capacityTarget) * 100, 1) : 0,
            'status' => getStatus($avgCapacity > 0, $capacityVal, $capacityTarget),
            'description' => 'Tingkat utilisasi kapasitas ruang penyimpanan rak dan staging area warehouse utama serta HUB regional.',
            'formula' => '(Kapasitas Ruang Terpakai / Total Kapasitas Maksimal Ruang) × 100%',
            'icon' => 'fa-warehouse',
            'color' => '#6f42c1'
        ],
        [
            'id' => 'delivery_effectiveness',
            'code' => 'KPI-OB-01',
            'name' => 'Delivery Effectiveness',
            'category' => 'Outbound Management',
            'unit' => '%',
            'is_currency' => false,
            'target' => $deliveryEffectivenessTarget,
            'target_display' => '≥ 95.0%',
            'actual' => $deliveryEffectivenessVal,
            'actual_display' => ($deliveryEffectivenessVal > 0) ? number_format($deliveryEffectivenessVal, 1, ',', '.') . '%' : '-',
            'achievement' => ($deliveryEffectivenessVal > 0) ? round(($deliveryEffectivenessVal / $deliveryEffectivenessTarget) * 100, 1) : 0,
            'status' => getStatus($deliveryEffectivenessVal > 0, $deliveryEffectivenessVal, $deliveryEffectivenessTarget),
            'description' => 'Efektivitas dan ketepatan pemenuhan Material Request (MR) & Delivery Order (DO) sampai di site tujuan tepat waktu.',
            'formula' => '(Jumlah Pengiriman On-Time & Sempurna / Total Permintaan Pengiriman) × 100%',
            'icon' => 'fa-truck-fast',
            'color' => '#e83e8c'
        ],
        [
            'id' => 'delivery_efficiency',
            'code' => 'KPI-OB-02',
            'name' => 'Efisiensi Delivery',
            'category' => 'Outbound Management',
            'unit' => 'IDR',
            'is_currency' => true,
            'target' => $deliveryEfficiencyTarget,
            'target_display' => 'Rp ' . number_format($deliveryEfficiencyTarget, 0, ',', '.'),
            'actual' => $deliveryEfficiencyVal,
            'actual_display' => ($deliveryEfficiencyVal > 0) ? 'Rp ' . number_format($deliveryEfficiencyVal, 0, ',', '.') : '-',
            'achievement' => ($deliveryEfficiencyVal > 0) ? round(($deliveryEfficiencyVal / $deliveryEfficiencyTarget) * 100, 1) : 0,
            'status' => getStatus($deliveryEfficiencyVal > 0, $deliveryEfficiencyVal, $deliveryEfficiencyTarget),
            'description' => 'Total penghematan biaya logistik pengiriman melalui konsolidasi muatan armada dan optimasi rute regional.',
            'formula' => 'Estimasi Biaya Logistik Standar - Aktual Realisasi Pengeluaran Logistik',
            'icon' => 'fa-money-bill-wave',
            'color' => '#20c997'
        ]
    ];

    echo json_encode([
        'status' => 'success',
        'is_dummy' => false,
        'has_data' => $hasDataInPeriod,
        'period' => [
            'month' => $month,
            'month_indo' => $monthIndoName,
            'year' => $year,
            'group' => $selectedPeriodGroup,
            'site' => $site
        ],
        'cards' => [
            'receiving_sla' => [
                'name' => 'Receiving (GR) SLA',
                'value' => $receivingSlaVal,
                'value_formatted' => ($inboundTotal > 0) ? number_format($receivingSlaVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $receivingSlaTarget,
                'unit' => '%'
            ],
            'registration_sla' => [
                'name' => 'Registration SLA',
                'value' => $registrationSlaVal,
                'value_formatted' => ($inboundTotal > 0) ? number_format($registrationSlaVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $registrationSlaTarget,
                'unit' => '%'
            ],
            'stock_opname' => [
                'name' => 'Stock Opname',
                'value' => $stockOpnameVal,
                'value_formatted' => ($soAuditedCount > 0) ? number_format($stockOpnameVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $stockOpnameTarget,
                'unit' => '%'
            ],
            'slow_moving' => [
                'name' => 'Slow Moving',
                'value' => $slowMovingVal,
                'value_formatted' => ($totalAssetsCount > 0) ? number_format($slowMovingVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $slowMovingTarget,
                'unit' => '%'
            ],
            'capacity' => [
                'name' => 'Capacity',
                'value' => $capacityVal,
                'value_formatted' => ($avgCapacity > 0) ? number_format($capacityVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $capacityTarget,
                'unit' => '%'
            ],
            'delivery_effectiveness' => [
                'name' => 'Delivery Effectiveness',
                'value' => $deliveryEffectivenessVal,
                'value_formatted' => ($deliveryEffectivenessVal > 0) ? number_format($deliveryEffectivenessVal, 1, ',', '.') . '%' : '0.0%',
                'target' => $deliveryEffectivenessTarget,
                'unit' => '%'
            ],
            'delivery_efficiency' => [
                'name' => 'Efisiensi Delivery',
                'value' => $deliveryEfficiencyVal,
                'value_formatted' => ($deliveryEfficiencyVal > 0) ? 'Rp ' . number_format($deliveryEfficiencyVal, 0, ',', '.') : 'Rp 0',
                'target' => $deliveryEfficiencyTarget,
                'unit' => 'IDR'
            ]
        ],
        'kpi_list' => $kpiList
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_kpi_data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while fetching KPI data.']);
}
