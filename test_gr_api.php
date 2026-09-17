<?php
// Direct DB test - bypass auth
require_once __DIR__ . '/backend/config/database.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $q = ($driver === 'pgsql') ? '"' : '`';
    
    // Test the exact query from get_inbound_gr.php
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM inbound_gr");
    $recordsTotal = (int)$totalStmt->fetchColumn();
    echo "recordsTotal: $recordsTotal" . PHP_EOL;
    
    // Test data fetch
    $dataSql = "SELECT id, no_reg, kd_spec, sn, pn, product_name, price,
                       pr_no, pr_date, po_no, po_date, do_no, do_date,
                       gr_no, gr_date, po_value, nama_project, term_of_payment,
                       kode_site_penerimaan, qty, uom, is_unique_item,
                       warranty, warranty_unit, manufacturer, vendor_name,
                       vendor_address, jenis_kepemilikan, pemilik, capex_opex,
                       loi_no, is_sent_to_artis_code, artis_date, artis_message,
                       is_sent_to_iips_code, iips_date, iips_message,
                       pic_submit_gr, pic_registration, periode_group
                FROM inbound_gr ORDER BY {$q}no_reg{$q} DESC LIMIT 25 OFFSET 0";
    
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Fetched rows: " . count($rows) . PHP_EOL;
    
    // Output as DataTables expects
    $response = [
        'draw' => 1,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsTotal,
        'data' => $rows
    ];
    
    $json = json_encode($response);
    if ($json === false) {
        echo "JSON encode error: " . json_last_error_msg() . PHP_EOL;
        
        // Try to find which field causes the issue
        foreach ($rows[0] as $key => $val) {
            $test = json_encode([$key => $val]);
            if ($test === false) {
                echo "  Problem field: $key => " . mb_detect_encoding($val) . " (len: " . strlen($val) . ")" . PHP_EOL;
                // Try to fix encoding
                $fixed = mb_convert_encoding($val, 'UTF-8', 'auto');
                $test2 = json_encode([$key => $fixed]);
                echo "  After fix: " . ($test2 ? "OK" : "Still broken") . PHP_EOL;
            }
        }
    } else {
        echo "JSON encode OK. Response size: " . strlen($json) . " bytes" . PHP_EOL;
        echo "First 500 chars of JSON: " . substr($json, 0, 500) . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack: " . $e->getTraceAsString() . PHP_EOL;
}
