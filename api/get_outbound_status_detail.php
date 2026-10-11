<?php
// api/get_outbound_status_detail.php
// Returns summary counts and detail rows for Outbound Status Flow (Alur Pemenuhan MR)
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Ensure table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS outbound_master (
        $idCol,
        mr_no TEXT,
        mr_type TEXT,
        mr_desc TEXT,
        mr_status TEXT,
        pck_no TEXT,
        pck_detail TEXT,
        pck_status TEXT,
        awb TEXT,
        dn_no TEXT,
        pr_no TEXT,
        po_no TEXT,
        origin_from TEXT,
        site_origin TEXT,
        site_origin_addr TEXT,
        destination_to TEXT,
        site_destination TEXT,
        site_destination_addr TEXT,
        pickup_type TEXT,
        via TEXT,
        lt TEXT,
        delivery_target TEXT,
        dn_status TEXT,
        last_log TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    $action = isset($_GET['action']) ? trim($_GET['action']) : 'detail';
    $status = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : 'TOTAL MR';
    $periode = isset($_GET['periode']) ? trim($_GET['periode']) : (isset($_GET['periode_group']) ? trim($_GET['periode_group']) : '');

    // Normalize status names
    if ($status === 'SHIPPED') {
        $status = 'TOTAL SHIPPED';
    }

    // Site Origin filter: Strictly lookup from 50002003289 - GUDANG WAREHOUSE TEKNO
    $siteOriginWhere = "(TRIM(site_origin) = '50002003289 - GUDANG WAREHOUSE TEKNO' OR site_origin LIKE '50002003289%')";

    // Build base period condition
    $periodWhere = "$siteOriginWhere";
    $periodParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $periodWhere = "(periode_group = ? OR periode_group LIKE ?) AND $siteOriginWhere";
        $periodParams = [$periode, $periode . '%'];
    }

    if ($action === 'counts') {
        // Fetch 2026 Monthly Trend for all months (Jan - Des)
        $chartYear = (isset($_GET['year']) && preg_match('/^\d{4}$/', $_GET['year'])) ? trim($_GET['year']) : '2026';

        $sqlMonthly = "
            SELECT 
                CASE 
                    WHEN LOWER(periode_group) LIKE '%january%' OR LOWER(periode_group) LIKE '%januari%' THEN 1
                    WHEN LOWER(periode_group) LIKE '%february%' OR LOWER(periode_group) LIKE '%februari%' THEN 2
                    WHEN LOWER(periode_group) LIKE '%march%' OR LOWER(periode_group) LIKE '%maret%' THEN 3
                    WHEN LOWER(periode_group) LIKE '%april%' THEN 4
                    WHEN LOWER(periode_group) LIKE '%may%' OR LOWER(periode_group) LIKE '%mei%' THEN 5
                    WHEN LOWER(periode_group) LIKE '%june%' OR LOWER(periode_group) LIKE '%juni%' THEN 6
                    WHEN LOWER(periode_group) LIKE '%july%' OR LOWER(periode_group) LIKE '%juli%' THEN 7
                    WHEN LOWER(periode_group) LIKE '%august%' OR LOWER(periode_group) LIKE '%agustus%' THEN 8
                    WHEN LOWER(periode_group) LIKE '%september%' THEN 9
                    WHEN LOWER(periode_group) LIKE '%october%' OR LOWER(periode_group) LIKE '%oktober%' THEN 10
                    WHEN LOWER(periode_group) LIKE '%november%' THEN 11
                    WHEN LOWER(periode_group) LIKE '%december%' OR LOWER(periode_group) LIKE '%desember%' THEN 12
                    ELSE 0
                END as mth,
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' THEN mr_no END) as total_mr,
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' AND UPPER(TRIM(mr_status)) = 'CLOSED' THEN mr_no END) as closed_mr,
                COUNT(CASE WHEN UPPER(TRIM(mr_status)) = 'CLOSED' THEN 1 END) as closed_cnt,
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' AND (
                    UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
                    OR (UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'CANCELED', 'REJECTED'))
                ) THEN mr_no END) as open_mr,
                COUNT(DISTINCT CASE WHEN po_no IS NOT NULL AND TRIM(po_no) != '' THEN po_no END) as total_po,
                COUNT(*) as total_rows
            FROM outbound_master 
            WHERE periode_group LIKE ? AND $siteOriginWhere
            GROUP BY mth
            ORDER BY mth
        ";

        $stmtMth = $pdo->prepare($sqlMonthly);
        $stmtMth->execute(["%$chartYear%"]);
        $mthRows = $stmtMth->fetchAll(PDO::FETCH_ASSOC);

        $mrCounts = array_fill(0, 12, 0);
        $closedCounts = array_fill(0, 12, 0);
        $closedDistinct = array_fill(0, 12, 0);
        $openCounts = array_fill(0, 12, 0);
        $poCounts = array_fill(0, 12, 0);
        $rowCounts = array_fill(0, 12, 0);

        $totalMrYear = 0;
        $totalClosedYear = 0;
        $totalOpenYear = 0;
        $totalPoYear = 0;

        foreach ($mthRows as $r) {
            $m = (int)$r['mth'];
            if ($m >= 1 && $m <= 12) {
                $idx = $m - 1;
                $mrCounts[$idx] = (int)$r['total_mr'];
                $closedCounts[$idx] = (int)$r['closed_cnt'];
                $closedDistinct[$idx] = (int)$r['closed_mr'];
                $openCounts[$idx] = (int)($r['open_mr'] ?? 0);
                $poCounts[$idx] = (int)$r['total_po'];
                $rowCounts[$idx] = (int)$r['total_rows'];

                $totalMrYear += (int)$r['total_mr'];
                $totalClosedYear += (int)$r['closed_cnt'];
                $totalOpenYear += (int)($r['open_mr'] ?? 0);
                $totalPoYear += (int)$r['total_po'];
            }
        }

        $mrPercentages = array_fill(0, 12, 0);
        $closedPercentages = array_fill(0, 12, 0);
        $closeRates = array_fill(0, 12, 0);
        $openPercentages = array_fill(0, 12, 0);
        $openRates = array_fill(0, 12, 0);
        $poPercentages = array_fill(0, 12, 0);

        for ($i = 0; $i < 12; $i++) {
            $mrPercentages[$i] = $totalMrYear > 0 ? round(($mrCounts[$i] / $totalMrYear) * 100, 1) : 0;
            $closedPercentages[$i] = $totalClosedYear > 0 ? round(($closedCounts[$i] / $totalClosedYear) * 100, 1) : 0;
            $closeRates[$i] = $mrCounts[$i] > 0 ? round(($closedDistinct[$i] / $mrCounts[$i]) * 100, 1) : 0;
            $openPercentages[$i] = $totalOpenYear > 0 ? round(($openCounts[$i] / $totalOpenYear) * 100, 1) : 0;
            $openRates[$i] = $mrCounts[$i] > 0 ? round(($openCounts[$i] / $mrCounts[$i]) * 100, 1) : 0;
            $poPercentages[$i] = $totalPoYear > 0 ? round(($poCounts[$i] / $totalPoYear) * 100, 1) : 0;
        }

        // Calculate Moda Counts (Udara, Laut, Darat, Udara PTP - Non duplicate DN NO per origin rule)
        $isPeriodFilter = (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-');
        $modaWhere = $isPeriodFilter ? $periodWhere : "periode_group LIKE ? AND $siteOriginWhere";
        $modaParams = $isPeriodFilter ? $periodParams : ["%$chartYear%"];

        $stmtModa = $pdo->prepare("SELECT 
            COUNT(DISTINCT CASE WHEN UPPER(TRIM(via)) LIKE '%UDARA%' AND UPPER(TRIM(via)) NOT LIKE '%DTP%' AND UPPER(TRIM(via)) NOT LIKE '%PTP%' AND dn_no IS NOT NULL AND TRIM(dn_no) != '' THEN dn_no END) as udara,
            COUNT(DISTINCT CASE WHEN UPPER(TRIM(via)) LIKE '%LAUT%' AND dn_no IS NOT NULL AND TRIM(dn_no) != '' THEN dn_no END) as laut,
            COUNT(DISTINCT CASE WHEN UPPER(TRIM(via)) LIKE '%DARAT%' AND dn_no IS NOT NULL AND TRIM(dn_no) != '' THEN dn_no END) as darat,
            COUNT(DISTINCT CASE WHEN (UPPER(TRIM(via)) LIKE '%DTP%' OR UPPER(TRIM(via)) LIKE '%PTP%') AND dn_no IS NOT NULL AND TRIM(dn_no) != '' THEN dn_no END) as udara_ptp,
            COUNT(DISTINCT CASE WHEN via IS NOT NULL AND TRIM(via) != '' AND dn_no IS NOT NULL AND TRIM(dn_no) != '' THEN dn_no END) as total_moda
        FROM outbound_master WHERE $modaWhere");
        $stmtModa->execute($modaParams);
        $mRow = $stmtModa->fetch(PDO::FETCH_ASSOC) ?: [];
        $tModa = (int)($mRow['total_moda'] ?? 0);

        $modaPercentages = [
            'labels' => ['Udara', 'Laut', 'Darat', 'Udara PTP'],
            'percentages' => [
                $tModa > 0 ? round(((int)($mRow['udara'] ?? 0) / $tModa) * 100, 1) : 0,
                $tModa > 0 ? round(((int)($mRow['laut'] ?? 0) / $tModa) * 100, 1) : 0,
                $tModa > 0 ? round(((int)($mRow['darat'] ?? 0) / $tModa) * 100, 1) : 0,
                $tModa > 0 ? round(((int)($mRow['udara_ptp'] ?? 0) / $tModa) * 100, 1) : 0
            ],
            'counts' => [
                (int)($mRow['udara'] ?? 0),
                (int)($mRow['laut'] ?? 0),
                (int)($mRow['darat'] ?? 0),
                (int)($mRow['udara_ptp'] ?? 0)
            ],
            'total' => $tModa
        ];

        // 5. Tender / Direct Selection Monthly Chart (from outbound_forwarder with origin rule)
        $stmtProc = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN LOWER(periode_group) LIKE '%january%' OR LOWER(periode_group) LIKE '%januari%' THEN 1
                    WHEN LOWER(periode_group) LIKE '%february%' OR LOWER(periode_group) LIKE '%februari%' THEN 2
                    WHEN LOWER(periode_group) LIKE '%march%' OR LOWER(periode_group) LIKE '%maret%' THEN 3
                    WHEN LOWER(periode_group) LIKE '%april%' THEN 4
                    WHEN LOWER(periode_group) LIKE '%may%' OR LOWER(periode_group) LIKE '%mei%' THEN 5
                    WHEN LOWER(periode_group) LIKE '%june%' OR LOWER(periode_group) LIKE '%juni%' THEN 6
                    WHEN LOWER(periode_group) LIKE '%july%' OR LOWER(periode_group) LIKE '%juli%' THEN 7
                    WHEN LOWER(periode_group) LIKE '%august%' OR LOWER(periode_group) LIKE '%agustus%' THEN 8
                    WHEN LOWER(periode_group) LIKE '%september%' THEN 9
                    WHEN LOWER(periode_group) LIKE '%october%' OR LOWER(periode_group) LIKE '%oktober%' THEN 10
                    WHEN LOWER(periode_group) LIKE '%november%' THEN 11
                    WHEN LOWER(periode_group) LIKE '%december%' OR LOWER(periode_group) LIKE '%desember%' THEN 12
                    ELSE 0
                END as mth,
                COUNT(CASE WHEN UPPER(TRIM(proc_vendor_mode)) = 'TENDER' THEN 1 END) as tender_cnt,
                COUNT(CASE WHEN UPPER(TRIM(proc_vendor_mode)) LIKE '%DIRECT%' THEN 1 END) as direct_cnt,
                COUNT(CASE WHEN proc_vendor_mode IS NOT NULL AND TRIM(proc_vendor_mode) != '' THEN 1 END) as total_proc
            FROM outbound_forwarder
            WHERE (asal_code = '50002003289' OR asal_site LIKE '%TEKNO%') AND periode_group LIKE ?
            GROUP BY mth
            ORDER BY mth
        ");
        $stmtProc->execute(["%$chartYear%"]);
        $procRows = $stmtProc->fetchAll(PDO::FETCH_ASSOC);

        $tenderPcts = array_fill(0, 12, 0);
        $directPcts = array_fill(0, 12, 0);
        $tenderCounts = array_fill(0, 12, 0);
        $directCounts = array_fill(0, 12, 0);

        foreach ($procRows as $pr) {
            $m = (int)$pr['mth'];
            if ($m >= 1 && $m <= 12) {
                $idx = $m - 1;
                $tot = (int)$pr['total_proc'];
                $tCnt = (int)$pr['tender_cnt'];
                $dCnt = (int)$pr['direct_cnt'];
                $tenderCounts[$idx] = $tCnt;
                $directCounts[$idx] = $dCnt;
                if ($tot > 0) {
                    $tenderPcts[$idx] = round(($tCnt / $tot) * 100, 1);
                    $directPcts[$idx] = round(($dCnt / $tot) * 100, 1);
                }
            }
        }

        $monthlyCharts = [
            'year' => $chartYear,
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'bulanan_mr' => [
                'percentages' => $mrPercentages,
                'counts' => $mrCounts,
                'total_year' => $totalMrYear
            ],
            'bulanan_po' => [
                'percentages' => $poPercentages,
                'counts' => $poCounts,
                'total_year' => $totalPoYear
            ],
            'close_mr' => [
                'percentages' => $closedPercentages,
                'close_rates' => $closeRates,
                'counts' => $closedCounts,
                'distinct_closed' => $closedDistinct,
                'total_year' => $totalClosedYear
            ],
            'open_mr' => [
                'percentages' => $openPercentages,
                'open_rates' => $openRates,
                'counts' => $openCounts,
                'total_year' => $totalOpenYear
            ],
            'moda_delivery' => $modaPercentages,
            'tender_direct' => [
                'tender_percentages' => $tenderPcts,
                'direct_percentages' => $directPcts,
                'tender_counts' => $tenderCounts,
                'direct_counts' => $directCounts
            ]
        ];

        // If no period is specified and user requested empty default, return 0s for cards but keep 2026 charts
        if (empty($periode) || $periode === 'PILIH PERIODE DATA' || $periode === '-') {
            echo json_encode([
                'status' => 'success',
                'counts' => [
                    'total_mr' => 0,
                    'total_po' => 0,
                    'total_po_price' => 0,
                    'total_po_price_formatted' => 'Rp 0',
                    'saving' => 0,
                    'saving_formatted' => 'Rp 0',
                    'most_cost_delivery_via' => '-',
                    'most_cost_delivery_value' => 0,
                    'most_cost_delivery_formatted' => 'Rp 0',
                    'total_packed' => 0,
                    'total_shipped' => 0,
                    'mr_open' => 0,
                    'total_mr_open' => 0,
                    'dalam_perjalanan' => 0,
                    'tiba_di_lokasi' => 0,
                    'segments' => [
                        'internal_delivery' => 0,
                        'internal_pickup' => 0,
                        'internal_handcarry' => 0,
                        'external_mover' => 0
                    ],
                    'top_sites_mr_open' => [],
                    'most_moda_delivery' => '-',
                    'most_moda_count' => 0,
                    'monthly_charts' => $monthlyCharts
                ]
            ]);
            exit;
        }

        // 1. Total MR = Count data (Kolom NO MR distinct)
        $stmtMr = $pdo->prepare("SELECT COUNT(DISTINCT mr_no) FROM outbound_master WHERE $periodWhere AND mr_no IS NOT NULL AND TRIM(mr_no) != ''");
        $stmtMr->execute($periodParams);
        $totalMr = (int) $stmtMr->fetchColumn();
        
        // 2. Total Packed = count data of PCK NO (distinct pck_no)
        $stmtPck = $pdo->prepare("SELECT COUNT(DISTINCT pck_no) FROM outbound_master WHERE $periodWhere AND pck_no IS NOT NULL AND TRIM(pck_no) != ''");
        $stmtPck->execute($periodParams);
        $totalPacked = (int) $stmtPck->fetchColumn();
        
        // 3. Total Shipped = count data of Shipped (DN NO) Non duplicate from Kolom MR Status Shipped
        $stmtPt = $pdo->prepare("SELECT pickup_type, COUNT(DISTINCT dn_no) as cnt FROM outbound_master WHERE $periodWhere AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'SHIPPED' AND dn_no IS NOT NULL AND TRIM(dn_no) != '' AND pickup_type IS NOT NULL AND TRIM(pickup_type) != '' GROUP BY pickup_type");
        $stmtPt->execute($periodParams);
        $allPickupTypes = $stmtPt->fetchAll(PDO::FETCH_ASSOC);

        $deliveryCount = 0;
        $pickupCount = 0;
        $handcarryCount = 0;
        $moverCount = 0;

        foreach ($allPickupTypes as $pt) {
            $name = trim($pt['pickup_type']);
            $norm = strtolower(str_replace([' ', '-', '_'], '', $name));
            $cnt = (int)$pt['cnt'];

            if (strpos($norm, 'delivery') !== false) {
                $deliveryCount += $cnt;
            } elseif (strpos($norm, 'pickup') !== false) {
                $pickupCount += $cnt;
            } elseif (strpos($norm, 'handcarry') !== false) {
                $handcarryCount += $cnt;
            } elseif (strpos($norm, 'mover') !== false || strpos($norm, 'forwarder') !== false || strpos($norm, 'ekspedisi') !== false || strpos($norm, 'external') !== false) {
                $moverCount += $cnt;
            } else {
                $deliveryCount += $cnt;
            }
        }

        $internalCount = $deliveryCount + $pickupCount + $handcarryCount;
        $externalCount = $moverCount;

        // Distinct overall DN NO count for Total Shipped
        $stmtShippedTotal = $pdo->prepare("SELECT COUNT(DISTINCT dn_no) FROM outbound_master WHERE $periodWhere AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'SHIPPED' AND dn_no IS NOT NULL AND TRIM(dn_no) != ''");
        $stmtShippedTotal->execute($periodParams);
        $totalShipped = (int) $stmtShippedTotal->fetchColumn();

        // 4. MR Open = count of MR Status: Open and 3 condition of Status MR: Delivered, Partially Closed, Shipped (Non duplicate)
        $stmtMrOpen = $pdo->prepare("
            SELECT COUNT(DISTINCT mr_no) 
            FROM outbound_master 
            WHERE $periodWhere 
              AND mr_no IS NOT NULL AND TRIM(mr_no) != ''
              AND (
                  UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
                  OR (UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'CANCELED', 'REJECTED'))
              )
        ");
        $stmtMrOpen->execute($periodParams);
        $mrOpen = (int) $stmtMrOpen->fetchColumn();

        // 5. Total Dalam Perjalanan = Count data (DN Status Shipped + MR Status kecuali CLOSED, REJECTED, FULFILLED)
        $stmtJalan = $pdo->prepare("SELECT COUNT(*) FROM outbound_master WHERE $periodWhere AND UPPER(TRIM(COALESCE(dn_status, ''))) = 'SHIPPED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'REJECTED', 'FULFILLED')");
        $stmtJalan->execute($periodParams);
        $dalamPerjalanan = (int) $stmtJalan->fetchColumn();

        // 6. Total Tiba Di Lokasi = Count data (MR Status Closed + DN Status Delivered)
        $stmtTiba = $pdo->prepare("SELECT COUNT(*) FROM outbound_master WHERE $periodWhere AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'CLOSED' AND UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED'");
        $stmtTiba->execute($periodParams);
        $tibaLokasi = (int) $stmtTiba->fetchColumn();

        // 7. Most Moda Delivery = Sum data terbanyak di VIA
        $stmtVia = $pdo->prepare("SELECT via, COUNT(*) as cnt FROM outbound_master WHERE $periodWhere AND via IS NOT NULL AND TRIM(via) != '' GROUP BY via ORDER BY cnt DESC LIMIT 1");
        $stmtVia->execute($periodParams);
        $mostViaRow = $stmtVia->fetch(PDO::FETCH_ASSOC);
        $mostModa = $mostViaRow ? trim($mostViaRow['via']) : '-';
        $mostModaCount = $mostViaRow ? (int)$mostViaRow['cnt'] : 0;

        // Site MR Open:
        // count of MR Status: Open dan 3 condition Status MR: Delivered, Partially Closed, Shipped
        $stmtTopSites = $pdo->prepare("
            SELECT 
                site_destination, 
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' THEN mr_no END) as distinct_mr,
                COUNT(*) as total_mr
            FROM outbound_master 
            WHERE $periodWhere 
              AND site_destination IS NOT NULL AND TRIM(site_destination) != '' 
              AND (
                  UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
                  OR (UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'CANCELED', 'REJECTED'))
              )
            GROUP BY site_destination 
            ORDER BY distinct_mr DESC, total_mr DESC, site_destination ASC 
            LIMIT 10
        ");
        $stmtTopSites->execute($periodParams);
        $topSites = $stmtTopSites->fetchAll(PDO::FETCH_ASSOC);

        // Total PO = Count distinct NO PO for site origin
        $stmtPo = $pdo->prepare("SELECT COUNT(DISTINCT po_no) FROM outbound_master WHERE $periodWhere AND po_no IS NOT NULL AND TRIM(po_no) != ''");
        $stmtPo->execute($periodParams);
        $totalPo = (int) $stmtPo->fetchColumn();

        // 8. Outbound Forwarder metrics: Total PO Price, Saving (Valuation Price - PO Price), Most Cost Delivery
        $fwdPeriodWhere = "(asal_code = '50002003289' OR asal_site LIKE '%TEKNO%')";
        $fwdParams = [];
        if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
            $fwdPeriodWhere .= " AND (periode_group = ? OR periode_group LIKE ?)";
            $fwdParams = [$periode, $periode . '%'];
        }

        $stmtFwdTotals = $pdo->prepare("
            SELECT 
                SUM(CASE 
                    WHEN po_price ~ '^[0-9]+(\.[0-9]+)?$' THEN po_price::numeric 
                    WHEN po_tgl ~ '^[0-9]+(\.[0-9]+)?$' THEN po_tgl::numeric 
                    ELSE 0 
                END) as total_po_price,
                SUM(CASE 
                    WHEN valuation_price ~ '^[0-9]+(\.[0-9]+)?$' THEN valuation_price::numeric 
                    ELSE 0 
                END) as total_val_price
            FROM outbound_forwarder
            WHERE $fwdPeriodWhere
        ");
        $stmtFwdTotals->execute($fwdParams);
        $fwdRow = $stmtFwdTotals->fetch(PDO::FETCH_ASSOC);

        $totalPoPrice = (float)($fwdRow['total_po_price'] ?? 0);
        $totalValPrice = (float)($fwdRow['total_val_price'] ?? 0);
        $saving = $totalValPrice - $totalPoPrice;

        // Most Cost Delivery (Filter Via, SUM PO Price. Diambil nilai tertinggi dari VIA)
        $stmtMostCost = $pdo->prepare("
            SELECT 
                COALESCE(NULLIF(TRIM(delivery_via), ''), NULLIF(TRIM(delivery_type), ''), '-') as via_name,
                SUM(CASE 
                    WHEN po_price ~ '^[0-9]+(\.[0-9]+)?$' THEN po_price::numeric 
                    WHEN po_tgl ~ '^[0-9]+(\.[0-9]+)?$' THEN po_tgl::numeric 
                    ELSE 0 
                END) as cost_sum
            FROM outbound_forwarder
            WHERE $fwdPeriodWhere AND (
                (delivery_via IS NOT NULL AND TRIM(delivery_via) != '') OR 
                (delivery_type IS NOT NULL AND TRIM(delivery_type) != '')
            )
            GROUP BY via_name
            ORDER BY cost_sum DESC
            LIMIT 1
        ");
        $stmtMostCost->execute($fwdParams);
        $mostCostRow = $stmtMostCost->fetch(PDO::FETCH_ASSOC);
        $mostCostVia = $mostCostRow ? trim((string)$mostCostRow['via_name']) : '-';
        $mostCostVal = $mostCostRow ? (float)$mostCostRow['cost_sum'] : 0;

        echo json_encode([
            'status' => 'success',
            'counts' => [
                'total_mr' => $totalMr,
                'total_po' => $totalPo,
                'total_po_price' => $totalPoPrice,
                'total_po_price_formatted' => 'Rp ' . number_format($totalPoPrice, 0, ',', '.'),
                'saving' => $saving,
                'saving_formatted' => ($saving < 0 ? '-Rp ' : 'Rp ') . number_format(abs($saving), 0, ',', '.'),
                'most_cost_delivery_via' => $mostCostVia,
                'most_cost_delivery_value' => $mostCostVal,
                'most_cost_delivery_formatted' => 'Rp ' . number_format($mostCostVal, 0, ',', '.'),
                'total_packed' => $totalPacked,
                'total_shipped' => $totalShipped,
                'mr_open' => $mrOpen,
                'total_mr_open' => $mrOpen,
                'dalam_perjalanan' => $dalamPerjalanan,
                'tiba_di_lokasi' => $tibaLokasi,
                'segments' => [
                    'internal_delivery' => $deliveryCount,
                    'internal_pickup' => $pickupCount,
                    'internal_handcarry' => $handcarryCount,
                    'external_mover' => $moverCount
                ],
                'top_sites_mr_open' => $topSites,
                'most_moda_delivery' => $mostModa,
                'most_moda_count' => $mostModaCount,
                'monthly_charts' => $monthlyCharts
            ]
        ]);
        exit;
    }

    // Detail Action: fetch rows for the selected status
    if (empty($periode) || $periode === 'PILIH PERIODE DATA' || $periode === '-') {
        echo json_encode([
            'status' => 'success',
            'requested_status' => $status,
            'count' => 0,
            'data' => []
        ]);
        exit;
    }

    $where = $periodWhere;
    $params = $periodParams;

    if ($status === 'TOTAL PACKED') {
        $where .= " AND pck_no IS NOT NULL AND TRIM(pck_no) != ''";
    } elseif ($status === 'TOTAL SHIPPED') {
        $where .= " AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'SHIPPED'";
    } elseif ($status === 'MR OPEN' || $status === 'TOTAL MR OPEN') {
        $where .= " AND mr_no IS NOT NULL AND TRIM(mr_no) != '' AND (
            UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
            OR (UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'CANCELED', 'REJECTED'))
        )";
    } elseif ($status === 'DALAM PERJALANAN') {
        $where .= " AND UPPER(TRIM(COALESCE(dn_status, ''))) = 'SHIPPED' AND UPPER(TRIM(COALESCE(mr_status, ''))) NOT IN ('CLOSED', 'REJECTED', 'FULFILLED')";
    } elseif ($status === 'TIBA DI LOKASI') {
        $where .= " AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'CLOSED' AND UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED'";
    }

    $stmt = $pdo->prepare("SELECT mr_no, mr_type, mr_desc, mr_status, pck_no, pck_detail, pck_status, awb, dn_no, pr_no, po_no, origin_from, site_origin, site_origin_addr, destination_to, site_destination, site_destination_addr, pickup_type, via, lt, delivery_target, dn_status, last_log FROM outbound_master WHERE $where ORDER BY id DESC LIMIT 1500");
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format rows according to the requested status headers (non-duplicate where applicable)
    $formattedRows = [];
    $seenKeys = [];
    foreach ($rawRows as $r) {
        $userVal = !empty($r['origin_from']) ? $r['origin_from'] : (!empty($r['site_origin']) ? $r['site_origin'] : '-');
        $tujuanVal = !empty($r['site_destination']) ? $r['site_destination'] : (!empty($r['destination_to']) ? $r['destination_to'] : '-');

        if ($status === 'TOTAL MR') {
            $mrNo = trim((string)($r['mr_no'] ?? ''));
            if ($mrNo !== '' && isset($seenKeys[$mrNo])) {
                continue;
            }
            if ($mrNo !== '') $seenKeys[$mrNo] = true;

            $pickupBy = !empty($r['pickup_type']) ? $r['pickup_type'] : (!empty($r['via']) ? $r['via'] : '-');
            $ketVal = !empty($r['mr_desc']) ? $r['mr_desc'] : (!empty($r['mr_status']) ? $r['mr_status'] : (!empty($r['dn_status']) ? $r['dn_status'] : '-'));
            $formattedRows[] = [
                'no_mr'     => $r['mr_no'] ?: '-',
                'user'      => $userVal,
                'tujuan'    => $tujuanVal,
                'pickup_by' => $pickupBy,
                'ket'       => $ketVal
            ];
            if (count($formattedRows) >= 500) break;
        } elseif ($status === 'TOTAL PACKED') {
            $pckNo = trim((string)($r['pck_no'] ?? ''));
            if ($pckNo !== '' && isset($seenKeys[$pckNo])) {
                continue;
            }
            if ($pckNo !== '') $seenKeys[$pckNo] = true;

            $formattedRows[] = [
                'no_pck'     => $r['pck_no'] ?: '-',
                'pck_detail' => $r['pck_detail'] ?: '-',
                'user'       => $userVal,
                'tujuan'     => $tujuanVal,
                'no_mr'      => $r['mr_no'] ?: '-',
                'no_dn'      => $r['dn_no'] ?: '-'
            ];
            if (count($formattedRows) >= 500) break;
        } elseif ($status === 'TOTAL SHIPPED') {
            $dnNo = trim((string)($r['dn_no'] ?? ''));
            if ($dnNo !== '' && isset($seenKeys[$dnNo])) {
                continue;
            }
            if ($dnNo !== '') $seenKeys[$dnNo] = true;

            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'pickup_type'     => $r['pickup_type'] ?: '-',
                'via'             => $r['via'] ?: '-',
                'lt'              => $r['lt'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-',
                'last_log'        => $r['last_log'] ?: '-'
            ];
            if (count($formattedRows) >= 500) break;
        } elseif ($status === 'MR OPEN' || $status === 'TOTAL MR OPEN') {
            $mrNo = trim((string)($r['mr_no'] ?? ''));
            if ($mrNo !== '' && isset($seenKeys[$mrNo])) {
                continue;
            }
            if ($mrNo !== '') $seenKeys[$mrNo] = true;

            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'status_mr'       => $r['mr_status'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'status_dn'       => $r['dn_status'] ?: '-',
                'pickup_type'     => $r['pickup_type'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-'
            ];
            if (count($formattedRows) >= 500) break;
        } elseif ($status === 'DALAM PERJALANAN' || $status === 'TIBA DI LOKASI') {
            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'status_mr'       => $r['mr_status'] ?: '-',
                'lt'              => $r['lt'] ?: '-',
                'status_dn'       => $r['dn_status'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-',
                'last_log'        => $r['last_log'] ?: '-'
            ];
            if (count($formattedRows) >= 500) break;
        }
    }

    echo json_encode([
        'status' => 'success',
        'requested_status' => $status,
        'count' => count($formattedRows),
        'data' => $formattedRows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
