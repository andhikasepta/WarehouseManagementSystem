<?php
// Shared Navigation Component (Left Sidebar + Topbar)
// Accepts $activePage ('inbound', 'warehouse', 'outbound', 'master_data', 'wms_select', 'user_management', 'dashboard')
// Accepts optional $hidePeriodSelector (boolean)
// Accepts optional $hideNavLinks (boolean)
// Accepts optional $hideLoginButton (boolean)
// Accepts optional $hideNavbarUl (boolean)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($activePage)) {
    $activePage = '';
}

$navUser = [
    'id' => $_SESSION['user_id'] ?? '',
    'name' => $_SESSION['name'] ?? 'Guest User',
    'role' => $_SESSION['role'] ?? 'guest',
    'employment_type' => $_SESSION['employment_type'] ?? 'Karyawan Tetap',
    'job_title' => $_SESSION['job_title'] ?? '',
    'is_logged_in' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
];

$allowedNavModules = $_SESSION['allowed_modules'] ?? [];
$isSuperAdminNav = ($navUser['role'] === 'superadmin');
$canAccessMasterData = $navUser['is_logged_in'] && ($isSuperAdminNav || in_array('master_data', $allowedNavModules) || in_array('master_data_inbound', $allowedNavModules) || in_array('master_data_storage', $allowedNavModules) || in_array('master_data_outbound', $allowedNavModules) || in_array('site_location', $allowedNavModules));

$shouldHideNavLinks = isset($hideNavLinks) && $hideNavLinks;
$shouldHideNavbarUl = isset($hideNavbarUl) && $hideNavbarUl;
$showSidebar = $navUser['is_logged_in'] && !$shouldHideNavLinks;

$navRoleTitle = 'Admin Warehouse';
if ($navUser['role'] === 'superadmin')
    $navRoleTitle = 'Super Admin';
elseif ($navUser['role'] === 'head_asset_warehouse_admin')
    $navRoleTitle = 'Head-Department';
elseif ($navUser['role'] === 'head_warehouse_admin')
    $navRoleTitle = 'Head-Subdept';
elseif ($navUser['role'] === 'inbound_admin')
    $navRoleTitle = 'Inbound Administrator';
elseif ($navUser['role'] === 'outbound_admin')
    $navRoleTitle = 'Outbound Administrator';
elseif ($navUser['role'] === 'warehouse_admin')
    $navRoleTitle = 'Storage Administrator';
elseif ($navUser['role'] === 'outsourcing')
    $navRoleTitle = 'User';
elseif ($navUser['role'] === 'repository_admin')
    $navRoleTitle = 'Repository Administrator';

// Fetch active announcement server-side for zero-latency instant navbar rendering
if (!isset($pdo)) {
    if (defined('BACKEND_PATH')) {
        @include_once BACKEND_PATH . 'config/database.php';
    } else {
        @include_once __DIR__ . '/../../backend/config/database.php';
    }
}

$navAnnouncement = null;
$navAnnouncementsList = [];
$navAnnouncementStatus = 'none';

if (isset($pdo)) {
    try {
        date_default_timezone_set('Asia/Jakarta');
        $currentNow = date('Y-m-d H:i:s');
        $sqlAnn = "SELECT id, title, description, type, version, start_datetime, end_datetime, updated_at
                   FROM announcements 
                   WHERE is_active = 1 
                     AND DATE(?) BETWEEN DATE(start_datetime) AND DATE(end_datetime)
                   ORDER BY id ASC";
        $stmtAnn = $pdo->prepare($sqlAnn);
        $stmtAnn->execute([$currentNow]);
        $navAnnouncementsList = $stmtAnn->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($navAnnouncementsList)) {
            $navAnnouncementStatus = 'active';
            $monthsIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            foreach ($navAnnouncementsList as &$ann) {
                $ann['type'] = $ann['type'] ?? 'maintenance';
                $startTs = strtotime($ann['start_datetime']);
                $endTs = strtotime($ann['end_datetime']);
                $startDateStr = date('d', $startTs) . ' ' . $monthsIndo[date('n', $startTs) - 1] . ' ' . date('Y', $startTs);
                $startTimeStr = date('H:i', $startTs);
                $endDateStr = date('d', $endTs) . ' ' . $monthsIndo[date('n', $endTs) - 1] . ' ' . date('Y', $endTs);
                $endTimeStr = date('H:i', $endTs);
                if ($startDateStr === $endDateStr) {
                    $ann['formatted_period'] = $startDateStr . ' (' . $startTimeStr . ' - ' . $endTimeStr . ' WIB)';
                } else {
                    $ann['formatted_period'] = $startDateStr . ' ' . $startTimeStr . ' - ' . $endDateStr . ' ' . $endTimeStr . ' WIB';
                }

                if (!empty($ann['version'])) {
                    $ann['version_text'] = $ann['version'];
                } else {
                    $text = ($ann['title'] ?? '') . ' ' . ($ann['description'] ?? '');
                    if (preg_match('/(?:versi|version)?\s*((?:beta|alpha|rc)[-_ ]?v?\d+(?:\.\d+)*(?:[-_][a-z0-9]+)?)/i', $text, $m)) {
                        $ann['version_text'] = trim($m[1]);
                    } elseif (preg_match('/((?:beta|alpha|rc|v)?[-_]?v?\d+(?:\.\d+)+(?:[-_][a-z0-9]+)?)/i', $text, $m)) {
                        $ver = trim($m[1]);
                        $ann['version_text'] = preg_match('/^[0-9]/', $ver) ? 'V' . $ver : $ver;
                    } elseif (preg_match('/(?:versi|version)\s*([a-z0-9_\-.]+)/i', $text, $m)) {
                        $ann['version_text'] = trim($m[1]);
                    } else {
                        $ann['version_text'] = 'V1.0.0';
                    }
                }
            }
            unset($ann);
            $navAnnouncement = $navAnnouncementsList[0];
        }
    } catch (Exception $e) {
        // Silently continue if table/PDO isn't ready
    }
}
?>

