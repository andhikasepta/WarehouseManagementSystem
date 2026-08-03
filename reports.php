<?php
require_once __DIR__ . '/auth.php';
checkModuleAccess('reports');

$pageTitle = 'Reports - Dashboard Warehouse';
include 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">
                <?php 
                $activePage = 'reports'; 
                include 'components/navbar.php'; 
                ?>
                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Reports</h1>
                    </div>
                </div>
            </div>
<?php include 'components/footer.php'; ?>
