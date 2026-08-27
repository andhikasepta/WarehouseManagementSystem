<?php
require_once __DIR__ . '/../../backend/auth.php';
if (!defined('SPA_MODE')) checkModuleAccess('analytics');

if (!defined('SPA_MODE')) {
    $pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
    include FRONTEND_PATH . 'components/header.php';
?>
<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">
                <?php 
                $activePage = 'analytics'; 
                include FRONTEND_PATH . 'components/navbar.php'; 
                ?>
<?php } ?>
                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Analytics</h1>
                    </div>
                </div>
            </div>
<?php if (!defined('SPA_MODE')) { ?>
<?php include FRONTEND_PATH . 'components/footer.php'; ?>
<?php } ?>
