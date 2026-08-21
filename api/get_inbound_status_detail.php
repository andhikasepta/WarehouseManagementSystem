<?php
// api/get_inbound_status_detail.php
// Returns summary counts and detail rows for Inbound Status Flow
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

    // Normalize status names
    $statusMap = [
        'TOTAL PO' => 'TOTAL PO INBOUND',
        'TOTAL INBOUND' => 'TOTAL PO INBOUND',
        'TOTAL PO ONTIME' => 'PO ONTIME DELIVERY',
        'ONTIME' => 'PO ONTIME DELIVERY',
        'TOTAL PO TERLAMBAT' => 'PO TERLAMBAT DELIVERY',
        'TERLAMBAT' => 'PO TERLAMBAT DELIVERY',
        'TOTAL PENERIMAAN (GR)' => 'PO SUDAH GR',
        'TOTAL GR' => 'PO SUDAH GR',
        'TOTAL REGISTRASI' => 'PO SUDAH REGISTRASI'
    ];
    if (isset($statusMap[$status])) {
        $status = $statusMap[$status];
    }

    // Build base period condition
    $periodWhere = "1=1";
    $periodParams = [];
    if (!empty($periode) && $periode !== 'PILIH PERIODE DATA' && $periode !== '-') {
        $periodWhere = "(periode_group = ? OR periode_group LIKE ?)";
        $periodParams = [$periode, $periode . '%'];
    }

    if ($action === 'counts') {
        // If no period is specified and user requested empty default, return 0s
        if (empty($periode) || $periode === 'PILIH PERIODE DATA' || $periode === '-') {
            echo json_encode([
                'status' => 'success',
                'counts' => [
                    'total_po_inbound' => 0,
                    'po_ontime_delivery' => 0,
                    'po_terlambat_delivery' => 0,
                    'po_sudah_gr' => 0,
                    'po_sudah_registrasi' => 0,
                    'gr_non_po' => 0,
                    'total_gr' => 0,
                    'total_registrasi' => 0,
                    'dept_chart' => [
                        'labels' => [],
                        'sudah_gr' => [],
                        'belum_gr' => []
                    ],
                    'trend_chart' => [
                        'ontime' => array_fill(0, 12, 0),
                        'terlambat' => array_fill(0, 12, 0)
                    ]
                ]
            ]);
            exit;
        }

        // 1. Total PO Inbound (distinct PO numbers)
        $stmtPo = $pdo->prepare("SELECT COUNT(DISTINCT po_nomor) FROM inbound_master WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != ''");
        $stmtPo->execute($periodParams);
        $totalPoInbound = (int) $stmtPo->fetchColumn();

        // 2. Ontime Delivery: target delivery >= tgl generate or target delivery is null
        $stmtOntime = $pdo->prepare("SELECT COUNT(DISTINCT po_nomor) FROM inbound_master WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != '' AND (po_target_delivery >= po_tgl_generate OR po_target_delivery IS NULL)");
        $stmtOntime->execute($periodParams);
        $poOntime = (int) $stmtOntime->fetchColumn();

        // 3. Terlambat Delivery: target delivery < tgl generate
        $stmtLate = $pdo->prepare("SELECT COUNT(DISTINCT po_nomor) FROM inbound_master WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != '' AND (po_target_delivery < po_tgl_generate)");
        $stmtLate->execute($periodParams);
        $poTerlambat = (int) $stmtLate->fetchColumn();

        // 4. Sudah GR: po_qty_item > 0
        $stmtGr = $pdo->prepare("SELECT COUNT(DISTINCT po_nomor) FROM inbound_master WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != '' AND po_qty_item > 0");
        $stmtGr->execute($periodParams);
        $poSudahGr = (int) $stmtGr->fetchColumn();

        // 5. Sudah Registrasi: po_qty_item > 0
        $stmtReg = $pdo->prepare("SELECT COUNT(DISTINCT po_nomor) FROM inbound_master WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != '' AND po_qty_item > 0");
        $stmtReg->execute($periodParams);
        $poSudahReg = (int) $stmtReg->fetchColumn();

        // 6. GR Non PO: records without standard PO number
        $stmtNonPo = $pdo->prepare("SELECT COUNT(*) FROM inbound_master WHERE $periodWhere AND (po_nomor IS NULL OR TRIM(po_nomor) = '')");
        $stmtNonPo->execute($periodParams);
        $grNonPo = (int) $stmtNonPo->fetchColumn();

        $totalGr = $poSudahGr + $grNonPo;
        $totalRegistrasi = $poSudahReg;

        // Department breakdown for Bar Chart
        $stmtDept = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(pr_nama_bagian), ''), 'OTHER') as dept_name, 
            COUNT(DISTINCT CASE WHEN po_qty_item > 0 THEN po_nomor END) as sudah_gr,
            COUNT(DISTINCT CASE WHEN po_qty_item <= 0 OR po_qty_item IS NULL THEN po_nomor END) as belum_gr
            FROM inbound_master 
            WHERE $periodWhere AND po_nomor IS NOT NULL AND TRIM(po_nomor) != ''
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
            " . ($driver === 'pgsql' ? "EXTRACT(MONTH FROM po_tgl_generate)" : "MONTH(po_tgl_generate)") . " as mth,
            COUNT(DISTINCT CASE WHEN po_target_delivery >= po_tgl_generate OR po_target_delivery IS NULL THEN po_nomor END) as ontime_cnt,
            COUNT(DISTINCT CASE WHEN po_target_delivery < po_tgl_generate THEN po_nomor END) as late_cnt
            FROM inbound_master
            WHERE $periodWhere AND po_tgl_generate IS NOT NULL
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

    if ($status === 'PO ONTIME DELIVERY') {
        $where .= " AND (po_nomor IS NOT NULL AND TRIM(po_nomor) != '') AND (po_target_delivery >= po_tgl_generate OR po_target_delivery IS NULL)";
    } elseif ($status === 'PO TERLAMBAT DELIVERY') {
        $where .= " AND (po_nomor IS NOT NULL AND TRIM(po_nomor) != '') AND (po_target_delivery < po_tgl_generate)";
    } elseif ($status === 'PO SUDAH GR' || $status === 'TOTAL GR') {
        $where .= " AND (po_nomor IS NOT NULL AND TRIM(po_nomor) != '') AND po_qty_item > 0";
    } elseif ($status === 'PO SUDAH REGISTRASI' || $status === 'TOTAL REGISTRASI') {
        $where .= " AND (po_nomor IS NOT NULL AND TRIM(po_nomor) != '') AND po_qty_item > 0";
    } elseif ($status === 'GR NON PO') {
        $where .= " AND (po_nomor IS NULL OR TRIM(po_nomor) = '')";
    } else {
        // TOTAL PO INBOUND
        $where .= " AND (po_nomor IS NOT NULL AND TRIM(po_nomor) != '')";
    }

    $stmt = $pdo->prepare("SELECT id, pr_nomor, pr_nama_site, pr_item_kategori, pr_pic_teknis_nama, pr_nama_bagian, pr_nama_divisi, po_nomor, po_deskripsi, po_vendor, po_tgl_generate, po_nama_item, po_qty_item, po_uom_item, po_target_delivery, project_id, periode_group FROM inbound_master WHERE $where ORDER BY id DESC LIMIT 500");
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format rows according to the requested status
    $formattedRows = [];
    foreach ($rawRows as $r) {
        $desc = !empty($r['po_deskripsi']) ? $r['po_deskripsi'] : (!empty($r['po_nama_item']) ? $r['po_nama_item'] : '-');
        $pic = !empty($r['pr_pic_teknis_nama']) ? $r['pr_pic_teknis_nama'] : '-';
        $dept = !empty($r['pr_nama_bagian']) ? $r['pr_nama_bagian'] : (!empty($r['pr_nama_divisi']) ? $r['pr_nama_divisi'] : '-');
        $vendor = !empty($r['po_vendor']) ? $r['po_vendor'] : '-';
        $qtyStr = ($r['po_qty_item'] !== null) ? number_format((float)$r['po_qty_item'], 0, ',', '.') . ' ' . ($r['po_uom_item'] ?: 'Unit') : '-';

        if ($status === 'GR NON PO') {
            $formattedRows[] = [
                'deskripsi_perangkat' => $desc,
                'pic_asset_planner'   => $pic,
                'department'          => $dept,
                'vendor'              => $vendor,
                'qty'                 => $qtyStr
            ];
        } elseif ($status === 'PO SUDAH GR' || $status === 'TOTAL GR') {
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_gr'             => $vendor,
                'qty'                => $qtyStr,
                'tgl_generate'       => $r['po_tgl_generate'] ?: '-',
                'target_delivery'    => $r['po_target_delivery'] ?: '-'
            ];
        } elseif ($status === 'PO SUDAH REGISTRASI' || $status === 'TOTAL REGISTRASI') {
            $formattedRows[] = [
                'no_po'              => $r['po_nomor'] ?: '-',
                'deskripsi_po'       => $desc,
                'pic_asset_planner'  => $pic,
                'department'         => $dept,
                'pic_registrasi'     => $pic,
                'qty'                => $qtyStr,
                'tgl_generate'       => $r['po_tgl_generate'] ?: '-',
                'target_delivery'    => $r['po_target_delivery'] ?: '-'
            ];
        } else {
            // TOTAL PO INBOUND, PO ONTIME DELIVERY, PO TERLAMBAT DELIVERY
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
