<?php
// api/get_active_announcement.php - Public API to fetch active/expired maintenance announcement
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'active' => false]);
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$currentNow = date('Y-m-d H:i:s');

try {
    $monthsIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // 1. Check for announcements active on current date (whole-day pre-notification)
    // Uses DATE() so an announcement with period 22:00-23:00 shows all day
    $sql = "SELECT id, title, description, type, version, start_datetime, end_datetime, updated_at
            FROM announcements 
            WHERE is_active = 1 
              AND DATE(?) BETWEEN DATE(start_datetime) AND DATE(end_datetime)
            ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentNow]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($announcements)) {
        foreach ($announcements as &$ann) {
            $ann['type'] = $ann['type'] ?? 'maintenance';
            $ann['formatted_period'] = formatPeriod($ann, $monthsIndo);
            $ann['version_text'] = !empty($ann['version']) ? $ann['version'] : extractVersionText($ann);
        }
        unset($ann);

        echo json_encode([
            'success' => true, 
            'active' => true, 
            'status' => 'active', 
            'announcements' => $announcements,
            'announcement' => $announcements[0],
            'total' => count($announcements)
        ]);
        exit;
    }

    // 2. No active announcement for today
    echo json_encode(['success' => true, 'active' => false, 'status' => 'none']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'active' => false, 'error' => $e->getMessage()]);
}

function formatPeriod($ann, $months) {
    $startTs = strtotime($ann['start_datetime']);
    $endTs = strtotime($ann['end_datetime']);

    $startDateStr = date('d', $startTs) . ' ' . $months[date('n', $startTs) - 1] . ' ' . date('Y', $startTs);
    $startTimeStr = date('H:i', $startTs);
    $endDateStr = date('d', $endTs) . ' ' . $months[date('n', $endTs) - 1] . ' ' . date('Y', $endTs);
    $endTimeStr = date('H:i', $endTs);

    if ($startDateStr === $endDateStr) {
        return $startDateStr . ' (' . $startTimeStr . ' - ' . $endTimeStr . ' WIB)';
    }
    return $startDateStr . ' ' . $startTimeStr . ' - ' . $endDateStr . ' ' . $endTimeStr . ' WIB';
}

function extractVersionText($ann) {
    $text = ($ann['title'] ?? '') . ' ' . ($ann['description'] ?? '');

    // 1. Match "Versi Beta-v1.0.0" or "Beta-v1.0.0" or "Beta-1.0.0"
    if (preg_match('/(?:versi|version)?\s*((?:beta|alpha|rc)[-_ ]?v?\d+(?:\.\d+)*(?:[-_][a-z0-9]+)?)/i', $text, $matches)) {
        return trim($matches[1]);
    }

    // 2. Match prefixes like v1.0.1 or 1.0.0
    if (preg_match('/((?:beta|alpha|rc|v)?[-_]?v?\d+(?:\.\d+)+(?:[-_][a-z0-9]+)?)/i', $text, $matches)) {
        $ver = trim($matches[1]);
        if (preg_match('/^[0-9]/', $ver)) {
            $ver = 'V' . $ver;
        }
        return $ver;
    }

    // 3. Match "Versi Beta-1.0.0" or "Version 1.0"
    if (preg_match('/(?:versi|version)\s*([a-z0-9_\-.]+)/i', $text, $matches)) {
        return trim($matches[1]);
    }

    return 'V1.0.0';
}
