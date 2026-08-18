<?php
// api/save_site_location.php
ini_set('memory_limit', '512M');
set_time_limit(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
validateCsrf();

function ensureSiteLocationTableExists($pdo)
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $jsonCol = ($driver === 'pgsql') ? "raw_data JSONB" : "raw_data JSON";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    $sql = "CREATE TABLE IF NOT EXISTS site_location (
        $idCol,
        category TEXT,
        intan TEXT,
        regional TEXT,
        region TEXT,
        area_cluster TEXT,
        address TEXT,
        province TEXT,
        city TEXT,
        sub_district TEXT,
        village TEXT,
        postal_code TEXT,
        latitude TEXT,
        longitude TEXT,
        $jsonCol,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol
    )";
    $pdo->exec($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }

    try {
        ensureSiteLocationTableExists($pdo);
        $canAdd = canAdd('site_location') || canAdd('master_data');
        if (!$canAdd) {
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah/mengimpor data Site Location.']);
            exit;
        }

        $action = $data['action'] ?? 'append';

        if ($action === 'init') {
            // Truncate table
            $canDelete = canDelete('site_location') || canDelete('master_data');
            if (!$canDelete) {
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus seluruh data Site Location.']);
                exit;
            }
            $pdo->exec("TRUNCATE TABLE site_location");
            echo json_encode(['status' => 'success', 'message' => 'Site location table cleared']);
            exit;
        }

        if ($action === 'append' || $action === 'batch') {
            $rows = $data['data'] ?? [];
            if (!is_array($rows) || empty($rows)) {
                echo json_encode(['status' => 'error', 'message' => 'No data rows provided']);
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO site_location (
                site_id, category, intan, region, area_cluster,
                address, province, city, sub_district, village,
                postal_code, latitude, longitude, raw_data
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )");

            $insertedCount = 0;
            foreach ($rows as $row) {
                if (!is_array($row))
                    continue;

                $siteId = isset($row['site_id']) ? trim((string) $row['site_id']) : '';
                $category = isset($row['category']) ? trim((string) $row['category']) : '';
                $intan = isset($row['intan']) ? trim((string) $row['intan']) : '';
                $region = isset($row['region']) ? trim((string) $row['region']) : '';
                $areaCluster = isset($row['area_cluster']) ? trim((string) $row['area_cluster']) : '';
                $address = isset($row['address']) ? trim((string) $row['address']) : '';
                $province = isset($row['province']) ? trim((string) $row['province']) : '';
                $city = isset($row['city']) ? trim((string) $row['city']) : '';
                $subDistrict = isset($row['sub_district']) ? trim((string) $row['sub_district']) : '';
                $village = isset($row['village']) ? trim((string) $row['village']) : '';
                $postalCode = isset($row['postal_code']) ? trim((string) $row['postal_code']) : '';
                $latitude = isset($row['latitude']) ? trim((string) $row['latitude']) : '';
                $longitude = isset($row['longitude']) ? trim((string) $row['longitude']) : '';

                // Raw data: use the _raw key if available, otherwise store the mapped row
                $rawData = isset($row['_raw']) ? $row['_raw'] : $row;
                $rawJson = json_encode($rawData, JSON_UNESCAPED_UNICODE);

                // Skip completely empty rows
                $allEmpty = empty($siteId) && empty($category) && empty($intan) && empty($region)
                    && empty($areaCluster) && empty($address) && empty($province) && empty($city)
                    && empty($subDistrict) && empty($village) && empty($postalCode)
                    && empty($latitude) && empty($longitude);
                if ($allEmpty)
                    continue;

                $stmt->execute([
                    $siteId,
                    $category,
                    $intan,
                    $region,
                    $areaCluster,
                    $address,
                    $province,
                    $city,
                    $subDistrict,
                    $village,
                    $postalCode,
                    $latitude,
                    $longitude,
                    $rawJson
                ]);
                $insertedCount++;
            }

            $pdo->commit();
            echo json_encode([
                'status' => 'success',
                'message' => $insertedCount . ' rows inserted successfully',
                'inserted' => $insertedCount
            ]);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . $action]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("save_site_location error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("save_site_location error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
}
