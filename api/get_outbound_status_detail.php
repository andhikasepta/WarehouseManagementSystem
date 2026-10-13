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

/**
 * Flush buffered JSON output to the client and persist it as a cache file.
 */
function wmsSaveCountsCache($cacheDir, $cacheFile)
{
    $json = ob_get_clean();
    echo $json;
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    if (is_dir($cacheDir) && is_writable($cacheDir)) {
        @file_put_contents($cacheFile, $json, LOCK_EX);
    }
}

if (!function_exists('parsePriceFloat')) {
    function parsePriceFloat($val)
    {
        if ($val === null || $val === '') return 0.0;
        if (is_numeric($val)) return (float)$val;
        $str = trim((string)$val);
        $cleaned = preg_replace('/[^\d.,-]/', '', $str);
        if ($cleaned === '' || $cleaned === '-') return 0.0;

        if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (strpos($cleaned, '.') !== false) {
            if (substr_count($cleaned, '.') > 1) {
                $cleaned = str_replace('.', '', $cleaned);
            } elseif (preg_match('/^\d+\.\d{3}$/', $cleaned)) {
                $cleaned = str_replace('.', '', $cleaned);
            }
        } elseif (strpos($cleaned, ',') !== false) {
            if (substr_count($cleaned, ',') > 1) {
                $cleaned = str_replace(',', '', $cleaned);
            } elseif (preg_match('/^\d+,\d{3}$/', $cleaned)) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                $cleaned = str_replace(',', '.', $cleaned);
            }
        }
        return (float)$cleaned;
    }
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
    $periode = trim(preg_replace('/\s*\((?:ALL\s+)?BATCH(?:\s+ALL)?\)\s*$/i', '', $periode));

    $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

    // Normalize status names
    if ($status === 'SHIPPED') {
        $status = 'TOTAL SHIPPED';
    }

    // Site Origin filter: Strictly lookup from 50002003289 - GUDANG WAREHOUSE TEKNO
    $siteOriginWhere = "(TRIM(site_origin) = '50002003289 - GUDANG WAREHOUSE TEKNO' OR site_origin $likeOp '50002003289%')";

    // Build base period condition
    $periodWhere = "$siteOriginWhere";
    $periodParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $periodWhere = "(periode_group = ? OR periode_group $likeOp ?) AND $siteOriginWhere";
        $periodParams = [$periode, $periode . '%'];
    }

    if ($action === 'counts') {
        // Monthly trend year: explicit ?year=, else year of the selected period, else current year
        if (isset($_GET['year']) && preg_match('/^\d{4}$/', $_GET['year'])) {
            $chartYear = trim($_GET['year']);
        } elseif (!empty($periode) && preg_match('/(20\d{2})/', $periode, $ym)) {
            $chartYear = $ym[1];
        } else {
            $chartYear = date('Y');
        }

        // Server-side response cache (cleared by clearWmsBackendCache() on every write)
        $cacheDir = __DIR__ . '/../backend/cache';
        $cacheFile = $cacheDir . '/outbound_counts_' . md5($periode . '|' . $chartYear) . '.json';
        if (!isset($_GET['nocache']) && is_file($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
            $cachedContent = @file_get_contents($cacheFile);
            if ($cachedContent && strpos($cachedContent, '"counts"') !== false) {
                echo $cachedContent;
                exit;
            }
        }
        ob_start();

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
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' AND UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED') THEN mr_no END) as open_mr,
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

        // Count distinct PO NO on pr forwarder per month for the monthly PO chart
        try {
            $stmtMthFwdPo = $pdo->prepare("
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
                    COUNT(DISTINCT CASE 
                        WHEN sr_no IS NOT NULL AND TRIM(sr_no) != '' AND TRIM(sr_no) != '-' THEN
                            CASE 
                                WHEN purpose IS NOT NULL AND TRIM(purpose) != '' AND TRIM(purpose) != '-' THEN TRIM(purpose)
                                WHEN po_no IS NOT NULL AND TRIM(po_no) != '' AND TRIM(po_no) != '-' THEN TRIM(po_no)
                            END
                    END) as fwd_po_cnt
                FROM outbound_forwarder
                WHERE periode_group LIKE ?
                GROUP BY mth
            ");
            $stmtMthFwdPo->execute(["%$chartYear%"]);
            $fwdMthPoRows = $stmtMthFwdPo->fetchAll(PDO::FETCH_ASSOC);
            $hasFwdPoData = false;
            foreach ($fwdMthPoRows as $fr) {
                $m = (int)$fr['mth'];
                if ($m >= 1 && $m <= 12) {
                    $idx = $m - 1;
                    $fCnt = (int)$fr['fwd_po_cnt'];
                    if ($fCnt > 0) {
                        $poCounts[$idx] = $fCnt;
                        $hasFwdPoData = true;
                    }
                }
            }
            if ($hasFwdPoData) {
                $totalPoYear = array_sum($poCounts);
            }
        } catch (Exception $e) {}

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
                    'mr_closed' => 0,
                    'mr_closed_percent' => 0,
                    'total_fulfilled' => 0,
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
            wmsSaveCountsCache($cacheDir, $cacheFile);
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

        // 4. MR Open = count of MR Status: Open, Delivered, Partially Closed, Shipped (Non duplicate)
        $stmtMrOpen = $pdo->prepare("
            SELECT COUNT(DISTINCT mr_no) 
            FROM outbound_master 
            WHERE $periodWhere 
              AND mr_no IS NOT NULL AND TRIM(mr_no) != ''
              AND UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
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

        // 7. Most Moda Delivery = Sum data terbanyak di VIA (Darat, Udara, Laut, etc.)
        $stmtVia = $pdo->prepare("SELECT UPPER(TRIM(via)) as via_clean, COUNT(*) as cnt FROM outbound_master WHERE $periodWhere AND via IS NOT NULL AND TRIM(via) != '' AND UPPER(TRIM(via)) NOT IN ('-', 'N/A', 'NULL', 'NONE') GROUP BY via_clean ORDER BY cnt DESC LIMIT 1");
        $stmtVia->execute($periodParams);
        $mostViaRow = $stmtVia->fetch(PDO::FETCH_ASSOC);
        $mostModa = $mostViaRow ? trim($mostViaRow['via_clean']) : '-';
        $mostModaCount = $mostViaRow ? (int)$mostViaRow['cnt'] : 0;

        // Site MR Open:
        // count of MR Status: Open, Delivered, Partially Closed, Shipped
        $stmtTopSites = $pdo->prepare("
            SELECT 
                site_destination, 
                COUNT(DISTINCT CASE WHEN mr_no IS NOT NULL AND TRIM(mr_no) != '' THEN mr_no END) as distinct_mr,
                COUNT(*) as total_mr
            FROM outbound_master 
            WHERE $periodWhere 
              AND site_destination IS NOT NULL AND TRIM(site_destination) != '' 
              AND UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')
            GROUP BY site_destination 
            ORDER BY distinct_mr DESC, total_mr DESC, site_destination ASC 
            LIMIT 10
        ");
        $stmtTopSites->execute($periodParams);
        $topSites = $stmtTopSites->fetchAll(PDO::FETCH_ASSOC);

        // 8. Outbound Forwarder metrics: Total PO Mover, Total PO Price, Saving (Valuation Price - PO Price), Most Cost Delivery
        // "for saving is lookup from PO NO on pr forwarder that as 1 not duplicated that have Procurement SR NO that will be counted ofr (Valuation Price Purpose-PO Price) if the SR is empty don't count it"
        $fwdPeriodWhere = "1=1";
        $fwdParams = [];
        if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
            $fwdPeriodWhere = "(periode_group = ? OR periode_group $likeOp ?)";
            $fwdParams = [$periode, $periode . '%'];
        }

        // Scope to Tekno warehouse origin if Tekno records exist for this period
        try {
            $checkTekno = $pdo->prepare("SELECT 1 FROM outbound_forwarder WHERE $fwdPeriodWhere AND (asal_code = '50002003289' OR asal_site $likeOp '%TEKNO%') LIMIT 1");
            $checkTekno->execute($fwdParams);
            if ($checkTekno->fetchColumn()) {
                $fwdPeriodWhere .= " AND (asal_code = '50002003289' OR asal_site $likeOp '%TEKNO%')";
            }
        } catch (Exception $e) {}

        $stmtFwdRows = $pdo->prepare("
            SELECT 
                no_dn,
                sr_no,
                pr_no,
                po_no,
                purpose,
                COALESCE(NULLIF(TRIM(po_vendor), ''), NULLIF(TRIM(proc_vendor_name), ''), '-') as po_vendor,
                po_price,
                raw_data->>'po_tgl' as raw_po_tgl,
                valuation_price,
                suggestion,
                tujuan_site,
                delivery_via,
                delivery_type
            FROM outbound_forwarder
            WHERE $fwdPeriodWhere
            ORDER BY id ASC
        ");
        $stmtFwdRows->execute($fwdParams);
        $allFwdRows = $stmtFwdRows->fetchAll(PDO::FETCH_ASSOC);

        $distinctPos = [];
        $viaSums = [];

        foreach ($allFwdRows as $fr) {
            // Determine real PO NO
            $pPurpose = trim((string)($fr['purpose'] ?? ''));
            $pPoNo = trim((string)($fr['po_no'] ?? ''));
            $rawPoNo = '';
            if ($pPurpose !== '' && (stripos($pPurpose, 'PO') !== false || strpos($pPurpose, '/') !== false)) {
                $rawPoNo = $pPurpose;
            } elseif ($pPoNo !== '' && stripos($pPoNo, 'PO') !== false) {
                $rawPoNo = $pPoNo;
            } elseif ($pPurpose !== '' && $pPurpose !== '-') {
                $rawPoNo = $pPurpose;
            } elseif ($pPoNo !== '' && $pPoNo !== '-') {
                $rawPoNo = $pPoNo;
            }

            if ($rawPoNo === '' || $rawPoNo === '-') continue;

            $valPrice = parsePriceFloat($fr['valuation_price'] ?? 0);
            if ($valPrice <= 0 && !empty($fr['suggestion'])) {
                $valPrice = parsePriceFloat($fr['suggestion']);
            }

            // Determine real PO Price: check po_price column or raw_data po_tgl
            $poPrice = 0.0;
            if (isset($fr['po_price']) && is_numeric($fr['po_price'])) {
                $poPrice = (float)$fr['po_price'];
            } elseif (!empty($fr['raw_po_tgl']) && is_numeric($fr['raw_po_tgl'])) {
                $poPrice = (float)$fr['raw_po_tgl'];
            } else {
                $poPrice = parsePriceFloat($fr['po_price'] ?? 0);
                if ($poPrice == 0 && !empty($fr['raw_po_tgl'])) {
                    $poPrice = parsePriceFloat($fr['raw_po_tgl']);
                }
            }

            $rawSrNo = trim((string)($fr['sr_no'] ?? ''));

            // Vendor: if po_price in column is string vendor name while po_vendor is numeric excel date
            $vendor = $fr['po_vendor'];
            if ((is_numeric($vendor) || $vendor === '-') && !empty($fr['po_price']) && !is_numeric($fr['po_price'])) {
                $vendor = $fr['po_price'];
            }

            // Via cost aggregation
            $viaName = trim((string)($fr['delivery_via'] ?: ($fr['delivery_type'] ?: '-')));
            if ($viaName !== '' && $viaName !== '-') {
                if (!isset($viaSums[$viaName])) $viaSums[$viaName] = 0;
                $viaSums[$viaName] += $poPrice;
            }

            if (!isset($distinctPos[$rawPoNo])) {
                $distinctPos[$rawPoNo] = [
                    'po_no' => $rawPoNo,
                    'sr_no' => ($rawSrNo !== '' && $rawSrNo !== '-') ? $rawSrNo : '',
                    'no_dn' => $fr['no_dn'] ?: '-',
                    'pr_no' => $fr['pr_no'] ?: '-',
                    'po_vendor' => $vendor ?: '-',
                    'tujuan_site' => $fr['tujuan_site'] ?: '-',
                    'delivery_via' => $fr['delivery_via'] ?: '-',
                    'po_price' => $poPrice,
                    'valuation_price' => $valPrice
                ];
            } else {
                if (empty($distinctPos[$rawPoNo]['sr_no']) && $rawSrNo !== '' && $rawSrNo !== '-') {
                    $distinctPos[$rawPoNo]['sr_no'] = $rawSrNo;
                }
                if ($distinctPos[$rawPoNo]['po_price'] == 0 && $poPrice > 0) {
                    $distinctPos[$rawPoNo]['po_price'] = $poPrice;
                }
                if ($distinctPos[$rawPoNo]['valuation_price'] == 0 && $valPrice > 0) {
                    $distinctPos[$rawPoNo]['valuation_price'] = $valPrice;
                }
            }
        }

        // Rule user: "untuk outbound total PO Price card itu sum dari PO yang punya no SR. begitu juga total PO card"
        $distinctPosWithSr = [];
        foreach ($distinctPos as $poKey => $poData) {
            if (!empty($poData['sr_no'])) {
                $distinctPosWithSr[$poKey] = $poData;
            }
        }

        $totalPo = count($distinctPosWithSr);
        $totalSaving = 0.0;
        $totalPoPrice = 0.0;

        foreach ($distinctPosWithSr as $poKey => $poData) {
            $totalPoPrice += $poData['po_price'];
            // Hanya PO dengan valuation_price > 0 yang dihitung selisih saving-nya agar tidak menjadi minus jika valuation kosong
            if ($poData['valuation_price'] > 0) {
                $sav = $poData['valuation_price'] - $poData['po_price'];
                $totalSaving += $sav;
            }
        }

        $saving = $totalSaving;

        // Most Cost Delivery (Diambil nilai tertinggi dari VIA)
        $mostCostVia = '-';
        $mostCostVal = 0.0;
        if (!empty($viaSums)) {
            arsort($viaSums);
            $mostCostVia = key($viaSums);
            $mostCostVal = current($viaSums);
        }

        // Distinct closed MR count & percentage for MR CLOSED card
        $stmtClosedMr = $pdo->prepare("SELECT COUNT(DISTINCT mr_no) FROM outbound_master WHERE $periodWhere AND mr_no IS NOT NULL AND TRIM(mr_no) != '' AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'CLOSED'");
        $stmtClosedMr->execute($periodParams);
        $mrClosed = (int) $stmtClosedMr->fetchColumn();
        $mrClosedPercent = $totalMr > 0 ? round(($mrClosed / $totalMr) * 100, 1) : 0;
        $totalFulfilled = $tibaLokasi > 0 ? $tibaLokasi : $mrClosed;

        echo json_encode([
            'status' => 'success',
            'counts' => [
                'total_mr' => $totalMr,
                'total_po' => $totalPo,
                'total_po_mover' => $totalPo,
                'total_po_price' => $totalPoPrice,
                'total_po_price_formatted' => 'Rp ' . number_format($totalPoPrice, 0, ',', '.'),
                'saving' => $saving,
                'saving_formatted' => ($saving < 0 ? '-Rp ' : 'Rp ') . number_format(abs($saving), 0, ',', '.'),
                'most_cost_delivery_via' => $mostCostVia,
                'most_cost_delivery_value' => $mostCostVal,
                'most_cost_delivery_formatted' => 'Rp ' . number_format($mostCostVal, 0, ',', '.'),
                'total_packed' => $totalPacked,
                'total_shipped' => $totalShipped,
                'mr_closed' => $mrClosed,
                'mr_closed_percent' => $mrClosedPercent,
                'total_fulfilled' => $totalFulfilled,
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
        wmsSaveCountsCache($cacheDir, $cacheFile);
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

    $fwdPeriodWhere = "1=1";
    $fwdParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $fwdPeriodWhere = "(periode_group = ? OR periode_group $likeOp ?)";
        $fwdParams = [$periode, $periode . '%'];
    }

    try {
        $checkTekno = $pdo->prepare("SELECT 1 FROM outbound_forwarder WHERE $fwdPeriodWhere AND (asal_code = '50002003289' OR asal_site $likeOp '%TEKNO%') LIMIT 1");
        $checkTekno->execute($fwdParams);
        if ($checkTekno->fetchColumn()) {
            $fwdPeriodWhere .= " AND (asal_code = '50002003289' OR asal_site $likeOp '%TEKNO%')";
        }
    } catch (Exception $e) {}

    if ($status === 'TOTAL PO MOVER' || $status === 'NILAI PO MOVER' || $status === 'SAVING PO MOVER' || $status === 'SAVING' || $status === 'TOTAL PO' || $status === 'TOTAL PO PRICE') {
        $stmtFwd = $pdo->prepare("
            SELECT 
                no_dn, sr_no, pr_no, po_no, purpose,
                COALESCE(NULLIF(TRIM(po_vendor), ''), NULLIF(TRIM(proc_vendor_name), ''), '-') as po_vendor, 
                po_price, raw_data->>'po_tgl' as raw_po_tgl,
                valuation_price, suggestion, tujuan_site, delivery_via 
            FROM outbound_forwarder 
            WHERE $fwdPeriodWhere 
            ORDER BY id ASC
        ");
        $stmtFwd->execute($fwdParams);
        $fwdRows = $stmtFwd->fetchAll(PDO::FETCH_ASSOC);

        $distinctPos = [];
        foreach ($fwdRows as $fr) {
            // Determine real PO NO
            $pPurpose = trim((string)($fr['purpose'] ?? ''));
            $pPoNo = trim((string)($fr['po_no'] ?? ''));
            $rawPoNo = '';
            if ($pPurpose !== '' && (stripos($pPurpose, 'PO') !== false || strpos($pPurpose, '/') !== false)) {
                $rawPoNo = $pPurpose;
            } elseif ($pPoNo !== '' && stripos($pPoNo, 'PO') !== false) {
                $rawPoNo = $pPoNo;
            } elseif ($pPurpose !== '' && $pPurpose !== '-') {
                $rawPoNo = $pPurpose;
            } elseif ($pPoNo !== '' && $pPoNo !== '-') {
                $rawPoNo = $pPoNo;
            }

            if ($rawPoNo === '' || $rawPoNo === '-') continue;

            $valPrice = parsePriceFloat($fr['valuation_price'] ?? 0);
            if ($valPrice <= 0 && !empty($fr['suggestion'])) {
                $valPrice = parsePriceFloat($fr['suggestion']);
            }

            // Determine real PO Price
            $poPrice = 0.0;
            if (isset($fr['po_price']) && is_numeric($fr['po_price'])) {
                $poPrice = (float)$fr['po_price'];
            } elseif (!empty($fr['raw_po_tgl']) && is_numeric($fr['raw_po_tgl'])) {
                $poPrice = (float)$fr['raw_po_tgl'];
            } else {
                $poPrice = parsePriceFloat($fr['po_price'] ?? 0);
                if ($poPrice == 0 && !empty($fr['raw_po_tgl'])) {
                    $poPrice = parsePriceFloat($fr['raw_po_tgl']);
                }
            }

            $rawSrNo = trim((string)($fr['sr_no'] ?? ''));

            // Vendor resolution
            $vendor = $fr['po_vendor'];
            if ((is_numeric($vendor) || $vendor === '-') && !empty($fr['po_price']) && !is_numeric($fr['po_price'])) {
                $vendor = $fr['po_price'];
            }

            if (!isset($distinctPos[$rawPoNo])) {
                $distinctPos[$rawPoNo] = [
                    'po_no' => $rawPoNo,
                    'sr_no' => ($rawSrNo !== '' && $rawSrNo !== '-') ? $rawSrNo : '',
                    'no_dn' => $fr['no_dn'] ?: '-',
                    'pr_no' => $fr['pr_no'] ?: '-',
                    'po_vendor' => $vendor ?: '-',
                    'tujuan_site' => $fr['tujuan_site'] ?: '-',
                    'delivery_via' => $fr['delivery_via'] ?: '-',
                    'po_price' => $poPrice,
                    'valuation_price' => $valPrice
                ];
            } else {
                if (empty($distinctPos[$rawPoNo]['sr_no']) && $rawSrNo !== '' && $rawSrNo !== '-') {
                    $distinctPos[$rawPoNo]['sr_no'] = $rawSrNo;
                }
                if ($distinctPos[$rawPoNo]['po_price'] == 0 && $poPrice > 0) {
                    $distinctPos[$rawPoNo]['po_price'] = $poPrice;
                }
                if ($distinctPos[$rawPoNo]['valuation_price'] == 0 && $valPrice > 0) {
                    $distinctPos[$rawPoNo]['valuation_price'] = $valPrice;
                }
            }
        }

        $formattedRows = [];
        foreach ($distinctPos as $poKey => $poData) {
            // Rule: Untuk outbound TOTAL PO, TOTAL PO PRICE, dan SAVING hanya PO yang punya no SR
            if (empty($poData['sr_no'])) {
                continue;
            }

            $hasVal = ($poData['valuation_price'] > 0);
            $sav = $hasVal ? ($poData['valuation_price'] - $poData['po_price']) : null;
            $formattedRows[] = [
                'no_dn' => $poData['no_dn'],
                'sr_no' => $poData['sr_no'] ?: '-',
                'pr_no' => $poData['pr_no'],
                'po_no' => $poData['po_no'],
                'po_vendor' => $poData['po_vendor'],
                'tujuan_site' => $poData['tujuan_site'],
                'delivery_via' => $poData['delivery_via'],
                'po_price_formatted' => ($poData['po_price'] > 0) ? ('Rp ' . number_format($poData['po_price'], 0, ',', '.')) : 'Rp 0',
                'val_price_formatted' => $hasVal ? ('Rp ' . number_format($poData['valuation_price'], 0, ',', '.')) : '-',
                'saving_formatted' => ($sav !== null) ? (($sav < 0 ? '-Rp ' : 'Rp ') . number_format(abs($sav), 0, ',', '.')) : '-'
            ];
            if (count($formattedRows) >= 2500) break;
        }

        echo json_encode([
            'status' => 'success',
            'requested_status' => $status,
            'count' => count($formattedRows),
            'data' => $formattedRows
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
        $where .= " AND mr_no IS NOT NULL AND TRIM(mr_no) != '' AND UPPER(TRIM(mr_status)) IN ('OPEN', 'DELIVERED', 'PARTIALLY CLOSED', 'SHIPPED')";
    } elseif ($status === 'MR CLOSED') {
        $where .= " AND mr_no IS NOT NULL AND TRIM(mr_no) != '' AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'CLOSED'";
    } elseif ($status === 'FULFILLED') {
        $where .= " AND UPPER(TRIM(COALESCE(mr_status, ''))) = 'CLOSED' AND UPPER(TRIM(COALESCE(dn_status, ''))) = 'DELIVERED'";
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

        if ($status === 'TOTAL MR' || $status === 'MR CLOSED') {
            $mrNo = trim((string)($r['mr_no'] ?? ''));
            if ($mrNo !== '' && isset($seenKeys[$mrNo])) {
                continue;
            }
            if ($mrNo !== '') $seenKeys[$mrNo] = true;

            $pickupBy = !empty($r['pickup_type']) ? $r['pickup_type'] : (!empty($r['via']) ? $r['via'] : '-');
            $ketVal = !empty($r['mr_desc']) ? $r['mr_desc'] : (!empty($r['mr_status']) ? $r['mr_status'] : (!empty($r['dn_status']) ? $r['dn_status'] : '-'));
            $formattedRows[] = [
                'no_mr'           => $r['mr_no'] ?: '-',
                'user'            => $userVal,
                'tujuan'          => $tujuanVal,
                'pickup_by'       => $pickupBy,
                'ket'             => $ketVal,
                'status_mr'       => $r['mr_status'] ?: '-',
                'no_dn'           => $r['dn_no'] ?: '-',
                'status_dn'       => $r['dn_status'] ?: '-',
                'pickup_type'     => $r['pickup_type'] ?: '-',
                'delivery_target' => $r['delivery_target'] ?: '-'
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
        } elseif ($status === 'FULFILLED' || $status === 'DALAM PERJALANAN' || $status === 'TIBA DI LOKASI') {
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
