<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('master_data');

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

    <style>
        .master-select-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .master-select-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
            border-color: #4e73df;
        }
        .master-select-card .card-icon-wrap {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            background-color: #f8f9fc;
        }
        .master-select-card .card-icon-wrap i {
            font-size: 1.5rem;
            color: #5a5c69;
        }
        .master-select-card:hover .card-icon-wrap {
            background-color: #eaecf4;
        }
        .master-select-card .card-title-main {
            font-size: 1rem;
            font-weight: 700;
            color: #3a3b45;
            margin-bottom: 0;
        }
    </style>

</head>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">
                
                <!-- Topbar -->
                <?php 
                $activePage = 'master_data'; 
                $hidePeriodSelector = true;
                include FRONTEND_PATH . 'components/navbar.php'; 
                ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">
                    <div class="mb-4 pb-3 border-bottom">
                        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                            Master Data
                        </h1>
                    </div>

                    <!-- 2 Cards Selection -->
                    <div class="row mt-3">
                        <!-- Card 1: Master Data -->
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card master-select-card shadow-sm h-100" onclick="window.location.href='master_data_detail.php'">
                                <div class="card-body text-center py-4 px-3">
                                    <div class="card-icon-wrap">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <h6 class="card-title-main">Master Data</h6>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Site Location Warehouse -->
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card master-select-card shadow-sm h-100" onclick="window.location.href='site_location.php'">
                                <div class="card-body text-center py-4 px-3">
                                    <div class="card-icon-wrap">
                                        <i class="fas fa-map-marked-alt"></i>
                                    </div>
                                    <h6 class="card-title-main">Site Location Warehouse</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

<?php include FRONTEND_PATH . 'components/footer.php'; ?>
</html>

