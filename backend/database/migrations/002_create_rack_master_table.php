<?php
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";

    $sql = "CREATE TABLE IF NOT EXISTS rack_master (
        $idCol,
        label VARCHAR(255) NOT NULL UNIQUE,
        rack VARCHAR(255) NOT NULL,
        category VARCHAR(255)
    )";
    $pdo->exec($sql);
};
