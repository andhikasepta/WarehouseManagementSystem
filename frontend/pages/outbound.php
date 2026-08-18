<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('outbound');

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php
                $activePage = 'outbound';
                include FRONTEND_PATH . 'components/navbar.php';
                ?>

                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading just like inbound -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Outbound Management</h1>
                    </div>

                    <style>
                        .border-right-divider {
                            border-right: 1px solid #e3e6f0;
                        }

                        .custom-select {
                            background: #ffffff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3e%3cpath fill='%235a5c69' d='M0 0l5 6 5-6z'/%3e%3c/svg%3e") no-repeat right 0.75rem center/10px 6px !important;
                            padding-right: 1.75rem;
                        }

                        .status-card-clickable {
                            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                            cursor: pointer;
                            border-radius: 6px;
                            padding: 0.5rem 0.65rem !important;
                            margin: 0 0.2rem;
                        }

                        .status-card-clickable:hover {
                            background-color: #f1f5f9 !important;
                            box-shadow: inset 0 0 0 1px #cbd5e1 !important;
                        }

                        @media (max-width: 767.98px) {
                            .border-right-divider {
                                border-right: none;
                                border-bottom: 1px solid #e3e6f0;
                                padding-bottom: 12px;
                            }
                        }
                    </style>

                    <!-- Alur Status MR Outbound Card -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div
                                    class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Alur Pemenuhan MR
                                    </h6>
                                </div>
                                <div class="card-body py-3 px-4">
                                    <div class="row text-center align-items-center">
                                        <!-- 1. Total MR -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded"
                                            data-toggle="modal" data-target="#mrStatusDetailModal"
                                            data-status="TOTAL MR">
                                            <div class="h3 font-weight-bold text-primary mb-1" id="card-total-mr">0
                                            </div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TOTAL
                                                MR</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 2. Total Packed -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded"
                                            data-toggle="modal" data-target="#mrStatusDetailModal"
                                            data-status="TOTAL PACKED">
                                            <div class="h3 font-weight-bold text-info mb-1" id="card-total-packed">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">
                                                TOTAL PACKED</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 3. Total Shipped -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded"
                                            data-toggle="modal" data-target="#mrStatusDetailModal"
                                            data-status="TOTAL SHIPPED">
                                            <div class="h3 font-weight-bold text-danger mb-1" id="card-shipped">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TOTAL SHIPPED
                                            </div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 4. Dalam Perjalanan -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded"
                                            data-toggle="modal" data-target="#mrStatusDetailModal"
                                            data-status="DALAM PERJALANAN">
                                            <div class="h3 font-weight-bold text-success mb-1"
                                                id="card-dalam-perjalanan">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">DALAM
                                                PERJALANAN</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 5. Tiba di Lokasi -->
                                        <div class="col-md mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded"
                                            data-toggle="modal" data-target="#mrStatusDetailModal"
                                            data-status="TIBA DI LOKASI">
                                            <div class="h3 font-weight-bold text-secondary mb-1" id="card-tiba-lokasi">0
                                            </div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TIBA DI
                                                LOKASI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-secondary" role="progressbar"
                                                    style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row 1 (3 Cards: Total PO Price, Total PO, Saving) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Total PO Price -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        TOTAL PO PRICE</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-total-po-price">Rp 0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Total PO -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        TOTAL PO</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-total-po">0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Saving -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        SAVING</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-saving">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row 2 (2 Cards: Most Cost Delivery, Most Moda Delivery) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Most Cost Delivery -->
                        <div class="col-xl-6 col-md-6 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        MOST COST DELIVERY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-most-cost-delivery">-</div>
                                </div>
                            </div>
                        </div>
                        <!-- Most Moda Delivery -->
                        <div class="col-xl-6 col-md-6 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        MOST MODA DELIVERY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-most-moda-delivery">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Single Outer Card: Ranking & Sebaran (Contains 3 Inner Grouping Cards) -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div class="card-header bg-white py-3 border-bottom-0">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Ranking &amp; Sebaran
                                    </h6>
                                </div>
                                <div class="card-body pt-0 pb-3 px-4">
                                    <div class="row">
                                        <!-- Left Column: 5 Kota Shipped Terbanyak, 5 Kota PO Value, & 5 Kota PO Terbanyak -->
                                        <div class="col-lg-6 d-flex flex-column justify-content-between mb-4 mb-lg-0">
                                            <!-- Inner Card 1: 5 Kota Shipped Terbanyak -->
                                            <div class="card border shadow-sm mb-3" style="border-radius: 8px;">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase"
                                                        style="letter-spacing: 0.5px;">
                                                        5 Kota Shipped Terbanyak
                                                    </h6>
                                                    <div id="list-5-kota-shipped-terbanyak">
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">1. Jakarta</span>
                                                                <span class="small font-weight-bold text-success">0 Shipped</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">2. Surabaya</span>
                                                                <span class="small font-weight-bold text-success">0 Shipped</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">3. Medan</span>
                                                                <span class="small font-weight-bold text-success">0 Shipped</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">4. Bandung</span>
                                                                <span class="small font-weight-bold text-success">0 Shipped</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">5. Semarang</span>
                                                                <span class="small font-weight-bold text-success">0 Shipped</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-success" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inner Card 2: 5 Kota PO Value -->
                                            <div class="card border shadow-sm mb-3" style="border-radius: 8px;">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase"
                                                        style="letter-spacing: 0.5px;">
                                                        5 Kota PO Value
                                                    </h6>
                                                    <div id="list-5-kota-po-value">
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">1. Jakarta</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">2. Surabaya</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">3. Medan</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">4. Bandung</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">5. Semarang</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inner Card 3: 5 Kota PO Terbanyak (Bellow 5 Kota PO Value) -->
                                            <div class="card border shadow-sm mb-0" style="border-radius: 8px;">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase"
                                                        style="letter-spacing: 0.5px;">
                                                        5 Kota PO Terbanyak
                                                    </h6>
                                                    <div id="list-5-kota-po-terbanyak">
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">1. Jakarta</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">2. Surabaya</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">3. Medan</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">4. Bandung</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">5. Semarang</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar"
                                                                    style="width: 0%" aria-valuenow="0"
                                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: 10 Site MR Open (Full Right) -->
                                        <div class="col-lg-6 d-flex flex-column mb-4 mb-lg-0">
                                            <!-- Inner Card 4: 10 Site MR Open -->
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-body p-3 d-flex flex-column">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase"
                                                        style="letter-spacing: 0.5px;">
                                                        10 Site MR Open
                                                    </h6>
                                                    <div id="list-10-site-mr-open"
                                                        class="d-flex flex-column justify-content-between flex-grow-1">
                                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                                            <div class="d-flex flex-column justify-content-center flex-grow-1 mb-2">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small font-weight-bold text-gray-700"><?php echo $i; ?>.
                                                                        Site <?php echo chr(64 + $i); ?></span>
                                                                    <span class="small font-weight-bold text-warning">0
                                                                        MR</span>
                                                                </div>
                                                                <div class="progress progress-sm"
                                                                    style="height: 5px; border-radius: 4px;">
                                                                    <div class="progress-bar bg-warning" role="progressbar"
                                                                        style="width: 0%" aria-valuenow="0"
                                                                        aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Single Outer Card: Tren & Komposisi -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div class="card-header bg-white py-3 border-bottom-0">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Tren &amp; Komposisi
                                    </h6>
                                </div>
                                <div class="card-body pt-0 pb-3 px-4">
                                    <!-- Top Sub-Row: 2 Cards (Chart Bulanan — Jumlah MR (%), Chart Bulanan — Jumlah PO (%)) -->
                                    <div class="row mb-3">
                                        <!-- Card 1: Chart Bulanan — Jumlah MR (%) -->
                                        <div class="col-xl-6 col-lg-6 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase"
                                                        style="letter-spacing: 0.5px;">Chart Bulanan — Jumlah MR (%)
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="chart-bar" style="height: 250px; position: relative;">
                                                        <canvas id="chartBulananJumlahMr"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card 2: Chart Bulanan — Jumlah PO (%) -->
                                        <div class="col-xl-6 col-lg-6 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase"
                                                        style="letter-spacing: 0.5px;">Chart Bulanan — Jumlah PO (%)
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="chart-bar" style="height: 250px; position: relative;">
                                                        <canvas id="chartBulananJumlahPo"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom Sub-Row: 3 Cards (Chart Close MR (%), Cost Delivery per Moda (Value), Chart Tender / Direct Selection (%)) -->
                                    <div class="row">
                                        <!-- Card 3: Chart Close MR (%) -->
                                        <div class="col-xl-4 col-lg-4 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase"
                                                        style="letter-spacing: 0.5px;">Chart Close MR (%)</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="chart-pie" style="height: 250px; position: relative;">
                                                        <canvas id="chartCloseMr"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card 4: Cost Delivery per Moda (Value) -->
                                        <div class="col-xl-4 col-lg-4 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase"
                                                        style="letter-spacing: 0.5px;">Cost Delivery per Moda (Value)
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                    <div class="chart-bar" style="height: 220px; position: relative;">
                                                        <canvas id="costDeliveryPerModaChart"></canvas>
                                                    </div>
                                                    <div class="mt-auto pt-1 d-flex flex-wrap align-items-center justify-content-start text-nowrap pl-1"
                                                        style="font-size: 0.68rem; gap: 14px;">
                                                        <span
                                                            class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i
                                                                class="fas fa-circle mr-1.5"
                                                                style="color: #4e73df; font-size: 0.5rem;"></i>
                                                            Udara</span>
                                                        <span
                                                            class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i
                                                                class="fas fa-circle mr-1.5"
                                                                style="color: #1cc88a; font-size: 0.5rem;"></i>
                                                            Laut</span>
                                                        <span
                                                            class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i
                                                                class="fas fa-circle mr-1.5"
                                                                style="color: #36b9cc; font-size: 0.5rem;"></i>
                                                            Darat</span>
                                                        <span
                                                            class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i
                                                                class="fas fa-circle mr-1.5"
                                                                style="color: #f6c23e; font-size: 0.5rem;"></i> Udara
                                                            PTP</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card 5: Chart Tender / Direct Selection (%) -->
                                        <div class="col-xl-4 col-lg-4 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase"
                                                        style="letter-spacing: 0.5px;">Chart Tender / Direct Selection
                                                        (%)</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="chart-pie" style="height: 250px; position: relative;">
                                                        <canvas id="chartTenderDirectSelection"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- MR Status Detail Modal -->
            <div class="modal fade" id="mrStatusDetailModal" tabindex="-1" role="dialog"
                aria-labelledby="mrStatusDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow"
                        style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                        <div class="modal-header border-bottom py-3 px-4 align-items-center"
                            style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                            <h5 class="modal-title font-weight-bold text-gray-800 my-auto d-flex align-items-center flex-wrap"
                                id="mrStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                <span>Detail Status MR:</span>
                                <span id="modalMrStatusTitleText" class="font-weight-bold text-primary ml-1"></span>
                                <span class="badge badge-danger px-2.5 py-1 font-weight-bold ml-2 d-none"
                                    id="shipped-modal-total-badge" style="font-size: 0.8rem; border-radius: 6px;">
                                    <i class="fas fa-boxes mr-1"></i> Total: <span id="shipped-total-qty">0</span> QTY
                                </span>
                            </h5>
                            <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">

                            <!-- Shipped Segments Slider Breakdown (Internal & External) -->
                            <div id="shipped-segment-container" class="mb-4 d-none">

                                <!-- Slider Navigation Tabs / Toggle -->
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="btn-group p-1 bg-light border rounded-pill shadow-sm" role="group"
                                        aria-label="Shipped Segments Toggle">
                                        <button type="button"
                                            class="btn btn-sm btn-info rounded-pill px-3 font-weight-bold active"
                                            id="btn-tab-internal" style="transition: all 0.2s ease;">
                                            Internal (<span id="tab-internal-qty">0</span> QTY)
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-light text-muted rounded-pill px-3 font-weight-bold ml-1"
                                            id="btn-tab-external" style="transition: all 0.2s ease;">
                                            External (<span id="tab-external-qty">0</span> QTY)
                                        </button>
                                    </div>
                                </div>

                                <!-- Bootstrap Carousel Slider -->
                                <div id="shippedSegmentCarousel" class="carousel slide" data-ride="carousel"
                                    data-interval="false">
                                    <div class="carousel-inner shadow-sm rounded border bg-light"
                                        style="min-height: 175px;">

                                        <!-- Slide 1: Internal (Delivery, Pickup, Handcarry) -->
                                        <div class="carousel-item active p-3">
                                            <div
                                                class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                <div>
                                                    <span class="font-weight-bold text-gray-800"
                                                        style="font-size: 0.9rem;">Internal</span>
                                                </div>
                                                <span class="badge badge-info px-2.5 py-1.5 font-weight-bold"
                                                    style="font-size: 0.82rem; border-radius: 6px;">
                                                    Total: <span id="internal-segment-total-qty">0</span> QTY
                                                </span>
                                            </div>

                                            <div class="row">
                                                <!-- Delivery -->
                                                <div class="col-md-4 mb-2 mb-md-0">
                                                    <div
                                                        class="bg-white p-3 rounded border shadow-xs h-100 text-center">
                                                        <div
                                                            class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                                            Delivery
                                                        </div>
                                                        <div class="h4 font-weight-bold text-primary mb-0"
                                                            id="qty-internal-delivery">0 QTY</div>
                                                    </div>
                                                </div>
                                                <!-- Pickup -->
                                                <div class="col-md-4 mb-2 mb-md-0">
                                                    <div
                                                        class="bg-white p-3 rounded border shadow-xs h-100 text-center">
                                                        <div
                                                            class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                                            Pickup
                                                        </div>
                                                        <div class="h4 font-weight-bold text-info mb-0"
                                                            id="qty-internal-pickup">0 QTY</div>
                                                    </div>
                                                </div>
                                                <!-- Handcarry -->
                                                <div class="col-md-4">
                                                    <div
                                                        class="bg-white p-3 rounded border shadow-xs h-100 text-center">
                                                        <div
                                                            class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                                            Handcarry
                                                        </div>
                                                        <div class="h4 font-weight-bold text-warning mb-0"
                                                            id="qty-internal-handcarry">0 QTY</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Slide 2: External (Mover) -->
                                        <div class="carousel-item p-3">
                                            <div
                                                class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                <div>
                                                    <span class="font-weight-bold text-gray-800"
                                                        style="font-size: 0.9rem;">External</span>
                                                </div>
                                                <span
                                                    class="badge badge-warning text-white px-2.5 py-1.5 font-weight-bold"
                                                    style="font-size: 0.82rem; border-radius: 6px;">
                                                    Total: <span id="external-segment-total-qty">0</span> QTY
                                                </span>
                                            </div>

                                            <div class="row align-items-center">
                                                <!-- Mover -->
                                                <div class="col-md-12 mb-2 mb-md-0">
                                                    <div class="bg-white p-3 rounded border shadow-xs text-center">
                                                        <div
                                                            class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                                            Mover / Forwarder
                                                        </div>
                                                        <div class="h4 font-weight-bold text-danger mb-0"
                                                            id="qty-external-mover">0 QTY</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded" style="border-color: #eaecf4 !important; max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover table-striped text-center mb-0" id="tableMrStatusDetail"
                                    style="font-size: 0.85rem; width: 100%;">
                                    <thead class="bg-light text-gray-700 font-weight-bold" id="tableMrStatusDetailHead"
                                        style="border-bottom: 2px solid #eaecf4; position: sticky; top: 0; z-index: 1; background-color: #f8f9fc;">
                                        <!-- Rendered dynamically by JS -->
                                    </thead>
                                    <tbody id="tableMrStatusDetailBody">
                                        <tr>
                                            <td colspan="6" class="py-5 text-muted bg-white">
                                                <i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
                                                Belum ada data tersedia untuk status ini.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

            <!-- Page level plugins & Custom Chart script -->
            <script src="frontend/vendor/chart.js/Chart.min.js"></script>
            <script src="frontend/js/demo/chart-outbound-demo.js"></script>

            <script>
                $(document).ready(function () {
                    // Status Table Configuration Map
                    var statusColumnConfig = {
                        'TOTAL MR': {
                            headers: ['NO MR', 'User', 'Tujuan', 'Pickup by', 'Ket'],
                            keys: ['no_mr', 'user', 'tujuan', 'pickup_by', 'ket']
                        },
                        'TOTAL PACKED': {
                            headers: ['NO PCK', 'PCK DETAIL', 'User', 'Tujuan', 'NO MR', 'NO DN'],
                            keys: ['no_pck', 'pck_detail', 'user', 'tujuan', 'no_mr', 'no_dn']
                        },
                        'TOTAL SHIPPED': {
                            headers: ['NO MR', 'NO DN', 'User', 'Tujuan', 'Pickup Type', 'Via', 'LT', 'Delivery Target', 'Last Log'],
                            keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'pickup_type', 'via', 'lt', 'delivery_target', 'last_log']
                        },
                        'DALAM PERJALANAN': {
                            headers: ['NO MR', 'NO DN', 'User', 'Tujuan', 'Status MR', 'LT', 'Status DN', 'Delivery Target', 'Last Log'],
                            keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'status_mr', 'lt', 'status_dn', 'delivery_target', 'last_log']
                        },
                        'TIBA DI LOKASI': {
                            headers: ['NO MR', 'NO DN', 'User', 'Tujuan', 'Status MR', 'LT', 'Status DN', 'Delivery Target', 'Last Log'],
                            keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'status_mr', 'lt', 'status_dn', 'delivery_target', 'last_log']
                        }
                    };

                    // Function to load and render table rows inside the modal
                    function loadModalStatusTable(statusName) {
                        var cfg = statusColumnConfig[statusName] || statusColumnConfig['TOTAL MR'];
                        var thead = $('#tableMrStatusDetailHead');
                        var tbody = $('#tableMrStatusDetailBody');

                        // Build table headers
                        var headHtml = '<tr>';
                        cfg.headers.forEach(function (h) {
                            headHtml += '<th class="py-2.5 px-3 border-top-0 whitespace-nowrap">' + h + '</th>';
                        });
                        headHtml += '</tr>';
                        thead.html(headHtml);

                        // Show Loading Spinner
                        var colCount = cfg.headers.length;
                        tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data detail status...</td></tr>');

                        // Fetch live rows from API
                        $.ajax({
                            url: 'api/get_outbound_status_detail.php',
                            type: 'GET',
                            data: { status: statusName },
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success' && res.data && res.data.length > 0) {
                                    var rowsHtml = '';
                                    res.data.forEach(function (row) {
                                        rowsHtml += '<tr>';
                                        cfg.keys.forEach(function (k) {
                                            var val = row[k] !== undefined && row[k] !== null && row[k] !== '' ? row[k] : '-';
                                            rowsHtml += '<td class="py-2 px-3 align-middle text-gray-800">' + val + '</td>';
                                        });
                                        rowsHtml += '</tr>';
                                    });
                                    tbody.html(rowsHtml);
                                } else {
                                    tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                }
                            },
                            error: function () {
                                tbody.html('<tr><td colspan="' + colCount + '" class="py-4 text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                            }
                        });
                    }

                    // Function to refresh top summary cards from database
                    function loadStatusCardCounts() {
                        $.ajax({
                            url: 'api/get_outbound_status_detail.php',
                            type: 'GET',
                            data: { action: 'counts' },
                            dataType: 'json',
                            success: function (res) {
                                if (res.status === 'success' && res.counts) {
                                    var c = res.counts;
                                    var total = c.total_mr || 0;

                                    $('#card-total-mr').text(total);
                                    $('#card-total-packed').text(c.total_packed || 0);
                                    $('#card-shipped').text(c.total_shipped || 0);
                                    $('#card-dalam-perjalanan').text(c.dalam_perjalanan || 0);
                                    $('#card-tiba-lokasi').text(c.tiba_di_lokasi || 0);

                                    // Update progress bars
                                    if (total > 0) {
                                        $('#card-total-mr').closest('.status-card-clickable').find('.progress-bar').css('width', '100%');
                                        $('#card-total-packed').closest('.status-card-clickable').find('.progress-bar').css('width', Math.min(100, Math.round((c.total_packed / total) * 100)) + '%');
                                        $('#card-shipped').closest('.status-card-clickable').find('.progress-bar').css('width', Math.min(100, Math.round((c.total_shipped / total) * 100)) + '%');
                                        $('#card-dalam-perjalanan').closest('.status-card-clickable').find('.progress-bar').css('width', Math.min(100, Math.round((c.dalam_perjalanan / total) * 100)) + '%');
                                        $('#card-tiba-lokasi').closest('.status-card-clickable').find('.progress-bar').css('width', Math.min(100, Math.round((c.tiba_di_lokasi / total) * 100)) + '%');
                                    }

                                    // Update segment numbers if present
                                    if (c.segments) {
                                        var s = c.segments;
                                        $('#qty-internal-delivery').text((s.internal_delivery || 0) + ' QTY');
                                        $('#qty-internal-pickup').text((s.internal_pickup || 0) + ' QTY');
                                        $('#qty-internal-handcarry').text((s.internal_handcarry || 0) + ' QTY');
                                        var intTotal = (s.internal_delivery || 0) + (s.internal_pickup || 0) + (s.internal_handcarry || 0);
                                        $('#internal-segment-total-qty').text(intTotal);
                                        $('#tab-internal-qty').text(intTotal);

                                        $('#qty-external-mover').text((s.external_mover || 0) + ' QTY');
                                        $('#external-segment-total-qty').text(s.external_mover || 0);
                                        $('#tab-external-qty').text(s.external_mover || 0);
                                        $('#shipped-total-qty').text(c.total_shipped || 0);
                                    }
                                }
                            }
                        });
                    }

                    // Initial Load
                    loadStatusCardCounts();

                    // Tab button click handlers for Carousel slider
                    $('#btn-tab-internal').on('click', function () {
                        $('#shippedSegmentCarousel').carousel(0);
                    });

                    $('#btn-tab-external').on('click', function () {
                        $('#shippedSegmentCarousel').carousel(1);
                    });

                    // Sync button state on slide
                    $('#shippedSegmentCarousel').on('slid.bs.carousel', function (e) {
                        if (e.to === 0) {
                            $('#btn-tab-internal').removeClass('btn-light text-muted').addClass('btn-info text-white active');
                            $('#btn-tab-external').removeClass('btn-warning text-white active').addClass('btn-light text-muted');
                        } else if (e.to === 1) {
                            $('#btn-tab-external').removeClass('btn-light text-muted').addClass('btn-warning text-white active');
                            $('#btn-tab-internal').removeClass('btn-info text-white active').addClass('btn-light text-muted');
                        }
                    });

                    // Card click handler to show specific table
                    $('.status-card-clickable').on('click', function () {
                        var statusName = $(this).attr('data-status');
                        $('#modalMrStatusTitleText').text(statusName);

                        if (statusName === 'TOTAL SHIPPED' || statusName === 'SHIPPED') {
                            $('#shipped-segment-container').removeClass('d-none');
                            $('#shipped-modal-total-badge').removeClass('d-none');
                            $('#shippedSegmentCarousel').carousel(0);
                            $('#btn-tab-internal').removeClass('btn-light text-muted').addClass('btn-info text-white active');
                            $('#btn-tab-external').removeClass('btn-warning text-white active').addClass('btn-light text-muted');
                        } else {
                            $('#shipped-segment-container').addClass('d-none');
                            $('#shipped-modal-total-badge').addClass('d-none');
                        }

                        loadModalStatusTable(statusName);
                    });
                });
            </script>

</body>

</html>