<style>
    /* Dark Blue Sidebar Styles */
    #wms-sidebar {
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1040;
        background: linear-gradient(180deg, #0b192c 0%, #112236 40%, #1e3e62 100%);
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease-in-out;
    }

    #wms-sidebar .sidebar-brand {
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.15);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    #wms-sidebar .sidebar-brand-img {
        max-height: 32px;
        width: auto;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    #wms-sidebar .sidebar-nav {
        list-style: none;
        padding: 0.35rem 0;
        margin: 0;
        flex-grow: 1;
        overflow-y: hidden;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    #wms-sidebar .sidebar-nav::-webkit-scrollbar {
        display: none;
    }

    #wms-sidebar .sidebar-heading {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 0.4rem 1rem 0.15rem 1rem;
    }

    #wms-sidebar .nav-item {
        margin: 0.12rem 0.5rem;
    }

    #wms-sidebar .nav-item>.nav-link {
        display: flex;
        align-items: center;
        padding: 0.45rem 0.85rem;
        color: #cbd5e1;
        font-size: 0.83rem;
        font-weight: 600;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    #wms-sidebar .nav-item>.nav-link i {
        font-size: 0.95rem;
        width: 1.4rem;
        margin-right: 0.65rem;
        color: #94a3b8;
        transition: color 0.2s ease-in-out;
    }

    #wms-sidebar .nav-item>.nav-link span {
        padding-left: 0.15rem;
    }

    #wms-sidebar .nav-item>.nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        transform: translateX(3px);
    }

    #wms-sidebar .nav-item>.nav-link:hover i {
        color: #60a5fa;
    }

    #wms-sidebar .nav-item.active>.nav-link {
        background: linear-gradient(90deg, #1e4b7a 0%, #285b93 100%);
        color: #ffffff;
        font-weight: 700;
        border-left: 3px solid #60a5fa;
        box-shadow: 0 2px 8px rgba(30, 75, 122, 0.4);
    }

    #wms-sidebar .nav-item.active>.nav-link i {
        color: #60a5fa;
    }

    /* Sidebar Submenu Dropdown Styles */
    #wms-sidebar .sidebar-submenu {
        padding: 0.25rem 0.35rem;
        margin: 0.25rem 0 0.35rem 0.75rem;
        background: rgba(0, 0, 0, 0.25);
        border-left: 2px solid rgba(96, 165, 250, 0.3);
        border-radius: 0 8px 8px 0;
    }

    #wms-sidebar .sidebar-submenu .sub-link {
        display: flex;
        align-items: center;
        padding: 0.4rem 0.75rem;
        margin: 0.15rem 0;
        color: #cbd5e1;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    #wms-sidebar .sidebar-submenu .sub-link i {
        font-size: 0.8rem;
        width: 1.1rem;
        margin-right: 0.5rem;
        color: #94a3b8;
        transition: color 0.2s ease-in-out;
    }

    #wms-sidebar .sidebar-submenu .sub-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        transform: translateX(3px);
    }

    #wms-sidebar .sidebar-submenu .sub-link:hover i {
        color: #60a5fa;
    }

    #wms-sidebar .sidebar-submenu .sub-link.active {
        background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%) !important;
        color: #ffffff !important;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4);
    }

    #wms-sidebar .sidebar-submenu .sub-link.active i {
        color: #ffffff !important;
    }

    /* Chevron indicator right-alignment */
    #wms-sidebar .nav-link .dropdown-arrow,
    #wms-sidebar .nav-link .fa-chevron-down,
    #wms-sidebar .nav-link .fa-angle-down {
        margin-left: auto !important;
        margin-right: 0 !important;
        font-size: 0.68rem !important;
        display: inline-block !important;
    }

    #wms-sidebar a[aria-expanded="true"] .dropdown-arrow,
    #wms-sidebar a[aria-expanded="true"] .fa-chevron-down,
    #wms-sidebar a[aria-expanded="true"] .fa-angle-down {
        color: #60a5fa !important;
    }

    #wms-sidebar .sidebar-user-footer {
        padding: 1rem;
        background: rgba(0, 0, 0, 0.25);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    #wms-sidebar .user-info-card {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.05);
    }

    /* Layout Adjustments for Left Sidebar */
    <?php if ($showSidebar): ?>
        @media (min-width: 992px) {
            #content-wrapper {
                margin-left: 250px !important;
                transition: margin-left 0.3s ease-in-out;
            }

            .topbar.fixed-top {
                left: 250px !important;
                width: calc(100% - 250px) !important;
                transition: left 0.3s ease-in-out, width 0.3s ease-in-out;
            }

            .container-fluid {
                padding-top: 75px !important;
            }
        }

        /* Mini Icon Sidebar Mode (Desktop Collapsed State) */
        @media (min-width: 992px) {
            body.sidebar-toggled #wms-sidebar {
                width: 65px !important;
                transform: translateX(0) !important;
                visibility: visible !important;
            }

            body.sidebar-toggled #wms-sidebar .sidebar-brand {
                padding: 0.75rem 0.25rem !important;
            }

            body.sidebar-toggled #wms-sidebar .sidebar-brand span {
                font-size: 1rem !important;
                letter-spacing: 1px !important;
            }

            body.sidebar-toggled #wms-sidebar .sidebar-heading {
                font-size: 0 !important;
                line-height: 0 !important;
                padding: 0 !important;
                margin: 0.65rem 0.6rem !important;
                height: 1px !important;
                background-color: rgba(255, 255, 255, 0.15) !important;
                color: transparent !important;
                overflow: hidden !important;
                display: block !important;
            }

            body.sidebar-toggled #wms-sidebar .sidebar-nav>.sidebar-heading:first-child,
            body.sidebar-toggled #wms-sidebar .sidebar-nav>.sidebar-heading:first-of-type {
                display: none !important;
            }

            body.sidebar-toggled #wms-sidebar .nav-item {
                margin: 0.25rem 0.35rem !important;
            }

            body.sidebar-toggled #wms-sidebar .nav-item .nav-link {
                justify-content: center !important;
                padding: 0.5rem !important;
                text-align: center !important;
                border-left: none !important;
                border-radius: 6px !important;
            }

            body.sidebar-toggled #wms-sidebar .nav-item .nav-link i {
                margin-right: 0 !important;
                width: auto !important;
                font-size: 1.15rem !important;
            }

            body.sidebar-toggled #wms-sidebar .nav-item .nav-link span {
                display: none !important;
            }

            body.sidebar-toggled #content-wrapper {
                margin-left: 65px !important;
                transition: margin-left 0.3s ease-in-out;
            }

            body.sidebar-toggled .topbar.fixed-top {
                left: 65px !important;
                width: calc(100% - 65px) !important;
                transition: left 0.3s ease-in-out, width 0.3s ease-in-out;
            }
        }

        @media (max-width: 991.98px) {
            #wms-sidebar {
                transform: translateX(-100%);
            }

            body.mobile-sidebar-open #wms-sidebar {
                transform: translateX(0);
            }

            #content-wrapper {
                margin-left: 0 !important;
            }

            .topbar.fixed-top {
                left: 0 !important;
                width: 100% !important;
            }

            .container-fluid {
                padding-top: 75px !important;
            }
        }

    <?php else: ?>
        .container-fluid {
            padding-top: 75px !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

    <?php endif; ?>

    /* Global Ultra-Compact Layout Styles for All Pages (Inbound, Storage, Outbound, Master Data, etc.) */

    /* Fix background scrolling & scroll chaining for all modals */
    body.modal-open {
        overflow: hidden !important;
    }

    .modal,
    .modal-dialog,
    .modal-content,
    .modal-body,
    .table-responsive {
        overscroll-behavior: contain !important;
        overscroll-behavior-y: contain !important;
    }

    .modal-dialog-scrollable .modal-body {
        overflow-y: auto !important;
        overscroll-behavior: contain !important;
    }

    /* Topbar Height */
    .topbar {
        height: 3.5rem !important;
    }

    /* Page Typography Compaction */
    h1,
    .h1,
    h1.h3 {
        font-size: 1.15rem !important;
        margin-bottom: 0.25rem !important;
        font-weight: 700 !important;
    }

    h2,
    .h2 {
        font-size: 1.05rem !important;
    }

    h3,
    .h3 {
        font-size: 0.95rem !important;
    }

    h4,
    .h4 {
        font-size: 0.9rem !important;
    }

    h5,
    .h5 {
        font-size: 0.85rem !important;
    }

    h6,
    .h6 {
        font-size: 0.8rem !important;
    }

    body {
        font-size: 0.82rem !important;
    }

    /* Card Heights & Compaction */
    .card {
        margin-bottom: 0.5rem !important;
        border-radius: 6px !important;
    }

    .card .card-body {
        padding: 0.4rem 0.6rem !important;
    }

    .card .card-header {
        padding: 0.35rem 0.6rem !important;
        min-height: unset !important;
    }

    .card .card-footer {
        padding: 0.35rem 0.6rem !important;
    }

    .card .h5,
    .card h5 {
        font-size: 0.88rem !important;
        line-height: 1.2 !important;
    }

    .card .h3,
    .card h3 {
        font-size: 1.1rem !important;
        line-height: 1.2 !important;
    }

    .card .text-xs {
        font-size: 0.62rem !important;
        line-height: 1.2 !important;
    }

    .card.py-2 {
        padding-top: 0.1rem !important;
        padding-bottom: 0.1rem !important;
    }

    .status-card-clickable {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }

    .card i.fa-2x {
        font-size: 1.2em !important;
    }

    /* Spacing & Gaps */
    .mb-4,
    .my-4 {
        margin-bottom: 0.5rem !important;
    }

    .mb-3,
    .my-3 {
        margin-bottom: 0.35rem !important;
    }

    .pb-3 {
        padding-bottom: 0.35rem !important;
    }

    .py-2 {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }

    .py-3 {
        padding-top: 0.35rem !important;
        padding-bottom: 0.35rem !important;
    }

    .row>[class*="col-"].mb-4 {
        margin-bottom: 0.5rem !important;
    }

    /* Table & Form Elements Compaction */
    .table td,
    .table th {
        padding: 0.3rem 0.5rem !important;
        font-size: 0.78rem !important;
    }

    .form-control,
    .custom-select,
    select.form-control {
        height: calc(1.2em + 0.45rem + 2px) !important;
        padding: 0.15rem 0.45rem !important;
        font-size: 0.78rem !important;
    }

    .btn {
        padding: 0.22rem 0.5rem !important;
        font-size: 0.78rem !important;
    }

    .btn-sm {
        padding: 0.15rem 0.4rem !important;
        font-size: 0.74rem !important;
    }

    /* Fixed Height & Compact Metric Cards for Storage / Warehouse Management */
    .storage-card,
    .card[class*="border-left-"]:not(.inbound-metric-card) {
        min-height: 64px !important;
        max-height: 64px !important;
        height: 64px !important;
        overflow: hidden !important;
    }

    .storage-card .card-body,
    .card[class*="border-left-"]:not(.inbound-metric-card) .card-body {
        height: 100% !important;
        padding: 0.35rem 0.65rem !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: flex-start !important;
        overflow: hidden !important;
    }

    /* Inbound Metric Cards - Title on Top, Value Aligned at Bottom Baseline */
    .inbound-metric-card,
    .card.inbound-metric-card {
        min-height: 64px !important;
        height: 100% !important;
    }

    .inbound-metric-card .card-body,
    .card.inbound-metric-card .card-body {
        height: 100% !important;
        padding: 0.35rem 0.55rem !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
    }

    .inbound-metric-card .text-xs {
        white-space: normal !important;
        word-break: normal !important;
        line-height: 1.15 !important;
        margin-bottom: 0.15rem !important;
        font-size: 0.63rem !important;
    }

    .inbound-metric-card .h5,
    .inbound-metric-card h5 {
        margin-top: auto !important;
        margin-bottom: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.92rem !important;
        line-height: 1.1 !important;
    }

    .card[class*="border-left-"] .h5,
    .card[class*="border-left-"] h5,
    .card[class*="border-left-"] .h3,
    .card[class*="border-left-"] h3 {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.88rem !important;
        line-height: 1.15 !important;
        margin-bottom: 0 !important;
    }

    .card[class*="border-left-"] #card-last-update {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin-bottom: 0 !important;
    }

    .card[class*="border-left-"] .text-xs {
        font-size: 0.62rem !important;
        margin-bottom: 0.15rem !important;
        line-height: 1.15 !important;
    }

    .tooltip-inner {
        font-size: 0.72rem !important;
        padding: 0.25rem 0.5rem !important;
    }

    .card[class*="border-left-"] .progress {
        height: 4px !important;
        margin-top: 2px !important;
    }

    .card[class*="border-left-"] .fa-2x {
        font-size: 1.15em !important;
    }

    .card[class*="border-left-"] .row.no-gutters {
        width: 100% !important;
    }

    .card[class*="border-left-"] .col-auto {
        padding-left: 0.5rem !important;
        padding-right: 0.4rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Footer Compaction */
    .sticky-footer {
        padding: 0.4rem 0 !important;
    }

    .sticky-footer .copyright {
        font-size: 0.7rem !important;
        line-height: 1.2 !important;
    }

    /* Card Text Overlap & Wrap Fix */
    .card .text-nowrap,
    .card-body .text-nowrap,
    .status-card-clickable .text-nowrap,
    .card .text-xs,
    .status-card-clickable .text-xs {
        line-height: 1.25 !important;
    }

    body.sidebar-toggled #wms-sidebar .nav-item .nav-link .nav-lock-icon {
        display: none !important;
    }

    #wms-sidebar .nav-item .nav-link .nav-lock-icon {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-left: auto;
        opacity: 0.75;
        transition: color 0.2s ease, opacity 0.2s ease;
    }

    #wms-sidebar .nav-item .nav-link:hover .nav-lock-icon {
        color: #fbbf24;
        opacity: 1;
    }

    @keyframes bullhornPulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.22);
        }

        100% {
            transform: scale(1);
        }
    }

    .animate-pulse {
        animation: bullhornPulse 1.8s infinite ease-in-out;
        display: inline-block;
    }

    /* =============================================
   MOBILE RESPONSIVE STYLES
   ============================================= */

    /* Mobile Sidebar Overlay Backdrop */
    #sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1035;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    body.mobile-sidebar-open #sidebar-overlay {
        display: block;
        opacity: 1;
    }

    /* Prevent body scroll when mobile sidebar is open */
    body.mobile-sidebar-open {
        overflow: hidden !important;
    }

    /* Make sidebar scrollable on mobile */
    @media (max-width: 991.98px) {
        #wms-sidebar {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        #wms-sidebar .sidebar-nav {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* Prevent horizontal overflow on entire page */
    html,
    body {
        overflow-x: hidden !important;
    }

    /* ─── Override inline padding-top on ALL pages ─── */
    @media (max-width: 768px) {

        .container-fluid[style*="padding-top: 100px"],
        .container-fluid[style*="padding-top:100px"],
        .container[style*="padding-top: 120px"],
        .container[style*="padding-top:120px"] {
            padding-top: 60px !important;
        }
    }

    @media (max-width: 576px) {

        .container-fluid[style*="padding-top: 100px"],
        .container-fluid[style*="padding-top:100px"],
        .container[style*="padding-top: 120px"],
        .container[style*="padding-top:120px"] {
            padding-top: 50px !important;
        }
    }

    /* === Tablet Adjustments (≤991px) === */
    @media (max-width: 991.98px) {

        /* Dashboard & Outbound flow steps: allow horizontal scroll */
        #inbound-steps-container,
        #outbound-steps-container,
        .card-body>.d-flex.flex-nowrap,
        .card-body>.mt-3>.d-flex.flex-nowrap {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
        }

        /* Flow step minimum width so they don't collapse */
        .flow-step {
            min-width: 80px !important;
            flex-shrink: 0 !important;
        }

        /* Flow step icons shrink slightly */
        .flow-step .rounded-circle {
            width: 32px !important;
            height: 32px !important;
        }

        .flow-step .rounded-circle i {
            font-size: 0.8rem !important;
        }

        /* Warehouse cards: 2 per row on tablet */
        .col-xl-3.col-md-6,
        .col-xl-2.col-md-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }
    }

    /* === Mobile Topbar Adjustments (≤768px) === */
    @media (max-width: 768px) {

        /* Compact topbar height */
        .topbar {
            height: 3rem !important;
            padding: 0 0.5rem !important;
        }

        /* Hide announcement button text, show icon only */
        #announcement-notify-wrapper #btn-announcement-notify span {
            display: none !important;
        }

        #announcement-notify-wrapper #btn-announcement-notify {
            padding: 0.3rem 0.5rem !important;
            min-width: unset !important;
        }

        #announcement-notify-wrapper #btn-announcement-notify i {
            margin-right: 0 !important;
        }

        /* Period selector: show calendar icon clearly, hide text */
        #selected-period-text {
            display: none !important;
        }

        #periodDropdown {
            padding: 0.35rem 0.5rem !important;
        }

        #periodDropdown .fa-calendar-alt {
            font-size: 1.25rem !important;
            color: #5a5c69 !important;
        }

        #periodDropdown .fa-chevron-down {
            font-size: 0.55rem !important;
            margin-left: 0.15rem !important;
        }

        /* User dropdown: show icon + name/role compact on mobile */
        #userDropdown {
            padding: 0.2rem 0.35rem !important;
        }

        #userDropdown .fa-user-circle {
            font-size: 1.3rem !important;
            color: #5a5c69 !important;
        }

        #userDropdown .user-info-mobile {
            display: flex !important;
        }

        #userDropdown .user-info-mobile .user-name-text {
            font-size: 0.68rem !important;
            max-width: 90px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #userDropdown .user-info-mobile .user-role-text {
            font-size: 0.55rem !important;
            max-width: 90px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #userDropdown .fa-chevron-down {
            font-size: 0.55rem !important;
            margin-left: 0.1rem !important;
        }


        /* Reduce hamburger button size */
        #sidebarToggleTop {
            padding: 0.2rem 0.4rem !important;
            margin-right: 0.25rem !important;
        }

        /* Ensure dropdown menus don't overflow screen */
        .topbar .dropdown-menu {
            max-width: calc(100vw - 1rem) !important;
            right: 0 !important;
            left: auto !important;
        }

        /* Period dropdown - make it mobile friendly */
        #period-dropdown-menu {
            min-width: 260px !important;
            max-width: calc(100vw - 2rem) !important;
            right: -0.5rem !important;
        }

        /* Topbar divider hidden on mobile */
        .topbar-divider {
            display: none !important;
        }

        /* Compact container-fluid */
        .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            padding-top: 60px !important;
        }

        /* Page heading responsive */
        h1,
        .h1,
        h1.h3 {
            font-size: 1rem !important;
        }

        /* ─── Card grid responsive: metric cards 2 per row ─── */
        .col-xl-2.col-md-4.col-sm-6,
        .col-xl-4.col-md-4.col-sm-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        /* Warehouse metric cards - 2 per row */
        .col-xl-3.col-md-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        /* Warehouse smaller cards (col-xl-2) stack 2 per row */
        .col-xl-2.col-md-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        /* Chart cards: full width on mobile */
        .col-xl-6.col-lg-6,
        .col-xl-4.col-lg-4 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Chart container heights: readable & uncompressed on mobile */
        .chart-bar,
        .chart-pie,
        .chart-area,
        div[style*="height: 320px"],
        div[style*="height: 300px"],
        div[style*="height: 260px"],
        div[style*="height: 250px"] {
            height: 260px !important;
            max-height: 280px !important;
        }

        /* Charts with inner min-width scroll containers */
        .chart-bar>div[style*="min-width"],
        .chart-area>div[style*="min-width"] {
            min-width: 100% !important;
        }

        /* Chart card overflow: allow horizontal scroll */
        .chart-bar {
            overflow-x: auto !important;
            overflow-y: hidden !important;
        }

        /* Card headers & body compact on mobile */
        .card .card-header h6 {
            font-size: 0.72rem !important;
            line-height: 1.3 !important;
        }

        .card .card-header h6 span {
            font-size: 0.62rem !important;
        }

        /* Status flow cards: stack properly */
        .status-card-clickable {
            margin-bottom: 0.5rem !important;
        }

        /* Dashboard flow steps: smaller icons & text */
        .flow-step {
            min-width: 70px !important;
            padding: 0.25rem !important;
        }

        .flow-step .rounded-circle {
            width: 28px !important;
            height: 28px !important;
            margin-bottom: 0.25rem !important;
        }

        .flow-step .rounded-circle i {
            font-size: 0.7rem !important;
        }

        .flow-step .font-weight-bold[style*="font-size: 0.62rem"] {
            font-size: 0.52rem !important;
        }

        .flow-step .font-weight-bold[style*="font-size: 0.82rem"] {
            font-size: 0.68rem !important;
        }

        /* Flow step arrows smaller */
        .text-gray-300.align-self-center {
            font-size: 0.5rem !important;
            margin: 0 !important;
        }

        /* Dashboard summary card badges */
        .badge[style*="font-size: 0.65rem"] {
            font-size: 0.55rem !important;
            padding: 0.15rem 0.4rem !important;
        }

        /* Dashboard pie chart section */
        #dashInboundFlowPieChart {
            max-height: 120px !important;
        }

        /* Dashboard Inventory/Outbound summary layout: stack columns */
        .col-xl-9.col-lg-8,
        .col-xl-8.col-lg-8,
        .col-xl-7.col-lg-7 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        .col-xl-3.col-lg-4,
        .col-xl-4.col-lg-4.col-md-12,
        .col-xl-5.col-lg-5 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            border-left: none !important;
            padding-left: 15px !important;
            margin-top: 0.75rem;
        }

        /* Outbound: ranking & sebaran inner cards stack */
        .col-lg-6.d-flex {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Row negative margin fix for tight layouts */
        .row[style*="margin-left: -4px"] {
            margin-left: -2px !important;
            margin-right: -2px !important;
        }

        .row[style*="margin-left: -4px"]>[class*="col-"] {
            padding-left: 2px !important;
            padding-right: 2px !important;
        }

        /* Fixed-height card overrides for mobile */
        .storage-card,
        .card[class*="border-left-"]:not(.inbound-metric-card) {
            min-height: auto !important;
            max-height: none !important;
            height: auto !important;
        }

        /* Outbound metric cards allow auto height */
        .card.border-left-primary,
        .card.border-left-success,
        .card.border-left-info,
        .card.border-left-warning,
        .card.border-left-danger,
        .card.border-left-secondary {
            min-height: auto !important;
            max-height: none !important;
            height: auto !important;
        }

        /* Outbound card body text auto size */
        .card .h4 {
            font-size: 0.85rem !important;
            word-break: break-word !important;
        }

        /* Table: tighter on mobile */
        .table td,
        .table th {
            padding: 0.25rem 0.35rem !important;
            font-size: 0.72rem !important;
        }

        /* Modal adjustments */
        .modal-dialog {
            margin: 0.5rem !important;
            max-width: calc(100vw - 1rem) !important;
        }

        .modal-body {
            padding: 0.75rem 1rem !important;
        }

        #announcementNoticeModal .modal-body {
            padding: 1rem 1.8rem !important;
            max-height: 85vh !important;
            overflow-y: auto !important;
        }

        #announcementNoticeModal #btnBodyNoticePrev {
            left: 4px !important;
        }

        #announcementNoticeModal #btnBodyNoticeNext {
            right: 4px !important;
        }

        #announcementNoticeModal #noticeBottomBadge {
            max-width: 100% !important;
            white-space: normal !important;
            font-size: 0.75rem !important;
            padding: 0.5rem 0.75rem !important;
        }

        .modal-dialog.modal-lg {
            max-width: calc(100vw - 1rem) !important;
        }

        .modal-body[style*="max-height: 75vh"] {
            max-height: 65vh !important;
        }

        /* WMS Select page: cards full width on mobile */
        .module-card .module-icon-bg {
            height: 100px !important;
        }

        .module-card .card-body {
            padding: 1rem !important;
        }

        .module-card h4 {
            font-size: 1rem !important;
        }
    }

    /* === Small Mobile (≤576px) === */
    @media (max-width: 576px) {
        .topbar {
            height: 2.8rem !important;
            padding: 0 0.35rem !important;
        }

        /* Further reduce topbar button sizes */
        #sidebarToggleTop {
            padding: 0.15rem 0.3rem !important;
            font-size: 0.9rem !important;
        }

        /* Container fluid tighter */
        .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            padding-top: 50px !important;
        }

        /* Non-sidebar pages - compact logo on mobile */
        .topbar .sidebar-brand-img,
        .topbar img[alt="WMS Logo"] {
            max-height: 32px !important;
            height: 32px !important;
        }

        /* Metric cards: single column on very small screens */
        .col-xl-2.col-md-4.col-sm-6,
        .col-xl-4.col-md-4.col-sm-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Warehouse metric cards single column */
        .col-xl-3.col-md-6,
        .col-xl-2.col-md-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Outbound metric cards: single column */
        .col-xl-4.col-md-4 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Chart containers readable on small mobile */
        .chart-pie,
        .chart-bar,
        .chart-area,
        div[style*="height: 320px"],
        div[style*="height: 300px"],
        div[style*="height: 260px"],
        div[style*="height: 250px"],
        div[style*="height: 220px"] {
            height: 240px !important;
            max-height: 260px !important;
        }

        /* Dashboard flow steps: even more compact */
        .flow-step {
            min-width: 60px !important;
            padding: 0.15rem !important;
        }

        .flow-step .rounded-circle {
            width: 24px !important;
            height: 24px !important;
        }

        .flow-step .rounded-circle i {
            font-size: 0.6rem !important;
        }

        .flow-step div[style*="margin-top"] {
            margin-top: 0.15rem !important;
        }

        /* Flow arrows hide on very small screens to save space */
        .text-gray-300.align-self-center .fa-chevron-right {
            font-size: 0.4rem !important;
        }

        /* Dashboard card titles compact */
        .card-body .font-weight-bold[style*="font-size: 0.85rem"] {
            font-size: 0.72rem !important;
        }

        /* Dashboard pie chart even smaller */
        div[style*="height: 145px"],
        div[style*="height: 110px"] {
            height: 100px !important;
            max-height: 100px !important;
        }

        /* KPI boxes compact */
        .bg-light.rounded.p-2 .row .col-6 div[style*="font-size: 1.1rem"] {
            font-size: 0.85rem !important;
        }

        .bg-light.rounded.p-2 .row .col-6 div[style*="font-size: 0.6rem"] {
            font-size: 0.5rem !important;
        }

        /* Outbound ranking inner cards */
        .card.border.shadow-sm .card-body {
            padding: 0.5rem !important;
        }

        .card.border.shadow-sm h6 {
            font-size: 0.68rem !important;
            margin-bottom: 0.5rem !important;
        }

        /* Table horizontal scroll */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        /* Non-sidebar pages: logo compact */
        .topbar .d-flex[style*="cursor: default"] img {
            max-height: 30px !important;
            height: 30px !important;
        }

        #announcementNoticeModal .modal-body {
            padding: 0.75rem 1.25rem !important;
        }

        #announcementNoticeModal #noticeIconContainer {
            width: 50px !important;
            height: 50px !important;
        }

        #announcementNoticeModal #noticeIconEl {
            font-size: 1.25rem !important;
        }

        /* WMS Select module cards */
        .col-lg-4.col-md-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        .module-card .module-icon-bg {
            height: 80px !important;
        }

        .module-card .module-icon-bg i {
            font-size: 2rem !important;
        }

        /* Page heading stack on small screens */
        .d-sm-flex {
            flex-direction: column !important;
        }

        .d-sm-flex h1 {
            margin-bottom: 0.5rem !important;
        }
    }

    /* === Very Small Mobile (≤400px) === */
    @media (max-width: 400px) {

        /* Even tighter container */
        .container-fluid {
            padding-left: 0.35rem !important;
            padding-right: 0.35rem !important;
        }

        /* Body font further reduced */
        body {
            font-size: 0.75rem !important;
        }

        /* Card body tighter */
        .card .card-body {
            padding: 0.3rem 0.4rem !important;
        }

        #announcementNoticeModal #noticeBottomBadge {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    }
</style>

<?php if ($showSidebar): ?>
    <!-- Left Sidebar Navigation -->
    <aside id="wms-sidebar">
        <div class="sidebar-brand text-center" style="padding: 0.5rem 1rem; min-height: 60px;">
            <div class="d-flex align-items-center justify-content-center text-white h-100" style="cursor: default;">
                <img src="frontend/img/WMS_Logo.png" alt="WMS Logo" class="sidebar-brand-img"
                    style="max-height: 50px; height: 50px; width: auto; max-width: 100%; object-fit: contain; filter: brightness(0) invert(1); pointer-events: none;">
            </div>
        </div>

        <ul class="sidebar-nav">
            <?php
            $navRole = $navUser['role'] ?? '';
            $isHeadRoleNav = (strpos($navRole, 'head_') === 0) || ($navRole === 'head_warehouse_admin');
            $isSuperAdminNav = ($navRole === 'superadmin');

            // Access Permission Check Flags - strictly based on RBAC checkboxes
            $hasDashboardAccess = !$isSuperAdminNav && in_array('dashboard', $allowedNavModules);
            $hasInboundAccess = !$isSuperAdminNav && in_array('inbound', $allowedNavModules);
            $hasWarehouseAccess = !$isSuperAdminNav && in_array('warehouse', $allowedNavModules);
            $hasOutboundAccess = !$isSuperAdminNav && in_array('outbound', $allowedNavModules);
            $hasKpiMonitoringAccess = !$isSuperAdminNav && in_array('kpi_monitoring', $allowedNavModules);
            $hasMainMenuSection = $hasInboundAccess || $hasWarehouseAccess || $hasOutboundAccess || $hasKpiMonitoringAccess;
            $hasMasterDataAccess = !$isSuperAdminNav && (in_array('master_data', $allowedNavModules) || in_array('master_data_inbound', $allowedNavModules) || in_array('master_data_storage', $allowedNavModules) || in_array('master_data_outbound', $allowedNavModules) || in_array('site_location', $allowedNavModules));
            $hasDataSettingsSection = $hasMasterDataAccess;
            $hasReportsAccess = !$isSuperAdminNav && in_array('reports', $allowedNavModules);
            $hasAnalyticsAccess = !$isSuperAdminNav && in_array('analytics', $allowedNavModules);
            $hasReportSection = $hasReportsAccess || $hasAnalyticsAccess;
            $hasUserMgmtAccess = $isSuperAdminNav;
            $hasAnnouncementsAccess = $isSuperAdminNav || in_array('announcements', $allowedNavModules);
            $hasRepositoryManagementAccess = $isSuperAdminNav || ($navRole === 'repository_admin') || in_array('repository_management', $allowedNavModules);
            $hasSystemSection = $hasUserMgmtAccess || $hasAnnouncementsAccess || $hasRepositoryManagementAccess;
            ?>

            <!-- Overview Section -->
            <?php if ($hasDashboardAccess): ?>
                <div class="sidebar-heading">Overview</div>
                <li class="nav-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-th-large fa-fw"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Main Menu Section -->
            <?php if ($hasMainMenuSection): ?>
                <div class="sidebar-heading mt-2">Main Menu</div>
                <?php if ($hasInboundAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'inbound') ? 'active' : ''; ?>">
                        <a class="nav-link" href="inbound.php">
                            <i class="fas fa-box-open fa-fw"></i>
                            <span>Inbound</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($hasWarehouseAccess):
                    $isStorageActive = in_array($activePage, ['warehouse', 'storage_hub']);
                    ?>
                    <li class="nav-item <?php echo $isStorageActive ? 'active' : ''; ?>">
                        <a class="nav-link collapsed d-flex align-items-center justify-content-between w-100"
                            href="#collapseStorageMenu" data-toggle="collapse" data-target="#collapseStorageMenu"
                            aria-expanded="false" aria-controls="collapseStorageMenu" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-warehouse fa-fw"></i>
                                <span>Storage</span>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </a>
                        <div id="collapseStorageMenu" class="collapse <?php echo $isStorageActive ? 'show' : ''; ?>"
                            data-parent="#wms-sidebar">
                            <div class="sidebar-submenu">
                                <a class="sub-link <?php echo ($activePage == 'warehouse') ? 'active' : ''; ?>"
                                    href="warehouse.php">
                                    <i class="fas fa-building"></i> <span>Storage Tekno</span>
                                </a>
                                <a class="sub-link <?php echo ($activePage == 'storage_hub') ? 'active' : ''; ?>"
                                    href="storage_hub.php">
                                    <i class="fas fa-store-alt"></i> <span>Storage HUB &amp; Outlet</span>
                                </a>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
                <?php if ($hasOutboundAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'outbound') ? 'active' : ''; ?>">
                        <a class="nav-link" href="outbound.php">
                            <i class="fas fa-truck-loading fa-fw"></i>
                            <span>Outbound</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($hasKpiMonitoringAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'kpi_monitoring') ? 'active' : ''; ?>">
                        <a class="nav-link" href="kpi_monitoring.php">
                            <i class="fas fa-tachometer-alt fa-fw"></i>
                            <span>KPI Monitoring</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Data Settings Section -->
            <?php if ($hasDataSettingsSection): ?>
                <div class="sidebar-heading mt-2">Data Settings</div>
                <?php if ($hasMasterDataAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'master_data') ? 'active' : ''; ?>">
                        <a class="nav-link" href="master_data.php">
                            <i class="fas fa-database fa-fw"></i>
                            <span>Master Data</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Report Section -->
            <?php if ($hasReportSection): ?>
                <div class="sidebar-heading mt-2">Report</div>
                <?php if ($hasReportsAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'reports') ? 'active' : ''; ?>">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-file-alt fa-fw"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($hasAnalyticsAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'analytics') ? 'active' : ''; ?>">
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-line fa-fw"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- System Section -->
            <?php if ($hasSystemSection): ?>
                <div class="sidebar-heading mt-2">System</div>
                <?php if ($hasUserMgmtAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'user_management') ? 'active' : ''; ?>">
                        <a class="nav-link" href="user_management.php">
                            <i class="fas fa-users-cog fa-fw"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($hasAnnouncementsAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'announcements') ? 'active' : ''; ?>">
                        <a class="nav-link" href="announcements.php">
                            <i class="fas fa-bullhorn fa-fw"></i>
                            <span>Pengumuman</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($hasRepositoryManagementAccess): ?>
                    <li class="nav-item <?php echo ($activePage == 'repository_management') ? 'active' : ''; ?>">
                        <a class="nav-link" href="repository_management.php">
                            <i class="fas fa-folder-open fa-fw"></i>
                            <span>Repository</span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

    </aside>
<?php endif; ?>

<!-- Topbar Navigation -->
<!-- Mobile Sidebar Overlay Backdrop -->
<div id="sidebar-overlay"></div>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 fixed-top shadow" style="z-index: 1020;">
    <?php if ($showSidebar): ?>
        <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-2 text-primary">
            <i class="fa fa-bars"></i>
        </button>

        <!-- Announcement Broadcast Button (Beside Hamburger on sidebar pages) -->
        <div id="announcement-notify-wrapper" class="d-flex align-items-center mr-2">
            <?php if ($navAnnouncementStatus === 'active' && $navAnnouncement): ?>
                <button id="btn-announcement-notify"
                    class="btn btn-primary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1"
                    style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.82rem; color: #ffffff !important; cursor: pointer;"
                    title="Klik untuk melihat Pengumuman Maintenance" data-toggle="modal"
                    data-target="#announcementNoticeModal">
                    <i class="fas fa-bullhorn mr-1 text-white" style="font-size: 0.9rem;"></i>
                    <span class="font-weight-bold">Pengumuman</span>
                </button>
            <?php else: ?>
                <button id="btn-announcement-notify"
                    class="btn btn-secondary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1"
                    style="background: #b0b7c3; opacity: 0.65; border: none; font-size: 0.82rem; color: #ffffff !important; cursor: not-allowed;"
                    title="Tidak ada Pengumuman aktif" disabled>
                    <i class="fas fa-bullhorn mr-1 text-white" style="font-size: 0.9rem;"></i>
                    <span class="font-weight-bold">Pengumuman</span>
                </button>
            <?php endif; ?>
        </div>
        <div class="mr-auto"></div>

    <?php else: ?>
        <!-- Logo First, then Announcement Button on the Right Side of Logo -->
        <div class="d-flex align-items-center mr-auto my-2 ml-md-3">
            <div class="d-flex align-items-center mr-3" style="cursor: default;">
                <img src="frontend/img/WMS_Logo.png" alt="WMS Logo"
                    style="max-height: 50px; height: 50px; width: auto; object-fit: contain; pointer-events: none;">
            </div>

            <!-- Announcement Broadcast Button (On right side of Logo for non-sidebar pages like wms_select) -->
            <div id="announcement-notify-wrapper" class="d-flex align-items-center">
                <?php if ($navAnnouncementStatus === 'active' && $navAnnouncement): ?>
                    <button id="btn-announcement-notify"
                        class="btn btn-primary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1"
                        style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.82rem; color: #ffffff !important; cursor: pointer;"
                        title="Klik untuk melihat Pengumuman Maintenance" data-toggle="modal"
                        data-target="#announcementNoticeModal">
                        <i class="fas fa-bullhorn mr-1 text-white" style="font-size: 0.9rem;"></i>
                        <span class="font-weight-bold">Pengumuman</span>
                    </button>
                <?php else: ?>
                    <button id="btn-announcement-notify"
                        class="btn btn-secondary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1"
                        style="background: #b0b7c3; opacity: 0.65; border: none; font-size: 0.82rem; color: #ffffff !important; cursor: not-allowed;"
                        title="Tidak ada Pengumuman aktif" disabled>
                        <i class="fas fa-bullhorn mr-1 text-white" style="font-size: 0.9rem;"></i>
                        <span class="font-weight-bold">Pengumuman</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$shouldHideNavbarUl): ?>
        <ul class="navbar-nav ml-auto align-items-center text-nowrap" style="white-space: nowrap;">
            <?php if (!isset($hidePeriodSelector) || !$hidePeriodSelector): ?>
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle text-nowrap d-flex align-items-center" href="#" id="periodDropdown"
                        role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="far fa-calendar-alt fa-fw text-gray-600" style="font-size: 1.1rem;"></i>
                        <span class="ml-2 d-none d-lg-inline text-gray-600 small text-nowrap" id="selected-period-text"
                            style="font-weight: bold;">PILIH PERIODE DATA</span> <i
                            class="fas fa-chevron-down fa-sm fa-fw text-gray-400 ml-1"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in p-3" aria-labelledby="periodDropdown"
                        id="period-dropdown-menu" style="min-width: 290px;">
                        <h6 class="dropdown-header px-0 pt-0 text-primary font-weight-bold mb-2">
                            <i class="far fa-calendar-alt mr-1"></i> PILIH PERIODE DATA
                        </h6>

                        <?php if ($activePage == 'storage_hub'): ?>
                            <div class="form-group mb-2" id="site-select-group">
                                <label for="period-site-select" class="small font-weight-bold text-gray-600 mb-1">HUB/Outlet
                                    Warehouse</label>
                                <select class="form-control form-control-sm" id="period-site-select">
                                    <option value="">-- Pilih HUB/Outlet Warehouse --</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-group mb-2">
                            <label for="period-month-select" class="small font-weight-bold text-gray-600 mb-1">Bulan</label>
                            <select class="form-control form-control-sm" id="period-month-select">
                                <option value="">-- Pilih Bulan --</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="period-batch-select" class="small font-weight-bold text-gray-600 mb-1">Batch</label>
                            <select class="form-control form-control-sm" id="period-batch-select">
                                <option value="">-- Pilih Batch --</option>
                                <option value="1">Batch 1</option>
                                <option value="2">Batch 2</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="period-year-select" class="small font-weight-bold text-gray-600 mb-1">Tahun</label>
                            <select class="form-control form-control-sm" id="period-year-select">
                                <option value="">-- Pilih Tahun --</option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm btn-block font-weight-bold mt-3" id="btn-load-period" disabled>
                            <i class="fas fa-check mr-1"></i>Tampilkan Data
                        </button>
                        <button class="btn btn-outline-secondary btn-sm btn-block font-weight-bold mt-2" id="btn-reset-period">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </button>
                    </div>
                </li>
            <?php endif; ?>

            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- User Information & Logout Dropdown -->
            <?php if ($navUser['is_logged_in']): ?>
                <li class="nav-item dropdown no-arrow text-nowrap">
                    <a class="nav-link dropdown-toggle text-nowrap py-0 d-flex align-items-center" href="#" id="userDropdown"
                        role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="d-flex align-items-center justify-content-center" style="min-width: 28px;">
                            <i class="fas fa-user-circle text-gray-400" style="font-size: 1.75rem;"></i>
                        </div>
                        <div class="ml-2 d-flex flex-column text-left text-nowrap justify-content-center user-info-mobile">
                            <span class="text-gray-800 small font-weight-bold text-nowrap user-name-text"
                                style="line-height: 1.2;">
                                <?php echo htmlspecialchars($navUser['name']); ?>
                            </span>
                            <span class="text-muted text-nowrap user-role-text"
                                style="font-size: 0.68rem; line-height: 1.1; margin-top: 1px;">
                                <?php echo htmlspecialchars(!empty($navUser['job_title']) ? $navUser['job_title'] : $navRoleTitle); ?>
                            </span>
                        </div>
                        <i class="fas fa-chevron-down fa-sm fa-fw text-gray-400 ml-1"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in p-1 text-nowrap"
                        aria-labelledby="userDropdown" style="min-width: 190px; width: max-content;">
                        <div class="dropdown-header text-muted font-weight-bold py-2 px-3 border-bottom mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Role: <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($navRoleTitle); ?></span>
                        </div>
                        <a class="dropdown-item text-gray-700 font-weight-bold text-nowrap py-2" href="#" data-toggle="modal"
                            data-target="#changePasswordModal">
                            <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-500"></i> Ganti Password
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item text-danger font-weight-bold text-nowrap py-2" href="login.php?action=logout">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i> Logout
                        </a>
                    </div>
                </li>
            <?php elseif (!$shouldHideLoginButton): ?>
                <li class="nav-item text-nowrap">
                    <a class="btn btn-primary btn-sm px-3 font-weight-bold text-nowrap" href="login.php">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebarToggle = document.getElementById('sidebarToggleTop');
        var sidebarOverlay = document.getElementById('sidebar-overlay');

        function closeMobileSidebar() {
            document.body.classList.remove('mobile-sidebar-open');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (window.innerWidth < 992) {
                    document.body.classList.toggle('mobile-sidebar-open');
                } else {
                    document.body.classList.toggle('sidebar-toggled');
                }
            }, true);
        }

        // Close mobile sidebar when clicking overlay backdrop
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeMobileSidebar);
        }

        // Close mobile sidebar when clicking a nav link (for navigation)
        var sidebarNavLinks = document.querySelectorAll('#wms-sidebar .nav-link:not([data-toggle="collapse"]), #wms-sidebar .sub-link');
        for (var i = 0; i < sidebarNavLinks.length; i++) {
            sidebarNavLinks[i].addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeMobileSidebar();
                }
            });
        }

        // Auto-close sidebar on window resize to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeMobileSidebar();
            }
        });

        var startDateInput = document.getElementById('period-start-date');
        var endDateInput = document.getElementById('period-end-date');
        var btnLoad = document.getElementById('btn-load-period');
        var selectedText = document.getElementById('selected-period-text');

        if (startDateInput && endDateInput && btnLoad) {
            function updateDateRangeBtn() {
                if (startDateInput.value && endDateInput.value) {
                    btnLoad.disabled = false;
                } else {
                    btnLoad.disabled = true;
                }
            }

            startDateInput.addEventListener('change', updateDateRangeBtn);
            endDateInput.addEventListener('change', updateDateRangeBtn);

            btnLoad.addEventListener('click', function () {
                if (startDateInput.value && endDateInput.value) {
                    var startFormatted = formatDateIndo(startDateInput.value);
                    var endFormatted = formatDateIndo(endDateInput.value);
                    var displayRange = startFormatted + ' - ' + endFormatted;

                    if (selectedText) {
                        selectedText.textContent = displayRange;
                    }

                    // Close dropdown if jQuery/Bootstrap is loaded
                    if (typeof $ !== 'undefined' && $('#periodDropdown').length) {
                        $('#periodDropdown').dropdown('toggle');
                    }

                    // Dispatch global custom event for dateRangeChanged
                    window.dispatchEvent(new CustomEvent('dateRangeChanged', {
                        detail: {
                            startDate: startDateInput.value,
                            endDate: endDateInput.value,
                            displayRange: displayRange
                        }
                    }));
                }
            });
        }

        var btnReset = document.getElementById('btn-reset-period');
        if (btnReset) {
            btnReset.addEventListener('click', function (e) {
                e.stopPropagation();
                if (startDateInput) startDateInput.value = '';
                if (endDateInput) endDateInput.value = '';

                var monthSel = document.getElementById('period-month-select');
                var yearSel = document.getElementById('period-year-select');
                if (monthSel) monthSel.value = '';
                if (yearSel) yearSel.value = '';

                if (btnLoad) btnLoad.disabled = true;
            });
        }

        function formatDateIndo(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return dateStr;
        }
    });
