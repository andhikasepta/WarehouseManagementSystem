<?php
// backend/auth.php - Session & Permission Helper

require_once __DIR__ . '/paths.php';

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

require_once CONFIG_PATH . 'database.php';

// Session inactivity timeout (15 minutes)
$maxInactivity = 900;
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
 * Get current logged in user details (auto-synced with DB for real-time UAC updates).
 */
function getCurrentUser($forceRefresh = false) {
    if (!isLoggedIn()) return null;

    static $cachedUser = null;
    if ($cachedUser !== null && !$forceRefresh) {
        return $cachedUser;
    }

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/config/database.php';
        }
        try {
            $stmt = $pdo->prepare("SELECT id, username, name, role, allowed_modules, permissions FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dbUser) {
                $decodedModules = json_decode($dbUser['allowed_modules'] ?? '[]', true);
                $decodedPermissions = json_decode($dbUser['permissions'] ?? '{}', true);

                $_SESSION['role'] = $dbUser['role'];
                $_SESSION['name'] = $dbUser['name'];
                $_SESSION['allowed_modules'] = is_array($decodedModules) ? $decodedModules : [];
                $_SESSION['permissions'] = is_array($decodedPermissions) ? $decodedPermissions : [];

                $cachedUser = [
                    'id' => $dbUser['id'],
                    'username' => $dbUser['username'],
                    'name' => $dbUser['name'],
                    'role' => $dbUser['role'],
                    'allowed_modules' => $_SESSION['allowed_modules'],
                    'permissions' => $_SESSION['permissions']
                ];
                return $cachedUser;
            }
        } catch (Exception $e) {
            error_log("getCurrentUser DB sync error: " . $e->getMessage());
        }
    }

    $cachedUser = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? 'admin',
        'allowed_modules' => $_SESSION['allowed_modules'] ?? [],
        'permissions' => $_SESSION['permissions'] ?? []
    ];
    return $cachedUser;
}

/**
 * Check if the current user has a specific permission on a module.
 * Strictly driven by Superadmin User Management permissions.
 * @param string $module  Module key (e.g. 'inbound', 'warehouse', 'master_data', 'site_location')
 * @param string $action  Permission action: 'view', 'add', or 'delete'
 * @return bool
 */
function hasPermission($module, $action = 'view') {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();

    // Superadmin has master full permissions
    if (($user['role'] ?? '') === 'superadmin') return true;

    $allowedModules = is_array($user['allowed_modules'] ?? null) ? $user['allowed_modules'] : [];
    $permissions    = is_array($user['permissions'] ?? null) ? $user['permissions'] : [];

    // Direct check on requested module
    if (in_array($module, $allowedModules)) {
        if (isset($permissions[$module]) && is_array($permissions[$module])) {
            if (isset($permissions[$module][$action])) {
                return !empty($permissions[$module][$action]);
            }
        }
        return ($action === 'view');
    }

    return false;
}

function canAdd($module) {
    return hasPermission($module, 'add');
}

function canDelete($module) {
    return hasPermission($module, 'delete');
}

/**
 * Get the full permissions array for the current user.
 * @return array
 */
function getUserPermissions() {
    if (!isLoggedIn()) return [];
    return $_SESSION['permissions'] ?? [];
}

// ── CSRF Protection ─────────────────────────────────────────────────
/**
 * Generate or retrieve the current CSRF token.
 * Token is created once per session and reused until session expires.
 * @return string The CSRF token
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request.
 * Checks POST body ('csrf_token'), JSON body, and X-CSRF-Token header.
 * On failure, sends 403 JSON response and exits.
 */
function validateCsrf() {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please refresh the page.']);
        exit;
    }

    // 1. Check X-CSRF-Token header (AJAX requests)
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!empty($headerToken) && hash_equals($sessionToken, $headerToken)) {
        return true;
    }

    // 2. Check POST field
    $postToken = $_POST['csrf_token'] ?? '';
    if (!empty($postToken) && hash_equals($sessionToken, $postToken)) {
        return true;
    }

    // 3. Check JSON body (for fetch with Content-Type: application/json)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $jsonToken = $jsonData['csrf_token'] ?? '';
        if (!empty($jsonToken) && hash_equals($sessionToken, $jsonToken)) {
            return true;
        }
    }

    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// Ensure CSRF token is always available in session
