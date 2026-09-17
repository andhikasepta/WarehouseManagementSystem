<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM inbound_gr');
    $count = $stmt->fetchColumn();
    echo "Total rows in inbound_gr: " . $count . PHP_EOL;
    
    if ($count > 0) {
        $stmt2 = $pdo->query('SELECT * FROM inbound_gr LIMIT 2');
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample data:" . PHP_EOL;
        print_r($rows);
    }
    
    // Also check table structure
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $colStmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'inbound_gr' ORDER BY ordinal_position");
    } else {
        $colStmt = $pdo->query("DESCRIBE inbound_gr");
    }
    echo PHP_EOL . "Table columns:" . PHP_EOL;
    while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($col);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
