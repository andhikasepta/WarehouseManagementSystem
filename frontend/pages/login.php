<?php
// login.php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/auth.php';

$error = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'wms_select.php';

// Show access denied notification if redirected back
if (isset($_GET['access_denied']) && $_GET['access_denied'] == '1') {
    $error = 'User tidak mendapatkan hak akses modul.';
}

$moduleSubtitle = '';
if (strpos($redirect, 'inbound') !== false) {
    $moduleSubtitle = 'Inbound Management';
} elseif (strpos($redirect, 'warehouse') !== false) {
    $moduleSubtitle = 'Storage Management';
} elseif (strpos($redirect, 'outbound') !== false) {
    $moduleSubtitle = 'Outbound Management';
} elseif (strpos($redirect, 'master_data') !== false) {
    $moduleSubtitle = 'Master Data';
} elseif (strpos($redirect, 'user_management') !== false) {
    $moduleSubtitle = 'User Management';
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: wms_select.php");
    exit;
}

// If already logged in and no logout request
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && !isset($_GET['action'])) {
    if (($_SESSION['role'] ?? '') === 'superadmin') {
        header("Location: user_management.php");
        exit;
    }
    header("Location: " . $redirect);
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $passwordValid = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } elseif ($password === $user['password']) {
                    // Auto-hash legacy plaintext password in DB
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                    $passwordValid = true;
                }
            }

            if ($passwordValid) {
                // Prevent session fixation attack
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                $decodedModules = json_decode($user['allowed_modules'], true);
                $_SESSION['allowed_modules'] = is_array($decodedModules) ? $decodedModules : [];

                $decodedPermissions = json_decode($user['permissions'] ?? '{}', true);
                $_SESSION['permissions'] = is_array($decodedPermissions) ? $decodedPermissions : [];

                // Determine which module is being requested from redirect
                $requestedModule = '';
                if (strpos($redirect, 'inbound') !== false) {
                    $requestedModule = 'inbound';
                } elseif (strpos($redirect, 'warehouse') !== false) {
                    $requestedModule = 'warehouse';
                } elseif (strpos($redirect, 'outbound') !== false) {
                    $requestedModule = 'outbound';
                } elseif (strpos($redirect, 'master_data') !== false) {
                    $requestedModule = 'master_data';
                }

                // Check module access (skip for superadmin)
                if (!empty($requestedModule) && $user['role'] !== 'superadmin') {
                    $allowedModules = is_array($decodedModules) ? $decodedModules : [];
                    if (!in_array($requestedModule, $allowedModules)) {
                        // User doesn't have access — destroy session and redirect back
                        $_SESSION = array();
                        if (ini_get("session.use_cookies")) {
                            $params = session_get_cookie_params();
                            setcookie(session_name(), '', time() - 42000,
                                $params["path"], $params["domain"],
                                $params["secure"], $params["httponly"]
                            );
                        }
                        session_destroy();
                        header("Location: login.php?redirect=" . urlencode($redirect) . "&access_denied=1");
                        exit;
                    }
                }

                if ($user['role'] === 'superadmin') {
                    header("Location: user_management.php");
                } elseif ($redirect === 'wms_select.php' || empty($redirect)) {
                    $allowed = is_array($decodedModules) ? $decodedModules : [];
                    if (in_array('dashboard', $allowed)) {
                        header("Location: dashboard.php");
                    } elseif (in_array('warehouse', $allowed)) {
                        header("Location: warehouse.php");
                    } elseif (in_array('inbound', $allowed)) {
                        header("Location: inbound.php");
                    } elseif (in_array('outbound', $allowed)) {
                        header("Location: outbound.php");
                    } elseif (!empty($allowed)) {
                        header("Location: " . $allowed[0] . ".php");
                    } else {
                        header("Location: wms_select.php");
                    }
                } else {
                    header("Location: " . $redirect);
                }
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem saat memproses login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login - Storage Management System Lintasarta">
    <meta name="author" content="Lintasarta">

    <title>Login - WMS Lintasarta</title>

    <link rel="icon" href="frontend/img/LogoLintas.png">
    <link href="frontend/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="frontend/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            color: #f8fafc;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .brand-logo {
            max-width: 170px;
            filter: brightness(0) invert(1);
        }

        .form-control-login {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: #ffffff;
            padding: 12px 16px;
            height: 48px;
            font-size: 0.95rem;
        }

        .form-control-login:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            color: #ffffff;
        }

        .footer-text {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 24px;
        }
    </style>

</head>

<body>

    <div class="login-card text-center">
        <div class="mb-4">
            <h4 class="font-weight-bold text-white mb-1">Login WMS</h4>
            <?php if (!empty($moduleSubtitle)): ?>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($moduleSubtitle); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-left small rounded-lg mb-4" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

            <div class="form-group text-left mb-3">
                <label for="username" class="small font-weight-bold text-gray-300">Username</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-login" id="username" name="username" placeholder="Masukkan username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group text-left mb-4">
                <label for="password" class="small font-weight-bold text-gray-300">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control form-control-login" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login shadow-sm mb-3">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-3">
            <a href="wms_select.php" class="text-muted small">
                <i class="fas fa-chevron-left mr-1"></i> Kembali ke Modul WMS
            </a>
        </div>

        <div class="footer-text">
            <?php echo htmlspecialchars(function_exists('getSystemAppVersion') ? getSystemAppVersion($pdo ?? null) : 'Beta-v1.0.0'); ?> &copy; PT. Aplikanusa Lintasarta
        </div>
    </div>

    <!-- Scripts -->
    <script src="frontend/vendor/jquery/jquery.min.js"></script>
    <script src="frontend/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>
