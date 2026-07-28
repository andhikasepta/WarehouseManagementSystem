<?php
// dashboard.php - Head-Warehouse Management Dashboard Overview
require_once __DIR__ . '/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? '';
$isHeadRole = (strpos($userRole, 'head_') === 0) || ($userRole === 'head_warehouse_admin');

// Only allow Head-Management roles (head_*)
if (!$isHeadRole) {
    renderAccessDeniedPage("Halaman Dashboard Overview ini khusus untuk Pimpinan Head-Management.");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="WMS Dashboard Overview - Head Warehouse Management">
    <title>WMS - Dashboard Overview</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .section-header-banner {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.15);
        }
        .module-badge {
            font-size: 0.75rem;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .card-graph-container {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-graph-container:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .chart-area-custom {
            position: relative;
            height: 320px;
            width: 100%;
        }
        .chart-pie-custom {
            position: relative;
            height: 280px;
            width: 100%;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content" class="flex-grow-1">
                
                <!-- Topbar -->
                <?php 
                $activePage = 'dashboard'; 
                include 'components/navbar.php'; 
                ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">

                    <!-- Page Header Banner -->
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                                Dashboard Overview
                            </h1>
                            <p class="text-muted small mb-0">Integrasi Grafik : Inbound, Storage &amp; Outbound</p>
                        </div>
                    </div>

                    <!-- Dashboard overview graphs temporarily commented out -->
                    <?php /*
                    <div class="row">
                        <!-- KPI Cards and Graphs commented out -->
                    </div>
                    */ ?>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

<?php include 'components/footer.php'; ?>

</body>
</html>