</script>

<!-- Check & Display Maintenance Announcement Modal for Logged-In Users -->
<script>
    // Auto-show Announcement Modal on session load (zero-latency server-side state)
    (function () {
        var isAnnouncementActive = <?php echo ($navAnnouncementStatus === 'active') ? 'true' : 'false'; ?>;
        var annId = '<?php echo htmlspecialchars($navAnnouncement["id"] ?? ""); ?>';
        var annUpdatedAt = '<?php echo htmlspecialchars($navAnnouncement["updated_at"] ?? ""); ?>';
        var currentUserId = '<?php echo htmlspecialchars($navUser["id"] ?? "guest"); ?>';

        if (!isAnnouncementActive) return;

        function initAnnouncementModal() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initAnnouncementModal, 50);
                return;
            }
            jQuery(document).ready(function ($) {
                var storageKey = 'announcement_dismissed_u' + currentUserId + '_' + annId + '_' + annUpdatedAt;
                if (!sessionStorage.getItem(storageKey)) {
                    $('#announcementNoticeModal').modal('show');
                    $('#announcementNoticeModal').on('hidden.bs.modal', function () {
                        sessionStorage.setItem(storageKey, 'true');
                    });
                }
            });
        }

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initAnnouncementModal();
        } else {
            document.addEventListener('DOMContentLoaded', initAnnouncementModal);
        }
    })();

    // ─── Real-time Announcement Polling (no reload needed) ───
    (function () {
        var POLL_INTERVAL_MS = 10000; // Check every 10 seconds for near-instant updates
        var BASE_PATH = (function () {
            // Resolve API path relative to the current page
            var scripts = document.getElementsByTagName('script');
            // Fallback: detect from current URL
            var path = window.location.pathname;
            var segments = path.split('/');
            segments.pop(); // remove filename
            return segments.join('/') + '/';
        })();

        var lastFingerprint = '';
        var currentUserId = '<?php echo htmlspecialchars($navUser["id"] ?? "guest"); ?>';

        // Capture initial state fingerprint from server-rendered data
        var initialId = '<?php echo htmlspecialchars($navAnnouncement["id"] ?? ""); ?>';
        var initialUpdatedAt = '<?php echo htmlspecialchars($navAnnouncement["updated_at"] ?? ""); ?>';
        if (initialId) {
            lastFingerprint = initialId + '|' + initialUpdatedAt;
        }

        function pollAnnouncement() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', BASE_PATH + 'api/get_active_announcement.php?_t=' + Date.now(), true);
            xhr.timeout = 10000;
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status !== 200) return;

                try {
                    var data = JSON.parse(xhr.responseText);
                } catch (e) { return; }

                if (!data.success) return;

                if (data.active && data.status === 'active' && (data.announcements || data.announcement)) {
                    var list = data.announcements || [data.announcement];
                    window.wmsAnnouncementsList = list;
                    if (!window.wmsAnnouncementIdx || window.wmsAnnouncementIdx >= list.length) {
                        window.wmsAnnouncementIdx = 0;
                    }

                    var ann = list[window.wmsAnnouncementIdx];
                    var newFingerprint = ann.id + '|' + (ann.updated_at || '') + '|' + list.length;

                    // Update the navbar button to ACTIVE state
                    updateNavbarButton(true);

                    // Update modal content with latest data
                    updateModalContent(ann);

                    // If this is a NEW or UPDATED announcement, auto-show the modal
                    if (newFingerprint !== lastFingerprint) {
                        lastFingerprint = newFingerprint;
                        var storageKey = 'announcement_dismissed_u' + currentUserId + '_' + ann.id + '_' + (ann.updated_at || '');

                        // Only auto-show if user hasn't dismissed this exact version
                        if (!sessionStorage.getItem(storageKey)) {
                            showAnnouncementModal(storageKey);
                        }
                    }
                } else {
                    // No active announcement — disable the button
                    if (lastFingerprint !== '') {
                        lastFingerprint = '';
                        updateNavbarButton(false);
                        hideAnnouncementModal();
                    }
                }
            };
            xhr.send();
        }

        function updateNavbarButton(isActive) {
            // Update ALL announcement buttons on the page (sidebar pages have one, non-sidebar pages have another)
            var wrappers = document.querySelectorAll('#announcement-notify-wrapper');
            for (var i = 0; i < wrappers.length; i++) {
                var btn = wrappers[i].querySelector('#btn-announcement-notify, button');
                if (!btn) continue;

                if (isActive) {
                    btn.className = 'btn btn-primary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1';
                    btn.style.cssText = 'background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.82rem; color: #ffffff !important; cursor: pointer;';
                    btn.title = 'Klik untuk melihat Pengumuman Maintenance';
                    btn.disabled = false;
                    btn.setAttribute('data-toggle', 'modal');
                    btn.setAttribute('data-target', '#announcementNoticeModal');
                } else {
                    btn.className = 'btn btn-secondary btn-sm rounded-pill font-weight-bold shadow-sm d-flex align-items-center px-3 py-1';
                    btn.style.cssText = 'background: #b0b7c3; opacity: 0.65; border: none; font-size: 0.82rem; color: #ffffff !important; cursor: not-allowed;';
                    btn.title = 'Tidak ada Pengumuman aktif';
                    btn.disabled = true;
                    btn.removeAttribute('data-toggle');
                    btn.removeAttribute('data-target');
                }
            }
        }

        window.navigateWmsAnnouncement = function (dir) {
            var list = window.wmsAnnouncementsList || [];
            if (list.length <= 1) return;
            window.wmsAnnouncementIdx = (window.wmsAnnouncementIdx + dir + list.length) % list.length;
            updateModalContent(list[window.wmsAnnouncementIdx]);
        };

        function updateModalContent(ann) {
            if (!ann) return;
            var modalHeaderEl = document.getElementById('announcementNoticeModalLabel');
            var iconContainerEl = document.getElementById('noticeIconContainer');
            var iconEl = document.getElementById('noticeIconEl');
            var titleEl = document.getElementById('noticeTitleText');
            var descEl = document.getElementById('noticeDescriptionText');
            var bottomBadgeEl = document.getElementById('noticeBottomBadge');
            var prevBtn = document.getElementById('btnBodyNoticePrev');
            var nextBtn = document.getElementById('btnBodyNoticeNext');
            var counterText = document.getElementById('noticeCounterText');

            var list = window.wmsAnnouncementsList || [];
            var total = list.length;
            if (total > 1) {
                if (prevBtn) prevBtn.style.display = 'inline-block';
                if (nextBtn) nextBtn.style.display = 'inline-block';
            } else {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
            }

            if (titleEl) titleEl.textContent = ann.title || '';
            if (descEl) descEl.textContent = ann.description || '';

            var isUpdate = (ann.type === 'update');

            if (modalHeaderEl) {
                modalHeaderEl.innerHTML = '<i class="fas fa-bullhorn mr-2"></i>Pengumuman';
            }

            if (iconContainerEl && iconEl) {
                if (isUpdate) {
                    iconContainerEl.style.backgroundColor = '#fee2e2';
                    iconContainerEl.style.color = '#dc2626';
                    iconEl.className = 'fas fa-exclamation fa-2x font-weight-bold';
                } else {
                    iconContainerEl.style.backgroundColor = '#e2e8f0';
                    iconContainerEl.style.color = '#0b192c';
                    iconEl.className = 'fas fa-tools fa-2x';
                }
            }

            if (bottomBadgeEl) {
                if (isUpdate) {
                    var verText = ann.version_text || extractVersionFromJS(ann);
                    bottomBadgeEl.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Versi Aplikasi <br><span id="noticePeriodText" class="font-weight-bold">' + escapeHtmlJS(verText) + '</span>';
                } else {
                    bottomBadgeEl.innerHTML = '<i class="fas fa-clock mr-1"></i> Periode Maintenance: <br><span id="noticePeriodText" class="font-weight-bold">' + escapeHtmlJS(ann.formatted_period || '') + '</span>';
                }
            }
        }

        function extractVersionFromJS(ann) {
            if (ann.version_text) return ann.version_text;
            var text = (ann.title || '') + ' ' + (ann.description || '');
            var match = text.match(/(?:versi|version)?\s*((?:beta|alpha|rc)[-_ ]?v?\d+(?:\.\d+)*(?:[-_][a-z0-9]+)?)/i);
            if (match) return match[1].trim();

            match = text.match(/((?:beta|alpha|rc|v)?[-_]?v?\d+(?:\.\d+)+(?:[-_][a-z0-9]+)?)/i);
            if (match) {
                var ver = match[1].trim();
                if (/^[0-9]/.test(ver)) {
                    ver = 'V' + ver;
                }
                return ver;
            }
            match = text.match(/(?:versi|version)\s*([a-z0-9_\-.]+)/i);
            if (match) return match[1].trim();
            return 'V1.0.0';
        }

        function escapeHtmlJS(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

        function showAnnouncementModal(storageKey) {
            if (typeof jQuery === 'undefined') {
                setTimeout(function () { showAnnouncementModal(storageKey); }, 100);
                return;
            }
            jQuery(function ($) {
                var $modal = $('#announcementNoticeModal');
                $modal.modal('show');
                // Dismiss tracking: mark as seen when user closes
                $modal.off('hidden.bs.modal.poll').on('hidden.bs.modal.poll', function () {
                    if (storageKey) sessionStorage.setItem(storageKey, 'true');
                });
            });
        }

        function hideAnnouncementModal() {
            if (typeof jQuery !== 'undefined') {
                jQuery('#announcementNoticeModal').modal('hide');
            }
        }

        // Expose global trigger so admin page can force an immediate check
        window.__wmsForceAnnouncementPoll = function () {
            pollAnnouncement();
        };

        // Start polling: first check 2s after load, then every 10s
        function startPolling() {
            setTimeout(pollAnnouncement, 2000); // First check almost immediately
            setInterval(pollAnnouncement, POLL_INTERVAL_MS);
        }

        if (document.readyState === 'complete') {
            startPolling();
        } else {
            window.addEventListener('load', startPolling);
        }
    })();
