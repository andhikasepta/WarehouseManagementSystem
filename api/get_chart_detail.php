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

    $type = trim($_GET['type'] ?? 'aging'); // 'aging', 'org', 'status', 'all'
    $label = trim($_GET['label'] ?? '');
    $periode = trim($_GET['periode'] ?? '');
    $limit = isset($_GET['limit']) ? min(1000, max(10, intval($_GET['limit']))) : 500;

    $where = [];
    $params = [];

    if ($periode && $periode !== 'PILIH DATA' && $periode !== 'PILIH PERIODE DATA') {
        $isBatchSpecific = (bool)preg_match('/-Batch\d+$/i', $periode);
        if ($isBatchSpecific) {
            $where[] = "periode_group = ?";
            $params[] = $periode;
        } else {
            $where[] = "(periode_group = ? OR periode_group LIKE ?)";
            $params[] = $periode;
            $params[] = $periode . '%';
        }
    }

    if ($type === 'aging') {
        if ($label && $label !== 'Unknown') {
            $where[] = "{$q}range{$q} = ?";
            $params[] = $label;
        }
    } elseif ($type === 'org') {
        if ($label && $label !== 'Unknown') {
            $where[] = "asset_planner_organization = ?";
            $params[] = $label;
        }
    } elseif ($type === 'category') {
        if ($label) {
            $where[] = "category = ?";
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
