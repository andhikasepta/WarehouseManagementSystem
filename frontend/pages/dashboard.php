<?php
// dashboard.php - Head-Warehouse Management Dashboard Overview
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('dashboard');
$currentUser = getCurrentUser();

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>
<style>
    .btn-detail-dark {
        background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);
        color: #ffffff !important;
        border: none;
        font-size: 0.68rem;
        border-radius: 6px;
        transition: all 0.25s ease-in-out;
    }

    .btn-detail-dark:hover,
    .btn-detail-dark:focus {
        background: linear-gradient(135deg, #1e3e62 0%, #365e8d 100%) !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 62, 98, 0.4) !important;
        filter: brightness(1.2);
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
            <div id="content" class="flex-grow-1">

                <!-- Topbar Navigation -->
                <?php
                $activePage = 'dashboard';
                include FRONTEND_PATH . 'components/navbar.php';
                ?>

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Dashboard Overview</h1>
                    </div>

                    <!-- Row 1: 4 Metric Cards (Total PO Diterima, Jumlah GR, Jumlah Registrasi, Inventory on Hand) -->
                    <!-- <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        TOTAL PO DITERIMA</div>
                                    <div class="h5 mb-2 font-weight-bold text-gray-800" id="card-total-po-diterima">0 PO</div>
                                    <div class="mt-1" id="card-po-trend">
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            <i class="fas fa-minus text-muted mr-1"></i>0% vs bulan lalu
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        JUMLAH GOODS RECEIPT</div>
                                    <div class="h5 mb-2 font-weight-bold text-gray-800" id="card-jumlah-gr">0 GR</div>
                                    <div class="mt-1" id="card-gr-trend">
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            <i class="fas fa-minus text-muted mr-1"></i>0% vs bulan lalu
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        JUMLAH REGISTRASI</div>
                                    <div class="h5 mb-2 font-weight-bold text-gray-800" id="card-jumlah-registrasi">0 Unit</div>
                                    <div class="mt-1" id="card-reg-trend">
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            <i class="fas fa-minus text-muted mr-1"></i>0% vs bulan lalu
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        INVENTORY ON HAND</div>
                                    <div class="h5 mb-2 font-weight-bold text-gray-800" id="card-inventory-on-hand">0 Unit</div>
                                    <div class="mt-1" id="card-inv-trend">
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            <i class="fas fa-minus text-muted mr-1"></i>0% vs bulan lalu
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Row 2: Inbound Summary (Full Width Card with Steps & Bar Chart) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Card 1: Inbound Summary -->
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-stream mr-2"></i>Inbound
                                            Summary</span>
                                        <span class="badge badge-primary font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Inbound Management</span>
                                    </div>

                                    <!-- Top Section: 7 Horizontal Steps Pipeline (Full Width, non-wrapping) -->
                                    <div class="mt-3 pt-1 pb-2 mb-3">
                                        <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                            id="inbound-steps-container">
                                            <!-- Step 1: Total PO Inbound -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(0)" style="cursor: pointer;"
                                                title="Klik untuk detail Total PO Inbound">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                    <i class="fas fa-file-invoice text-primary"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">Total PO Inbound</div>
                                                <div class="font-weight-bold text-primary" style="font-size: 0.82rem;"
                                                    id="flow-po-count">0 PO</div>
                                            </div>

                                            <!-- Arrow 1 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 2: PO Ontime Delivery -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(1)" style="cursor: pointer;"
                                                title="Klik untuk detail PO Ontime Delivery">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #e0f2fe;">
                                                    <i class="fas fa-file-alt text-info"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">PO Ontime Delivery</div>
                                                <div class="font-weight-bold text-info" style="font-size: 0.82rem;"
                                                    id="flow-po-proses-delivery">0 PO</div>
                                            </div>

                                            <!-- Arrow 2 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 3: PO Terlambat Delivery -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(2)" style="cursor: pointer;"
                                                title="Klik untuk detail PO Terlambat Delivery">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #fee2e2;">
                                                    <i class="fas fa-exclamation-triangle text-danger"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">PO Terlambat Delivery</div>
                                                <div class="font-weight-bold text-danger" style="font-size: 0.82rem;"
                                                    id="flow-po-terlambat-delivery">0 PO</div>
                                            </div>

                                            <!-- Arrow 3 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 4: PO Sudah GR -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(3)" style="cursor: pointer;"
                                                title="Klik untuk detail PO Sudah GR">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #ecfdf5;">
                                                    <i class="fas fa-boxes text-success"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">PO Sudah GR</div>
                                                <div class="font-weight-bold text-success" style="font-size: 0.82rem;"
                                                    id="flow-gr-count">0 PO</div>
                                            </div>

                                            <!-- Arrow 4 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 5: PO sudah Registrasi -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(4)" style="cursor: pointer;"
                                                title="Klik untuk detail PO Sudah Registrasi">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #e0e7ff;">
                                                    <i class="fas fa-barcode text-primary"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">PO Sudah Registrasi</div>
                                                <div class="font-weight-bold text-primary" style="font-size: 0.82rem;"
                                                    id="flow-reg-count">0 PO</div>
                                            </div>

                                            <!-- Vertical Divider: Separator for Total GR > Total Registrasi -->
                                            <div class="border-left align-self-stretch mx-2.5 my-1"
                                                style="height: auto; min-height: 75px;">
                                            </div>

                                            <!-- Step 6: Total GR -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(5)" style="cursor: pointer;"
                                                title="Klik untuk detail Total GR">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #fef3c7;">
                                                    <i class="fas fa-dolly-flatbed text-warning"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">Total GR</div>
                                                <div class="font-weight-bold text-warning" style="font-size: 0.82rem;"
                                                    id="flow-total-gr">0 GR</div>
                                            </div>

                                            <!-- Arrow 6 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 7: Total Registrasi -->
                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                onclick="openInboundSummaryModal(6)" style="cursor: pointer;"
                                                title="Klik untuk detail Total Registrasi">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #f1f5f9;">
                                                    <i class="fas fa-check-circle text-dark"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">Total Registrasi</div>
                                                <div class="font-weight-bold text-dark" style="font-size: 0.82rem;"
                                                    id="flow-done-count">0 Unit</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom Section: Pie Chart & Right Side Clickable Filter Labels -->
                                    <div class="pt-3 mt-1 border-top">
                                        <div class="row align-items-center">
                                            <!-- Pie Chart Column (Left Side) -->
                                            <div class="col-xl-8 col-lg-8 col-md-12 mb-3 mb-lg-0 pr-xl-3">
                                                <div style="position: relative; height: 145px; width: 100%;">
                                                    <canvas id="dashInboundFlowPieChart"></canvas>
                                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;"
                                                        id="inbound-pie-center-text">
                                                        <div class="text-uppercase text-muted font-weight-bold"
                                                            style="font-size: 0.55rem; line-height: 1;">TOTAL</div>
                                                        <div class="font-weight-bold text-gray-800"
                                                            style="font-size: 0.95rem; line-height: 1.1;"
                                                            id="inbound-pie-total-val">0</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Clickable Filter Labels Column (Right Side) -->
                                            <div class="col-xl-4 col-lg-4 col-md-12 border-left pl-xl-3 my-auto">
                                                <div class="pl-2 text-left"
                                                    style="max-height: 145px; overflow-y: auto;">
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-0" onclick="openInboundSummaryModal(0)"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail Total PO Inbound">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #4e73df;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">Total PO Inbound</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-1" onclick="openInboundSummaryModal(1)"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail PO Ontime Delivery">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #36b9cc;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Ontime Delivery</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-2" onclick="openInboundSummaryModal(2)"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail PO Terlambat Delivery">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #e74a3b;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Terlambat Delivery</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-3" onclick="openInboundSummaryModal(3)"
                                                        style="cursor: pointer;" title="Klik untuk detail PO Sudah GR">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #1cc88a;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Sudah GR</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-4" onclick="openInboundSummaryModal(4)"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail PO Sudah Registrasi">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #6f42c1;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Sudah Registrasi</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-5" onclick="openInboundSummaryModal(5)"
                                                        style="cursor: pointer;" title="Klik untuk detail Total GR">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #f6c23e;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">Total GR</span>
                                                    </div>
                                                    <div class="d-flex align-items-center text-nowrap filter-legend-item outbound-summary-clickable"
                                                        id="inbound-flow-legend-6" onclick="openInboundSummaryModal(6)"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail Total Registrasi">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #5a5c69;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">Total Registrasi</span>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="inbound.php"
                                                        class="btn btn-sm btn-block font-weight-bold py-1 btn-detail-dark shadow-sm">Lihat
                                                        Detail <i class="fas fa-arrow-right ml-1"
                                                            style="font-size: 0.6rem;"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Inventory Summary (Full Width Card with 5 Steps & Pie Chart on Right) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Card 2: Inventory Summary -->
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-boxes mr-2"></i>Storage
                                            Summary</span>
                                        <span class="badge badge-info font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Storage Management</span>
                                    </div>

                                    <div class="my-auto py-1">
                                        <div class="row align-items-center">
                                            <!-- Flow Steps Column (5 Steps: Total Perangkat > Aging <3m > Aging 3-12m > Aging >12m > Non Moving) -->
                                            <div class="col-xl-9 col-lg-8 col-md-12 mb-3 mb-lg-0 pr-xl-3">
                                                <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                    id="storage-steps-container">
                                                    <!-- Step 1: Total Perangkat -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(0)" style="cursor: pointer;"
                                                        title="Total Perangkat">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                            <i class="fas fa-cubes text-primary"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Total Perangkat</div>
                                                        <div class="font-weight-bold text-primary"
                                                            style="font-size: 0.82rem;" id="inv-total-perangkat">0 Unit
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 1 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 2: < 1 Tahun -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(1)" style="cursor: pointer;"
                                                        title="< 1 Tahun">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #ecfdf5;">
                                                            <i class="fas fa-history text-success"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">&lt; 1 Tahun</div>
                                                        <div class="font-weight-bold text-success"
                                                            style="font-size: 0.82rem;" id="inv-aging-less-3m">0 Unit
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 2 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 3: > 1 Tahun -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(2)" style="cursor: pointer;"
                                                        title="> 1 Tahun">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #e0f2fe;">
                                                            <i class="fas fa-hourglass-half text-info"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">&gt; 1 Tahun</div>
                                                        <div class="font-weight-bold text-info"
                                                            style="font-size: 0.82rem;" id="inv-aging-3-12m">0 Unit
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 3 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 4: > 2 Tahun -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(3)" style="cursor: pointer;"
                                                        title="> 2 Tahun">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #fef3c7;">
                                                            <i class="fas fa-calendar-alt text-warning"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">&gt; 2 Tahun</div>
                                                        <div class="font-weight-bold text-warning"
                                                            style="font-size: 0.82rem;" id="inv-aging-more-12m">0 Unit
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 4 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 5: RE-Use -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(4)" style="cursor: pointer;"
                                                        title="RE-Use">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #fee2e2;">
                                                            <i class="fas fa-pause-circle text-danger"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">RE-Use</div>
                                                        <div class="font-weight-bold text-danger"
                                                            style="font-size: 0.82rem;" id="inv-re-useg">0 Unit</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pie Chart Column (Right Side) -->
                                            <div class="col-xl-3 col-lg-4 col-md-12 border-left pl-xl-3 my-auto">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div style="width: 48%; position: relative; height: 110px;">
                                                        <canvas id="dashInventorySummaryPieChart"></canvas>
                                                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;"
                                                            id="inventory-pie-center-text">
                                                            <div class="text-uppercase text-muted font-weight-bold"
                                                                style="font-size: 0.50rem; line-height: 1;">TOTAL</div>
                                                            <div class="font-weight-bold text-gray-800"
                                                                style="font-size: 0.85rem; line-height: 1.1;"
                                                                id="inventory-pie-total-val">0</div>
                                                        </div>
                                                    </div>
                                                    <div class="pl-2 text-left"
                                                        style="width: 52%; max-height: 110px; overflow-y: auto;"
                                                        id="dash-inv-pie-legend">
                                                        <div class="mb-1 d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-success mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-1">&lt;
                                                                1 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-1">0%</span>
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-info mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-2">&gt;
                                                                1 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-2">0%</span>
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-warning mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-3">&gt;
                                                                2 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-3">0%</span>
                                                        </div>
                                                        <div class="d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-danger mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;"
                                                                id="inv-legend-name-4">RE-Use</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-4">0%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="warehouse.php"
                                                        class="btn btn-sm btn-block font-weight-bold py-1 btn-detail-dark shadow-sm">Lihat
                                                        Detail <i class="fas fa-arrow-right ml-1"
                                                            style="font-size: 0.6rem;"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Outbound Summary (Full Width Card with Steps & KPI Cards on Right) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Card 3: Outbound Summary -->
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i
                                                class="fas fa-shipping-fast mr-2"></i>Outbound Summary</span>
                                        <span class="badge badge-success font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Outbound Management</span>
                                    </div>

                                    <div class="my-auto py-1">
                                        <div class="row align-items-center">
                                            <!-- Flow Steps Column (4 Steps: MR Pending > Total PR/PO Mover > Nilai PO Mover > Saving) -->
                                            <div class="col-xl-7 col-lg-7 col-md-12 mb-3 mb-lg-0 pr-xl-3">
                                                <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                    id="outbound-steps-container">
                                                    <!-- Step 1: Total MR -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                        onclick="openOutboundSummaryModal('total_mr')"
                                                        style="cursor: pointer;" title="Klik untuk detail Total MR">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                            <i class="fas fa-file-alt text-primary"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Total MR</div>
                                                        <div class="font-weight-bold text-primary"
                                                            style="font-size: 0.82rem;" id="outbound-total-mr">0 MR
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 1 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 2: Total PR/PO Mover -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                        onclick="openOutboundSummaryModal('pr_po_mover')"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail Total PR/PO Mover">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #e0f2fe;">
                                                            <i class="fas fa-exchange-alt text-info"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Total PR/PO Mover</div>
                                                        <div class="font-weight-bold text-info"
                                                            style="font-size: 0.82rem;" id="outbound-total-mover">0 PO
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 2 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 3: Nilai PO Mover -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                        onclick="openOutboundSummaryModal('nilai_po_mover')"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail Nilai PO Mover">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #ecfdf5;">
                                                            <i class="fas fa-file-invoice-dollar text-success"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Nilai PO Mover</div>
                                                        <div class="font-weight-bold text-success"
                                                            style="font-size: 0.82rem;" id="outbound-nilai-mover">Rp 0
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 3 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 4: Saving -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                        onclick="openOutboundSummaryModal('saving')"
                                                        style="cursor: pointer;" title="Klik untuk detail Saving">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #fef3c7;">
                                                            <i class="fas fa-dollar-sign text-warning"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Saving</div>
                                                        <div class="font-weight-bold text-warning"
                                                            style="font-size: 0.82rem;" id="outbound-saving">Rp 0</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- KPI Cards Column (Right Side: MR CLOSED, Fulfill, Packing, Shipped) -->
                                            <div class="col-xl-5 col-lg-5 col-md-12 border-left pl-xl-3 my-auto">
                                                <div class="bg-light rounded p-2 mb-2 border">
                                                    <div class="row align-items-center text-center">
                                                        <div class="col-12 outbound-summary-clickable"
                                                            onclick="openOutboundSummaryModal('mr_closed')"
                                                            style="cursor: pointer;"
                                                            title="Klik untuk detail MR Closed">
                                                            <div class="text-uppercase text-success font-weight-bold text-nowrap"
                                                                style="font-size: 0.65rem;">MR CLOSED</div>
                                                            <div class="font-weight-bold text-success"
                                                                style="font-size: 1.25rem;" id="outbound-terkirim">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1 outbound-summary-clickable"
                                                        onclick="openOutboundSummaryModal('mr_closed')"
                                                        style="cursor: pointer;"
                                                        title="Klik untuk detail Progress MR Closed">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-1 text-nowrap">
                                                            <span class="font-weight-bold text-muted text-nowrap"
                                                                style="font-size: 0.62rem;">Progress MR Closed</span>
                                                            <span
                                                                class="font-weight-bold text-success text-nowrap ml-auto pl-1"
                                                                style="font-size: 0.62rem;"
                                                                id="outbound-progress-percent">0%</span>
                                                        </div>
                                                        <div class="progress rounded-pill" style="height: 4px;">
                                                            <div class="progress-bar bg-success" role="progressbar"
                                                                id="outbound-progress-bar" style="width: 0%"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row text-center">
                                                    <div class="col-4 pr-1 pl-2">
                                                        <div class="py-1 rounded border bg-white outbound-summary-clickable"
                                                            onclick="openOutboundSummaryModal('fulfill')"
                                                            style="cursor: pointer;" title="Klik untuk detail Fulfilled">
                                                            <i class="fas fa-box-open text-primary"
                                                                style="font-size: 0.72rem;"></i>
                                                            <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                style="font-size: 0.58rem;">Fulfilled</div>
                                                            <div class="font-weight-bold text-primary"
                                                                style="font-size: 0.78rem;" id="sub-fulfill-count">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 px-1">
                                                        <div class="py-1 rounded border bg-white outbound-summary-clickable"
                                                            onclick="openOutboundSummaryModal('packing')"
                                                            style="cursor: pointer;" title="Klik untuk detail Packing">
                                                            <i class="fas fa-dolly text-warning"
                                                                style="font-size: 0.72rem;"></i>
                                                            <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                style="font-size: 0.58rem;">Packed</div>
                                                            <div class="font-weight-bold text-warning"
                                                                style="font-size: 0.78rem;" id="sub-packing-count">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 pl-1 pr-2">
                                                        <div class="py-1 rounded border bg-white outbound-summary-clickable"
                                                            onclick="openOutboundSummaryModal('shipped')"
                                                            style="cursor: pointer;" title="Klik untuk detail Shipped">
                                                            <i class="fas fa-truck text-success"
                                                                style="font-size: 0.72rem;"></i>
                                                            <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                style="font-size: 0.58rem;">Shipped</div>
                                                            <div class="font-weight-bold text-success"
                                                                style="font-size: 0.78rem;" id="sub-shipped-count">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="outbound.php"
                                                        class="btn btn-sm btn-block font-weight-bold py-1 btn-detail-dark shadow-sm">Lihat
                                                        Detail <i class="fas fa-arrow-right ml-1"
                                                            style="font-size: 0.6rem;"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Storage Utilization & Receiving Trend -->
                    <div class="row mt-2" style="margin-left: -4px; margin-right: -4px;">

                        <!-- Card 1: Storage Utilization -->
                        <div class="col-xl-6 col-lg-6 col-md-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-2 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.82rem;"><i class="fas fa-warehouse mr-2"></i>Storage
                                            Utilization</span>
                                    </div>
                                    <div class="my-auto">
                                        <!-- Main Utilization Rate -->
                                        <div class="text-center mb-2">
                                            <div class="text-uppercase text-muted font-weight-bold"
                                                style="font-size: 0.6rem;">Utilization Rate</div>
                                            <div class="font-weight-bold text-primary"
                                                style="font-size: 1.6rem; line-height: 1.2;" id="storage-util-rate">0%
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="progress rounded-pill" style="height: 8px;">
                                                <div class="progress-bar" role="progressbar" id="storage-util-bar"
                                                    style="width: 0%; background: linear-gradient(90deg, #4e73df 0%, #224abe 100%);">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Sub metrics -->
                                        <div class="row text-center">
                                            <div class="col-4 pr-1">
                                                <div class="py-1.5 rounded border bg-light status-card-clickable"
                                                    onclick="openStorageUtilizationModal('total_capacity')"
                                                    style="cursor: pointer; transition: all 0.2s ease;"
                                                    title="Klik untuk melihat detail Total Capacity">
                                                    <i class="fas fa-th text-primary" style="font-size: 0.72rem;"></i>
                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap mt-1"
                                                        style="font-size: 0.55rem;">Total Capacity</div>
                                                    <div class="font-weight-bold text-gray-800"
                                                        style="font-size: 0.78rem;" id="storage-total-capacity">0 <span
                                                            class="text-muted font-weight-normal"
                                                            style="font-size: 0.55rem;">Location</span></div>
                                                </div>
                                            </div>
                                            <div class="col-4 px-1">
                                                <div class="py-1.5 rounded border bg-light status-card-clickable"
                                                    onclick="openStorageUtilizationModal('used')"
                                                    style="cursor: pointer; transition: all 0.2s ease;"
                                                    title="Klik untuk melihat detail Used Capacity">
                                                    <i class="fas fa-boxes text-warning"
                                                        style="font-size: 0.72rem;"></i>
                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap mt-1"
                                                        style="font-size: 0.55rem;">Used</div>
                                                    <div class="font-weight-bold text-warning"
                                                        style="font-size: 0.78rem;" id="storage-used">0 <span
                                                            class="text-muted font-weight-normal"
                                                            style="font-size: 0.55rem;">Location</span></div>
                                                </div>
                                            </div>
                                            <div class="col-4 pl-1">
                                                <div class="py-1.5 rounded border bg-light status-card-clickable"
                                                    onclick="openStorageUtilizationModal('available')"
                                                    style="cursor: pointer; transition: all 0.2s ease;"
                                                    title="Klik untuk melihat detail Available Capacity">
                                                    <i class="fas fa-cube text-success" style="font-size: 0.72rem;"></i>
                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap mt-1"
                                                        style="font-size: 0.55rem;">Available</div>
                                                    <div class="font-weight-bold text-success"
                                                        style="font-size: 0.78rem;" id="storage-available">0 <span
                                                            class="text-muted font-weight-normal"
                                                            style="font-size: 0.55rem;">Location</span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-2">
                                        <a href="warehouse.php"
                                            class="btn btn-sm btn-block font-weight-bold py-1 btn-detail-dark shadow-sm">Lihat
                                            Detail <i class="fas fa-arrow-right ml-1"
                                                style="font-size: 0.6rem;"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Trends (Perangkat IN & Perangkat OUT from Storage Tekno) -->
                        <div class="col-xl-6 col-lg-6 col-md-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-2 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.82rem;"><i class="fas fa-chart-line mr-2"></i>Trends
                                            Perangkat (IN & OUT)</span>
                                        <span class="badge badge-info font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;" id="trends-title-period">Storage Tekno</span>
                                    </div>
                                    <div class="my-auto" style="position: relative; height: 145px;">
                                        <canvas id="dashReceivingTrendLineChart"></canvas>
                                    </div>
                                    <div class="mt-auto pt-2">
                                        <a href="warehouse.php"
                                            class="btn btn-sm btn-block font-weight-bold py-1 btn-detail-dark shadow-sm">Lihat
                                            Detail <i class="fas fa-arrow-right ml-1"
                                                style="font-size: 0.6rem;"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                    <!-- Dashboard Summary Detail Modal (Empty Content Table / Detail for Inbound & Outbound) -->
                    <div class="modal fade" id="dashboardSummaryDetailModal" tabindex="-1" role="dialog"
                        aria-labelledby="summaryModalTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                                <div class="modal-header border-bottom py-2.5 px-3.5 align-items-center"
                                    style="background-color: #f8f9fc;">
                                    <h5 class="modal-title font-weight-bold text-gray-800 my-auto"
                                        style="font-size: 1rem;">
                                        <i id="summaryModalIcon" class="fas fa-stream text-primary mr-2"></i><span
                                            id="summaryModalTitle">Detail Summary</span>
                                    </h5>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.25rem 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-3.5 bg-white">
                                    <!-- Metric Value Summary Header -->
                                    <div class="p-3 mb-3 rounded-lg border bg-light shadow-sm text-center">
                                        <div class="text-xs font-weight-bold text-uppercase text-muted mb-1"
                                            id="summaryModalMetricLabel">METRIC NAME</div>
                                        <div class="h2 font-weight-bold text-primary mb-0" id="summaryModalQtyVal">0
                                        </div>
                                    </div>

                                    <!-- Empty Content Table -->
                                    <div class="table-responsive border rounded p-1 mb-0"
                                        style="border-color: #eaecf4 !important; max-height: 250px; overflow-y: auto;">
                                        <table class="table table-hover table-striped text-center mb-0 w-100"
                                            style="font-size: 0.82rem;">
                                            <thead class="bg-light text-gray-700 font-weight-bold"
                                                style="font-size: 0.8rem;">
                                                <tr>
                                                    <th class="py-2 border-top-0" style="width: 45px;">NO</th>
                                                    <th class="py-2 border-top-0 text-left" id="summaryModalColDoc">NO
                                                        DOKUMEN</th>
                                                    <th class="py-2 border-top-0 text-center" id="summaryModalColQty">
                                                        QTY</th>
                                                    <th class="py-2 border-top-0 text-center"
                                                        id="summaryModalColStatus">STATUS</th>
                                                </tr>
                                            </thead>
                                            <tbody id="summaryModalTableBody">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4 font-italic"
                                                        style="font-size: 0.82rem;">
                                                        <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                                                        Belum ada data detail untuk ditampilkan
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div
                                    class="modal-footer bg-light py-2 px-3.5 justify-content-between align-items-center">
                                    <span class="small text-muted">Total Data: <strong id="summaryModalRecordCount"
                                            class="text-gray-800">0</strong></span>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Utilisasi Area / Rack Detail Modal (Matching UTILISASI AREA / RACK in Storage Management) -->
                    <div class="modal fade" id="storageUtilisasiDetailModal" tabindex="-1" role="dialog"
                        aria-labelledby="storageUtilModalTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                                <div class="modal-header border-bottom py-2.5 px-3.5 align-items-center"
                                    style="background-color: #f8f9fc;">
                                    <div>
                                        <h6 class="modal-title font-weight-bold text-primary my-0"
                                            style="font-size: 0.95rem;">
                                            <i class="fas fa-boxes mr-1.5"></i> UTILISASI AREA / RACK
                                            <span class="badge badge-primary px-2 py-0.5 font-weight-bold ml-2"
                                                id="storageUtilModalBadge" style="font-size: 0.72rem;">Total
                                                Capacity</span>
                                        </h6>
                                        <small class="text-muted font-weight-normal">Kapasitas Rack &amp; Area
                                            Storage</small>
                                    </div>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.25rem 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-3.5 bg-white">
                                    <!-- Status Dots Summary (Green <= 50%, Yellow 51-75%, Red > 75%) -->
                                    <div
                                        class="d-flex flex-wrap align-items-center justify-content-between mb-2.5 pb-2 border-bottom">
                                        <div id="modal-rack-status-dots" class="d-flex align-items-center"
                                            style="gap: 6px;"></div>
                                        <div class="d-flex align-items-center">
                                            <div class="input-group input-group-sm" style="max-width: 220px;">
                                                <input type="text" class="form-control form-control-sm"
                                                    id="searchModalUtilisasiInput" placeholder="Cari Rack/Area..."
                                                    style="font-size: 0.75rem;">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Utilisasi Area / Rack Table (Identical format to warehouse.php) -->
                                    <div class="table-responsive border rounded"
                                        style="max-height: 360px; overflow-y: auto;">
                                        <table class="table table-hover mb-0 w-100" id="tableStorageUtilisasiDetail"
                                            style="font-size: 0.82rem;">
                                            <thead class="bg-light font-weight-bold text-gray-800"
                                                style="position: sticky; top: 0; z-index: 2; background-color: #f8f9fc; border-bottom: 2px solid #e3e6f0;">
                                                <tr>
                                                    <th class="py-2 px-3 text-left">RACK / AREA</th>
                                                    <th class="py-2 px-3 text-center" style="width: 55%;">CAPACITY</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableStorageUtilisasiBody">
                                                <tr>
                                                    <td colspan="2"
                                                        class="py-4 text-center text-muted bg-white font-italic">
                                                        <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                                                        Pilih periode data untuk menampilkan utilisasi area / rack
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light py-2 px-3.5 justify-content-end align-items-center">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- KPI Month Detail Modal (Matriks Evaluasi 9 KPI) -->
                    <div class="modal fade" id="kpiMonthDetailModal" tabindex="-1" role="dialog"
                        aria-labelledby="kpiMonthModalTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                                <div class="modal-header border-bottom py-2.5 px-3.5 align-items-center"
                                    style="background-color: #f8f9fc;">
                                    <h5 class="modal-title font-weight-bold text-gray-800 my-auto"
                                        style="font-size: 1rem;">
                                        <i class="fas fa-table text-primary mr-2"></i><span
                                            id="kpiMonthModalTitle">Matriks Evaluasi 9 KPI</span>
                                    </h5>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.25rem 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-3.5 bg-white">
                                    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                                        <span class="text-xs font-weight-bold text-uppercase text-muted"
                                            id="kpiMonthModalSubtitle">PERIODE: JANUARI 2026</span>
                                        <span class="badge badge-primary px-2.5 py-1" style="font-size: 0.75rem;">9
                                            Indikator KPI</span>
                                    </div>
                                    <div class="table-responsive border rounded p-1 mb-0"
                                        style="border-color: #eaecf4 !important;">
                                        <table class="table table-hover table-striped mb-0 w-100"
                                            style="font-size: 0.83rem;">
                                            <thead class="bg-light text-gray-700 font-weight-bold"
                                                style="font-size: 0.8rem;">
                                                <tr>
                                                    <th class="text-center py-2" style="width: 40px;">No</th>
                                                    <th class="py-2">Indikator KPI</th>
                                                    <th class="text-center py-2">Target</th>
                                                    <th class="text-right py-2">Realisasi</th>
                                                    <th class="text-center py-2">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="kpiMonthModalBody">
                                                <!-- Populated dynamically with 9 KPI rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.container-fluid -->
                </div>
                <!-- End of Main Content -->

                <script src="frontend/vendor/chart.js/Chart.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script src="frontend/js/formula-controller.js?v=23"></script>
                <script src="frontend/js/demo/chart-dashboard-demo.js?v=<?= time() ?>"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var ALL_MONTHS = [
                            "January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"
                        ];

                        var KPI_MATRIX_DEFAULT_ITEMS = [
                            { no: 1, name: 'Receiving (GR) SLA', target: '≥ 95.0%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 2, name: 'Registration SLA', target: '≥ 98.0%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 3, name: 'Stock Opname', target: '≥ 99.5%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 4, name: 'Stock Opname Warehouse Hub', target: '≥ 99.5%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 5, name: 'Stock Opname Outlet Warehouse', target: '≥ 99.5%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 6, name: 'Slow Moving', target: '≤ 15.0%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 7, name: 'Capacity', target: '70.0% - 80.0%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 8, name: 'Delivery Effectiveness', target: '≥ 95.0%', actual: '0%', unit: 'Persen (%)', status: '-' },
                            { no: 9, name: 'Efisiensi Delivery', target: 'Rp 130.000.000', actual: 'Rp 0', unit: 'IDR Rupiah', status: '-' }
                        ];

                        var KPI_MATRIX_JANUARY_ITEMS = [
                            { no: 1, name: 'Receiving (GR) SLA', target: '≥ 95.0%', actual: '96,5%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 2, name: 'Registration SLA', target: '≥ 98.0%', actual: '98,2%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 3, name: 'Stock Opname', target: '≥ 99.5%', actual: '99,8%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 4, name: 'Stock Opname Warehouse Hub', target: '≥ 99.5%', actual: '99,9%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 5, name: 'Stock Opname Outlet Warehouse', target: '≥ 99.5%', actual: '99,0%', unit: 'Persen (%)', status: 'Di Bawah Target' },
                            { no: 6, name: 'Slow Moving', target: '≤ 15.0%', actual: '12,8%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 7, name: 'Capacity', target: '70.0% - 80.0%', actual: '76,4%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 8, name: 'Delivery Effectiveness', target: '≥ 95.0%', actual: '97,4%', unit: 'Persen (%)', status: 'Tercapai' },
                            { no: 9, name: 'Efisiensi Delivery', target: 'Rp 130.000.000', actual: 'Rp 148.500.000', unit: 'IDR Rupiah', status: 'Tercapai' }
                        ];

                        function renderKpiMonthRows(items) {
                            var tbody = document.getElementById('kpiMonthModalBody');
                            if (!tbody) return;
                            tbody.replaceChildren();

                            items.forEach(function (item) {
                                var tr = document.createElement('tr');

                                var statusBadge = '<span class="badge badge-secondary px-2 py-1">-</span>';
                                var s = (item.status || '').toLowerCase();
                                if (s === 'tercapai' || s === 'achieved') {
                                    statusBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Tercapai</span>';
                                } else if (s === 'di bawah target' || s === 'below target' || s === 'not achieved' || s === 'critical' || s === 'di bawah') {
                                    statusBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>Di Bawah Target</span>';
                                } else if (s === 'perhatian' || s === 'warning') {
                                    statusBadge = '<span class="badge badge-warning px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Perhatian</span>';
                                } else if (s && s !== '-') {
                                    statusBadge = '<span class="badge badge-info px-2 py-1">' + item.status + '</span>';
                                }

                                tr.innerHTML = '<td class="text-center font-weight-bold text-muted">' + item.no + '</td>' +
                                    '<td class="font-weight-bold text-gray-800">' + item.name + '</td>' +
                                    '<td class="text-center font-weight-bold text-muted">' + item.target + '</td>' +
                                    '<td class="text-right font-weight-bold text-primary">' + item.actual + '</td>' +
                                    '<td class="text-center">' + statusBadge + '</td>';
                                tbody.appendChild(tr);
                            });
                        }

                        // Open Matriks Evaluasi 9 KPI modal on clicking month in chart
                        window.openKpiMonthDetailModal = function (monthIndex) {
                            var monthNamesIndo = [
                                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                            ];
                            var monthNamesEng = [
                                'January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December'
                            ];

                            var monthName = monthNamesIndo[monthIndex] || 'Bulan';
                            var engMonth = monthNamesEng[monthIndex] || '';

                            var yearSelect = document.getElementById('period-year-select');
                            var year = (yearSelect && yearSelect.value) ? yearSelect.value : '2026';

                            var titleEl = document.getElementById('kpiMonthModalTitle');
                            if (titleEl) titleEl.textContent = 'Matriks Evaluasi 9 KPI';

                            var subEl = document.getElementById('kpiMonthModalSubtitle');
                            if (subEl) subEl.textContent = 'PERIODE: ' + monthName.toUpperCase() + ' ' + year;

                            // January 2026 uses test dataset by default, others default to empty
                            if (monthIndex === 0) {
                                renderKpiMonthRows(KPI_MATRIX_JANUARY_ITEMS);
                            } else {
                                renderKpiMonthRows(KPI_MATRIX_DEFAULT_ITEMS);
                            }

                            var periodStr = engMonth + ' ' + year;
                            fetch('api/get_kpi_data.php?periode=' + encodeURIComponent(periodStr))
                                .then(function (res) { return res.json(); })
                                .then(function (res) {
                                    if (res && res.status === 'success' && Array.isArray(res.kpi_list) && res.kpi_list.length > 0) {
                                        var mappedItems = res.kpi_list.map(function (k, idx) {
                                            return {
                                                no: idx + 1,
                                                name: k.name,
                                                target: k.target_display,
                                                actual: k.actual_display,
                                                unit: k.is_currency ? 'IDR Rupiah' : 'Persen (%)',
                                                status: k.status || '-'
                                            };
                                        });
                                        renderKpiMonthRows(mappedItems);
                                    }
                                })
                                .catch(function (err) {
                                    console.log('Using default KPI matrix items:', err);
                                });

                            $('#kpiMonthDetailModal').modal('show');
                        };

                        // Generic Summary Detail Modal Handler (Empty Content Modal)
                        window.openGenericSummaryModal = function (cfg) {
                            var titleEl = document.getElementById('summaryModalTitle');
                            if (titleEl) titleEl.textContent = cfg.title || 'Detail Summary';

                            var iconEl = document.getElementById('summaryModalIcon');
                            if (iconEl) {
                                if (cfg.moduleType === 'inbound') {
                                    iconEl.className = 'fas fa-stream text-primary mr-2';
                                } else if (cfg.moduleType === 'outbound') {
                                    iconEl.className = 'fas fa-shipping-fast text-primary mr-2';
                                } else {
                                    iconEl.className = 'fas fa-cubes text-primary mr-2';
                                }
                            }

                            var metricEl = document.getElementById('summaryModalMetricLabel');
                            if (metricEl) metricEl.textContent = cfg.metricLabel || '';

                            var qtyEl = document.getElementById('summaryModalQtyVal');
                            if (qtyEl) qtyEl.textContent = cfg.qtyVal || '0';

                            var colDoc = document.getElementById('summaryModalColDoc');
                            if (colDoc) colDoc.textContent = cfg.docColTitle || 'NO DOKUMEN';

                            var tbody = document.getElementById('summaryModalTableBody');
                            if (tbody) {
                                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4 font-italic" style="font-size: 0.82rem;"><i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>Belum ada data detail untuk ditampilkan</td></tr>';
                            }

                            var recCount = document.getElementById('summaryModalRecordCount');
                            if (recCount) recCount.textContent = '0';

                            $('#dashboardSummaryDetailModal').modal('show');
                        };

                        // Inbound Summary interactive modal handler
                        window.openInboundSummaryModal = function (stepIndex) {
                            var titles = [
                                'Detail Total PO Inbound',
                                'Detail PO Ontime Delivery',
                                'Detail PO Terlambat Delivery',
                                'Detail PO Sudah GR',
                                'Detail PO Sudah Registrasi',
                                'Detail Total GR',
                                'Detail Total Registrasi'
                            ];
                            var labels = [
                                'TOTAL PO INBOUND',
                                'PO ONTIME DELIVERY',
                                'PO TERLAMBAT DELIVERY',
                                'PO SUDAH GR',
                                'PO SUDAH REGISTRASI',
                                'TOTAL GR',
                                'TOTAL REGISTRASI'
                            ];
                            var ids = [
                                'flow-po-count',
                                'flow-po-proses-delivery',
                                'flow-po-terlambat-delivery',
                                'flow-gr-count',
                                'flow-reg-count',
                                'flow-total-gr',
                                'flow-done-count'
                            ];

                            var totalPoEl = document.getElementById('flow-po-count');
                            var totalPoNum = totalPoEl ? (parseInt(totalPoEl.textContent) || 0) : 0;

                            var qtyEl = document.getElementById(ids[stepIndex]);
                            var qtyVal = qtyEl ? qtyEl.textContent.trim() : '0';
                            var qtyNum = parseInt(qtyVal) || 0;

                            var pctVal = '';
                            var showPct = true;
                            if (stepIndex === 0) {
                                pctVal = '100% (Baseline Total PO Inbound)';
                            } else if (stepIndex >= 1 && stepIndex <= 4) {
                                var pct = totalPoNum > 0 ? Math.round((qtyNum / totalPoNum) * 100) : 0;
                                pctVal = pct + '% dari Total PO Inbound';
                            } else {
                                var pctOther = totalPoNum > 0 ? Math.round((qtyNum / totalPoNum) * 100) : 0;
                                pctVal = pctOther + '% rasio pemenuhan';
                            }

                            openGenericSummaryModal({
                                moduleType: 'inbound',
                                title: titles[stepIndex] || 'Detail Inbound',
                                metricLabel: labels[stepIndex] || 'INBOUND METRIC',
                                qtyVal: qtyVal,
                                pctVal: pctVal,
                                showPct: showPct,
                                docColTitle: 'NO PO'
                            });
                        };

                        // Outbound Summary interactive modal handler
                        window.openOutboundSummaryModal = function (metricKey) {
                            var title = 'Detail Outbound';
                            var metricLabel = '';
                            var qtyVal = '0';
                            var pctVal = '';
                            var showPct = true;

                            var totalMrEl = document.getElementById('outbound-total-mr');
                            var totalMoverEl = document.getElementById('outbound-total-mover');
                            var nilaiMoverEl = document.getElementById('outbound-nilai-mover');
                            var savingEl = document.getElementById('outbound-saving');
                            var totalOrderEl = document.getElementById('outbound-total-order');
                            var terkirimEl = document.getElementById('outbound-terkirim');
                            var progressPctEl = document.getElementById('outbound-progress-percent');
                            var fulfillEl = document.getElementById('sub-fulfill-count');
                            var packingEl = document.getElementById('sub-packing-count');
                            var shippedEl = document.getElementById('sub-shipped-count');

                            var totalOrderNum = totalOrderEl ? (parseInt(totalOrderEl.textContent) || 0) : 0;
                            var progressPct = progressPctEl ? progressPctEl.textContent.trim() : '0%';

                            if (metricKey === 'total_mr' || metricKey === 'mr_pending') {
                                title = 'Detail Total MR';
                                metricLabel = 'TOTAL MR';
                                qtyVal = totalMrEl ? totalMrEl.textContent.trim() : '0 MR';
                                pctVal = 'Total Permintaan Material (MR)';
                                showPct = false;
                            } else if (metricKey === 'pr_po_mover') {
                                title = 'Detail Total PR/PO Mover';
                                metricLabel = 'TOTAL PR/PO MOVER';
                                qtyVal = totalMoverEl ? totalMoverEl.textContent.trim() : '0 PO';
                                pctVal = 'Total Pemenuhan via Mover';
                                showPct = false;
                            } else if (metricKey === 'nilai_po_mover') {
                                title = 'Detail Nilai PO Mover';
                                metricLabel = 'NILAI PO MOVER';
                                qtyVal = nilaiMoverEl ? nilaiMoverEl.textContent.trim() : 'Rp 0';
                                pctVal = 'Total Valuasi PO Mover';
                            } else if (metricKey === 'saving') {
                                title = 'Detail Saving';
                                metricLabel = 'SAVING';
                                qtyVal = savingEl ? savingEl.textContent.trim() : 'Rp 0';
                                pctVal = 'Efisiensi Biaya (Cost Saving)';
                            } else if (metricKey === 'mr_closed' || metricKey === 'progress_closed') {
                                title = 'Detail MR Closed';
                                metricLabel = 'MR CLOSED';
                                qtyVal = terkirimEl ? terkirimEl.textContent.trim() + ' Closed' : '0 Closed';
                                pctVal = progressPct + ' Selesai (Closed)';
                            } else if (metricKey === 'fulfill') {
                                title = 'Detail Fulfilled';
                                metricLabel = 'FULFILLED';
                                qtyVal = fulfillEl ? fulfillEl.textContent.trim() + ' Unit' : '0 Unit';
                                showPct = false;
                            } else if (metricKey === 'packing') {
                                title = 'Detail Packed';
                                metricLabel = 'PACKED';
                                qtyVal = packingEl ? packingEl.textContent.trim() + ' Unit' : '0 Unit';
                                showPct = false;
                            } else if (metricKey === 'shipped') {
                                title = 'Detail Shipped';
                                metricLabel = 'SHIPPED';
                                qtyVal = shippedEl ? shippedEl.textContent.trim() + ' Unit' : '0 Unit';
                                showPct = false;
                            }

                            openGenericSummaryModal({
                                moduleType: 'outbound',
                                title: title,
                                metricLabel: metricLabel,
                                qtyVal: qtyVal,
                                pctVal: pctVal,
                                showPct: showPct,
                                docColTitle: 'NO MR / PO'
                            });
                        };

                        // Keep the dropdown open when clicking inside the selects/options
                        var periodMenu = document.getElementById('period-dropdown-menu');
                        if (periodMenu) {
                            periodMenu.addEventListener('click', function (e) {
                                e.stopPropagation();
                            });
                        }

                        // Click handler for Storage Summary flow steps on dashboard overview
                        window.openStorageSummaryModal = function (stepIndex) {
                            var titles = [
                                'Detail Total Perangkat',
                                'Detail < 1 Tahun',
                                'Detail > 1 Tahun',
                                'Detail > 2 Tahun',
                                'Detail RE-Use'
                            ];
                            var labels = [
                                'TOTAL PERANGKAT',
                                '< 1 TAHUN',
                                '> 1 TAHUN',
                                '> 2 TAHUN',
                                'RE-USE'
                            ];
                            var ids = [
                                'inv-total-perangkat',
                                'inv-aging-less-3m',
                                'inv-aging-3-12m',
                                'inv-aging-more-12m',
                                'inv-re-useg'
                            ];

                            var qtyEl = document.getElementById(ids[stepIndex]);
                            var qtyVal = qtyEl ? qtyEl.textContent.trim() : '0 Unit';

                            openGenericSummaryModal({
                                moduleType: 'storage',
                                title: titles[stepIndex] || 'Detail Storage',
                                metricLabel: labels[stepIndex] || 'STORAGE METRIC',
                                qtyVal: qtyVal,
                                docColTitle: 'NO REGISTRASI'
                            });
                        };

                        function populateSelect(selectId, items, placeholder) {
                            var sel = document.getElementById(selectId);
                            if (!sel) return;
                            sel.replaceChildren();
                            var defOpt = document.createElement('option');
                            defOpt.value = '';
                            defOpt.textContent = placeholder;
                            sel.appendChild(defOpt);
                            items.forEach(function (item) {
                                var opt = document.createElement('option');
                                opt.value = item;
                                opt.textContent = item.toUpperCase();
                                sel.appendChild(opt);
                            });
                        }

                        function updateLoadButton() {
                            var m = document.getElementById('period-month-select');
                            var y = document.getElementById('period-year-select');
                            var btn = document.getElementById('btn-load-period');
                            if (btn) {
                                btn.disabled = !(m && m.value && y && y.value);
                            }
                        }

                        var monthSel = document.getElementById('period-month-select');
                        var yearSel = document.getElementById('period-year-select');
                        if (monthSel) monthSel.addEventListener('change', updateLoadButton);
                        if (yearSel) yearSel.addEventListener('change', updateLoadButton);

                        function fetchTrendsData(year) {
                            if (!year) year = new Date().getFullYear().toString();
                            fetch('api/get_yearly_in_out.php?year=' + encodeURIComponent(year))
                                .then(function (r) { return r.json(); })
                                .then(function (resData) {
                                    if (resData && resData.status === 'success' && resData.data) {
                                        if (window.updateDashReceivingTrendChart) {
                                            window.updateDashReceivingTrendChart(
                                                resData.data.in,
                                                resData.data.out,
                                                resData.data.in_details,
                                                resData.data.out_details,
                                                year
                                            );
                                        }
                                    } else {
                                        if (window.updateDashReceivingTrendChart) {
                                            window.updateDashReceivingTrendChart([], [], [], [], year);
                                        }
                                    }
                                })
                                .catch(function (err) {
                                    console.error('Error fetching trends data:', err);
                                });
                        }

                        function loadPeriods() {
                            fetch('api/get_periods.php')
                                .then(function (r) { return r.json(); })
                                .then(function (result) {
                                    var yearsSet = {};
                                    if (result.status === 'success' && result.data) {
                                        result.data.forEach(function (pg) {
                                            if (!pg || pg === 'Unknown Period') return;
                                            var parts = pg.split(' ');
                                            if (parts.length >= 2) {
                                                yearsSet[parts[1]] = true;
                                            }
                                        });
                                    }
                                    var availableYears = Object.keys(yearsSet).sort();
                                    populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                                    populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');

                                    var pText = document.getElementById('selected-period-text');
                                    if (pText) pText.textContent = "PILIH PERIODE DATA";

                                    if (window.FormulaController) {
                                        window.FormulaController.updateDashboardCards([], []);
                                    }

                                    var defaultYear = availableYears.length > 0 ? availableYears[availableYears.length - 1] : new Date().getFullYear().toString();
                                    fetchTrendsData(defaultYear);
                                })
                                .catch(function (err) {
                                    console.error('Error fetching periods:', err);
                                });
                        }

                        var btnLoad = document.getElementById('btn-load-period');
                        if (btnLoad) {
                            btnLoad.addEventListener('click', function () {
                                var m = document.getElementById('period-month-select');
                                var y = document.getElementById('period-year-select');
                                if (m && m.value && y && y.value) {
                                    var period = m.value + ' ' + y.value;
                                    loadDashboardData(period);
                                    if (window.jQuery) {
                                        $('#periodDropdown').dropdown('toggle');
                                    }
                                }
                            });
                        }

                        var btnReset = document.getElementById('btn-reset-period');
                        if (btnReset) {
                            btnReset.addEventListener('click', function () {
                                var m = document.getElementById('period-month-select');
                                var y = document.getElementById('period-year-select');
                                if (m) m.value = '';
                                if (y) y.value = '';
                                updateLoadButton();

                                var pText = document.getElementById('selected-period-text');
                                if (pText) pText.textContent = "PILIH PERIODE DATA";

                                window.currentDashboardData = [];
                                window.currentDashboardHeaders = [];
                                if (window.FormulaController) {
                                    window.FormulaController.updateDashboardCards([], []);
                                }
                                if (window.updateDashKpiMonitoringChart) {
                                    window.updateDashKpiMonitoringChart([null, null, null, null, null, null, null, null, null]);
                                }
                                fetchTrendsData();
                            });
                        }

                        function loadDashboardData(period) {
                            var pText = document.getElementById('selected-period-text');
                            if (pText) pText.textContent = period.toUpperCase();

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Data Is Processing Please Wait',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => { Swal.showLoading(); }
                                });
                            }

                            var parts = period.split(' ');
                            var yr = parts.length > 1 ? parts[parts.length - 1] : new Date().getFullYear().toString();

                            var fetchDashboard = fetch('api/get_data.php?periode=' + encodeURIComponent(period))
                                .then(function (response) { return response.json(); });

                            var fetchYearly = fetch('api/get_yearly_in_out.php?year=' + encodeURIComponent(yr))
                                .then(function (response) { return response.json(); });

                            Promise.all([fetchDashboard, fetchYearly])
                                .then(function (results) {
                                    var result = results[0];
                                    var resData = results[1];

                                    if (result && result.status === 'success' && result.data && result.data.length > 0) {
                                        var headers = Object.keys(result.data[0]);
                                        window.currentDashboardData = result.data;
                                        window.currentDashboardHeaders = headers;
                                        if (window.FormulaController) {
                                            window.FormulaController.updateDashboardCards(result.data, headers);
                                        }
                                    } else {
                                        window.currentDashboardData = [];
                                        window.currentDashboardHeaders = [];
                                        if (window.FormulaController) {
                                            window.FormulaController.updateDashboardCards([], []);
                                        }
                                    }

                                    if (resData && resData.status === 'success' && resData.data) {
                                        if (window.updateDashReceivingTrendChart) {
                                            window.updateDashReceivingTrendChart(
                                                resData.data.in,
                                                resData.data.out,
                                                resData.data.in_details,
                                                resData.data.out_details,
                                                yr
                                            );
                                        }
                                    } else {
                                        if (window.updateDashReceivingTrendChart) {
                                            window.updateDashReceivingTrendChart([], [], [], [], yr);
                                        }
                                    }

                                    if (typeof Swal !== 'undefined') Swal.close();
                                })
                                .catch(function (error) {
                                    console.error('Error fetching dashboard data:', error);
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire('Error', 'Failed to load data. Please try again.', 'error');
                                    }
                                });
                        }

                        // Storage Utilization (Area / Rack) Dashboard Integration & Modal Handlers
                        var rawUtilisasiRows = [];
                        var currentUtilisasiFilter = 'all';

                        window.openStorageUtilizationModal = function (filterType) {
                            currentUtilisasiFilter = filterType || 'all';
                            $('#storageUtilisasiDetailModal').modal('show');

                            $('#searchModalUtilisasiInput').val('');
                            updateUtilisasiModalFilterUI(currentUtilisasiFilter);
                            fetchAndRenderUtilisasiModal(currentUtilisasiFilter);
                        };

                        window.filterModalUtilisasiData = function (filterType) {
                            currentUtilisasiFilter = filterType;
                            updateUtilisasiModalFilterUI(currentUtilisasiFilter);
                            renderUtilisasiModalTable();
                        };

                        function updateUtilisasiModalFilterUI(filterType) {
                            $('#btn-modal-filter-all, #btn-modal-filter-used, #btn-modal-filter-available').removeClass('active');
                            if (filterType === 'used') {
                                $('#btn-modal-filter-used').addClass('active');
                                $('#storageUtilModalBadge').text('Used').removeClass().addClass('badge badge-warning px-2 py-0.5 font-weight-bold ml-2');
                            } else if (filterType === 'available') {
                                $('#btn-modal-filter-available').addClass('active');
                                $('#storageUtilModalBadge').text('Available').removeClass().addClass('badge badge-success px-2 py-0.5 font-weight-bold ml-2');
                            } else {
                                $('#btn-modal-filter-all').addClass('active');
                                $('#storageUtilModalBadge').text('Total Capacity').removeClass().addClass('badge badge-primary px-2 py-0.5 font-weight-bold ml-2');
                            }
                        }

                        function fetchAndRenderUtilisasiModal(filterType) {
                            var tbody = $('#tableStorageUtilisasiBody');
                            tbody.html('<tr><td colspan="2" class="py-4 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data Utilisasi Area / Rack...</td></tr>');

                            var currentPeriodEl = document.getElementById('selected-period-text');
                            var currentPeriodStr = currentPeriodEl ? currentPeriodEl.textContent.trim() : '';
                            var periodMonth = '';
                            var periodYear = '';
                            if (currentPeriodStr && currentPeriodStr !== 'PILIH DATA' && currentPeriodStr !== 'PILIH PERIODE DATA' && currentPeriodStr !== '-') {
                                var periodParts = currentPeriodStr.split(' ');
                                if (periodParts.length >= 2) {
                                    var rawMonth = periodParts[0];
                                    periodMonth = rawMonth.charAt(0).toUpperCase() + rawMonth.slice(1).toLowerCase();
                                    periodYear = periodParts[1];
                                }
                            }

                            var utilisasiUrl = 'api/get_rack_utilisasi.php';
                            if (periodMonth && periodYear) {
                                utilisasiUrl += '?month=' + encodeURIComponent(periodMonth) + '&year=' + encodeURIComponent(periodYear);
                            }

                            $.ajax({
                                url: utilisasiUrl,
                                type: 'GET',
                                dataType: 'json',
                                success: function (res) {
                                    if (res.status === 'success' && res.data) {
                                        rawUtilisasiRows = res.data;
                                        renderUtilisasiModalTable();
                                    } else {
                                        tbody.html('<tr><td colspan="2" class="py-4 text-center text-muted bg-white font-italic"><i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>Belum ada data utilisasi area/rack tersimpan untuk periode ini.</td></tr>');
                                        renderEmptyDots();
                                    }
                                },
                                error: function () {
                                    tbody.html('<tr><td colspan="2" class="py-4 text-center text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                                    renderEmptyDots();
                                }
                            });
                        }

                        function renderEmptyDots() {
                            var dotContainer = document.getElementById('modal-rack-status-dots');
                            if (dotContainer) {
                                dotContainer.innerHTML = '<span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-circle text-success mr-1"></i>0</span> ' +
                                    '<span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-circle text-warning mr-1"></i>0</span> ' +
                                    '<span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-circle text-danger mr-1"></i>0</span>';
                            }
                            $('#modal-util-rendered-count').text('0');
                        }

                        function renderUtilisasiModalTable() {
                            var searchVal = ($('#searchModalUtilisasiInput').val() || '').toLowerCase().trim();
                            var tbody = $('#tableStorageUtilisasiBody');

                            // Group by rack_group for display
                            var rackGroups = {};
                            for (var i = 0; i < rawUtilisasiRows.length; i++) {
                                var row = rawUtilisasiRows[i];
                                var rackName = String(row.rack_group || row.label || 'Unknown').trim();
                                if (!rackGroups[rackName]) {
                                    rackGroups[rackName] = { totalQty: 0, capacities: [], count: 0 };
                                }
                                rackGroups[rackName].totalQty += parseInt(row.qty) || 0;
                                rackGroups[rackName].capacities.push(parseFloat(row.capacity) || 0);
                                rackGroups[rackName].count++;
                            }

                            var rackNames = Object.keys(rackGroups);
                            rackNames.sort();

                            var greenCount = 0;
                            var yellowCount = 0;
                            var redCount = 0;
                            var renderedItems = [];

                            for (var q = 0; q < rackNames.length; q++) {
                                var rName = rackNames[q];
                                var group = rackGroups[rName];
                                var totalQty = group.totalQty;

                                var avgCap = 0;
                                if (group.capacities.length > 0) {
                                    var sumC = 0;
                                    for (var c = 0; c < group.capacities.length; c++) {
                                        sumC += group.capacities[c];
                                    }
                                    avgCap = Math.round(sumC / group.capacities.length);
                                }
                                if (avgCap > 100) avgCap = 100;

                                var isUsed = (avgCap > 0 || totalQty > 0);
                                if (currentUtilisasiFilter === 'used' && !isUsed) continue;
                                if (currentUtilisasiFilter === 'available' && isUsed && avgCap >= 100) continue;

                                if (searchVal && !rName.toLowerCase().includes(searchVal)) {
                                    continue;
                                }

                                var barColorClass = 'bg-success';
                                if (avgCap <= 50) {
                                    barColorClass = 'bg-success';
                                    greenCount++;
                                } else if (avgCap <= 75) {
                                    barColorClass = 'bg-warning';
                                    yellowCount++;
                                } else {
                                    barColorClass = 'bg-danger';
                                    redCount++;
                                }

                                renderedItems.push({
                                    name: rName,
                                    avgCap: avgCap,
                                    totalQty: totalQty,
                                    barColorClass: barColorClass
                                });
                            }

                            // Update status dots
                            var dotContainer = document.getElementById('modal-rack-status-dots');
                            if (dotContainer) {
                                dotContainer.innerHTML = '<span class="badge badge-light border text-dark px-2 py-1" style="font-size: 0.72rem;" title="Kapasitas <= 50%"><i class="fas fa-circle text-success mr-1"></i>' + greenCount + '</span> ' +
                                    '<span class="badge badge-light border text-dark px-2 py-1" style="font-size: 0.72rem;" title="Kapasitas 51-75%"><i class="fas fa-circle text-warning mr-1"></i>' + yellowCount + '</span> ' +
                                    '<span class="badge badge-light border text-dark px-2 py-1" style="font-size: 0.72rem;" title="Kapasitas > 75%"><i class="fas fa-circle text-danger mr-1"></i>' + redCount + '</span>';
                            }

                            $('#modal-util-rendered-count').text(renderedItems.length);

                            if (renderedItems.length === 0) {
                                tbody.html('<tr><td colspan="2" class="py-4 text-center text-muted bg-white font-italic"><i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>Tidak ada data rack/area yang sesuai dengan filter.</td></tr>');
                                return;
                            }

                            var html = '';
                            renderedItems.forEach(function (item) {
                                html += '<tr>' +
                                    '<td class="text-left font-weight-bold text-gray-800 py-2.5 px-3" style="font-size: 0.85rem; white-space: nowrap;">' +
                                    item.name +
                                    '</td>' +
                                    '<td class="py-2.5 px-3">' +
                                    '<div class="d-flex align-items-center">' +
                                    '<span class="mr-2 font-weight-bold text-gray-800 text-right" style="min-width: 38px; font-size: 0.8rem;">' + item.avgCap + '%</span>' +
                                    '<div class="progress progress-sm flex-grow-1" style="height: 10px; border-radius: 5px; background-color: #eaecf4;">' +
                                    '<div class="progress-bar ' + item.barColorClass + '" role="progressbar" style="width: ' + item.avgCap + '%; border-radius: 5px; transition: width 0.6s ease;" aria-valuenow="' + item.avgCap + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                                    '</div>' +
                                    '</div>' +
                                    '</td>' +
                                    '</tr>';
                            });

                            tbody.html(html);
                        }

                        $('#searchModalUtilisasiInput').on('keyup input', function () {
                            renderUtilisasiModalTable();
                        });

                        loadPeriods();
                        window.loadPeriods = loadPeriods;

                        if (window.jQuery) {
                            $('#storageManagementCarousel').on('slide.bs.carousel', function (e) {
                                var idx = e.to;
                                $('#storage-slide-indicator').text((idx + 1) + ' / 2');
                                $('#storage-slider-pills .nav-link').removeClass('active bg-info text-white').addClass('text-muted');
                                $('#storage-slider-pills .nav-link').eq(idx).addClass('active bg-info text-white').removeClass('text-muted');
                            });
                        }
                    });
                </script>

                <?php include FRONTEND_PATH . 'components/footer.php'; ?>
            </div>
        </div>
</body>

</html>