<?php
return function ($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS rack_utilisasi (
        $idCol,
        label VARCHAR(255) NOT NULL,
        month VARCHAR(20) NOT NULL,
        year VARCHAR(10) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        capacity DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol,
        CONSTRAINT unique_label_period UNIQUE (label, month, year)
    )";
    $pdo->exec($sql);
};
