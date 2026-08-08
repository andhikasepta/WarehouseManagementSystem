<?php
// dashboard.php - Head-Warehouse Management Dashboard Overview
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('dashboard');
$currentUser = getCurrentUser();

$pageTitle = 'Dashboard Overview - Head Warehouse Management';
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
                                            <!-- Step 1: Total PO -->
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(0)" style="cursor: pointer;"
                                                title="Klik untuk filter Total PO">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                    <i class="fas fa-file-invoice text-primary"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">Total PO</div>
                                                <div class="font-weight-bold text-primary" style="font-size: 0.82rem;"
                                                    id="flow-po-count">0 PO</div>
                                            </div>

                                            <!-- Arrow 1 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 2: PO Proses Delivery -->
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(1)" style="cursor: pointer;"
                                                title="Klik untuk filter PO Proses Delivery">
                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                    style="width: 36px; height: 36px; background-color: #e0f2fe;">
                                                    <i class="fas fa-file-alt text-info"
                                                        style="font-size: 0.95rem;"></i>
                                                </div>
                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                    style="font-size: 0.62rem;">PO Proses Delivery</div>
                                                <div class="font-weight-bold text-info" style="font-size: 0.82rem;"
                                                    id="flow-po-proses-delivery">0 PO</div>
                                            </div>

                                            <!-- Arrow 2 -->
                                            <div class="text-gray-300 align-self-center mx-1"
                                                style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i></div>

                                            <!-- Step 3: PO Terlambat Delivery -->
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(2)" style="cursor: pointer;"
                                                title="Klik untuk filter PO Terlambat Delivery">
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
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(3)" style="cursor: pointer;"
                                                title="Klik untuk filter PO Sudah GR">
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
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(4)" style="cursor: pointer;"
                                                title="Klik untuk filter PO Sudah Registrasi">
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
                                            <div class="align-self-center mx-2 d-flex align-items-center justify-content-center"
                                                style="height: 38px;">
                                                <div style="width: 1.5px; height: 34px; background-color: #cbd5e1; border-radius: 1px;"></div>
                                            </div>

                                            <!-- Step 6: Total GR -->
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(5)" style="cursor: pointer;"
                                                title="Klik untuk filter Total GR">
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
                                            <div class="flow-step text-center flex-fill px-1 py-1"
                                                onclick="toggleInboundFlowSegment(6)" style="cursor: pointer;"
                                                title="Klik untuk filter Total Registrasi">
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
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-0" onclick="toggleInboundFlowSegment(0)"
                                                        style="cursor: pointer;" title="Klik filter Total PO">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #4e73df;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">Total PO</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-1" onclick="toggleInboundFlowSegment(1)"
                                                        style="cursor: pointer;" title="Klik filter Proses Delivery">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #36b9cc;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Proses Delivery</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-2" onclick="toggleInboundFlowSegment(2)"
                                                        style="cursor: pointer;" title="Klik filter Terlambat Delivery">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #e74a3b;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Terlambat Delivery</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-3" onclick="toggleInboundFlowSegment(3)"
                                                        style="cursor: pointer;" title="Klik filter Sudah GR">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #1cc88a;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Sudah GR</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-4" onclick="toggleInboundFlowSegment(4)"
                                                        style="cursor: pointer;" title="Klik filter Sudah Registrasi">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #6f42c1;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">PO Sudah Registrasi</span>
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-5" onclick="toggleInboundFlowSegment(5)"
                                                        style="cursor: pointer;" title="Klik filter Total GR">
                                                        <i class="fas fa-circle mr-2.5"
                                                            style="font-size: 0.42rem; color: #f6c23e;"></i>
                                                        <span class="text-gray-800 font-weight-bold text-nowrap"
                                                            style="font-size: 0.58rem;">Total GR</span>
                                                    </div>
                                                    <div class="d-flex align-items-center text-nowrap filter-legend-item"
                                                        id="inbound-flow-legend-6" onclick="toggleInboundFlowSegment(6)"
                                                        style="cursor: pointer;" title="Klik filter Total Registrasi">
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
                                                <div
                                                    class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                    id="storage-steps-container">
                                                    <!-- Step 1: Total Perangkat -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        onclick="openStorageSummaryModal(0)" style="cursor: pointer;" title="Total Perangkat">
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
                                                        onclick="openStorageSummaryModal(1)" style="cursor: pointer;" title="< 1 Tahun">
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
                                                        onclick="openStorageSummaryModal(2)" style="cursor: pointer;" title="> 1 Tahun">
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
                                                        onclick="openStorageSummaryModal(3)" style="cursor: pointer;" title="> 2 Tahun">
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
                                                        onclick="openStorageSummaryModal(4)" style="cursor: pointer;" title="RE-Use">
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
                                                                style="font-size: 0.58rem;" id="inv-legend-name-1">&lt; 1 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-1">0%</span>
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-info mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-2">&gt; 1 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-2">0%</span>
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-warning mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-3">&gt; 2 Tahun</span>
                                                            <span class="text-muted font-weight-bold ml-auto pl-1"
                                                                style="font-size: 0.58rem;" id="inv-legend-3">0%</span>
                                                        </div>
                                                        <div class="d-flex align-items-center text-nowrap">
                                                            <i class="fas fa-circle text-danger mr-2.5"
                                                                style="font-size: 0.42rem;"></i>
                                                            <span class="text-gray-800 font-weight-bold text-nowrap"
                                                                style="font-size: 0.58rem;" id="inv-legend-name-4">RE-Use</span>
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
                                            <!-- Flow Steps Column (4 Steps: Total Order (MR) > Total PR/PO Mover > Nilai PO Mover > Saving) -->
                                            <div class="col-xl-7 col-lg-7 col-md-12 mb-3 mb-lg-0 pr-xl-3">
                                                <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                    id="outbound-steps-container">
                                                    <!-- Step 1: Total Order (MR) -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        style="cursor: pointer;" title="Total Order (MR)">
                                                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                            <i class="fas fa-file-alt text-primary"
                                                                style="font-size: 0.95rem;"></i>
                                                        </div>
                                                        <div class="font-weight-bold text-gray-700 text-nowrap mt-3 mb-1"
                                                            style="font-size: 0.62rem;">Total Order (MR)</div>
                                                        <div class="font-weight-bold text-primary"
                                                            style="font-size: 0.82rem;" id="outbound-total-mr">0 Order
                                                        </div>
                                                    </div>

                                                    <!-- Arrow 1 -->
                                                    <div class="text-gray-300 align-self-center mx-1"
                                                        style="font-size: 0.7rem;"><i class="fas fa-chevron-right"></i>
                                                    </div>

                                                    <!-- Step 2: Total PR/PO Mover -->
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        style="cursor: pointer;" title="Total PR/PO Mover">
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
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        style="cursor: pointer;" title="Nilai PO Mover">
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
                                                    <div class="flow-step text-center flex-fill px-1 py-1"
                                                        style="cursor: pointer;" title="Saving">
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

                                            <!-- KPI Cards Column (Right Side: TOTAL ORDER, TERKIRIM, Fulfill, Packing, Shipped) -->
                                            <div class="col-xl-5 col-lg-5 col-md-12 border-left pl-xl-3 my-auto">
                                                <div class="bg-light rounded p-2 mb-2 border">
                                                    <div class="row align-items-center text-center">
                                                        <div class="col-6 border-right">
                                                            <div class="text-uppercase text-muted font-weight-bold text-nowrap"
                                                                style="font-size: 0.6rem;">TOTAL ORDER</div>
                                                            <div class="font-weight-bold text-gray-800"
                                                                style="font-size: 1.1rem;" id="outbound-total-order">0
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="text-uppercase text-success font-weight-bold text-nowrap"
                                                                style="font-size: 0.6rem;">TERKIRIM</div>
                                                            <div class="font-weight-bold text-success"
                                                                style="font-size: 1.1rem;" id="outbound-terkirim">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-1 text-nowrap">
                                                            <span class="font-weight-bold text-muted text-nowrap"
                                                                style="font-size: 0.62rem;">Progress Terkirim</span>
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
                                                        <div class="py-1 rounded border bg-white">
                                                            <i class="fas fa-box-open text-primary"
                                                                style="font-size: 0.72rem;"></i>
                                                            <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                style="font-size: 0.58rem;">Fulfill</div>
                                                            <div class="font-weight-bold text-primary"
                                                                style="font-size: 0.78rem;" id="sub-fulfill-count">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 px-1">
                                                        <div class="py-1 rounded border bg-white">
                                                            <i class="fas fa-dolly text-warning"
                                                                style="font-size: 0.72rem;"></i>
                                                            <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                style="font-size: 0.58rem;">Packing</div>
                                                            <div class="font-weight-bold text-warning"
                                                                style="font-size: 0.78rem;" id="sub-packing-count">0
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 pl-1 pr-2">
                                                        <div class="py-1 rounded border bg-white">
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
                                                <div class="py-1.5 rounded border bg-light">
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
                                                <div class="py-1.5 rounded border bg-light">
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
                                                <div class="py-1.5 rounded border bg-light">
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
                                            style="font-size: 0.82rem;"><i
                                                class="fas fa-chart-line mr-2"></i>Trends Perangkat (IN & OUT)</span>
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

                    // Keep the dropdown open when clicking inside the selects/options
                    var periodMenu = document.getElementById('period-dropdown-menu');
                    if (periodMenu) {
                        periodMenu.addEventListener('click', function (e) {
                            e.stopPropagation();
                        });
                    }

                    // Click handler for Storage Summary flow steps on dashboard overview
                    window.openStorageSummaryModal = function(stepIndex) {
                        if (!window.currentDashboardData || window.currentDashboardData.length === 0) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Pilih Periode Data',
                                    text: 'Silakan pilih periode data terlebih dahulu.'
                                });
                            } else {
                                alert('Silakan pilih periode data terlebih dahulu.');
                            }
                            return;
                        }

                        var sheetData = window.currentDashboardData;
                        var headers = window.currentDashboardHeaders || Object.keys(sheetData[0] || {});
                        var rangeCol = FormulaController.findBestColumn(headers, ['range', 'RANGE', 'aging_range', 'AGING_RANGE'], ['range', 'aging', 'usia', 'umur']);
                        var catCol = FormulaController.findBestColumn(headers, ['category', 'CATEGORY', 'kategori', 'KATEGORI'], ['category', 'kategori', 'status']);

                        var filteredRecords = [];
                        var label = '';

                        if (stepIndex === 0) {
                            label = 'Total Perangkat';
                            filteredRecords = sheetData;
                        } else if (stepIndex === 1) {
                            label = '< 1 Tahun';
                            filteredRecords = sheetData.filter(function(row) {
                                var rVal = rangeCol ? String(row[rangeCol] || '').trim().toLowerCase() : '';
                                return (rVal.indexOf('<1') !== -1 || rVal.indexOf('< 1') !== -1 || rVal.indexOf('< 3') !== -1 || rVal.indexOf('<3') !== -1 || rVal.indexOf('< 1 tahun') !== -1 || rVal.indexOf('<1 tahun') !== -1 || rVal.indexOf('<') !== -1);
                            });
                        } else if (stepIndex === 2) {
                            label = '> 1 Tahun';
                            filteredRecords = sheetData.filter(function(row) {
                                var rVal = rangeCol ? String(row[rangeCol] || '').trim().toLowerCase() : '';
                                // Exclude > 2 Tahun items from > 1 Tahun
                                if (rVal.indexOf('>2') !== -1 || rVal.indexOf('> 2') !== -1 || rVal.indexOf('2 - 3') !== -1 || rVal.indexOf('2-3') !== -1) return false;
                                return (rVal.indexOf('>1') !== -1 || rVal.indexOf('> 1') !== -1 || rVal.indexOf('1-2') !== -1 || rVal.indexOf('1 - 2') !== -1 || rVal.indexOf('3-12') !== -1 || rVal.indexOf('3 - 12') !== -1 || rVal.indexOf('1 tahun') !== -1);
                            });
                        } else if (stepIndex === 3) {
                            label = '> 2 Tahun';
                            filteredRecords = sheetData.filter(function(row) {
                                var rVal = rangeCol ? String(row[rangeCol] || '').trim().toLowerCase() : '';
                                return (rVal.indexOf('>2') !== -1 || rVal.indexOf('> 2') !== -1 || rVal.indexOf('2 - 3') !== -1 || rVal.indexOf('2-3') !== -1 || rVal.indexOf('> 2 tahun') !== -1 || rVal.indexOf('>2 tahun') !== -1 || rVal.indexOf('>') !== -1);
                            });
                        } else if (stepIndex === 4) {
                            label = 'RE-Use';
                            filteredRecords = sheetData.filter(function(row) {
                                var cVal = catCol ? String(row[catCol] || '').trim().toLowerCase() : '';
                                return (cVal.indexOf('re-use') !== -1 || cVal.indexOf('reuse') !== -1 || cVal.indexOf('need to utilize') !== -1 || cVal.indexOf('slow moving') !== -1);
                            });
                        }

                        if (window.FormulaController) {
                            window.FormulaController.openDetailModal('STORAGE SUMMARY', label, filteredRecords);
                        }
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