getCsrfToken();

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

    if ($requiredModule === 'user_management' || $currentPage === 'user_management.php') {
        if ($user['role'] !== 'superadmin') {
            renderAccessDeniedPage('user_management', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
            exit;
        }
        return true;
    }

    if ($requiredModule === 'repository' || $currentPage === 'repository.php') {
        return true;
    }

    if ($requiredModule === 'repository_management' || $currentPage === 'repository_management.php') {
        $allowedModules = is_array($user['allowed_modules']) ? $user['allowed_modules'] : [];
        if ($user['role'] === 'superadmin' || $user['role'] === 'repository_admin' || in_array('repository_management', $allowedModules)) {
            return true;
        }
        renderAccessDeniedPage('repository_management', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
        exit;
    }

    if ($user['role'] === 'superadmin') {
        if ($currentPage !== 'user_management.php' && $currentPage !== 'announcements.php' && $currentPage !== 'repository.php' && $currentPage !== 'repository_management.php') {
            header("Location: user_management.php");
            exit;
        }
        return true;
    }

    $allowedModules = is_array($user['allowed_modules']) ? $user['allowed_modules'] : [];

    // Map page to module key if not explicitly given
    $moduleKey = $requiredModule;
    if (empty($moduleKey)) {
        $pageMap = [
            'announcements.php' => 'announcements',
        ];
        $registry = getModuleRegistry();
        foreach ($registry as $key => $mod) {
            if (!empty($mod['pages']) && is_array($mod['pages'])) {
                foreach ($mod['pages'] as $p) {
                    $pageMap[$p] = $key;
                }
            }
            if (!empty($mod['children']) && is_array($mod['children'])) {
                foreach ($mod['children'] as $childKey => $child) {
                    if (!empty($child['pages']) && is_array($child['pages'])) {
                        foreach ($child['pages'] as $cp) {
                            $pageMap[$cp] = $childKey;
                        }
                    }
                }
            }
        }
        $moduleKey = $pageMap[$currentPage] ?? '';
    }

    if (!empty($moduleKey)) {
        // If it's a child module, user has access if they have access to the child OR parent module
        $hasAccess = in_array($moduleKey, $allowedModules);
        if (!$hasAccess) {
            $registry = getModuleRegistry();
            // 1. Check if module is a child of an allowed parent
            foreach ($registry as $parentKey => $parentMod) {
                if (!empty($parentMod['children']) && array_key_exists($moduleKey, $parentMod['children'])) {
                    if (in_array($parentKey, $allowedModules)) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
            // 2. If checking parent (e.g. master_data), allow if user has access to any child or equivalent
            if (!$hasAccess && $moduleKey === 'master_data') {
                $checkSub = ['master_data_inbound', 'master_data_storage', 'master_data_outbound', 'site_location', 'inbound', 'warehouse', 'outbound'];
                foreach ($checkSub as $sub) {
                    if (in_array($sub, $allowedModules)) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
        }

        if (!$hasAccess) {
            renderAccessDeniedPage($moduleKey, $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
            exit;
        }
    }

    return true;
}

/**
 * Get module registry definitions
 */
function getModuleRegistry() {
    static $registry = null;
    if ($registry === null) {
        $registryPath = __DIR__ . '/config/module_registry.php';
        if (file_exists($registryPath)) {
            $registry = include $registryPath;
        } else {
            $registry = [];
        }
    }
    return $registry;
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
    $pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
    include FRONTEND_PATH . 'components/header.php';
    ?>
    <body id="page-top" class="bg-light">
        <div id="wrapper">
            <div id="content-wrapper" class="d-flex flex-column min-vh-100">
                <div id="content" class="flex-grow-1">
                    <?php 
                    $activePage = '';
                    $hidePeriodSelector = true;
                    include FRONTEND_PATH . 'components/navbar.php'; 
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
                                                 } elseif ($role === 'outsourcing') {
                                                     $targetUrl = 'wms_select.php';
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
                <?php include FRONTEND_PATH . 'components/footer.php'; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
