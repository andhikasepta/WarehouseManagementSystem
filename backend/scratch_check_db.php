<?php
require_once __DIR__ . '/config/database.php';

try {
    $in = $pdo->query("SELECT COUNT(*) FROM inbound_master")->fetchColumn();
    $out = $pdo->query("SELECT COUNT(*) FROM outbound_master")->fetchColumn();
    $ast = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    echo "Inbound: $in\nOutbound: $out\nAssets: $ast\n";

    $inSample = $pdo->query("SELECT * FROM inbound_master LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "Inbound sample:\n";
    print_r($inSample);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

