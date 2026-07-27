<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Portal System Lintasarta - AWan & WMS">
    <meta name="author" content="Lintasarta">

    <title>Portal System - Lintasarta</title>

    <link rel="icon" href="img/LogoLintas.png">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            color: #f8fafc;
            margin: 0;
            padding: 0;
            overflow: hidden;
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
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .brand-header {
            padding-top: 30px;
            padding-bottom: 10px;
        }

        .brand-logo {
            max-width: 180px;
            filter: brightness(0) invert(1);
        }

        .hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .hero-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .portal-card-link {
            display: block;
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
            text-decoration: none;
            border-radius: 18px;
            overflow: hidden;
            background: transparent;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .portal-card-link:hover {
            transform: scale(1.06);
        }

        .portal-card-img {
            width: 100%;
            height: auto;
            aspect-ratio: 1723 / 842;
            object-fit: fill;
            border-radius: 18px;
            display: block;
            border: none !important;
            box-shadow: none !important;
            filter: none !important;
        }

        .footer-text {
            color: #64748b;
            font-size: 0.9rem;
            padding: 24px 0;
        }
    </style>

</head>

<body>

    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>

    <div class="container main-container h-100">
        <!-- Header Text on Top -->
        <div class="brand-header text-center my-2">
            <img src="img/Lintasarta.png" alt="Lintasarta Logo" class="brand-logo mb-3">
            <h1 class="hero-title mb-2">Internal Portal System</h1>
            <p class="hero-subtitle">Asset And Warehouse Management</p>
        </div>

        <!-- Cards Row: Direct images with rounded corners, no box background, no shadow -->
        <div class="row justify-content-center align-items-center my-auto py-3">
            <!-- Card 1: AWan -->
            <div class="col-md-4 col-lg-4 mb-4 text-center">
                <a href="https://centrals.lintasarta.net/assets/" class="portal-card-link">
                    <img src="img/AWan.png" alt="AWan System" class="portal-card-img">
                </a>
            </div>

            <!-- Card 2: WMS -->
            <div class="col-md-4 col-lg-4 mb-4 text-center">
                <a href="wms_select.php" class="portal-card-link">
                    <img src="img/WMS.png" alt="Warehouse Management System" class="portal-card-img">
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-text text-center">
            <p class="mb-0">Unreleased Beta Versi &copy; PT. Aplikanusa Lintasarta</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>