</script>

<?php
$isUpdateType = ($navAnnouncement['type'] ?? 'maintenance') === 'update';
$navAnnText = ($navAnnouncement['title'] ?? '') . ' ' . ($navAnnouncement['description'] ?? '');
$navAnnVersion = 'V1.0.0';
if (preg_match('/(?:versi|version)?\s*((?:beta|alpha|rc)[-_ ]?v?\d+(?:\.\d+)*(?:[-_][a-z0-9]+)?)/i', $navAnnText, $m)) {
    $navAnnVersion = trim($m[1]);
} elseif (preg_match('/((?:beta|alpha|rc|v)?[-_]?v?\d+(?:\.\d+)+(?:[-_][a-z0-9]+)?)/i', $navAnnText, $m)) {
    $ver = trim($m[1]);
    $navAnnVersion = preg_match('/^[0-9]/', $ver) ? 'V' . $ver : $ver;
} elseif (preg_match('/(?:versi|version)\s*([a-z0-9_\-.]+)/i', $navAnnText, $m)) {
    $navAnnVersion = trim($m[1]);
}
?>
<script>
    window.wmsAnnouncementsList = <?php echo json_encode($navAnnouncementsList ?? []); ?>;
    window.wmsAnnouncementIdx = 0;
</script>

<!-- Global User Maintenance Announcement Modal -->
<div class="modal fade" id="announcementNoticeModal" tabindex="-1" role="dialog"
    aria-labelledby="announcementNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header border-0 text-white py-3 px-4"
                style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);">
                <h5 class="modal-title font-weight-bold text-white my-auto" id="announcementNoticeModalLabel">
                    <i class="fas fa-bullhorn mr-2"></i>Pengumuman
                </h5>
                <button type="button" class="close text-white opacity-75 my-auto" data-dismiss="modal"
                    aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center bg-white" style="position: relative; padding: 1.5rem 3.8rem !important;">
                <!-- Left Navigation Arrow on Modal Body (Pure Arrow, No Circle) -->
                <button type="button" id="btnBodyNoticePrev" class="btn p-0 border-0"
                    onclick="navigateWmsAnnouncement(-1)" title="Pengumuman Sebelumnya"
                    style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); z-index: 10; display: <?php echo (count($navAnnouncementsList ?? []) > 1) ? 'inline-block' : 'none'; ?>; color: #1e3e62; background: transparent; outline: none; cursor: pointer;">
                    <i class="fas fa-chevron-left fa-2x"></i>
                </button>

                <!-- Right Navigation Arrow on Modal Body (Pure Arrow, No Circle) -->
                <button type="button" id="btnBodyNoticeNext" class="btn p-0 border-0"
                    onclick="navigateWmsAnnouncement(1)" title="Pengumuman Selanjutnya"
                    style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; display: <?php echo (count($navAnnouncementsList ?? []) > 1) ? 'inline-block' : 'none'; ?>; color: #1e3e62; background: transparent; outline: none; cursor: pointer;">
                    <i class="fas fa-chevron-right fa-2x"></i>
                </button>

                <div class="mb-3">
                    <div class="icon-circle d-inline-flex align-items-center justify-content-center rounded-circle p-3 shadow-sm"
                        id="noticeIconContainer"
                        style="width: 70px; height: 70px; background-color: <?php echo $isUpdateType ? '#fee2e2' : '#e2e8f0'; ?>; color: <?php echo $isUpdateType ? '#dc2626' : '#0b192c'; ?>;">
                        <?php if ($isUpdateType): ?>
                            <i class="fas fa-exclamation fa-2x font-weight-bold" id="noticeIconEl"></i>
                        <?php else: ?>
                            <i class="fas fa-tools fa-2x" id="noticeIconEl"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <h5 class="font-weight-bold text-gray-800 mb-2" id="noticeTitleText">
                    <?php echo htmlspecialchars($navAnnouncement['title'] ?? ''); ?></h5>
                <p class="text-gray-700 small mb-3 text-left p-3 rounded"
                    style="background-color: #f8fafc; border: 1px solid #e2e8f0; white-space: pre-line; max-height: 180px; overflow-y: auto;"
                    id="noticeDescriptionText"><?php echo htmlspecialchars($navAnnouncement['description'] ?? ''); ?>
                </p>
                <div class="badge px-4 py-3 small font-weight-bold mt-3 mb-1" id="noticeBottomBadge"
                    style="font-size: 0.82rem; white-space: normal; background-color: #e2e8f0; color: #0b192c; line-height: 1.5;">
                    <?php if ($isUpdateType): ?>
                        <i class="fas fa-info-circle mr-1"></i>Versi Aplikasi <br><span id="noticePeriodText"
                            class="font-weight-bold"><?php echo htmlspecialchars($navAnnouncement['version_text'] ?? $navAnnVersion); ?></span>
                    <?php else: ?>
                        <i class="fas fa-clock mr-1"></i> Periode Maintenance: <br><span id="noticePeriodText"
                            class="font-weight-bold"><?php echo htmlspecialchars($navAnnouncement['formatted_period'] ?? ''); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header border-0 text-white py-3 px-4"
                style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);">
                <h5 class="modal-title font-weight-bold text-white my-auto" id="changePasswordModalLabel">
                    <i class="fas fa-key mr-2"></i>Ganti Password
                </h5>
                <button type="button" class="close text-white opacity-75 my-auto" data-dismiss="modal"
                    aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formChangePassword" autocomplete="off">
                <div class="modal-body bg-white p-4">
                    <div id="changePasswordAlert" class="alert alert-danger d-none small mb-3"></div>

                    <div class="form-group mb-3">
                        <label for="change_current_password" class="small font-weight-bold text-gray-700">Password Saat
                            Ini <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="change_current_password"
                                name="current_password" placeholder="Masukkan password saat ini" required
                                autocomplete="current-password" style="padding-right: 2.5rem !important;">
                            <button class="btn p-0 btn-toggle-pwd" type="button" data-target="change_current_password"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #64748b; outline: none; z-index: 5; box-shadow: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="change_new_password" class="small font-weight-bold text-gray-700">Password Baru
                            <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="change_new_password" name="new_password"
                                placeholder="Minimal 6 karakter" required autocomplete="new-password"
                                style="padding-right: 2.5rem !important;">
                            <button class="btn p-0 btn-toggle-pwd" type="button" data-target="change_new_password"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #64748b; outline: none; z-index: 5; box-shadow: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="change_confirm_password" class="small font-weight-bold text-gray-700">Konfirmasi
                            Password Baru <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="change_confirm_password"
                                name="confirm_password" placeholder="Ulangi password baru" required
                                autocomplete="new-password" style="padding-right: 2.5rem !important;">
                            <button class="btn p-0 btn-toggle-pwd" type="button" data-target="change_confirm_password"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #64748b; outline: none; z-index: 5; box-shadow: none;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary font-weight-bold px-4" id="btnSubmitChangePassword">
                        <i class="fas fa-save mr-1"></i>Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Password visibility toggle buttons
        document.querySelectorAll('.btn-toggle-pwd').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var icon = this.querySelector('i');
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
        });

        // Reset change password form when modal is hidden or shown
        if (window.jQuery) {
            $('#changePasswordModal').on('hidden.bs.modal show.bs.modal', function () {
                var form = document.getElementById('formChangePassword');
                if (form) form.reset();
                var alertDiv = document.getElementById('changePasswordAlert');
                if (alertDiv) {
                    alertDiv.classList.add('d-none');
                    alertDiv.textContent = '';
                }
                // Reset input types to password
                ['change_current_password', 'change_new_password', 'change_confirm_password'].forEach(function (id) {
                    var inp = document.getElementById(id);
                    if (inp) inp.type = 'password';
                });
                document.querySelectorAll('.btn-toggle-pwd i').forEach(function (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                });
            });
        }

        // Submit change password form
        var formPwd = document.getElementById('formChangePassword');
        if (formPwd) {
            formPwd.addEventListener('submit', function (e) {
                e.preventDefault();
                var alertDiv = document.getElementById('changePasswordAlert');
                if (alertDiv) {
                    alertDiv.classList.add('d-none');
                    alertDiv.textContent = '';
                }

                var currentPwd = document.getElementById('change_current_password').value.trim();
                var newPwd = document.getElementById('change_new_password').value.trim();
                var confirmPwd = document.getElementById('change_confirm_password').value.trim();

                if (!currentPwd || !newPwd || !confirmPwd) {
                    if (alertDiv) {
                        alertDiv.textContent = 'Semua kolom password wajib diisi.';
                        alertDiv.classList.remove('d-none');
                    }
                    return;
                }

                if (newPwd.length < 6) {
                    if (alertDiv) {
                        alertDiv.textContent = 'Password baru minimal harus 6 karakter.';
                        alertDiv.classList.remove('d-none');
                    }
                    return;
                }

                if (newPwd !== confirmPwd) {
                    if (alertDiv) {
                        alertDiv.textContent = 'Konfirmasi password baru tidak cocok.';
                        alertDiv.classList.remove('d-none');
                    }
                    return;
                }

                var btnSubmit = document.getElementById('btnSubmitChangePassword');
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
                }

                fetch('api/change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: currentPwd,
                        new_password: newPwd,
                        confirm_password: confirmPwd
                    })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan Password';
                        }

                        if (data.status === 'success') {
                            if (window.jQuery) {
                                $('#changePasswordModal').modal('hide');
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: data.message || 'Password Anda berhasil diperbarui.',
                                    icon: 'success',
                                    confirmButtonColor: '#1e3e62'
                                });
                            } else {
                                alert(data.message || 'Password Anda berhasil diperbarui.');
                            }
                        } else {
                            if (alertDiv) {
                                alertDiv.textContent = data.message || 'Gagal memperbarui password.';
                                alertDiv.classList.remove('d-none');
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire('Error', data.message || 'Gagal memperbarui password.', 'error');
                            } else {
                                alert(data.message || 'Gagal memperbarui password.');
                            }
                        }
                    })
                    .catch(function (err) {
                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan Password';
                        }
                        if (alertDiv) {
                            alertDiv.textContent = 'Terjadi kesalahan koneksi server.';
                            alertDiv.classList.remove('d-none');
                        }
                    });
            });
        }
    });
