<?php
require_once __DIR__ . '/auth.php';
checkModuleAccess('outbound');

$pageTitle = 'Outbound - Dashboard Warehouse';
include 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'outbound'; 
                include 'components/navbar.php'; 
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

                    <?php 
                    $monthsIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $currentDateStr = date('d') . ' ' . $monthsIndo[date('n') - 1] . ' ' . date('Y');
                    ?>

                    <!-- Alur Status MR Outbound Card -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Alur Pemenuhan MR <span class="text-muted font-weight-normal ml-1" id="large-card-date-outbound">(<?php echo $currentDateStr; ?>)</span>
                                    </h6>
                                </div>
                                <div class="card-body py-3 px-4">
                                    <div class="row text-center align-items-center">
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="MR APPROVED">
                                            <div class="h3 font-weight-bold text-primary mb-1" id="card-mr-approved">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">MR APPROVED</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="PCK SELESAI">
                                            <div class="h3 font-weight-bold text-info mb-1" id="card-pck-selesai">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PCK SELESAI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="DN TERBIT">
                                            <div class="h3 font-weight-bold text-warning mb-1" id="card-dn-terbit">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">DN TERBIT</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="PO KE MOVER">
                                            <div class="h3 font-weight-bold text-danger mb-1" id="card-po-mover">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO KE MOVER</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="DALAM PERJALANAN">
                                            <div class="h3 font-weight-bold text-success mb-1" id="card-dalam-perjalanan">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">DALAM PERJALANAN</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#mrStatusDetailModal" data-status="TIBA DI SITE">
                                            <div class="h3 font-weight-bold text-secondary mb-1" id="card-tiba-site">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TIBA DI SITE</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row 1 (3 Cards) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Total PO Price -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        TOTAL PO PRICE</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-total-po-price">Rp 0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Total PO -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        TOTAL PO</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-total-po">0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Total MR -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        TOTAL MR</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-total-mr">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row 2 (3 Cards) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Saving -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        SAVING</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-saving">Rp 0</div>
                                </div>
                            </div>
                        </div>
                        <!-- Most Cost Delivery -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        MOST COST DELIVERY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-most-cost-delivery">-</div>
                                </div>
                            </div>
                        </div>
                        <!-- Most Moda Delivery -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1" style="font-size: 0.72rem; line-height: 1.15;">
                                        MOST MODA DELIVERY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto" style="line-height: 1.1;" id="card-most-moda-delivery">-</div>
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
                                        <!-- Left Column: 5 Kota PO Terbanyak & 5 Kota PO Value Terbanyak -->
                                        <div class="col-lg-6 d-flex flex-column justify-content-between mb-4 mb-lg-0">
                                            <!-- Inner Grouping Card 1: 5 Kota PO Terbanyak -->
                                            <div class="card border shadow-sm mb-3" style="border-radius: 8px;">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase" style="letter-spacing: 0.5px;">
                                                        5 Kota PO Terbanyak
                                                    </h6>
                                                    <div id="list-5-kota-po-terbanyak">
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">1. Jakarta</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">2. Surabaya</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">3. Medan</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">4. Bandung</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">5. Semarang</span>
                                                                <span class="small font-weight-bold text-primary">0 PO</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inner Grouping Card 2: 5 Kota PO Value Terbanyak -->
                                            <div class="card border shadow-sm mb-0" style="border-radius: 8px;">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase" style="letter-spacing: 0.5px;">
                                                        5 Kota PO Value Terbanyak
                                                    </h6>
                                                    <div id="list-5-kota-po-value">
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">1. Jakarta</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">2. Surabaya</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">3. Medan</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">4. Bandung</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-0">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700">5. Semarang</span>
                                                                <span class="small font-weight-bold text-info">Rp 0</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: 10 Outlet dengan MR Open -->
                                        <div class="col-lg-6 d-flex flex-column mb-4 mb-lg-0">
                                            <!-- Inner Grouping Card 3: 10 Outlet dengan MR Open -->
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-body p-3 d-flex flex-column">
                                                    <h6 class="font-weight-bold text-primary mb-3 pb-2 border-bottom small text-uppercase" style="letter-spacing: 0.5px;">
                                                        10 Outlet dengan MR Open
                                                    </h6>
                                                    <div id="list-10-outlet-mr-open" class="d-flex flex-column justify-content-between flex-grow-1">
                                                        <?php for($i=1; $i<=10; $i++): ?>
                                                        <div class="d-flex flex-column justify-content-center flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small font-weight-bold text-gray-700"><?php echo $i; ?>. Outlet <?php echo chr(64 + $i); ?></span>
                                                                <span class="small font-weight-bold text-warning">0 MR</span>
                                                            </div>
                                                            <div class="progress progress-sm" style="height: 5px; border-radius: 4px;">
                                                                <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
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
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase" style="letter-spacing: 0.5px;">Chart Bulanan — Jumlah MR (%)</h6>
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
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase" style="letter-spacing: 0.5px;">Chart Bulanan — Jumlah PO (%)</h6>
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
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase" style="letter-spacing: 0.5px;">Chart Close MR (%)</h6>
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
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase" style="letter-spacing: 0.5px;">Cost Delivery per Moda (Value)</h6>
                                                </div>
                                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                    <div class="chart-bar" style="height: 220px; position: relative;">
                                                        <canvas id="costDeliveryPerModaChart"></canvas>
                                                    </div>
                                                    <div class="mt-auto pt-1 d-flex flex-wrap align-items-center justify-content-start text-nowrap pl-1" style="font-size: 0.68rem; gap: 14px;">
                                                        <span class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i class="fas fa-circle mr-1.5" style="color: #4e73df; font-size: 0.5rem;"></i> Udara</span>
                                                        <span class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i class="fas fa-circle mr-1.5" style="color: #1cc88a; font-size: 0.5rem;"></i> Laut</span>
                                                        <span class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i class="fas fa-circle mr-1.5" style="color: #36b9cc; font-size: 0.5rem;"></i> Darat</span>
                                                        <span class="d-inline-flex align-items-center text-gray-700 font-weight-bold"><i class="fas fa-circle mr-1.5" style="color: #f6c23e; font-size: 0.5rem;"></i> Udara PTP</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card 5: Chart Tender / Direct Selection (%) -->
                                        <div class="col-xl-4 col-lg-4 mb-4 mb-lg-0">
                                            <div class="card border shadow-sm h-100" style="border-radius: 8px;">
                                                <div class="card-header bg-white py-2 px-3 border-bottom-0">
                                                    <h6 class="m-0 font-weight-bold text-primary small text-uppercase" style="letter-spacing: 0.5px;">Chart Tender / Direct Selection (%)</h6>
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
            <div class="modal fade" id="mrStatusDetailModal" tabindex="-1" role="dialog" aria-labelledby="mrStatusDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow" style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                        <div class="modal-header border-bottom py-3 px-4 align-items-center" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                            <h5 class="modal-title font-weight-bold text-gray-800 my-auto" id="mrStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                Detail Status MR: <span id="modalMrStatusTitleText" class="font-weight-bold text-primary"></span>
                            </h5>
                            <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal" aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                            <div class="table-responsive border rounded" style="border-color: #eaecf4 !important;">
                                <table class="table text-center mb-0" id="tableMrStatusDetail" style="font-size: 0.85rem;">
                                    <thead class="bg-light text-gray-700 font-weight-bold" style="border-bottom: 2px solid #eaecf4;">
                                        <tr>
                                            <th class="py-2 border-top-0">No. MR</th>
                                            <th class="py-2 border-top-0">No. PO</th>
                                            <th class="py-2 border-top-0">Tujuan Site</th>
                                            <th class="py-2 border-top-0">Kota Tujuan</th>
                                            <th class="py-2 border-top-0">Tanggal MR</th>
                                            <th class="py-2 border-top-0">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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

<?php include 'components/footer.php'; ?>

<!-- Page level plugins & Custom Chart script -->
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-outbound-demo.js"></script>

<script>
$(document).ready(function() {
    $('.status-card-clickable').on('click', function() {
        var statusName = $(this).attr('data-status');
        $('#modalMrStatusTitleText').text(statusName);
    });

    window.addEventListener('dateRangeChanged', function(e) {
        if (e.detail && e.detail.displayRange) {
            $('#large-card-date-outbound').text('(' + e.detail.displayRange + ')');
        } else {
            $('#large-card-date-outbound').text('(<?php echo $currentDateStr; ?>)');
        }
    });
});
</script>

</body>
</html>
