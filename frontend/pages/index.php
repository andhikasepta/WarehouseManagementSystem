<?php
require_once __DIR__ . '/../../backend/config/database.php';

if (!function_exists('assetUrl')) {
    function assetUrl($path)
    {
        $fullPath = __DIR__ . '/../../' . ltrim($path, '/');
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();
        return $path . '?v=' . $version;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Portal System Lintasarta - AWan & WMS">
    <meta name="author" content="Lintasarta">

    <title>WMS - PT. Aplikanusa Lintasarta</title>

    <link rel="icon" href="<?php echo assetUrl('frontend/img/LogoLintas.png'); ?>">
    <link href="frontend/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="frontend/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Outfit', 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            color: #f8fafc;
            position: relative;
        }

        .bg-shape-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .bg-shape-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .main-container {
            height: 100vh;
            height: 100dvh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
            padding-top: 12px;
            padding-bottom: 6px;
        }

        .brand-header {
            padding-top: 10px;
            padding-bottom: 2px;
        }

        .brand-logo {
            width: 185px;
            max-width: 100%;
            height: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .hero-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .hero-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .portal-card-link {
            display: block;
            width: 100%;
            max-width: 295px;
            margin: 0 auto;
            text-decoration: none !important;
            border-radius: 14px;
            overflow: hidden;
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.32) !important;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .portal-card-link:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.48), 0 0 18px rgba(59, 130, 246, 0.22) !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
        }

        .portal-card-img {
            width: 100%;
            height: auto;
            aspect-ratio: 1723 / 842;
            object-fit: cover;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            display: block;
        }

        .portal-card-body {
            padding: 9px 14px 12px 14px;
            text-align: left;
        }

        .portal-card-title {
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .portal-card-desc {
            color: #94a3b8;
            font-size: 0.78rem;
            line-height: 1.35;
            margin-bottom: 0;
        }

        .footer-text {
            color: #64748b;
            font-size: 0.8rem;
            padding: 8px 0;
            margin-top: auto;
            width: 100%;
        }

        @media (max-width: 768px) {

            html,
            body {
                height: auto;
                min-height: 100%;
                overflow-x: hidden;
                overflow-y: auto;
            }

            .main-container {
                height: auto;
                max-height: none;
                min-height: 100vh;
                min-height: 100dvh;
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .brand-header {
                padding-top: 15px;
                padding-bottom: 5px;
            }

            .brand-logo {
                width: 150px;
                max-width: 100%;
                height: auto;
            }

            .hero-title {
                font-size: 1.4rem;
            }

            .hero-subtitle {
                font-size: 0.85rem;
            }

            .portal-card-link {
                max-width: 100%;
            }

            .footer-text {
                font-size: 0.75rem;
                padding: 12px 0;
            }
        }
    </style>

</head>

<body>

    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>

    <div class="container main-container">
        <!-- Header Text on Top -->
        <div class="brand-header text-center my-1">
            <img src="<?php echo assetUrl('frontend/img/Lintasarta.png'); ?>" alt="Lintasarta Logo"
                class="brand-logo mb-2">
            <h1 class="hero-title mb-1">Internal Portal System</h1>
            <p class="hero-subtitle mb-0">Asset And Warehouse Management</p>
        </div>

        <!-- Cards Row: Glassmorphism Card Container with Closer Padding -->
        <div class="row justify-content-center align-items-stretch my-auto py-2 px-md-3">
            <!-- Card 1: AWan System -->
            <div class="col-md-4 col-lg-4 mb-3 mb-md-0 text-center px-md-2 px-lg-2">
                <a href="https://centrals.lintasarta.net/assets/" class="portal-card-link h-100 d-flex flex-column">
                    <img src="<?php echo assetUrl('frontend/img/AWan.png'); ?>" alt="AWan System"
                        class="portal-card-img">
                    <div class="portal-card-body flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="portal-card-title">AWan</div>
                            <p class="portal-card-desc">Sistem Manajemen pencatatan dan mutasi asset.</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card 2: WMS System -->
            <div class="col-md-4 col-lg-4 mb-3 mb-md-0 text-center px-md-2 px-lg-2">
                <a href="wms_select.php" class="portal-card-link h-100 d-flex flex-column">
                    <img src="<?php echo assetUrl('frontend/img/WMS.png'); ?>" alt="Warehouse Management System"
                        class="portal-card-img">
                    <div class="portal-card-body flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="portal-card-title">Warehouse Management System</div>
                            <p class="portal-card-desc">Dashboard monitoring Inbound, Storage, dan Outbound.</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card 3: CentraDocs / Warehouse Repository -->
            <div class="col-md-4 col-lg-4 mb-3 mb-md-0 text-center px-md-2 px-lg-2">
                <a href="repository.php" class="portal-card-link h-100 d-flex flex-column">
                    <img src="<?php echo assetUrl('frontend/img/centradocs.png'); ?>" alt="CentraDocs"
                        class="portal-card-img">
                    <div class="portal-card-body flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="portal-card-title">Sentralisasi Dokumen</div>
                            <p class="portal-card-desc">Repository dokumen, panduan, &amp; work instruction (WI).
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-text text-center">
            <p class="mb-0">

                <?php echo htmlspecialchars(function_exists('getSystemAppVersion') ? getSystemAppVersion($pdo ?? null) : 'Beta-v1.0.0'); ?>
                &copy; PT. Aplikanusa Lintasarta
            </p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="frontend/vendor/jquery/jquery.min.js"></script>
    <script src="frontend/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>