</script>

<?php if ($isSuperAdminNav): ?>
<!-- Modal Upload WI from Sidebar -->
<div class="modal fade" id="uploadDocModalSidebar" tabindex="-1" role="dialog" aria-labelledby="uploadDocModalSidebarLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header border-0 text-white py-3 px-4" style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);">
                <h5 class="modal-title font-weight-bold text-white my-auto" id="uploadDocModalSidebarLabel">
                    <i class="fas fa-file-upload mr-2"></i>Upload File PDF (WI)
                </h5>
                <button type="button" class="close text-white opacity-75 my-auto" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="uploadDocSidebarForm" enctype="multipart/form-data">
                <div class="modal-body bg-white p-4">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-gray-700">Nama Dokumen / File <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="sidebarDocTitle" placeholder="Contoh: WI - Penerimaan Barang & Staging Area.pdf" required>
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-gray-700">File PDF <span class="text-danger">*</span></label>
                        <div class="p-3 text-center rounded border" style="background: #f8fafc; border: 2px dashed #cbd5e1 !important; cursor: pointer;" onclick="document.getElementById('sidebarPdfInput').click();">
                            <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                            <p class="mb-0 small font-weight-bold text-gray-800" id="sidebarFileLabel">Klik untuk memilih file PDF (Maks. 25MB)</p>
                            <input type="file" id="sidebarPdfInput" name="pdf_file" accept=".pdf,application/pdf" style="display: none;" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary font-weight-bold px-4" id="btnSubmitSidebarUpload" style="background: #1e3e62; border-color: #1e3e62;">
                        <i class="fas fa-upload mr-1"></i>Simpan &amp; Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebarPdfInput = document.getElementById('sidebarPdfInput');
    var sidebarFileLabel = document.getElementById('sidebarFileLabel');
    var sidebarDocTitle = document.getElementById('sidebarDocTitle');

    if (sidebarPdfInput) {
        sidebarPdfInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                var f = this.files[0];
                if (!f.name.toLowerCase().endsWith('.pdf')) {
                    if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Hanya file PDF (.pdf) yang diperbolehkan.', 'warning');
                    else alert('Hanya file PDF (.pdf) yang diperbolehkan.');
                    this.value = '';
                    return;
                }
                if (f.size > 25 * 1024 * 1024) {
                    if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Ukuran file PDF melebihi batas 25MB.', 'warning');
                    else alert('Ukuran file PDF melebihi batas 25MB.');
                    this.value = '';
                    return;
                }
                if (sidebarFileLabel) {
                    var sizeMB = (f.size / (1024 * 1024)).toFixed(2) + ' MB';
                    sidebarFileLabel.innerHTML = '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>' + f.name + ' (' + sizeMB + ')</span>';
                }
                if (sidebarDocTitle && !sidebarDocTitle.value.trim()) {
                    sidebarDocTitle.value = f.name.replace(/\.pdf$/i, '');
                }
            }
        });
    }

    var uploadSidebarForm = document.getElementById('uploadDocSidebarForm');
    if (uploadSidebarForm) {
        uploadSidebarForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('btnSubmitSidebarUpload');
            var formData = new FormData(this);

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah...';
            }

            fetch('api/manage_repository.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload mr-1"></i>Simpan &amp; Upload';
                }
                if (data.success) {
                    if (window.jQuery) {
                        $('#uploadDocModalSidebar').modal('hide');
                    }
                    uploadSidebarForm.reset();
                    if (sidebarFileLabel) {
                        sidebarFileLabel.textContent = 'Klik untuk memilih file PDF (Maks. 25MB)';
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message || 'File PDF berhasil diunggah ke repository.',
                            icon: 'success',
                            confirmButtonColor: '#1e3e62'
                        });
                    } else {
                        alert(data.message || 'File PDF berhasil diunggah ke repository.');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan saat mengunggah file.', 'error');
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat mengunggah file.');
                    }
                }
            })
            .catch(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload mr-1"></i>Simpan &amp; Upload';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Terjadi kesalahan komunikasi dengan server.', 'error');
                } else {
                    alert('Terjadi kesalahan komunikasi dengan server.');
                }
            });
        });
    }
});
</script>
<?php endif; ?>