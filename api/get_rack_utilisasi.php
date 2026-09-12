<?php
// api/get_rack_utilisasi.php
if (!ob_start('ob_gzhandler')) ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
session_write_close();

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $idCol = ($driver === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id INT AUTO_INCREMENT PRIMARY KEY";
    $updatedAtCol = ($driver === 'pgsql') ? "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" : "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

    // Ensure rack_utilisasi table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS rack_utilisasi (
        $idCol,
        label VARCHAR(255) NOT NULL,
        month VARCHAR(20) NOT NULL,
        year VARCHAR(10) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        capacity DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        $updatedAtCol,
        CONSTRAINT unique_label_period UNIQUE (label, month, year)
    )");

    $month = isset($_GET['month']) ? trim($_GET['month']) : '';
    $year = isset($_GET['year']) ? trim($_GET['year']) : '';
    if (preg_match('/\b(20\d{2})\b/', $year, $ym)) {
        $year = $ym[1];
    }
    $filter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all'; // all, used, available
    $action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : 'data';

    // Comprehensive Month Mapping (English, Indonesian, and standard 3-letter abbreviations)
    $monthMap = [
        'JAN' => 'January', 'JANUARI' => 'January', 'JANUARY' => 'January',
        'FEB' => 'February', 'FEBRUARI' => 'February', 'FEBRUARY' => 'February',
        'MAR' => 'March', 'MARET' => 'March', 'MARCH' => 'March',
        'APR' => 'April', 'APRIL' => 'April',
        'MAY' => 'May', 'MEI' => 'May',
        'JUN' => 'June', 'JUNI' => 'June', 'JUNE' => 'June',
        'JUL' => 'July', 'JULI' => 'July', 'JULY' => 'July',
        'AUG' => 'August', 'AGU' => 'August', 'AGUSTUS' => 'August', 'AUGUST' => 'August',
        'SEP' => 'September', 'SEPTEMBER' => 'September',
        'OCT' => 'October', 'OKT' => 'October', 'OKTOBER' => 'October', 'OCTOBER' => 'October',
        'NOV' => 'November', 'NOP' => 'November', 'NOVEMBER' => 'November',
        'DEC' => 'December', 'DES' => 'December', 'DESEMBER' => 'December', 'DECEMBER' => 'December'
    ];
    $cleanMonthKey = strtoupper($month);
    if (isset($monthMap[$cleanMonthKey])) {
        $month = $monthMap[$cleanMonthKey];
    }

    // Validate month against allow-list if provided
    $validMonths = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    $monthOrderCase = "CASE month 
        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
        ELSE 0 END";

    // If no month/year is specified, find the latest period in rack_utilisasi
    if ($month === '' || $year === '') {
        $latestStmt = $pdo->query("SELECT month, year FROM rack_utilisasi ORDER BY year DESC, $monthOrderCase DESC LIMIT 1");
        $latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
        if ($latest) {
            $month = $latest['month'];
            $year = $latest['year'];
        }
    }

    // Check if rack_master has any data
    $rackMasterCount = (int) $pdo->query("SELECT COUNT(*) FROM rack_master")->fetchColumn();

    $allResults = null;

    if ($rackMasterCount > 0) {
        // ── Use rack_master as the source ──
        if ($month !== '' && $year !== '' && in_array($month, $validMonths, true) && preg_match('/^\d{4}$/', $year)) {
            $safeMonth = $pdo->quote($month);
            $safeYear = $pdo->quote($year);
            // Return ALL labels from rack_master, left-joined with rack_utilisasi for this period.
            $stmt = $pdo->prepare(
                "SELECT rm.label, COALESCE(rm.name, rm.rack, rm.label) AS rack_group, 
                        rm.name, rm.barcode, rm.active, rm.category,
                        $safeMonth AS month, $safeYear AS year,
                        COALESCE(ru.qty, 0) AS qty,
                        COALESCE(ru.capacity, 0.00) AS capacity,
                        ru.id AS id
                 FROM rack_master rm
                 LEFT JOIN rack_utilisasi ru ON rm.label = ru.label 
                      AND (LOWER(ru.month) = LOWER(?) OR LOWER(ru.month) = LOWER(?))
                      AND ru.year = ?
                 ORDER BY rm.category, COALESCE(rm.name, rm.rack), rm.label"
            );
            // Pass full month name and 3-letter abbreviation to be 100% robust against DB variations
            $shortMonth = substr($month, 0, 3);
            $stmt->execute([$month, $shortMonth, $year]);
            $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Return from rack_master with latest rack_utilisasi
            if ($driver === 'pgsql') {
                $sql = "SELECT rm.label, COALESCE(rm.name, rm.rack, rm.label) AS rack_group, 
                               rm.name, rm.barcode, rm.active, rm.category,
                               COALESCE(ru.month, '') AS month, COALESCE(ru.year, '') AS year,
                               COALESCE(ru.qty, 0) AS qty,
                               COALESCE(ru.capacity, 0.00) AS capacity,
                               ru.id AS id
                        FROM rack_master rm
                        LEFT JOIN (
                            SELECT DISTINCT ON (label) label, month, year, qty, capacity, id
                            FROM rack_utilisasi ORDER BY label, id DESC
                        ) ru ON rm.label = ru.label
                        ORDER BY rm.category, COALESCE(rm.name, rm.rack), rm.label";
            } else {
                $sql = "SELECT rm.label, COALESCE(rm.name, rm.rack, rm.label) AS rack_group, 
                               rm.name, rm.barcode, rm.active, rm.category,
                               COALESCE(ru.month, '') AS month, COALESCE(ru.year, '') AS year,
                               COALESCE(ru.qty, 0) AS qty,
                               COALESCE(ru.capacity, 0.00) AS capacity,
                               ru.id AS id
                        FROM rack_master rm
                        LEFT JOIN (
                            SELECT * FROM rack_utilisasi ORDER BY id DESC
                        ) ru ON rm.label = ru.label
                        GROUP BY rm.label, rm.name, rm.category, rm.barcode, rm.active, rm.rack
                        ORDER BY rm.category, COALESCE(rm.name, rm.rack), rm.label";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // ── Fallback: auto-generate rack data from assets.sub_location ──
        // Parse sub_location (e.g. "EOC/LT-1/BESAR/AISLE-01/RACK-01/ROW-04/SHELF-06")
        // to extract rack groupings and count assets per rack.
        $periodWhere = '';
        $periodParams = [];
        if ($month !== '' && $year !== '' && in_array($month, $validMonths, true) && preg_match('/^\d{4}$/', $year)) {
            $periodWhere = " AND periode_group LIKE ?";
            $periodParams[] = $month . ' ' . $year . '%';
        }

        $assetSql = "SELECT sub_location, COUNT(*) as asset_count 
                     FROM assets 
                     WHERE sub_location IS NOT NULL AND TRIM(sub_location) != ''" . $periodWhere . "
                     GROUP BY sub_location 
                     ORDER BY sub_location";
        $assetStmt = $pdo->prepare($assetSql);
        $assetStmt->execute($periodParams);
        $assetRows = $assetStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by rack: extract rack group from sub_location path
        // Format: "AREA/FLOOR/ZONE/AISLE-XX/RACK-XX/ROW-XX/SHELF-XX"
        $rackGrouped = [];
        foreach ($assetRows as $aRow) {
            $subLoc = trim($aRow['sub_location']);
            $parts = explode('/', $subLoc);
            $cnt = (int) $aRow['asset_count'];

            // Try to find RACK-XX in the path segments
            $rackPart = '';
            $areaPart = '';
            $category = '';
            foreach ($parts as $p) {
                $p = trim($p);
                if (stripos($p, 'RACK-') === 0 || stripos($p, 'RACK') === 0) {
                    $rackPart = $p;
                } elseif (stripos($p, 'AISLE-') === 0 || stripos($p, 'AISLE') === 0) {
                    // Use AISLE as part of the rack group name
                    $areaPart = $p;
                }
            }

            // Derive category from the path (e.g., FASTMOVING, BESAR, DALAM, SLOW MOVING)
            $upperSubLoc = strtoupper($subLoc);
            if (strpos($upperSubLoc, 'FASTMOVING') !== false) {
                $category = 'FAST MOVING';
            } elseif (strpos($upperSubLoc, 'SLOW MOVING') !== false || strpos($upperSubLoc, 'SLOWMOVING') !== false) {
                $category = 'SLOW MOVING';
            } elseif (strpos($upperSubLoc, 'BESAR') !== false) {
                $category = 'AREA BESAR';
            } elseif (strpos($upperSubLoc, 'DALAM') !== false) {
                $category = 'AREA DALAM';
            } elseif (strpos($upperSubLoc, 'EOC') !== false) {
                $category = 'EOC';
            } else {
                $category = 'OTHER';
            }

            // Build rack group name from area + rack
            if ($rackPart && $areaPart) {
                $rackGroup = $areaPart . '/' . $rackPart;
            } elseif ($rackPart) {
                $rackGroup = $rackPart;
            } elseif (count($parts) >= 2) {
                // Use first two segments as a fallback group
                $rackGroup = $parts[0] . '/' . $parts[1];
            } else {
                $rackGroup = $subLoc;
            }

            if (!isset($rackGrouped[$rackGroup])) {
                $rackGrouped[$rackGroup] = [
                    'label' => $rackGroup,
                    'rack_group' => $rackGroup,
                    'name' => $rackGroup,
                    'barcode' => '',
                    'active' => 'ACTIVE',
                    'category' => $category,
                    'qty' => 0,
                    'capacity' => 0,
                    'sub_locations' => []
                ];
            }
            $rackGrouped[$rackGroup]['qty'] += $cnt;
            $rackGrouped[$rackGroup]['sub_locations'][] = $subLoc;
        }

        // Convert to indexed array and add month/year
        $generatedResults = [];
        foreach ($rackGrouped as $rg) {
            $generatedResults[] = [
                'label' => $rg['label'],
                'rack_group' => $rg['rack_group'],
                'name' => $rg['name'],
                'barcode' => $rg['barcode'],
                'active' => $rg['active'],
                'category' => $rg['category'],
                'month' => $month ?: '',
                'year' => $year ?: '',
                'qty' => $rg['qty'],
                'capacity' => 0,
                'id' => null
            ];
        }

        // Sort by category, rack_group
        usort($generatedResults, function($a, $b) {
            $catCmp = strcmp($a['category'], $b['category']);
            if ($catCmp !== 0) return $catCmp;
            return strcmp($a['rack_group'], $b['rack_group']);
        });

        $allResults = $generatedResults;
    }

    if ($allResults === null) {
        $allResults = [];
    }

    // Calculate Summary Metrics
    $totalLocations = count($allResults);
    $usedLocations = 0;
    $availableLocations = 0;
    $totalCapSum = 0;
    $totalQtySum = 0;
    $measuredStorageSum = 0;
    $measuredStorageCount = 0;

    $filteredResults = [];
    foreach ($allResults as $row) {
        $cap = (float) $row['capacity'];
        if ($cap > 0 && $cap <= 1.0) {
            $cap = $cap * 100.0;
        }
        $row['capacity'] = number_format($cap, 2, '.', '');
        $qty = (int) $row['qty'];
        $totalCapSum += $cap;
        $totalQtySum += $qty;

        $isUsed = ($cap > 0 || $qty > 0);
        if ($isUsed) {
            $usedLocations++;
        } else {
            $availableLocations++;
        }

        $cat = strtoupper(trim($row['category'] ?? ''));
        if ($cat !== 'QUARTERLY' && $cat !== 'KABEL') {
            $measuredStorageCount++;
        }

        if ($filter === 'used') {
            if ($isUsed) $filteredResults[] = $row;
        } elseif ($filter === 'available') {
            if (!$isUsed || $cap < 100) $filteredResults[] = $row;
        } else {
            $filteredResults[] = $row;
        }
    }

    // Excel behavior: 36,440 / 444 = 82.07% (~82%)
    // Excel sums all capacity values (36,440%) across the 444 storage rack locations
    $storageDenom = $measuredStorageCount > 0 ? $measuredStorageCount : $totalLocations;
    $avgUtilization = $storageDenom > 0 ? round($totalCapSum / $storageDenom, 1) : 0;
    if ($avgUtilization > 100.0) {
        $avgUtilization = 100.0;
    }

    echo json_encode([
        'status' => 'success',
        'period' => [
            'month' => $month,
            'year' => $year
        ],
        'summary' => [
            'total_capacity' => $totalLocations,
            'used' => $usedLocations,
            'available' => $availableLocations,
            'avg_utilization' => $avgUtilization,
            'total_qty' => $totalQtySum
        ],
        'count' => count($filteredResults),
        'data' => $filteredResults
    ]);
} catch (PDOException $e) {
    error_log("Database error fetching rack utilisasi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred while fetching data.']);
}
