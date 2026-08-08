<?php
// backend/migrate.php
require_once __DIR__ . '/paths.php';
require_once CONFIG_PATH . 'database.php';

// Setup migrations table if it doesn't exist
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        $idCol,
        migration VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Error setting up migrations table: " . $e->getMessage() . "\n");
}

function getExecutedMigrations($pdo) {
    $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function runMigrate($pdo) {
    $executed = getExecutedMigrations($pdo);
    $migrationsDir = BACKEND_PATH . 'database/migrations';
    
    if (!is_dir($migrationsDir)) {
        die("Migrations directory not found.\n");
    }

    $files = scandir($migrationsDir);
    $pending = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (!in_array($file, $executed)) {
                $pending[] = $file;
            }
        }
    }

    if (empty($pending)) {
        echo "Nothing to migrate.\n";
        return;
    }

    sort($pending);

    foreach ($pending as $file) {
        echo "Migrating: $file\n";
        try {
            // Include and run migration
            $migrationCode = require_once $migrationsDir . '/' . $file;
            if (is_callable($migrationCode)) {
                $migrationCode($pdo);
            }
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$file]);
            
            echo "Migrated:  $file\n";
        } catch (Exception $e) {
            die("Error migrating $file: " . $e->getMessage() . "\n");
        }
    }
}

function runRefresh($pdo) {
    echo "Refreshing database...\n";
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS \"$table\" CASCADE");
                echo "Dropped table: $table\n";
            }
        } else {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tables)) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                    echo "Dropped table: $table\n";
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            }
        }
        
        // Re-create migrations table
        $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
        $pdo->exec("CREATE TABLE migrations (
            $idCol,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        echo "Database refreshed. Running migrations...\n";
        runMigrate($pdo);
    } catch (PDOException $e) {
        die("Error refreshing database: " . $e->getMessage() . "\n");
    }
}

function runStatus($pdo) {
    $executed = getExecutedMigrations($pdo);
    $migrationsDir = BACKEND_PATH . 'database/migrations';
    
    if (!is_dir($migrationsDir)) {
        die("Migrations directory not found.\n");
    }

    $files = scandir($migrationsDir);
    $allMigrations = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $allMigrations[] = $file;
        }
    }
    sort($allMigrations);

    echo "+--------------------------------------------------+-------+\n";
    echo "| Migration                                        | Ran?  |\n";
    echo "+--------------------------------------------------+-------+\n";
    foreach ($allMigrations as $file) {
        $ran = in_array($file, $executed) ? 'Yes' : 'No';
        printf("| %-48s | %-5s |\n", $file, $ran);
    }
    echo "+--------------------------------------------------+-------+\n";
}

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        runMigrate($pdo);
        break;
    case 'refresh':
        runRefresh($pdo);
        break;
    case 'status':
        runStatus($pdo);
        break;
    default:
        echo "Usage:\n";
        echo "  php migrate.php migrate  - Run pending migrations\n";
        echo "  php migrate.php refresh  - Drop all tables and re-run migrations\n";
        echo "  php migrate.php status   - Show migration status\n";
        break;
}
