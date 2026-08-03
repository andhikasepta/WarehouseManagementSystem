<?php
// auth.php - Session & Permission Helper

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Session inactivity timeout (30 minutes)
$maxInactivity = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxInactivity)) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details.
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? 'admin',
        'allowed_modules' => $_SESSION['allowed_modules'] ?? []
    ];
}

/**
 * Enforce authentication and module permission access.
 * If user lacks permission, renders a permission error page and halts execution.
 */
function checkModuleAccess($requiredModule = '') {
    if (!isLoggedIn()) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        $queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        $redirectUrl = urlencode($currentPage . $queryString);
        header("Location: login.php?redirect={$redirectUrl}");
        exit;
    }

    $user = getCurrentUser();
    $currentPage = basename($_SERVER['PHP_SELF']);

    // 1. User Management Page Access (Strictly Super Admin Only)
    if ($requiredModule === 'user_management' || $currentPage === 'user_management.php') {
        if ($user['role'] !== 'superadmin') {
            renderAccessDeniedPage('user_management', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
            exit;
        }
        return true;
    }

    // 2. Super Admin Role Restriction (Super Admin is dedicated exclusively to System Management)
    if ($user['role'] === 'superadmin') {
        if ($currentPage !== 'user_management.php' && $currentPage !== 'announcements.php') {
            header("Location: user_management.php");
            exit;
        }
        return true;
    }

    // 3. Module Access Checks
    $isHeadRole = (strpos($user['role'], 'head_') === 0) || ($user['role'] === 'head_warehouse_admin');
    $allowedModules = is_array($user['allowed_modules']) ? $user['allowed_modules'] : [];

    // Dashboard Overview is strictly for Head roles
    if ($requiredModule === 'dashboard' || $currentPage === 'dashboard.php') {
        if (!$isHeadRole) {
            renderAccessDeniedPage('dashboard', $user, 'Halaman Dashboard Overview ini khusus untuk Pimpinan Head-Management.');
            exit;
        }
        return true;
    }

    // Reports and Analytics permission checks
    if ($requiredModule === 'reports' || $requiredModule === 'analytics') {
        if (!$isHeadRole && !in_array($requiredModule, $allowedModules)) {
            renderAccessDeniedPage($requiredModule, $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
            exit;
        }
        return true;
    }

    // Master Data permission check
    if ($requiredModule === 'master_data' || $currentPage === 'master_data.php') {
        if (!in_array('master_data', $allowedModules)) {
            renderAccessDeniedPage('master_data', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
            exit;
        }
        return true;
    }

    return true;
}

/**
 * Render clean access denied page when user lacks module permission.
 */
function renderAccessDeniedPage($requiredModule = '', $user = null, $customMessage = '') {
    if (!is_array($user)) {
        if (is_string($user) && !empty($user) && empty($customMessage)) {
            $customMessage = $user;
        }
        $user = getCurrentUser();
    }
    if (empty($customMessage)) {
        $customMessage = 'Akun Anda tidak diberikan izin akses ke modul ini.';
    }
    $pageTitle = 'Akses Ditolak - Dashboard Warehouse';
    include 'components/header.php';
    ?>
    <body id="page-top" class="bg-light">
        <div id="wrapper">
            <div id="content-wrapper" class="d-flex flex-column min-vh-100">
                <div id="content" class="flex-grow-1">
                    <?php 
                    $activePage = '';
                    $hidePeriodSelector = true;
                    include 'components/navbar.php'; 
                    ?>

                    <div class="container" style="padding-top: 130px; padding-bottom: 60px;">
                        <div class="row justify-content-center">
                            <div class="col-lg-7 text-center py-4">
                                <div class="card shadow-lg border-0 rounded-lg p-4 p-md-5">
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <div class="icon-circle bg-danger-light text-danger d-inline-flex align-items-center justify-content-center rounded-circle p-4 shadow-sm mb-3" style="width: 90px; height: 90px; background-color: #fde8e8;">
                                                <i class="fas fa-user-lock fa-3x"></i>
                                            </div>
                                        </div>
                                        <h3 class="font-weight-bold text-gray-800 mb-2">Akses Ditolak</h3>
                                        <p class="text-muted lead mb-4">
                                            Akun <strong><?php echo htmlspecialchars($user['name']); ?></strong> tidak memiliki hak akses.
                                        </p>
                                        <div class="alert alert-warning text-left small mb-4">
                                            <i class="fas fa-exclamation-circle mr-1"></i> <?php echo !empty($customMessage) ? htmlspecialchars($customMessage) : 'Silakan hubungi Super Admin untuk meminta penambahan hak akses modul ini.'; ?>
                                        </div>
                                         <div class="d-flex flex-wrap justify-content-center gap-2">
                                             <?php if ($user['role'] === 'superadmin'): ?>
                                                 <a href="user_management.php" class="btn btn-primary px-4 py-2 mr-2">
                                                     <i class="fas fa-users-cog mr-1"></i> Buka User Management
                                                 </a>
                                             <?php else: 
                                                 $role = $user['role'] ?? '';
                                                 $modules = is_array($user['allowed_modules'] ?? null) ? $user['allowed_modules'] : [];
                                                 $targetUrl = 'wms_select.php';

                                                 if ($role === 'inbound_admin') {
                                                     $targetUrl = 'inbound.php';
                                                 } elseif ($role === 'warehouse_admin' || $role === 'head_warehouse_admin') {
                                                     $targetUrl = 'warehouse.php';
                                                 } elseif ($role === 'outbound_admin') {
                                                     $targetUrl = 'outbound.php';
                                                 } elseif (!empty($modules)) {
                                                     if (in_array('warehouse', $modules)) {
                                                         $targetUrl = 'warehouse.php';
                                                     } else {
                                                         $targetUrl = $modules[0] . '.php';
                                                     }
                                                 }
                                             ?>
                                                 <a href="<?php echo htmlspecialchars($targetUrl); ?>" class="btn btn-primary px-4 py-2 mr-2">
                                                     <i class="fas fa-arrow-left mr-1"></i> Kembali Ke Modul
                                                 </a>
                                             <?php endif; ?>
                                            <a href="login.php?action=logout" class="btn btn-outline-danger px-4 py-2">
                                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include 'components/footer.php'; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
