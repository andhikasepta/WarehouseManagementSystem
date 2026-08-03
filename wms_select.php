<?php
require_once __DIR__ . '/auth.php';

// wms_select.php is only accessible before login or after session is destroyed
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'superadmin') {
        header("Location: user_management.php");
        exit;
    }
    $allowed = $user['allowed_modules'] ?? [];
    if (in_array('warehouse', $allowed)) {
        header("Location: warehouse.php");
        exit;
    } elseif (!empty($allowed)) {
        header("Location: " . $allowed[0] . ".php");
        exit;
    }
}

$pageTitle = 'Pilih Dashboard WMS - Lintasarta';
include 'components/header.php';
?>
    <style>
        .module-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow: hidden;
            background: #ffffff;
            cursor: pointer;
        }

        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12) !important;
        }

        .module-icon-bg {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .bg-gradient-inbound {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .bg-gradient-warehouse {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }

        .bg-gradient-outbound {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .btn-module {
            border-radius: 30px;
            font-weight: 700;
            padding: 10px 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body id="page-top" class="bg-light">

    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'wms_select'; 
                $hidePeriodSelector = true;
                $hideNavLinks = true;
                $hideLoginButton = true;
                $hideNavbarUl = true;
                include 'components/navbar.php'; 
                ?>

                <div class="container" style="padding-top: 120px; padding-bottom: 60px;">
                    <div class="text-center mb-5">
                        <h2 class="font-weight-bold text-gray-800 display-5 mb-2">Warehouse Management System Module</h2>
                    </div>

                    <div class="row justify-content-center">
                        <!-- Inbound Card -->
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card module-card shadow h-100" onclick="window.location.href='login.php?redirect=inbound.php'">
                                <div class="module-icon-bg bg-gradient-inbound text-white">
                                    <i class="fas fa-box-open fa-4x"></i>
                                </div>
                                <div class="card-body p-4 text-center d-flex flex-column">
                                    <h4 class="font-weight-bold text-gray-800 mb-2">INBOUND</h4>
                                    <p class="text-muted small flex-grow-1">
                                        Monitoring end-to-end Purchase Order — dari target kedatangan, Goods Receipt (GR), hingga registrasi barang di gudang.
                                    </p>
                                    <div class="mt-3">
                                        <a href="login.php?redirect=inbound.php" class="btn btn-success btn-module btn-block shadow-sm">
                                            Akses Inbound <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Storage Card -->
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card module-card shadow h-100" onclick="window.location.href='login.php?redirect=warehouse.php'">
                                <div class="module-icon-bg bg-gradient-warehouse text-white">
                                    <i class="fas fa-warehouse fa-4x"></i>
                                </div>
                                <div class="card-body p-4 text-center d-flex flex-column">
                                    <h4 class="font-weight-bold text-gray-800 mb-2">STORAGE</h4>
                                    <p class="text-muted small flex-grow-1">
                                        Monitoring pengelolaan inventory & kapasitas gudang — dari total aset, NBV, utilisasi space perangkat di gudang.
                                    </p>
                                    <div class="mt-3">
                                        <a href="login.php?redirect=warehouse.php" class="btn btn-primary btn-module btn-block shadow-sm">
                                            Akses Storage <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Outbound Card -->
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card module-card shadow h-100" onclick="window.location.href='login.php?redirect=outbound.php'">
                                <div class="module-icon-bg bg-gradient-outbound text-white">
                                    <i class="fas fa-truck-loading fa-4x"></i>
                                </div>
                                <div class="card-body p-4 text-center d-flex flex-column">
                                    <h4 class="font-weight-bold text-gray-800 mb-2">OUTBOUND</h4>
                                    <p class="text-muted small flex-grow-1">
                                        Monitoring pemenuhan Material Request (MR) — dari approval, packing (PCK), penerbitan Delivery Note (DN), penunjukan Mover/Forwarder, hingga perangkat tiba di site.
                                    </p>
                                    <div class="mt-3">
                                        <a href="login.php?redirect=outbound.php" class="btn btn-warning btn-module btn-block text-white shadow-sm">
                                            Akses Outbound <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.php" class="text-muted font-weight-bold small">
                            <i class="fas fa-chevron-left mr-1"></i> Kembali ke Landing Page Portal
                        </a>
                    </div>
                </div>

            </div>

<?php include 'components/footer.php'; ?>

</body>
</html>
