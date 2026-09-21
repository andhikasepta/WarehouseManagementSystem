<?php
// api/get_chart_detail.php
// Fetches detailed rows on demand when a user clicks a bar in dashboard charts
ini_set('memory_limit', '256M');
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
    $q = ($driver === 'pgsql') ? '"' : '`';

    $type = trim($_REQUEST['type'] ?? 'aging'); // 'aging', 'org', 'status', 'all'
    $label = trim($_REQUEST['label'] ?? '');
    $periode = trim($_REQUEST['periode'] ?? '');
    $limit = isset($_REQUEST['limit']) ? min(1000, max(10, intval($_REQUEST['limit']))) : 500;

    $where = [];
    $params = [];

    // Clean period string: remove "(ALL BATCH)", "(ALL)", etc.
    $cleanPeriod = trim(preg_replace('/\s*\((ALL BATCH|ALL)\)/i', '', $periode));

    if ($cleanPeriod && $cleanPeriod !== 'PILIH DATA' && $cleanPeriod !== 'PILIH PERIODE DATA' && $cleanPeriod !== '-') {
        $isBatchSpecific = (bool)preg_match('/-Batch\d+$/i', $cleanPeriod);
        if ($isBatchSpecific) {
            $where[] = "periode_group = ?";
            $params[] = $cleanPeriod;
        } else {
            $where[] = "(periode_group = ? OR periode_group LIKE ?)";
            $params[] = $cleanPeriod;
            $params[] = $cleanPeriod . '%';
        }
    }

    if ($type === 'aging') {
        if ($label === 'Unassigned' || $label === 'Unknown') {
            $where[] = "({$q}range{$q} IS NULL OR TRIM({$q}range{$q}) = '' OR {$q}range{$q} = 'Unassigned' OR {$q}range{$q} = 'Unknown')";
        } elseif ($label) {
            $where[] = "TRIM({$q}range{$q}) = ?";
            $params[] = $label;
        }
    } elseif ($type === 'org') {
        if ($label === 'Tanpa Organization' || $label === 'Unknown') {
            $where[] = "(asset_planner_organization IS NULL OR TRIM(asset_planner_organization) = '' OR asset_planner_organization = 'Tanpa Organization' OR asset_planner_organization = 'Unknown')";
        } elseif ($label) {
            $where[] = "TRIM(asset_planner_organization) = ?";
            $params[] = $label;
        }
    } elseif ($type === 'category') {
        if ($label) {
            $where[] = "TRIM(category) = ?";
            $params[] = $label;
        }
    }

    $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT spec_code, spec_name, reg_no, asset_planner_organization, nbv, so_result, so_location, {$q}range{$q}, sub_location, category, periode_group
            FROM assets $whereClause LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'type' => $type,
        'label' => $label,
        'count' => count($rows),
        'data' => $rows
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_chart_detail.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
