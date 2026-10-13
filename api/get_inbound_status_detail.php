<?php
// api/get_inbound_status_detail.php
// Returns summary counts and detail rows for Inbound Status Flow
@header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    @http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Ensure table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS inbound_master (
        $idCol,
        pr_nomor TEXT,
        pr_kode_site TEXT,
        pr_nama_site TEXT,
        pr_item_kategori TEXT,
        pr_pic_teknis_nama TEXT,
        pr_nama_bagian TEXT,
        pr_nama_divisi TEXT,
        pr_regional TEXT,
        pr_jenis_ma TEXT,
        po_nomor TEXT,
        po_deskripsi TEXT,
        po_vendor TEXT,
        po_tgl_generate DATE,
        po_nama_item TEXT,
        po_qty_item DECIMAL(15,2) DEFAULT 0,
        po_uom_item TEXT,
        po_target_delivery DATE,
        project_id TEXT,
        periode_group TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);

    $action = isset($_GET['action']) ? trim($_GET['action']) : 'detail';
    $status = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : 'TOTAL PO INBOUND';
    $periode = isset($_GET['periode']) ? trim($_GET['periode']) : (isset($_GET['periode_group']) ? trim($_GET['periode_group']) : '');
    $periode = trim(preg_replace('/\s*\((?:ALL\s+)?BATCH(?:\s+ALL)?\)\s*$/i', '', $periode));

    $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

    // Normalize status names
    $statusMap = [
        'TOTAL PO' => 'TOTAL PO INBOUND',
        'TOTAL INBOUND' => 'TOTAL PO INBOUND',
        'TOTAL PO ONTIME' => 'PO ONTIME DELIVERY',
        'ONTIME' => 'PO ONTIME DELIVERY',
        'TOTAL PO TERLAMBAT' => 'PO TERLAMBAT DELIVERY',
        'TERLAMBAT' => 'PO TERLAMBAT DELIVERY',
        'TOTAL PENERIMAAN (GR)' => 'TOTAL GR',
    ];
    if (isset($statusMap[$status])) {
        $status = $statusMap[$status];
    }

    // Build base period condition
    $periodWhere = "1=1";
    $periodParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $periodWhere = "(inbound_master.periode_group = ? OR inbound_master.periode_group $likeOp ?)";
        $periodParams = [$periode, $periode . '%'];
    }

    // Match condition for PO SUDAH GR:
    // Cross-table lookup: PO No exists in inbound_gr with a valid GR No
    $grMatchSql = "EXISTS (
        SELECT 1 FROM inbound_gr g 
        WHERE (
            (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor))
            OR (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND (TRIM(COALESCE(inbound_master.pr_nomor, '')) = '' OR TRIM(COALESCE(g.pr_no, '')) = ''))
            OR (TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor) AND TRIM(COALESCE(inbound_master.pr_nomor, '')) != '')
        )
        AND g.gr_no IS NOT NULL 
        AND TRIM(g.gr_no) != '' 
        AND TRIM(g.gr_no) != '-'
    )";

    // Match condition for PO SUDAH REGISTRASI:
    // Cross-table lookup: PO No exists in inbound_gr with a valid Registration No (no_reg)
    $regMatchSql = "EXISTS (
        SELECT 1 FROM inbound_gr g 
        WHERE (
            (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor))
            OR (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND (TRIM(COALESCE(inbound_master.pr_nomor, '')) = '' OR TRIM(COALESCE(g.pr_no, '')) = ''))
            OR (TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor) AND TRIM(COALESCE(inbound_master.pr_nomor, '')) != '')
        )
        AND g.no_reg IS NOT NULL 
        AND TRIM(g.no_reg) != '' 
        AND TRIM(g.no_reg) != '-'
    )";

    if ($action === 'counts') {
        // 1. Total PO Inbound: Count PO NO non-duplicate yang ada di kedua tabel (inbound_master & inbound_gr)
        $stmtPo = $pdo->prepare("SELECT COUNT(DISTINCT inbound_master.po_nomor) 
            FROM inbound_master 
            INNER JOIN inbound_gr g ON TRIM(inbound_master.po_nomor) = TRIM(g.po_no)
            WHERE $periodWhere 
              AND inbound_master.po_nomor IS NOT NULL 
              AND TRIM(inbound_master.po_nomor) != ''");
        $stmtPo->execute($periodParams);
        $totalPoInbound = (int) $stmtPo->fetchColumn();

        // 2 & 3. PO Ontime and Terlambat Delivery:
        // Lookup same PO NO on both PR to PO (inbound_master) and DATA GR (inbound_gr).
        // If PO target delivery on PR to PO table < GR Date on DATA GR table counted as PO Ontime, else is PO Terlambat.
        // PO NO not duplicated.
        $stmtOntimeLate = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN target_delivery IS NOT NULL AND gr_date IS NOT NULL AND target_delivery < gr_date THEN 1 END) as ontime_cnt,
                COUNT(CASE WHEN target_delivery IS NULL OR gr_date IS NULL OR target_delivery >= gr_date THEN 1 END) as late_cnt
            FROM (
                SELECT 
                    inbound_master.po_nomor,
                    MAX(inbound_master.po_target_delivery) as target_delivery,
                    MIN(g.gr_date) as gr_date
                FROM inbound_master
                INNER JOIN inbound_gr g ON TRIM(inbound_master.po_nomor) = TRIM(g.po_no)
                WHERE $periodWhere 
                  AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
                GROUP BY inbound_master.po_nomor
            ) sub
        ");
        $stmtOntimeLate->execute($periodParams);
        $rowOL = $stmtOntimeLate->fetch(PDO::FETCH_ASSOC) ?: [];
        $poOntime = (int)($rowOL['ontime_cnt'] ?? 0);
        $poTerlambat = (int)($rowOL['late_cnt'] ?? 0);

        // 4. PO Sudah GR: Lookup ke PO No di master Data GR kalau No PO itu udah ada No GRnya
        $stmtGr = $pdo->prepare("SELECT COUNT(DISTINCT inbound_master.po_nomor) 
            FROM inbound_master 
            WHERE $periodWhere 
              AND inbound_master.po_nomor IS NOT NULL 
              AND TRIM(inbound_master.po_nomor) != '' 
              AND $grMatchSql");
        $stmtGr->execute($periodParams);
        $poSudahGr = (int) $stmtGr->fetchColumn();

        // 5. PO Sudah Registrasi: Lookup ke PO No di master Data GR kalau No PO itu udah ada No Registnya
        $stmtReg = $pdo->prepare("SELECT COUNT(DISTINCT inbound_master.po_nomor) 
            FROM inbound_master 
            WHERE $periodWhere 
              AND inbound_master.po_nomor IS NOT NULL 
              AND TRIM(inbound_master.po_nomor) != '' 
              AND $regMatchSql");
        $stmtReg->execute($periodParams);
        $poSudahReg = (int) $stmtReg->fetchColumn();

        // 6. GR Non PO: records without standard PO number in inbound_master
        $stmtNonPo = $pdo->prepare("SELECT COUNT(*) 
            FROM inbound_master 
            WHERE $periodWhere 
              AND (inbound_master.po_nomor IS NULL OR TRIM(inbound_master.po_nomor) = '')");
        $stmtNonPo->execute($periodParams);
        $grNonPo = (int) $stmtNonPo->fetchColumn();

        // 7. Total GR : Count of GR NO setelah PO NO compare ada di kedua table (inbound_master dan inbound_gr)
        $stmtTotalGr = $pdo->prepare("SELECT COUNT(DISTINCT g.gr_no) 
            FROM inbound_gr g 
            INNER JOIN inbound_master ON TRIM(g.po_no) = TRIM(inbound_master.po_nomor)
            WHERE $periodWhere 
              AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
              AND g.gr_no IS NOT NULL AND TRIM(g.gr_no) != '' AND TRIM(g.gr_no) != '-'");
        $stmtTotalGr->execute($periodParams);
        $totalGr = (int) $stmtTotalGr->fetchColumn();

        // 8. Total Registrasi : Count of No Registrasi setelah PO NO compare ada di kedua table (inbound_master dan inbound_gr)
        $stmtTotalReg = $pdo->prepare("SELECT COUNT(DISTINCT g.no_reg) 
            FROM inbound_gr g 
            INNER JOIN inbound_master ON TRIM(g.po_no) = TRIM(inbound_master.po_nomor)
            WHERE $periodWhere 
              AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
              AND g.no_reg IS NOT NULL AND TRIM(g.no_reg) != '' AND TRIM(g.no_reg) != '-'");
        $stmtTotalReg->execute($periodParams);
        $totalRegistrasi = (int) $stmtTotalReg->fetchColumn();

        // Department breakdown for Bar Chart
        $stmtDept = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(inbound_master.pr_nama_bagian), ''), 'OTHER') as dept_name, 
            COUNT(DISTINCT CASE WHEN $grMatchSql THEN inbound_master.po_nomor END) as sudah_gr,
            COUNT(DISTINCT CASE WHEN NOT ($grMatchSql) THEN inbound_master.po_nomor END) as belum_gr
            FROM inbound_master 
            WHERE $periodWhere AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
            GROUP BY dept_name
            ORDER BY sudah_gr DESC
            LIMIT 10");
        $stmtDept->execute($periodParams);
        $deptRows = $stmtDept->fetchAll(PDO::FETCH_ASSOC);

        $deptLabels = [];
        $deptSudahGr = [];
        $deptBelumGr = [];
        foreach ($deptRows as $dr) {
            $deptLabels[] = $dr['dept_name'];
            $deptSudahGr[] = (int) $dr['sudah_gr'];
            $deptBelumGr[] = (int) $dr['belum_gr'];
        }

        // Monthly GR Trend
        $ontimeMonthly = array_fill(0, 12, 0);
        $terlambatMonthly = array_fill(0, 12, 0);

        $stmtTrend = $pdo->prepare("SELECT 
            " . ($driver === 'pgsql' ? "EXTRACT(MONTH FROM gr_date)" : "MONTH(gr_date)") . " as mth,
            COUNT(CASE WHEN target_delivery IS NOT NULL AND gr_date IS NOT NULL AND target_delivery < gr_date THEN 1 END) as ontime_cnt,
            COUNT(CASE WHEN target_delivery IS NULL OR gr_date IS NULL OR target_delivery >= gr_date THEN 1 END) as late_cnt
            FROM (
                SELECT 
                    inbound_master.po_nomor,
                    MAX(inbound_master.po_target_delivery) as target_delivery,
                    MIN(g.gr_date) as gr_date
                FROM inbound_master
                INNER JOIN inbound_gr g ON TRIM(inbound_master.po_nomor) = TRIM(g.po_no)
                WHERE $periodWhere 
                  AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
                  AND g.gr_date IS NOT NULL
                GROUP BY inbound_master.po_nomor
            ) sub
            GROUP BY mth
        ");
        $stmtTrend->execute($periodParams);
        $trendRows = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);
        foreach ($trendRows as $tr) {
            $mIdx = ((int) $tr['mth']) - 1;
            if ($mIdx >= 0 && $mIdx < 12) {
                $ontimeMonthly[$mIdx] = (int) $tr['ontime_cnt'];
                $terlambatMonthly[$mIdx] = (int) $tr['late_cnt'];
            }
        }

        echo json_encode([
            'status' => 'success',
            'counts' => [
                'total_po_inbound' => $totalPoInbound,
                'po_ontime_delivery' => $poOntime,
                'po_terlambat_delivery' => $poTerlambat,
                'po_sudah_gr' => $poSudahGr,
                'po_sudah_registrasi' => $poSudahReg,
                'gr_non_po' => $grNonPo,
                'total_gr' => $totalGr,
                'total_registrasi' => $totalRegistrasi,
                'dept_chart' => [
                    'labels' => $deptLabels,
                    'sudah_gr' => $deptSudahGr,
                    'belum_gr' => $deptBelumGr
                ],
                'trend_chart' => [
                    'ontime' => $ontimeMonthly,
                    'terlambat' => $terlambatMonthly
                ]
            ]
        ]);
        exit;
    }

    // Detail Action: fetch rows for the selected status
    if ($status === 'TOTAL GR') {
        $stmt = $pdo->prepare("SELECT 
            g.gr_no,
            MAX(g.po_no) as no_po,
            MAX(COALESCE(NULLIF(TRIM(inbound_master.po_deskripsi), ''), NULLIF(TRIM(inbound_master.po_nama_item), ''), g.product_name)) as deskripsi_po,
            MAX(inbound_master.pr_pic_teknis_nama) as pic_asset_planner,
            MAX(COALESCE(NULLIF(TRIM(inbound_master.pr_nama_bagian), ''), inbound_master.pr_nama_divisi)) as department,
            MAX(COALESCE(NULLIF(TRIM(g.pic_submit_gr), ''), g.vendor_name, inbound_master.po_vendor)) as pic_gr,
            MAX(COALESCE(NULLIF(TRIM(g.qty), ''), '1')) as qty,
            MAX(COALESCE(NULLIF(TRIM(g.uom), ''), 'Unit')) as uom,
            MAX(COALESCE(g.gr_date, inbound_master.po_target_delivery)) as target_delivery
        FROM inbound_gr g
        INNER JOIN inbound_master ON TRIM(inbound_master.po_nomor) = TRIM(g.po_no)
        WHERE $periodWhere
          AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
          AND g.gr_no IS NOT NULL AND TRIM(g.gr_no) != '' AND TRIM(g.gr_no) != '-'
        GROUP BY g.gr_no
        ORDER BY g.gr_no DESC
        LIMIT 500");
        $stmt->execute($periodParams);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedRows = [];
        foreach ($rawRows as $r) {
            $desc = !empty($r['deskripsi_po']) ? $r['deskripsi_po'] : '-';
            $pic = !empty($r['pic_asset_planner']) ? $r['pic_asset_planner'] : '-';
            $dept = !empty($r['department']) ? $r['department'] : '-';
            $qtyDisp = !empty($r['qty']) ? ($r['qty'] . ' ' . ($r['uom'] ?? 'Unit')) : '-';
            $formattedRows[] = [
                'no_gr'              => $r['gr_no'] ?: '-',
                'no_po'              => $r['no_po'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_gr'             => !empty($r['pic_gr']) ? $r['pic_gr'] : '-',
                'qty'                => $qtyDisp,
                'target_delivery'    => $r['target_delivery'] ?: '-'
            ];
        }

        echo json_encode([
            'status' => 'success',
            'requested_status' => $status,
            'count' => count($formattedRows),
            'data' => $formattedRows
        ]);
        exit;
    }

    if ($status === 'TOTAL REGISTRASI') {
        $stmt = $pdo->prepare("SELECT 
            g.no_reg,
            g.po_no as no_po,
            COALESCE(NULLIF(TRIM(inbound_master.po_deskripsi), ''), NULLIF(TRIM(inbound_master.po_nama_item), ''), g.product_name) as deskripsi_po,
            inbound_master.pr_pic_teknis_nama as pic_asset_planner,
            COALESCE(NULLIF(TRIM(inbound_master.pr_nama_bagian), ''), inbound_master.pr_nama_divisi) as department,
            COALESCE(NULLIF(TRIM(g.pic_registration), ''), g.pic_submit_gr, g.vendor_name, inbound_master.po_vendor) as pic_registrasi,
            COALESCE(NULLIF(TRIM(g.qty), ''), '1') as qty,
            COALESCE(NULLIF(TRIM(g.uom), ''), 'Unit') as uom,
            COALESCE(g.artis_date, g.iips_date, g.gr_date, inbound_master.po_target_delivery) as target_delivery
        FROM inbound_gr g
        INNER JOIN inbound_master ON TRIM(inbound_master.po_nomor) = TRIM(g.po_no)
        WHERE $periodWhere
          AND inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != ''
          AND g.no_reg IS NOT NULL AND TRIM(g.no_reg) != '' AND TRIM(g.no_reg) != '-'
        ORDER BY g.id DESC
        LIMIT 500");
        $stmt->execute($periodParams);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedRows = [];
        foreach ($rawRows as $r) {
            $desc = !empty($r['deskripsi_po']) ? $r['deskripsi_po'] : '-';
            $pic = !empty($r['pic_asset_planner']) ? $r['pic_asset_planner'] : '-';
            $dept = !empty($r['department']) ? $r['department'] : '-';
            $qtyDisp = !empty($r['qty']) ? ($r['qty'] . ' ' . ($r['uom'] ?? 'Unit')) : '-';
            $formattedRows[] = [
                'no_reg'             => $r['no_reg'] ?: '-',
                'no_po'              => $r['no_po'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_registrasi'     => !empty($r['pic_registrasi']) ? $r['pic_registrasi'] : '-',
                'qty'                => $qtyDisp,
                'target_delivery'    => $r['target_delivery'] ?: '-'
            ];
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

    if ($status === 'PO ONTIME DELIVERY') {
        $where .= " AND (inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != '') AND inbound_master.po_nomor IN (
            SELECT m2.po_nomor
            FROM inbound_master m2
            INNER JOIN inbound_gr g2 ON TRIM(m2.po_nomor) = TRIM(g2.po_no)
            WHERE $periodWhere AND m2.po_nomor IS NOT NULL AND TRIM(m2.po_nomor) != ''
            GROUP BY m2.po_nomor
            HAVING MAX(m2.po_target_delivery) IS NOT NULL AND MIN(g2.gr_date) IS NOT NULL AND MAX(m2.po_target_delivery) < MIN(g2.gr_date)
        )";
        $params = array_merge($params, $periodParams);
    } elseif ($status === 'PO TERLAMBAT DELIVERY') {
        $where .= " AND (inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != '') AND inbound_master.po_nomor IN (
            SELECT m2.po_nomor
            FROM inbound_master m2
            INNER JOIN inbound_gr g2 ON TRIM(m2.po_nomor) = TRIM(g2.po_no)
            WHERE $periodWhere AND m2.po_nomor IS NOT NULL AND TRIM(m2.po_nomor) != ''
            GROUP BY m2.po_nomor
            HAVING MAX(m2.po_target_delivery) IS NULL OR MIN(g2.gr_date) IS NULL OR MAX(m2.po_target_delivery) >= MIN(g2.gr_date)
        )";
        $params = array_merge($params, $periodParams);
    } elseif ($status === 'PO SUDAH GR') {
        $where .= " AND (inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != '') AND $grMatchSql";
    } elseif ($status === 'PO SUDAH REGISTRASI') {
        $where .= " AND (inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != '') AND $regMatchSql";
    } elseif ($status === 'GR NON PO') {
        $where .= " AND (inbound_master.po_nomor IS NULL OR TRIM(inbound_master.po_nomor) = '')";
    } else {
        // TOTAL PO INBOUND: Match PO NO di inbound_master dan inbound_gr
        $where .= " AND (inbound_master.po_nomor IS NOT NULL AND TRIM(inbound_master.po_nomor) != '') AND EXISTS (
            SELECT 1 FROM inbound_gr g_match WHERE TRIM(g_match.po_no) = TRIM(inbound_master.po_nomor)
        )";
    }

    $isGroupedPo = ($status !== 'GR NON PO');

    if ($isGroupedPo) {
        $isGrStatus = ($status === 'PO SUDAH GR');
        $isRegStatus = ($status === 'PO SUDAH REGISTRASI');
        $isOntimeOrLate = ($status === 'PO ONTIME DELIVERY' || $status === 'PO TERLAMBAT DELIVERY');
        $joinGr = ($isGrStatus || $isRegStatus) ? "LEFT JOIN inbound_gr g ON (
            (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor))
            OR (TRIM(g.po_no) = TRIM(inbound_master.po_nomor) AND (TRIM(COALESCE(inbound_master.pr_nomor, '')) = '' OR TRIM(COALESCE(g.pr_no, '')) = ''))
            OR (TRIM(g.pr_no) = TRIM(inbound_master.pr_nomor) AND TRIM(COALESCE(inbound_master.pr_nomor, '')) != '')
        ) AND " . ($isGrStatus ? "(g.gr_no IS NOT NULL AND TRIM(g.gr_no) != '' AND TRIM(g.gr_no) != '-')" : "(g.no_reg IS NOT NULL AND TRIM(g.no_reg) != '' AND TRIM(g.no_reg) != '-')") : ($isOntimeOrLate ? "LEFT JOIN inbound_gr g ON TRIM(g.po_no) = TRIM(inbound_master.po_nomor)" : "");

        $selectGrCols = "";
        if ($isGrStatus) {
            $selectGrCols = ", MAX(g.gr_no) as gr_no, MAX(g.pic_submit_gr) as pic_submit_gr, MAX(g.vendor_name) as gr_vendor, NULL as no_reg, NULL as pic_registration, NULL as gr_date";
        } elseif ($isRegStatus) {
            $selectGrCols = ", NULL as gr_no, NULL as pic_submit_gr, NULL as gr_vendor, MAX(g.no_reg) as no_reg, MAX(g.pic_registration) as pic_registration, NULL as gr_date";
        } elseif ($isOntimeOrLate) {
            $selectGrCols = ", MAX(g.gr_no) as gr_no, NULL as pic_submit_gr, NULL as gr_vendor, NULL as no_reg, NULL as pic_registration, MIN(g.gr_date) as gr_date";
        } else {
            $selectGrCols = ", NULL as gr_no, NULL as pic_submit_gr, NULL as gr_vendor, NULL as no_reg, NULL as pic_registration, NULL as gr_date";
        }

        $stmt = $pdo->prepare("SELECT 
            inbound_master.po_nomor,
            MAX(COALESCE(NULLIF(TRIM(inbound_master.po_deskripsi), ''), inbound_master.po_nama_item)) as po_deskripsi,
            MAX(inbound_master.pr_pic_teknis_nama) as pr_pic_teknis_nama,
            MAX(COALESCE(NULLIF(TRIM(inbound_master.pr_nama_bagian), ''), inbound_master.pr_nama_divisi)) as pr_nama_bagian,
            MAX(inbound_master.po_vendor) as po_vendor,
            SUM(COALESCE(inbound_master.po_qty_item, 0)) as po_qty_item,
            MAX(inbound_master.po_uom_item) as po_uom_item,
            MAX(inbound_master.po_tgl_generate) as po_tgl_generate,
            MAX(inbound_master.po_target_delivery) as po_target_delivery,
            MAX(inbound_master.periode_group) as periode_group
            $selectGrCols
        FROM inbound_master 
        $joinGr
        WHERE $where 
        GROUP BY inbound_master.po_nomor 
        ORDER BY MAX(inbound_master.id) DESC 
        LIMIT 500");
        $stmt->execute($params);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, pr_nomor, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama, pr_nama_bagian, pr_nama_divisi, po_nomor, po_deskripsi, po_vendor, po_tgl_generate, po_nama_item, po_qty_item, po_uom_item, po_target_delivery, project_id, periode_group FROM inbound_master WHERE $where ORDER BY id DESC LIMIT 500");
        $stmt->execute($params);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Format rows according to the requested status
    $formattedRows = [];
    foreach ($rawRows as $r) {
        $desc = !empty($r['po_deskripsi']) ? $r['po_deskripsi'] : (!empty($r['po_nama_item']) ? $r['po_nama_item'] : '-');
        $pic = !empty($r['pr_pic_teknis_nama']) ? $r['pr_pic_teknis_nama'] : '-';
        $dept = !empty($r['pr_nama_bagian']) ? $r['pr_nama_bagian'] : (!empty($r['pr_nama_divisi']) ? $r['pr_nama_divisi'] : '-');
        $vendor = !empty($r['po_vendor']) ? $r['po_vendor'] : '-';
        $qtyVal = (float)($r['po_qty_item'] ?? 0);
        $formattedQty = ($qtyVal == floor($qtyVal)) ? number_format($qtyVal, 0, ',', '.') : number_format($qtyVal, 2, ',', '.');
        $qtyStr = ($r['po_qty_item'] !== null) ? $formattedQty . ' ' . ($r['po_uom_item'] ?: 'Unit') : '-';

        if ($status === 'GR NON PO') {
            $formattedRows[] = [
                'deskripsi_perangkat' => $desc,
                'pic_asset_planner'   => $pic,
                'department'          => $dept,
                'vendor'              => $vendor,
                'qty'                 => $qtyStr
            ];
        } elseif ($status === 'PO SUDAH GR') {
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'no_gr'              => !empty($r['gr_no']) ? $r['gr_no'] : '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_gr'             => !empty($r['pic_submit_gr']) ? $r['pic_submit_gr'] : (!empty($r['gr_vendor']) ? $r['gr_vendor'] : $vendor),
                'qty'                => $qtyStr,
                'tgl_generate'       => $r['po_tgl_generate'] ?: '-',
                'target_delivery'    => $r['po_target_delivery'] ?: '-'
            ];
        } elseif ($status === 'PO SUDAH REGISTRASI') {
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'no_reg'             => !empty($r['no_reg']) ? $r['no_reg'] : '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_registrasi'     => !empty($r['pic_registration']) ? $r['pic_registration'] : $pic,
                'qty'                => $qtyStr,
                'tgl_generate'       => $r['po_tgl_generate'] ?: '-',
                'target_delivery'    => $r['po_target_delivery'] ?: '-'
            ];
        } elseif ($status === 'PO ONTIME DELIVERY' || $status === 'PO TERLAMBAT DELIVERY') {
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'vendor'             => $vendor,
                'qty'                => $qtyStr,
                'target_delivery'    => $r['po_target_delivery'] ?: '-',
                'gr_date'            => !empty($r['gr_date']) ? $r['gr_date'] : '-'
            ];
        } else {
            // TOTAL PO INBOUND
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'vendor'             => $vendor,
                'qty'                => $qtyStr,
                'tgl_generate'       => $r['po_tgl_generate'] ?: '-',
                'target_delivery'    => $r['po_target_delivery'] ?: '-'
            ];
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
