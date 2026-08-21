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

                    <!-- Row 2: Inbound Summary Group Card -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0">
                                <div class="card-body py-3 px-3">
                                    <!-- Group Card Header -->
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-file-alt mr-2"></i>Inbound
                                            Summary</span>
                                        <span class="badge badge-primary font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Inbound Management</span>
                                    </div>

                                    <!-- Group Card Body: 2 Inner Cards (Inbound Flow & Inbound Chart) -->
                                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                                        <!-- Inner Card 1: Inbound Flow -->
                                        <div class="col-xl-8 col-lg-7 col-md-12 mb-3 mb-lg-0"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                            id="inbound-steps-container">
                                                            <!-- Step 1: Total PO Inbound -->
                                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                                onclick="openInboundSummaryModal(0)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Inbound">
                                                                <div class="rounded-circle mx-auto mb-1.5 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 36px; height: 36px; background-color: #eef2ff;">
                                                                    <i class="fas fa-file-invoice text-primary"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total PO Inbound</div>
                                                                <div class="font-weight-bold text-primary"
                                                                    style="font-size: 0.82rem;" id="flow-po-count">0 PO
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 1 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i></div>

                                                            <!-- Step 2: Total PO Ontime -->
                                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                                onclick="openInboundSummaryModal(1)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Ontime">
                                                                <div class="rounded-circle mx-auto mb-1.5 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 36px; height: 36px; background-color: #ecfdf5;">
                                                                    <i class="fas fa-check-circle text-success"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total PO Ontime</div>
                                                                <div class="font-weight-bold text-success"
                                                                    style="font-size: 0.82rem;"
                                                                    id="flow-po-proses-delivery">0 PO</div>
                                                            </div>

                                                            <!-- Arrow 2 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i></div>

                                                            <!-- Step 3: Total PO Terlambat -->
                                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                                onclick="openInboundSummaryModal(2)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Terlambat">
                                                                <div class="rounded-circle mx-auto mb-1.5 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 36px; height: 36px; background-color: #fee2e2;">
                                                                    <i class="fas fa-exclamation-triangle text-danger"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total PO Terlambat</div>
                                                                <div class="font-weight-bold text-danger"
                                                                    style="font-size: 0.82rem;"
                                                                    id="flow-po-terlambat-delivery">0 PO</div>
                                                            </div>

                                                            <!-- Vertical Divider -->
                                                            <div class="border-left align-self-stretch mx-2 my-1"
                                                                style="height: auto; min-height: 60px;">
                                                            </div>

                                                            <!-- Step 4: Total Penerimaan (GR) -->
                                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                                onclick="openInboundSummaryModal(3)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total Penerimaan (GR)">
                                                                <div class="rounded-circle mx-auto mb-1.5 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 36px; height: 36px; background-color: #fef3c7;">
                                                                    <i class="fas fa-dolly-flatbed text-warning"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total Penerimaan (GR)
                                                                </div>
                                                                <div class="font-weight-bold text-warning"
                                                                    style="font-size: 0.82rem;" id="flow-total-gr">0 GR
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 4 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i></div>

                                                            <!-- Step 5: Total Registrasi -->
                                                            <div class="flow-step text-center flex-fill px-1 py-1 outbound-summary-clickable"
                                                                onclick="openInboundSummaryModal(4)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total Registrasi">
                                                                <div class="rounded-circle mx-auto mb-1.5 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 36px; height: 36px; background-color: #f1f5f9;">
                                                                    <i class="fas fa-check-circle text-dark"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total Registrasi</div>
                                                                <div class="font-weight-bold text-dark"
                                                                    style="font-size: 0.82rem;" id="flow-done-count">0
                                                                    Unit</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Inner Card 2: Inbound Chart & Breakdown -->
                                        <div class="col-xl-4 col-lg-5 col-md-12"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <!-- Centered Doughnut Chart -->
                                                        <div style="position: relative; height: 110px; width: 100%;"
                                                            class="mb-2">
                                                            <canvas id="dashInboundFlowPieChart"></canvas>
                                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;"
                                                                id="inbound-pie-center-text">
                                                                <div class="text-uppercase text-muted font-weight-bold"
                                                                    style="font-size: 0.48rem; line-height: 1;">TOTAL PO
                                                                </div>
                                                                <div class="font-weight-bold text-gray-800"
                                                                    style="font-size: 0.85rem; line-height: 1.1;"
                                                                    id="inbound-pie-total-val">0 PO</div>
                                                                <div class="text-xs text-primary font-weight-bold"
                                                                    id="inbound-pie-pct-val"
                                                                    style="font-size: 0.55rem; line-height: 1;">100%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Labels Below the Chart (Horizontal Grid) -->
                                                        <div
                                                            class="d-flex align-items-center justify-content-between text-center mb-2 px-0.5">
                                                            <!-- Total PO -->
                                                            <div class="filter-legend-item outbound-summary-clickable px-1 flex-fill"
                                                                id="inbound-flow-legend-0"
                                                                onclick="openInboundSummaryModal(0)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Inbound">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-center mb-0.5">
                                                                    <i class="fas fa-circle mr-1"
                                                                        style="font-size: 0.38rem; color: #4e73df;"></i>
                                                                    <span
                                                                        class="text-gray-800 font-weight-bold text-nowrap"
                                                                        style="font-size: 0.58rem;">Total PO</span>
                                                                </div>
                                                                <span
                                                                    class="badge badge-light border text-primary px-1 py-0.2 font-weight-bold"
                                                                    id="legend-po-total-badge"
                                                                    style="font-size: 0.58rem;">0 (100%)</span>
                                                            </div>

                                                            <!-- Ontime -->
                                                            <div class="filter-legend-item outbound-summary-clickable px-1 flex-fill"
                                                                id="inbound-flow-legend-1"
                                                                onclick="openInboundSummaryModal(1)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Ontime">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-center mb-0.5">
                                                                    <i class="fas fa-circle mr-1"
                                                                        style="font-size: 0.38rem; color: #1cc88a;"></i>
                                                                    <span
                                                                        class="text-gray-800 font-weight-bold text-nowrap"
                                                                        style="font-size: 0.58rem;">Ontime</span>
                                                                </div>
                                                                <span
                                                                    class="badge badge-light border text-success px-1 py-0.2 font-weight-bold"
                                                                    id="legend-po-ontime-badge"
                                                                    style="font-size: 0.58rem;">0 (0%)</span>
                                                            </div>

                                                            <!-- Terlambat -->
                                                            <div class="filter-legend-item outbound-summary-clickable px-1 flex-fill"
                                                                id="inbound-flow-legend-2"
                                                                onclick="openInboundSummaryModal(2)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Terlambat">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-center mb-0.5">
                                                                    <i class="fas fa-circle mr-1"
                                                                        style="font-size: 0.38rem; color: #e74a3b;"></i>
                                                                    <span
                                                                        class="text-gray-800 font-weight-bold text-nowrap"
                                                                        style="font-size: 0.58rem;">Terlambat</span>
                                                                </div>
                                                                <span
                                                                    class="badge badge-light border text-danger px-1 py-0.2 font-weight-bold"
                                                                    id="legend-po-terlambat-badge"
                                                                    style="font-size: 0.58rem;">0 (0%)</span>
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

                    <!-- Row 3: Storage Summary Group Card -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0">
                                <div class="card-body py-3 px-3">
                                    <!-- Group Card Header -->
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-boxes mr-2"></i>Storage
                                            Summary</span>
                                        <span class="badge badge-info font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Storage Management</span>
                                    </div>

                                    <!-- Group Card Body: 2 Inner Cards (Storage Flow & Storage Chart) -->
                                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                                        <!-- Inner Card 1: Storage Flow -->
                                        <div class="col-xl-8 col-lg-7 col-md-12 mb-3 mb-lg-0"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <div class="d-flex align-items-center justify-content-around text-center flex-nowrap w-100"
                                                            id="storage-steps-container" style="gap: 16px;">
                                                            <!-- Step 1: Total Perangkat (qty) Box -->
                                                            <div class="flow-step text-center flex-fill py-3 px-3 rounded border bg-white shadow-sm outbound-summary-clickable"
                                                                onclick="openStorageSummaryModal(0)"
                                                                style="cursor: pointer; transition: all 0.2s ease-in-out;"
                                                                title="Klik untuk detail Total Perangkat (qty)">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 42px; height: 42px; background-color: #eef2ff;">
                                                                    <i class="fas fa-cubes text-primary"
                                                                        style="font-size: 1.1rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.70rem;">Total Perangkat</div>
                                                                <div class="font-weight-bold text-primary"
                                                                    style="font-size: 1.05rem;"
                                                                    id="inv-total-perangkat">0 Unit
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 1 -->
                                                            <div class="text-gray-400 align-self-center px-1"
                                                                style="font-size: 1.1rem;"><i
                                                                    class="fas fa-chevron-right"></i>
                                                            </div>

                                                            <!-- Step 2: Total NBV (Rp) Box -->
                                                            <div class="flow-step text-center flex-fill py-3 px-3 rounded border bg-white shadow-sm outbound-summary-clickable"
                                                                onclick="openStorageSummaryModal(1)"
                                                                style="cursor: pointer; transition: all 0.2s ease-in-out;"
                                                                title="Klik untuk detail Total NBV (Rp)">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 42px; height: 42px; background-color: #ecfdf5;">
                                                                    <i class="fas fa-file-invoice-dollar text-success"
                                                                        style="font-size: 1.1rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.70rem;">Total NBV</div>
                                                                <div class="font-weight-bold text-success"
                                                                    style="font-size: 1.05rem;" id="inv-total-nbv">Rp 0
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Inner Card 2: Storage Chart & Breakdown -->
                                        <div class="col-xl-4 col-lg-5 col-md-12"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body py-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <!-- Centered Doughnut Chart -->
                                                        <div style="position: relative; height: 110px; width: 100%;"
                                                            class="mb-2">
                                                            <canvas id="dashInventorySummaryPieChart"></canvas>
                                                        </div>

                                                        <!-- Labels Below the Chart (Horizontal Grid of 2 Items) -->
                                                        <div class="d-flex align-items-center justify-content-between text-center mb-2 px-0.5"
                                                            id="dash-inv-pie-legend">
                                                            <!-- Item 1: Total Perangkat -->
                                                            <div class="filter-legend-item outbound-summary-clickable px-1 flex-fill"
                                                                id="storage-legend-item-1"
                                                                onclick="openStorageSummaryModal(0)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total Perangkat (qty)">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-center mb-0.5">
                                                                    <i class="fas fa-circle mr-1"
                                                                        style="font-size: 0.38rem; color: #4e73df;"></i>
                                                                    <span
                                                                        class="text-gray-800 font-weight-bold text-nowrap"
                                                                        style="font-size: 0.58rem;"
                                                                        id="inv-legend-name-1">Total Perangkat</span>
                                                                </div>
                                                                <span
                                                                    class="badge badge-light border text-primary px-1 py-0.2 font-weight-bold"
                                                                    id="inv-legend-1" style="font-size: 0.58rem;">0
                                                                    Unit</span>
                                                            </div>

                                                            <!-- Item 2: Total NBV -->
                                                            <div class="filter-legend-item outbound-summary-clickable px-1 flex-fill"
                                                                id="storage-legend-item-2"
                                                                onclick="openStorageSummaryModal(1)"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total NBV (Rp)">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-center mb-0.5">
                                                                    <i class="fas fa-circle mr-1"
                                                                        style="font-size: 0.38rem; color: #1cc88a;"></i>
                                                                    <span
                                                                        class="text-gray-800 font-weight-bold text-nowrap"
                                                                        style="font-size: 0.58rem;"
                                                                        id="inv-legend-name-2">Total NBV</span>
                                                                </div>
                                                                <span
                                                                    class="badge badge-light border text-success px-1 py-0.2 font-weight-bold"
                                                                    id="inv-legend-2" style="font-size: 0.58rem;">Rp
                                                                    0</span>
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

                    <!-- Row 4: Storage Utilization & Receiving Trend -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">

                        <!-- Card 1: Storage Utilization -->
                        <div class="col-xl-5 col-lg-5 col-md-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-warehouse mr-2"></i>Storage
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
                                                        style="font-size: 0.78rem;" id="storage-total-capacity">100%
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4 px-1">
                                                <div class="py-1.5 rounded border bg-light status-card-clickable"
                                                    onclick="openStorageUtilizationModal('used')"
                                                    style="cursor: pointer; transition: all 0.2s ease;"
                                                    title="Klik untuk melihat detail Total Penggunaan">
                                                    <i class="fas fa-boxes text-warning"
                                                        style="font-size: 0.72rem;"></i>
                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap mt-1"
                                                        style="font-size: 0.55rem;">Total Penggunaan</div>
                                                    <div class="font-weight-bold text-warning"
                                                        style="font-size: 0.78rem;" id="storage-used">0%</div>
                                                </div>
                                            </div>
                                            <div class="col-4 pl-1">
                                                <div class="py-1.5 rounded border bg-light status-card-clickable"
                                                    onclick="openStorageUtilizationModal('available')"
                                                    style="cursor: pointer; transition: all 0.2s ease;"
                                                    title="Klik untuk melihat detail Total Tersedia">
                                                    <i class="fas fa-cube text-success" style="font-size: 0.72rem;"></i>
                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap mt-1"
                                                        style="font-size: 0.55rem;">Total Tersedia</div>
                                                    <div class="font-weight-bold text-success"
                                                        style="font-size: 0.78rem;" id="storage-available">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Trends (Perangkat IN & Perangkat OUT from Storage Tekno) -->
                        <div class="col-xl-7 col-lg-7 col-md-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body py-3 px-3 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i class="fas fa-chart-line mr-2"></i>Trends
                                            Perangkat (IN & OUT)</span>
                                    </div>
                                    <div class="my-auto" style="position: relative; height: 145px;">
                                        <canvas id="dashReceivingTrendLineChart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Row 5: Outbound Summary Group Card -->
                    <div class="row mt-2" style="margin-left: -4px; margin-right: -4px;">
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow border-0">
                                <div class="card-body py-3 px-3">
                                    <!-- Group Card Header -->
                                    <div class="d-flex align-items-center justify-content-between mb-3 text-nowrap">
                                        <span class="font-weight-bold text-primary text-nowrap"
                                            style="font-size: 0.85rem;"><i
                                                class="fas fa-shipping-fast mr-2"></i>Outbound Summary</span>
                                        <span class="badge badge-success font-weight-normal text-nowrap px-2.5 py-1"
                                            style="font-size: 0.65rem;">Outbound Management</span>
                                    </div>

                                    <!-- Group Card Body: 2 Inner Segment Cards (Outbound Flow & Outbound Status Breakdown) -->
                                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                                        <!-- Inner Card 1: Outbound Flow -->
                                        <div class="col-xl-7 col-lg-7 col-md-12 mb-3 mb-lg-0"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body pt-4 pb-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <div class="d-flex align-items-center justify-content-between text-center flex-nowrap w-100"
                                                            id="outbound-steps-container">
                                                            <!-- Step 1: Total Material Request (MR) -->
                                                            <div class="flow-step text-center flex-fill px-1 py-2 outbound-summary-clickable"
                                                                onclick="openOutboundSummaryModal('total_mr')"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total Material Request (MR)">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 38px; height: 38px; background-color: #eef2ff;">
                                                                    <i class="fas fa-file-alt text-primary"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total Material Request
                                                                    (MR)</div>
                                                                <div class="font-weight-bold text-primary"
                                                                    style="font-size: 0.82rem;" id="outbound-total-mr">0
                                                                    MR
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 1 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i>
                                                            </div>

                                                            <!-- Step 2: Total PO Mover (qty) -->
                                                            <div class="flow-step text-center flex-fill px-1 py-2 outbound-summary-clickable"
                                                                onclick="openOutboundSummaryModal('pr_po_mover')"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total PO Mover (qty)">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 38px; height: 38px; background-color: #e0f2fe;">
                                                                    <i class="fas fa-exchange-alt text-info"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total PO Mover (qty)
                                                                </div>
                                                                <div class="font-weight-bold text-info"
                                                                    style="font-size: 0.82rem;"
                                                                    id="outbound-total-mover">0 PO
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 2 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i>
                                                            </div>

                                                            <!-- Step 3: Total Value PO Mover -->
                                                            <div class="flow-step text-center flex-fill px-1 py-2 outbound-summary-clickable"
                                                                onclick="openOutboundSummaryModal('nilai_po_mover')"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Total Value PO Mover">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 38px; height: 38px; background-color: #ecfdf5;">
                                                                    <i class="fas fa-file-invoice-dollar text-success"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Total Value PO Mover
                                                                </div>
                                                                <div class="font-weight-bold text-success"
                                                                    style="font-size: 0.82rem;"
                                                                    id="outbound-nilai-mover">Rp 0
                                                                </div>
                                                            </div>

                                                            <!-- Arrow 3 -->
                                                            <div class="text-gray-300 align-self-center mx-1"
                                                                style="font-size: 0.7rem;"><i
                                                                    class="fas fa-chevron-right"></i>
                                                            </div>

                                                            <!-- Step 4: Efisiensi -->
                                                            <div class="flow-step text-center flex-fill px-1 py-2 outbound-summary-clickable"
                                                                onclick="openOutboundSummaryModal('saving')"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Efisiensi">
                                                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 38px; height: 38px; background-color: #fef3c7;">
                                                                    <i class="fas fa-dollar-sign text-warning"
                                                                        style="font-size: 0.95rem;"></i>
                                                                </div>
                                                                <div class="font-weight-bold text-gray-700 text-nowrap mt-2 mb-1"
                                                                    style="font-size: 0.62rem;">Efisiensi</div>
                                                                <div class="font-weight-bold text-warning"
                                                                    style="font-size: 0.82rem;" id="outbound-saving">Rp
                                                                    0</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Inner Card 2: Outbound Status Breakdown -->
                                        <div class="col-xl-5 col-lg-5 col-md-12"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card border bg-white h-100 shadow-none">
                                                <div
                                                    class="card-body pt-4 pb-3 px-3 d-flex flex-column justify-content-center">
                                                    <div class="w-100 my-auto">
                                                        <div class="bg-light rounded p-2 mb-2 border">
                                                            <div class="row align-items-center text-center">
                                                                <div class="col-12 outbound-summary-clickable"
                                                                    onclick="openOutboundSummaryModal('mr_closed')"
                                                                    style="cursor: pointer;"
                                                                    title="Klik untuk detail MR Closed">
                                                                    <div class="text-uppercase text-success font-weight-bold text-nowrap"
                                                                        style="font-size: 0.65rem;">MR CLOSED</div>
                                                                    <div class="font-weight-bold text-success"
                                                                        style="font-size: 1.25rem;"
                                                                        id="outbound-terkirim">0
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-1 outbound-summary-clickable"
                                                                onclick="openOutboundSummaryModal('mr_closed')"
                                                                style="cursor: pointer;"
                                                                title="Klik untuk detail Progress MR Closed">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center mb-1 text-nowrap">
                                                                    <span
                                                                        class="font-weight-bold text-muted text-nowrap"
                                                                        style="font-size: 0.62rem;">Progress MR
                                                                        Closed</span>
                                                                    <span
                                                                        class="font-weight-bold text-success text-nowrap ml-auto pl-1"
                                                                        style="font-size: 0.62rem;"
                                                                        id="outbound-progress-percent">0%</span>
                                                                </div>
                                                                <div class="progress rounded-pill" style="height: 4px;">
                                                                    <div class="progress-bar bg-success"
                                                                        role="progressbar" id="outbound-progress-bar"
                                                                        style="width: 0%"></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row text-center">
                                                            <div class="col-4 pr-1">
                                                                <div class="py-1 rounded border bg-light outbound-summary-clickable"
                                                                    onclick="openOutboundSummaryModal('fulfill')"
                                                                    style="cursor: pointer;"
                                                                    title="Klik untuk detail Fulfilled">
                                                                    <i class="fas fa-box-open text-primary"
                                                                        style="font-size: 0.72rem;"></i>
                                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                        style="font-size: 0.58rem;">Fulfilled</div>
                                                                    <div class="font-weight-bold text-primary"
                                                                        style="font-size: 0.78rem;"
                                                                        id="sub-fulfill-count">0
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-4 px-1">
                                                                <div class="py-1 rounded border bg-light outbound-summary-clickable"
                                                                    onclick="openOutboundSummaryModal('packing')"
                                                                    style="cursor: pointer;"
                                                                    title="Klik untuk detail Packing">
                                                                    <i class="fas fa-dolly text-warning"
                                                                        style="font-size: 0.72rem;"></i>
                                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                        style="font-size: 0.58rem;">Packed</div>
                                                                    <div class="font-weight-bold text-warning"
                                                                        style="font-size: 0.78rem;"
                                                                        id="sub-packing-count">0
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-4 pl-1">
                                                                <div class="py-1 rounded border bg-light outbound-summary-clickable"
                                                                    onclick="openOutboundSummaryModal('shipped')"
                                                                    style="cursor: pointer;"
                                                                    title="Klik untuk detail Shipped">
                                                                    <i class="fas fa-truck text-success"
                                                                        style="font-size: 0.72rem;"></i>
                                                                    <div class="font-weight-bold text-muted text-uppercase text-nowrap"
                                                                        style="font-size: 0.58rem;">Shipped</div>
                                                                    <div class="font-weight-bold text-success"
                                                                        style="font-size: 0.78rem;"
                                                                        id="sub-shipped-count">0
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
                        </div>
                    </div>

                    <!-- Inbound Status Detail Modal (Matching Inbound Management) -->
                    <div class="modal fade" id="poStatusDetailModal" tabindex="-1" role="dialog"
                        aria-labelledby="poStatusDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                            <div class="modal-content border-0 shadow"
                                style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                                <div class="modal-header border-bottom py-3 px-4 align-items-center"
                                    style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                                    <h5 class="modal-title font-weight-bold text-gray-800 my-auto d-flex align-items-center flex-wrap"
                                        id="poStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                        <span>Detail Status PO:</span>
                                        <span id="modalInboundStatusTitleText"
                                            class="font-weight-bold text-primary ml-1"></span>
                                        <span class="badge badge-primary px-2.5 py-1 font-weight-bold ml-2"
                                            id="inbound-modal-total-badge"
                                            style="font-size: 0.8rem; border-radius: 6px;">
                                            <i class="fas fa-file-invoice mr-1"></i> Total: <span
                                                id="inbound-modal-count-display">0</span> Data
                                        </span>
                                    </h5>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                                    <div class="card mb-3 border bg-light shadow-sm"
                                        style="border-radius: 8px; border-color: #eaecf4 !important;">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex flex-wrap align-items-center w-100"
                                                id="modalInboundDynamicFilterRow" style="gap: 8px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive border rounded shadow-sm"
                                        style="border-color: #eaecf4 !important; background: #ffffff;">
                                        <table class="table table-hover table-striped text-center mb-0 w-100"
                                            id="tableInboundPoStatusDetail" style="font-size: 0.84rem;">
                                            <thead class="thead-light text-gray-800 font-weight-bold"
                                                id="tableInboundPoStatusDetailHead"
                                                style="border-bottom: 2px solid #e3e6f0;">
                                                <tr>
                                                    <th class="py-2 border-top-0">No. PO</th>
                                                    <th class="py-2 border-top-0">Deskripsi PO</th>
                                                    <th class="py-2 border-top-0">PIC Asset Planner</th>
                                                    <th class="py-2 border-top-0">Department</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableInboundPoStatusDetailBody">
                                                <tr>
                                                    <td colspan="4" class="py-5 text-muted bg-white">
                                                        <i
                                                            class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
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

                    <!-- Outbound MR Status Detail Modal (Matching Outbound Management) -->
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
                                        <span id="modalOutboundStatusTitleText"
                                            class="font-weight-bold text-primary ml-1"></span>
                                        <span class="badge badge-danger px-2.5 py-1 font-weight-bold ml-2 d-none"
                                            id="shipped-modal-total-badge"
                                            style="font-size: 0.8rem; border-radius: 6px;">
                                            <i class="fas fa-boxes mr-1"></i> Total: <span
                                                id="shipped-total-qty">0</span> QTY
                                        </span>
                                        <span class="badge badge-primary px-2.5 py-1 font-weight-bold ml-2"
                                            id="outbound-modal-total-badge"
                                            style="font-size: 0.8rem; border-radius: 6px;">
                                            <i class="fas fa-shipping-fast mr-1"></i> Total: <span
                                                id="outbound-modal-count-display">0</span> Data
                                        </span>
                                    </h5>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                                    <div class="card mb-3 border bg-light shadow-sm"
                                        style="border-radius: 8px; border-color: #eaecf4 !important;">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex flex-wrap align-items-center w-100"
                                                id="modalOutboundDynamicFilterRow" style="gap: 8px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive border rounded shadow-sm"
                                        style="border-color: #eaecf4 !important; background: #ffffff;">
                                        <table class="table table-hover table-striped text-center mb-0 w-100"
                                            id="tableOutboundMrStatusDetail" style="font-size: 0.84rem;">
                                            <thead class="thead-light text-gray-800 font-weight-bold"
                                                id="tableOutboundMrStatusDetailHead"
                                                style="border-bottom: 2px solid #e3e6f0;">
                                                <tr>
                                                    <th class="py-2 border-top-0">No. MR</th>
                                                    <th class="py-2 border-top-0">User</th>
                                                    <th class="py-2 border-top-0">Tujuan</th>
                                                    <th class="py-2 border-top-0">Pickup By</th>
                                                    <th class="py-2 border-top-0">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableOutboundMrStatusDetailBody">
                                                <tr>
                                                    <td colspan="5" class="py-5 text-muted bg-white">
                                                        <i
                                                            class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
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

                    <!-- Storage Asset Summary Detail Modal (Matching Storage Management) -->
                    <div class="modal fade" id="storageAssetDetailModal" tabindex="-1" role="dialog"
                        aria-labelledby="storageAssetDetailModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                            <div class="modal-content border-0 shadow"
                                style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                                <div class="modal-header border-bottom py-3 px-4 align-items-center"
                                    style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                                    <h5 class="modal-title font-weight-bold text-gray-800 my-auto d-flex align-items-center flex-wrap"
                                        id="storageAssetDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                        <span>Detail Storage:</span>
                                        <span id="modalStorageStatusTitleText"
                                            class="font-weight-bold text-primary ml-1"></span>
                                        <span class="badge badge-primary px-2.5 py-1 font-weight-bold ml-2"
                                            id="storage-modal-total-badge"
                                            style="font-size: 0.8rem; border-radius: 6px;">
                                            <i class="fas fa-cubes mr-1"></i> Total: <span
                                                id="storage-modal-count-display">0</span> Data
                                        </span>
                                    </h5>
                                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                        aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                                    <div class="card mb-3 border bg-light shadow-sm"
                                        style="border-radius: 8px; border-color: #eaecf4 !important;">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex flex-wrap align-items-center w-100"
                                                id="modalStorageDynamicFilterRow" style="gap: 8px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive border rounded shadow-sm"
                                        style="border-color: #eaecf4 !important; background: #ffffff;">
                                        <table class="table table-hover table-striped text-center mb-0 w-100"
                                            id="tableStorageAssetDetail" style="font-size: 0.84rem;">
                                            <thead class="thead-light text-gray-800 font-weight-bold"
                                                id="tableStorageAssetDetailHead"
                                                style="border-bottom: 2px solid #e3e6f0;">
                                                <tr>
                                                    <th class="py-2 border-top-0">Serial Number</th>
                                                    <th class="py-2 border-top-0">Deskripsi Item</th>
                                                    <th class="py-2 border-top-0">Kategori</th>
                                                    <th class="py-2 border-top-0">Site / Lokasi</th>
                                                    <th class="py-2 border-top-0">Aging</th>
                                                    <th class="py-2 border-top-0">Nilai Buku (NBV)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableStorageAssetDetailBody">
                                                <tr>
                                                    <td colspan="6" class="py-5 text-muted bg-white">
                                                        <i
                                                            class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
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
                                                    id="searchModalUtilisasiInput" placeholder="Search..."
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

                        // Current active period on dashboard
                        var currentSelectedPeriod = '';

                        // ─── 1. Inbound Summary Modal System (Matching Inbound Module) ───
                        var inboundStatusColumnConfig = {
                            'TOTAL PO INBOUND': {
                                headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Tgl Generate', 'Target Delivery'],
                                keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'tgl_generate', 'target_delivery']
                            },
                            'PO ONTIME DELIVERY': {
                                headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Target Delivery'],
                                keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'target_delivery']
                            },
                            'PO TERLAMBAT DELIVERY': {
                                headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Target Delivery'],
                                keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'target_delivery']
                            },
                            'PO SUDAH GR': {
                                headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor / PIC GR', 'Qty', 'Target Delivery'],
                                keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_gr', 'qty', 'target_delivery']
                            },
                            'PO SUDAH REGISTRASI': {
                                headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC Registrasi', 'Qty', 'Target Delivery'],
                                keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_registrasi', 'qty', 'target_delivery']
                            }
                        };

                        var inboundStatusFilterMap = {
                            'TOTAL PO INBOUND': [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
                                { key: 'vendor', label: 'Vendor' }
                            ],
                            'PO ONTIME DELIVERY': [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
                                { key: 'vendor', label: 'Vendor' }
                            ],
                            'PO TERLAMBAT DELIVERY': [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
                                { key: 'vendor', label: 'Vendor' }
                            ],
                            'PO SUDAH GR': [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
                                { key: 'pic_gr', label: 'PIC GR' }
                            ],
                            'PO SUDAH REGISTRASI': [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
                            ]
                        };

                        var currentInboundModalRows = [];
                        var currentInboundConfig = inboundStatusColumnConfig['TOTAL PO INBOUND'];
                        var currentInboundSort = { key: null, dir: 'asc' };

                        function renderInboundTableHeader() {
                            var thead = $('#tableInboundPoStatusDetailHead');
                            var tr = $('<tr></tr>');
                            currentInboundConfig.headers.forEach(function (h, idx) {
                                var key = currentInboundConfig.keys[idx];
                                var sortIcon = '<i class="fas fa-sort text-gray-400 ml-1" style="font-size: 0.72rem;"></i>';
                                if (currentInboundSort.key === key) {
                                    sortIcon = currentInboundSort.dir === 'asc'
                                        ? '<i class="fas fa-sort-up text-primary ml-1" style="font-size: 0.78rem;"></i>'
                                        : '<i class="fas fa-sort-down text-primary ml-1" style="font-size: 0.78rem;"></i>';
                                }
                                var th = $('<th class="py-2 border-top-0 sortable-inbound-header user-select-none" style="cursor: pointer;" data-key="' + key + '">' + h + ' ' + sortIcon + '</th>');
                                tr.append(th);
                            });
                            thead.html(tr);
                        }

                        function renderInboundModalRows(rows) {
                            var tbody = $('#tableInboundPoStatusDetailBody');
                            var colCount = currentInboundConfig.headers.length;
                            if (!rows || rows.length === 0) {
                                tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk filter ini.</td></tr>');
                                $('#inbound-modal-count-display').text('0');
                                return;
                            }

                            $('#inbound-modal-count-display').text(rows.length);
                            var html = '';
                            rows.forEach(function (r) {
                                html += '<tr>';
                                currentInboundConfig.keys.forEach(function (k) {
                                    var val = r[k] !== undefined && r[k] !== null && r[k] !== '' ? r[k] : '-';
                                    var alignClass = (k === 'no_po' || k === 'deskripsi_po') ? 'text-left' : 'text-center';
                                    html += '<td class="py-2 px-2 align-middle ' + alignClass + '" style="font-size: 0.82rem;">' + $('<div>').text(val).html() + '</td>';
                                });
                                html += '</tr>';
                            });
                            tbody.html(html);
                        }

                        function applyInboundModalFilters() {
                            var filterValues = {};
                            $('.dash-inbound-modal-filter').each(function () {
                                var key = $(this).attr('data-key');
                                var val = $(this).val();
                                if (val) {
                                    filterValues[key] = val.toString().trim().toLowerCase();
                                }
                            });

                            var searchTerm = ($('#filter-inbound-modal-search').val() || '').trim().toLowerCase();

                            var filtered = currentInboundModalRows.filter(function (row) {
                                for (var k in filterValues) {
                                    var cellVal = (row[k] || '').toString().trim().toLowerCase();
                                    if (cellVal !== filterValues[k]) {
                                        return false;
                                    }
                                }

                                if (searchTerm) {
                                    var matchSearch = currentInboundConfig.keys.some(function (k) {
                                        var cellVal = (row[k] || '').toString().toLowerCase();
                                        return cellVal.indexOf(searchTerm) !== -1;
                                    });
                                    if (!matchSearch) return false;
                                }

                                return true;
                            });

                            if (currentInboundSort.key) {
                                filtered.sort(function (a, b) {
                                    var valA = (a[currentInboundSort.key] || '').toString().trim();
                                    var valB = (b[currentInboundSort.key] || '').toString().trim();
                                    return currentInboundSort.dir === 'asc'
                                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });
                                });
                            }

                            renderInboundModalRows(filtered);
                        }

                        function updateInboundModalFilters(statusName, rawData) {
                            var filterRow = $('#modalInboundDynamicFilterRow');
                            var filterDefs = inboundStatusFilterMap[statusName] || [
                                { key: 'department', label: 'Department' },
                                { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
                            ];
                            var html = '';

                            filterDefs.forEach(function (f) {
                                var key = f.key;
                                var label = f.label;
                                var uniqueVals = [];
                                (rawData || []).forEach(function (r) {
                                    var v = (r[key] || '').toString().trim();
                                    if (v && v !== '-' && uniqueVals.indexOf(v) === -1) {
                                        uniqueVals.push(v);
                                    }
                                });
                                uniqueVals.sort();

                                html += '<div style="flex: 1 1 140px; max-width: 200px; min-width: 120px;">';
                                html += '<select class="form-control form-control-sm custom-select custom-select-sm dash-inbound-modal-filter" data-key="' + key + '" style="font-size: 0.78rem; border-radius: 6px;">';
                                html += '<option value="">Semua ' + label + '</option>';
                                uniqueVals.forEach(function (uv) {
                                    html += '<option value="' + $('<div>').text(uv).html() + '">' + $('<div>').text(uv).html() + '</option>';
                                });
                                html += '</select>';
                                html += '</div>';
                            });

                            html += '<div style="flex: 1 1 170px; max-width: 230px; min-width: 140px;">';
                            html += '<div class="input-group input-group-sm">';
                            html += '<div class="input-group-prepend"><span class="input-group-text bg-white border-right-0 text-muted" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search" style="font-size: 0.72rem;"></i></span></div>';
                            html += '<input type="text" class="form-control form-control-sm border-left-0" id="filter-inbound-modal-search" placeholder="Search..." style="font-size: 0.78rem; border-radius: 0 6px 6px 0;">';
                            html += '</div>';
                            html += '</div>';

                            html += '<div>';
                            html += '<button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btn-reset-inbound-modal-filter" title="Reset Filter" style="border-radius: 6px; font-size: 0.75rem; padding: 0.25rem 0.65rem;">';
                            html += '<i class="fas fa-undo mr-1"></i> Reset';
                            html += '</button>';
                            html += '</div>';

                            filterRow.html(html);

                            $('.dash-inbound-modal-filter').off('change').on('change', applyInboundModalFilters);
                            $('#filter-inbound-modal-search').off('keyup input change').on('keyup input change', applyInboundModalFilters);
                            $('#btn-reset-inbound-modal-filter').off('click').on('click', function () {
                                $('.dash-inbound-modal-filter').val('');
                                $('#filter-inbound-modal-search').val('');
                                currentInboundSort = { key: null, dir: 'asc' };
                                renderInboundTableHeader();
                                applyInboundModalFilters();
                            });
                        }

                        $('#tableInboundPoStatusDetailHead').off('click', '.sortable-inbound-header').on('click', '.sortable-inbound-header', function () {
                            var key = $(this).attr('data-key');
                            if (currentInboundSort.key === key) {
                                currentInboundSort.dir = (currentInboundSort.dir === 'asc') ? 'desc' : 'asc';
                            } else {
                                currentInboundSort.key = key;
                                currentInboundSort.dir = 'asc';
                            }
                            renderInboundTableHeader();
                            applyInboundModalFilters();
                        });

                        // Inbound Summary interactive modal handler
                        window.openInboundSummaryModal = function (stepIndex) {
                            var statusKeys = [
                                'TOTAL PO INBOUND',
                                'PO ONTIME DELIVERY',
                                'PO TERLAMBAT DELIVERY',
                                'PO SUDAH GR',
                                'PO SUDAH REGISTRASI'
                            ];
                            var statusName = statusKeys[stepIndex] || 'TOTAL PO INBOUND';
                            var cfg = inboundStatusColumnConfig[statusName] || inboundStatusColumnConfig['TOTAL PO INBOUND'];

                            currentInboundConfig = cfg;
                            currentInboundSort = { key: null, dir: 'asc' };
                            currentInboundModalRows = [];

                            $('#modalInboundStatusTitleText').text(statusName);
                            renderInboundTableHeader();

                            var tbody = $('#tableInboundPoStatusDetailBody');
                            var colCount = cfg.headers.length;

                            var pText = document.getElementById('selected-period-text');
                            var periodVal = pText ? pText.textContent.trim() : '';
                            if (periodVal === 'PILIH PERIODE DATA' || periodVal === '-') periodVal = '';

                            if (!periodVal) {
                                updateInboundModalFilters(statusName, []);
                                tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                $('#inbound-modal-count-display').text('0');
                                $('#poStatusDetailModal').modal('show');
                                return;
                            }

                            tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data detail PO inbound...</td></tr>');
                            $('#modalInboundDynamicFilterRow').empty();
                            $('#inbound-modal-count-display').text('Memuat...');

                            $.ajax({
                                url: 'api/get_inbound_status_detail.php',
                                type: 'GET',
                                data: { status: statusName, periode: periodVal },
                                dataType: 'json',
                                success: function (res) {
                                    if (res.status === 'success' && res.data && res.data.length > 0) {
                                        currentInboundModalRows = res.data;
                                        updateInboundModalFilters(statusName, res.data);
                                        renderInboundModalRows(res.data);
                                    } else {
                                        updateInboundModalFilters(statusName, []);
                                        tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                        $('#inbound-modal-count-display').text('0');
                                    }
                                },
                                error: function () {
                                    tbody.html('<tr><td colspan="' + colCount + '" class="py-4 text-center text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                                    $('#inbound-modal-count-display').text('0');
                                }
                            });

                            $('#poStatusDetailModal').modal('show');
                        };

                        // ─── 2. Outbound Summary Modal System (Matching Outbound Module) ───
                        var outboundStatusColumnConfig = {
                            'TOTAL MR': {
                                headers: ['No. MR', 'User', 'Tujuan', 'Pickup By', 'Keterangan'],
                                keys: ['no_mr', 'user', 'tujuan', 'pickup_by', 'ket']
                            },
                            'TOTAL PACKED': {
                                headers: ['No. PCK', 'PCK Detail', 'User', 'Tujuan', 'No. MR', 'No. DN'],
                                keys: ['no_pck', 'pck_detail', 'user', 'tujuan', 'no_mr', 'no_dn']
                            },
                            'TOTAL SHIPPED': {
                                headers: ['No. MR', 'No. DN', 'User', 'Tujuan', 'Pickup Type', 'Via', 'LT', 'Target Delivery', 'Last Log'],
                                keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'pickup_type', 'via', 'lt', 'delivery_target', 'last_log']
                            },
                            'DALAM PERJALANAN': {
                                headers: ['No. MR', 'No. DN', 'User', 'Tujuan', 'Status MR', 'LT', 'Status DN', 'Target Delivery', 'Last Log'],
                                keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'status_mr', 'lt', 'status_dn', 'delivery_target', 'last_log']
                            },
                            'TIBA DI LOKASI': {
                                headers: ['No. MR', 'No. DN', 'User', 'Tujuan', 'Status MR', 'LT', 'Status DN', 'Target Delivery', 'Last Log'],
                                keys: ['no_mr', 'no_dn', 'user', 'tujuan', 'status_mr', 'lt', 'status_dn', 'delivery_target', 'last_log']
                            }
                        };

                        var outboundStatusFilterMap = {
                            'TOTAL MR': [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' },
                                { key: 'pickup_by', label: 'Pickup By' }
                            ],
                            'TOTAL PACKED': [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' },
                                { key: 'pck_detail', label: 'PCK Detail' }
                            ],
                            'TOTAL SHIPPED': [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' },
                                { key: 'pickup_type', label: 'Pickup Type' },
                                { key: 'via', label: 'Via' }
                            ],
                            'DALAM PERJALANAN': [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' },
                                { key: 'status_mr', label: 'Status MR' }
                            ],
                            'TIBA DI LOKASI': [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' },
                                { key: 'status_mr', label: 'Status MR' }
                            ]
                        };

                        var currentOutboundModalRows = [];
                        var currentOutboundConfig = outboundStatusColumnConfig['TOTAL MR'];
                        var currentOutboundSort = { key: null, dir: 'asc' };

                        function renderOutboundTableHeader() {
                            var thead = $('#tableOutboundMrStatusDetailHead');
                            var tr = $('<tr></tr>');
                            currentOutboundConfig.headers.forEach(function (h, idx) {
                                var key = currentOutboundConfig.keys[idx];
                                var sortIcon = '<i class="fas fa-sort text-gray-400 ml-1" style="font-size: 0.72rem;"></i>';
                                if (currentOutboundSort.key === key) {
                                    sortIcon = currentOutboundSort.dir === 'asc'
                                        ? '<i class="fas fa-sort-up text-primary ml-1" style="font-size: 0.78rem;"></i>'
                                        : '<i class="fas fa-sort-down text-primary ml-1" style="font-size: 0.78rem;"></i>';
                                }
                                var th = $('<th class="py-2.5 px-3 border-top-0 text-left sortable-outbound-header user-select-none" style="cursor: pointer;" data-key="' + key + '">' + h + ' ' + sortIcon + '</th>');
                                tr.append(th);
                            });
                            thead.html(tr);
                        }

                        function renderOutboundModalRows(rows) {
                            var tbody = $('#tableOutboundMrStatusDetailBody');
                            var colCount = currentOutboundConfig.headers.length;
                            if (!rows || rows.length === 0) {
                                tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Tidak ada data yang sesuai filter.</td></tr>');
                                $('#outbound-modal-count-display').text('0');
                                return;
                            }

                            $('#outbound-modal-count-display').text(rows.length);
                            var html = '';
                            rows.forEach(function (r) {
                                html += '<tr>';
                                currentOutboundConfig.keys.forEach(function (k) {
                                    var val = r[k] !== undefined && r[k] !== null && r[k] !== '' ? r[k] : '-';
                                    html += '<td class="py-2 px-3 align-middle text-left text-nowrap" style="font-size: 0.82rem;">' + $('<div>').text(val).html() + '</td>';
                                });
                                html += '</tr>';
                            });
                            tbody.html(html);
                        }

                        function applyOutboundModalFilters() {
                            var filterValues = {};
                            $('.dash-outbound-modal-filter').each(function () {
                                var key = $(this).attr('data-key');
                                var val = $(this).val();
                                if (val) {
                                    filterValues[key] = val.toString().trim().toLowerCase();
                                }
                            });

                            var searchTerm = ($('#filter-outbound-modal-search').val() || '').trim().toLowerCase();

                            var filtered = currentOutboundModalRows.filter(function (row) {
                                for (var k in filterValues) {
                                    var cellVal = (row[k] || '').toString().trim().toLowerCase();
                                    if (cellVal !== filterValues[k]) {
                                        return false;
                                    }
                                }

                                if (searchTerm) {
                                    var matchSearch = currentOutboundConfig.keys.some(function (k) {
                                        var cellVal = (row[k] || '').toString().toLowerCase();
                                        return cellVal.indexOf(searchTerm) !== -1;
                                    });
                                    if (!matchSearch) return false;
                                }

                                return true;
                            });

                            if (currentOutboundSort.key) {
                                filtered.sort(function (a, b) {
                                    var valA = (a[currentOutboundSort.key] || '').toString().trim();
                                    var valB = (b[currentOutboundSort.key] || '').toString().trim();
                                    return currentOutboundSort.dir === 'asc'
                                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });
                                });
                            }

                            renderOutboundModalRows(filtered);
                        }

                        function updateOutboundModalFilters(statusName, rawData) {
                            var filterRow = $('#modalOutboundDynamicFilterRow');
                            var filterDefs = outboundStatusFilterMap[statusName] || [
                                { key: 'user', label: 'User' },
                                { key: 'tujuan', label: 'Tujuan' }
                            ];
                            var html = '';

                            filterDefs.forEach(function (f) {
                                var key = f.key;
                                var label = f.label;
                                var uniqueVals = [];
                                (rawData || []).forEach(function (r) {
                                    var v = (r[key] || '').toString().trim();
                                    if (v && v !== '-' && uniqueVals.indexOf(v) === -1) {
                                        uniqueVals.push(v);
                                    }
                                });
                                uniqueVals.sort();

                                html += '<div style="flex: 1 1 140px; max-width: 200px; min-width: 120px;">';
                                html += '<select class="form-control form-control-sm custom-select custom-select-sm dash-outbound-modal-filter" data-key="' + key + '" style="font-size: 0.78rem; border-radius: 6px;">';
                                html += '<option value="">Semua ' + label + '</option>';
                                uniqueVals.forEach(function (uv) {
                                    html += '<option value="' + $('<div>').text(uv).html() + '">' + $('<div>').text(uv).html() + '</option>';
                                });
                                html += '</select>';
                                html += '</div>';
                            });

                            html += '<div style="flex: 1 1 170px; max-width: 230px; min-width: 140px;">';
                            html += '<div class="input-group input-group-sm">';
                            html += '<div class="input-group-prepend"><span class="input-group-text bg-white border-right-0 text-muted" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search" style="font-size: 0.72rem;"></i></span></div>';
                            html += '<input type="text" class="form-control form-control-sm border-left-0" id="filter-outbound-modal-search" placeholder="Search..." style="font-size: 0.78rem; border-radius: 0 6px 6px 0;">';
                            html += '</div>';
                            html += '</div>';

                            html += '<div>';
                            html += '<button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btn-reset-outbound-modal-filter" title="Reset Filter" style="border-radius: 6px; font-size: 0.75rem; padding: 0.25rem 0.65rem;">';
                            html += '<i class="fas fa-undo mr-1"></i> Reset';
                            html += '</button>';
                            html += '</div>';

                            filterRow.html(html);

                            $('.dash-outbound-modal-filter').off('change').on('change', applyOutboundModalFilters);
                            $('#filter-outbound-modal-search').off('keyup input change').on('keyup input change', applyOutboundModalFilters);
                            $('#btn-reset-outbound-modal-filter').off('click').on('click', function () {
                                $('.dash-outbound-modal-filter').val('');
                                $('#filter-outbound-modal-search').val('');
                                currentOutboundSort = { key: null, dir: 'asc' };
                                renderOutboundTableHeader();
                                applyOutboundModalFilters();
                            });
                        }

                        $('#tableOutboundMrStatusDetailHead').off('click', '.sortable-outbound-header').on('click', '.sortable-outbound-header', function () {
                            var key = $(this).attr('data-key');
                            if (currentOutboundSort.key === key) {
                                currentOutboundSort.dir = (currentOutboundSort.dir === 'asc') ? 'desc' : 'asc';
                            } else {
                                currentOutboundSort.key = key;
                                currentOutboundSort.dir = 'asc';
                            }
                            renderOutboundTableHeader();
                            applyOutboundModalFilters();
                        });

                        // Outbound Summary interactive modal handler
                        window.openOutboundSummaryModal = function (metricKey) {
                            var statusName = 'TOTAL MR';
                            if (metricKey === 'packing') {
                                statusName = 'TOTAL PACKED';
                            } else if (metricKey === 'shipped' || metricKey === 'fulfill') {
                                statusName = 'TOTAL SHIPPED';
                            } else if (metricKey === 'mr_closed' || metricKey === 'progress_closed') {
                                statusName = 'TIBA DI LOKASI';
                            } else {
                                statusName = 'TOTAL MR';
                            }

                            var cfg = outboundStatusColumnConfig[statusName] || outboundStatusColumnConfig['TOTAL MR'];
                            currentOutboundConfig = cfg;
                            currentOutboundSort = { key: null, dir: 'asc' };
                            currentOutboundModalRows = [];

                            $('#modalOutboundStatusTitleText').text(statusName);
                            renderOutboundTableHeader();

                            var tbody = $('#tableOutboundMrStatusDetailBody');
                            var colCount = cfg.headers.length;

                            var pText = document.getElementById('selected-period-text');
                            var periodVal = pText ? pText.textContent.trim() : '';
                            if (periodVal === 'PILIH PERIODE DATA' || periodVal === '-') periodVal = '';

                            if (!periodVal) {
                                updateOutboundModalFilters(statusName, []);
                                tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                $('#outbound-modal-count-display').text('0');
                                $('#mrStatusDetailModal').modal('show');
                                return;
                            }

                            tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data detail outbound...</td></tr>');
                            $('#modalOutboundDynamicFilterRow').empty();
                            $('#outbound-modal-count-display').text('Memuat...');

                            $.ajax({
                                url: 'api/get_outbound_status_detail.php',
                                type: 'GET',
                                data: { status: statusName, periode: periodVal },
                                dataType: 'json',
                                success: function (res) {
                                    if (res.status === 'success' && res.data && res.data.length > 0) {
                                        currentOutboundModalRows = res.data;
                                        updateOutboundModalFilters(statusName, res.data);
                                        renderOutboundModalRows(res.data);
                                    } else {
                                        updateOutboundModalFilters(statusName, []);
                                        tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                        $('#outbound-modal-count-display').text('0');
                                    }
                                },
                                error: function () {
                                    tbody.html('<tr><td colspan="' + colCount + '" class="py-4 text-center text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                                    $('#outbound-modal-count-display').text('0');
                                }
                            });

                            $('#mrStatusDetailModal').modal('show');
                        };

                        // ─── 3. Storage Asset Summary Modal System (Matching Storage Management) ───
                        var currentStorageModalRows = [];
                        var currentStorageSort = { key: null, dir: 'asc' };
                        var storageHeaders = ['Serial Number', 'Deskripsi Item', 'Kategori', 'Site / Lokasi', 'Aging', 'Nilai Buku (NBV)'];
                        var storageKeys = ['serial_number', 'item_description', 'category', 'site_name', 'aging', 'nbv'];

                        function renderStorageTableHeader() {
                            var thead = $('#tableStorageAssetDetailHead');
                            var tr = $('<tr></tr>');
                            storageHeaders.forEach(function (h, idx) {
                                var key = storageKeys[idx];
                                var sortIcon = '<i class="fas fa-sort text-gray-400 ml-1" style="font-size: 0.72rem;"></i>';
                                if (currentStorageSort.key === key) {
                                    sortIcon = currentStorageSort.dir === 'asc'
                                        ? '<i class="fas fa-sort-up text-primary ml-1" style="font-size: 0.78rem;"></i>'
                                        : '<i class="fas fa-sort-down text-primary ml-1" style="font-size: 0.78rem;"></i>';
                                }
                                var th = $('<th class="py-2.5 px-3 border-top-0 text-left sortable-storage-header user-select-none" style="cursor: pointer;" data-key="' + key + '">' + h + ' ' + sortIcon + '</th>');
                                tr.append(th);
                            });
                            thead.html(tr);
                        }

                        function renderStorageModalRows(rows) {
                            var tbody = $('#tableStorageAssetDetailBody');
                            if (!rows || rows.length === 0) {
                                tbody.html('<tr><td colspan="6" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Tidak ada data asset storage yang sesuai filter.</td></tr>');
                                $('#storage-modal-count-display').text('0');
                                return;
                            }

                            $('#storage-modal-count-display').text(rows.length);
                            var html = '';
                            rows.forEach(function (r) {
                                html += '<tr>';
                                storageKeys.forEach(function (k) {
                                    var val = r[k] !== undefined && r[k] !== null && r[k] !== '' ? r[k] : '-';
                                    html += '<td class="py-2 px-3 align-middle text-left text-nowrap" style="font-size: 0.82rem;">' + $('<div>').text(val).html() + '</td>';
                                });
                                html += '</tr>';
                            });
                            tbody.html(html);
                        }

                        function applyStorageModalFilters() {
                            var filterValues = {};
                            $('.dash-storage-modal-filter').each(function () {
                                var key = $(this).attr('data-key');
                                var val = $(this).val();
                                if (val) {
                                    filterValues[key] = val.toString().trim().toLowerCase();
                                }
                            });

                            var searchTerm = ($('#filter-storage-modal-search').val() || '').trim().toLowerCase();

                            var filtered = currentStorageModalRows.filter(function (row) {
                                for (var k in filterValues) {
                                    var cellVal = (row[k] || '').toString().trim().toLowerCase();
                                    if (cellVal !== filterValues[k]) {
                                        return false;
                                    }
                                }

                                if (searchTerm) {
                                    var matchSearch = storageKeys.some(function (k) {
                                        var cellVal = (row[k] || '').toString().toLowerCase();
                                        return cellVal.indexOf(searchTerm) !== -1;
                                    });
                                    if (!matchSearch) return false;
                                }

                                return true;
                            });

                            if (currentStorageSort.key) {
                                filtered.sort(function (a, b) {
                                    var valA = (a[currentStorageSort.key] || '').toString().trim();
                                    var valB = (b[currentStorageSort.key] || '').toString().trim();
                                    return currentStorageSort.dir === 'asc'
                                        ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                                        : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });
                                });
                            }

                            renderStorageModalRows(filtered);
                        }

                        function updateStorageModalFilters(rawData) {
                            var filterRow = $('#modalStorageDynamicFilterRow');
                            var filterDefs = [
                                { key: 'category', label: 'Kategori' },
                                { key: 'site_name', label: 'Site / Lokasi' }
                            ];
                            var html = '';

                            filterDefs.forEach(function (f) {
                                var key = f.key;
                                var label = f.label;
                                var uniqueVals = [];
                                (rawData || []).forEach(function (r) {
                                    var v = (r[key] || '').toString().trim();
                                    if (v && v !== '-' && uniqueVals.indexOf(v) === -1) {
                                        uniqueVals.push(v);
                                    }
                                });
                                uniqueVals.sort();

                                html += '<div style="flex: 1 1 140px; max-width: 200px; min-width: 120px;">';
                                html += '<select class="form-control form-control-sm custom-select custom-select-sm dash-storage-modal-filter" data-key="' + key + '" style="font-size: 0.78rem; border-radius: 6px;">';
                                html += '<option value="">Semua ' + label + '</option>';
                                uniqueVals.forEach(function (uv) {
                                    html += '<option value="' + $('<div>').text(uv).html() + '">' + $('<div>').text(uv).html() + '</option>';
                                });
                                html += '</select>';
                                html += '</div>';
                            });

                            html += '<div style="flex: 1 1 170px; max-width: 230px; min-width: 140px;">';
                            html += '<div class="input-group input-group-sm">';
                            html += '<div class="input-group-prepend"><span class="input-group-text bg-white border-right-0 text-muted" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search" style="font-size: 0.72rem;"></i></span></div>';
                            html += '<input type="text" class="form-control form-control-sm border-left-0" id="filter-storage-modal-search" placeholder="Search..." style="font-size: 0.78rem; border-radius: 0 6px 6px 0;">';
                            html += '</div>';
                            html += '</div>';

                            html += '<div>';
                            html += '<button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btn-reset-storage-modal-filter" title="Reset Filter" style="border-radius: 6px; font-size: 0.75rem; padding: 0.25rem 0.65rem;">';
                            html += '<i class="fas fa-undo mr-1"></i> Reset';
                            html += '</button>';
                            html += '</div>';

                            filterRow.html(html);

                            $('.dash-storage-modal-filter').off('change').on('change', applyStorageModalFilters);
                            $('#filter-storage-modal-search').off('keyup input change').on('keyup input change', applyStorageModalFilters);
                            $('#btn-reset-storage-modal-filter').off('click').on('click', function () {
                                $('.dash-storage-modal-filter').val('');
                                $('#filter-storage-modal-search').val('');
                                currentStorageSort = { key: null, dir: 'asc' };
                                renderStorageTableHeader();
                                applyStorageModalFilters();
                            });
                        }

                        $('#tableStorageAssetDetailHead').off('click', '.sortable-storage-header').on('click', '.sortable-storage-header', function () {
                            var key = $(this).attr('data-key');
                            if (currentStorageSort.key === key) {
                                currentStorageSort.dir = (currentStorageSort.dir === 'asc') ? 'desc' : 'asc';
                            } else {
                                currentStorageSort.key = key;
                                currentStorageSort.dir = 'asc';
                            }
                            renderStorageTableHeader();
                            applyStorageModalFilters();
                        });

                        // Click handler for Storage Summary flow steps on dashboard overview
                        window.openStorageSummaryModal = function (stepIndex) {
                            var titles = [
                                'Total Perangkat',
                                'Total NBV (Nilai Buku)',
                                'Aging < 1 Tahun',
                                'Aging > 1 Tahun',
                                'Aging > 2 Tahun',
                                'RE-Use'
                            ];
                            var title = titles[stepIndex] || 'Detail Storage';
                            $('#modalStorageStatusTitleText').text(title);
                            renderStorageTableHeader();

                            var tbody = $('#tableStorageAssetDetailBody');

                            var pText = document.getElementById('selected-period-text');
                            var periodVal = pText ? pText.textContent.trim() : '';
                            if (periodVal === 'PILIH PERIODE DATA' || periodVal === '-') periodVal = '';

                            if (!periodVal) {
                                updateStorageModalFilters([]);
                                tbody.html('<tr><td colspan="6" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                $('#storage-modal-count-display').text('0');
                                $('#storageAssetDetailModal').modal('show');
                                return;
                            }

                            tbody.html('<tr><td colspan="6" class="py-5 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data asset storage...</td></tr>');
                            $('#modalStorageDynamicFilterRow').empty();
                            $('#storage-modal-count-display').text('Memuat...');

                            var dataUrl = 'api/get_data.php?periode=' + encodeURIComponent(periodVal);

                            fetch(dataUrl)
                                .then(function (res) { return res.json(); })
                                .then(function (res) {
                                    if (res && res.status === 'success' && Array.isArray(res.data) && res.data.length > 0) {
                                        var mapped = res.data.map(function (row) {
                                            var getFld = function (keys) {
                                                for (var idx = 0; idx < keys.length; idx++) {
                                                    var k = keys[idx];
                                                    for (var rk in row) {
                                                        if (rk.toLowerCase().replace(/[^a-z0-9]/g, '') === k.toLowerCase().replace(/[^a-z0-9]/g, '')) {
                                                            return row[rk];
                                                        }
                                                    }
                                                }
                                                return '-';
                                            };

                                            var sn = getFld(['serialnumber', 'sn', 'serial_number', 'no_seri']);
                                            var desc = getFld(['itemdescription', 'deskripsi_item', 'nama_barang', 'item_name']);
                                            var cat = getFld(['itemcategory', 'kategori', 'category']);
                                            var site = getFld(['sitename', 'site_name', 'lokasi', 'location']);
                                            var aging = getFld(['aging', 'umur_barang', 'aging_bucket', 'age']);
                                            var nbv = getFld(['nbv', 'nilaibuku', 'nilai_buku', 'book_value']);

                                            return {
                                                serial_number: sn,
                                                item_description: desc,
                                                category: cat,
                                                site_name: site,
                                                aging: aging,
                                                nbv: (nbv !== '-' && !isNaN(nbv)) ? 'Rp ' + Number(nbv).toLocaleString('id-ID') : nbv
                                            };
                                        });

                                        // Apply step-specific slice / filter
                                        var filteredRows = mapped;
                                        if (stepIndex === 2) {
                                            filteredRows = mapped.filter(function (r) {
                                                var ag = (r.aging || '').toLowerCase();
                                                return ag.includes('< 1') || ag.includes('<1') || ag.includes('less') || ag.includes('0-1');
                                            });
                                        } else if (stepIndex === 3) {
                                            filteredRows = mapped.filter(function (r) {
                                                var ag = (r.aging || '').toLowerCase();
                                                return ag.includes('1-2') || ag.includes('1 - 2') || ag.includes('> 1') || ag.includes('>1');
                                            });
                                        } else if (stepIndex === 4) {
                                            filteredRows = mapped.filter(function (r) {
                                                var ag = (r.aging || '').toLowerCase();
                                                return ag.includes('> 2') || ag.includes('>2') || ag.includes('more than 2');
                                            });
                                        } else if (stepIndex === 5) {
                                            filteredRows = mapped.filter(function (r) {
                                                var cat = (r.category || '').toLowerCase();
                                                var desc = (r.item_description || '').toLowerCase();
                                                return cat.includes('re-use') || cat.includes('reuse') || desc.includes('re-use') || desc.includes('reuse');
                                            });
                                        }

                                        currentStorageModalRows = (filteredRows.length > 0) ? filteredRows : mapped.slice(0, 100);
                                        updateStorageModalFilters(currentStorageModalRows);
                                        renderStorageModalRows(currentStorageModalRows);
                                    } else {
                                        updateStorageModalFilters([]);
                                        tbody.html('<tr><td colspan="6" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                        $('#storage-modal-count-display').text('0');
                                    }
                                })
                                .catch(function () {
                                    tbody.html('<tr><td colspan="6" class="py-4 text-center text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                                    $('#storage-modal-count-display').text('0');
                                });

                            $('#storageAssetDetailModal').modal('show');
                        };

                        // Keep the dropdown open when clicking inside the selects/options
                        var periodMenu = document.getElementById('period-dropdown-menu');
                        if (periodMenu) {
                            periodMenu.addEventListener('click', function (e) {
                                e.stopPropagation();
                            });
                        }

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
                            var b = document.getElementById('period-batch-select');
                            var y = document.getElementById('period-year-select');
                            var btn = document.getElementById('btn-load-period');
                            if (btn) {
                                btn.disabled = !(m && m.value && b && b.value && y && y.value);
                            }
                        }

                        var monthSel = document.getElementById('period-month-select');
                        var batchSel = document.getElementById('period-batch-select');
                        var yearSel = document.getElementById('period-year-select');
                        if (monthSel) monthSel.addEventListener('change', updateLoadButton);
                        if (batchSel) batchSel.addEventListener('change', updateLoadButton);
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
                                    var availableYears = (result.years && result.years.length > 0) ? result.years : Object.keys(yearsSet).sort();
                                    populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                                    populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');

                                    var pText = document.getElementById('selected-period-text');
                                    if (pText) pText.textContent = "PILIH PERIODE DATA";

                                    if (window.FormulaController) {
                                        window.FormulaController.updateDashboardCards([], []);
                                    }
                                    if (window.updateDashReceivingTrendChart) {
                                        window.updateDashReceivingTrendChart([], [], [], [], '');
                                    }
                                    if (window.updateDashKpiMonitoringChart) {
                                        window.updateDashKpiMonitoringChart([null, null, null, null, null, null, null, null, null]);
                                    }
                                })
                                .catch(function (err) {
                                    console.error('Error fetching periods:', err);
                                });
                        }

                        var btnLoad = document.getElementById('btn-load-period');
                        if (btnLoad) {
                            btnLoad.addEventListener('click', function () {
                                var m = document.getElementById('period-month-select');
                                var b = document.getElementById('period-batch-select');
                                var y = document.getElementById('period-year-select');
                                if (m && m.value && b && b.value && y && y.value) {
                                    var period = m.value + ' ' + y.value + '-Batch' + b.value;
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
                                var b = document.getElementById('period-batch-select');
                                var y = document.getElementById('period-year-select');
                                if (m) m.value = '';
                                if (b) b.value = '';
                                if (y) y.value = '';
                                updateLoadButton();

                                var pText = document.getElementById('selected-period-text');
                                if (pText) pText.textContent = "PILIH PERIODE DATA";

                                window.currentDashboardData = [];
                                window.currentDashboardHeaders = [];
                                if (window.FormulaController) {
                                    window.FormulaController.updateDashboardCards([], []);
                                }
                                if (window.updateDashReceivingTrendChart) {
                                    window.updateDashReceivingTrendChart([], [], [], [], '');
                                }

                                if (window.updateInboundFlowPieChart) {
                                    window.updateInboundFlowPieChart([0, 0, 0]);
                                }
                                var elPo = document.getElementById('flow-po-count');
                                if (elPo) elPo.textContent = '0 PO';
                                var elOntime = document.getElementById('flow-po-proses-delivery');
                                if (elOntime) elOntime.textContent = '0 PO';
                                var elTerlambat = document.getElementById('flow-po-terlambat-delivery');
                                if (elTerlambat) elTerlambat.textContent = '0 PO';
                                var elGr = document.getElementById('flow-total-gr');
                                if (elGr) elGr.textContent = '0 GR';
                                var elReg = document.getElementById('flow-done-count');
                                if (elReg) elReg.textContent = '0 Registrasi';

                                var elMr = document.getElementById('outbound-total-mr');
                                if (elMr) elMr.textContent = '0 MR';
                                var elPck = document.getElementById('sub-packing-count');
                                if (elPck) elPck.textContent = '0 Unit';
                                var elShp = document.getElementById('sub-shipped-count');
                                if (elShp) elShp.textContent = '0 Unit';
                                var elTiba = document.getElementById('outbound-terkirim');
                                if (elTiba) elTiba.textContent = '0 Selesai';
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

                            var parts = period.match(/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/);
                            var yr = parts ? parts[2] : new Date().getFullYear().toString();

                            var fetchDashboard = fetch('api/get_data.php?periode=' + encodeURIComponent(period))
                                .then(function (response) { return response.json(); });

                            var fetchYearly = fetch('api/get_yearly_in_out.php?year=' + encodeURIComponent(yr))
                                .then(function (response) { return response.json(); });

                            var fetchInbound = fetch('api/get_inbound_status_detail.php?action=counts&periode=' + encodeURIComponent(period))
                                .then(function (response) { return response.json(); })
                                .catch(function () { return null; });

                            var fetchOutbound = fetch('api/get_outbound_status_detail.php?action=counts&periode=' + encodeURIComponent(period))
                                .then(function (response) { return response.json(); })
                                .catch(function () { return null; });

                            Promise.all([fetchDashboard, fetchYearly, fetchInbound, fetchOutbound])
                                .then(function (results) {
                                    var result = results[0];
                                    var resData = results[1];
                                    var inboundRes = results[2];
                                    var outboundRes = results[3];

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

                                    if (inboundRes && inboundRes.status === 'success' && inboundRes.counts) {
                                        var c = inboundRes.counts;
                                        if (window.updateInboundFlowPieChart) {
                                            window.updateInboundFlowPieChart([c.total_po_inbound, c.po_ontime_delivery, c.po_terlambat_delivery]);
                                        }
                                        var elPo = document.getElementById('flow-po-count');
                                        if (elPo) elPo.textContent = c.total_po_inbound + ' PO';
                                        var elOntime = document.getElementById('flow-po-proses-delivery');
                                        if (elOntime) elOntime.textContent = c.po_ontime_delivery + ' PO';
                                        var elTerlambat = document.getElementById('flow-po-terlambat-delivery');
                                        if (elTerlambat) elTerlambat.textContent = c.po_terlambat_delivery + ' PO';
                                        var elGr = document.getElementById('flow-total-gr');
                                        if (elGr) elGr.textContent = c.total_gr + ' GR';
                                        var elReg = document.getElementById('flow-done-count');
                                        if (elReg) elReg.textContent = c.total_registrasi + ' Registrasi';
                                    }

                                    if (outboundRes && outboundRes.status === 'success' && outboundRes.counts) {
                                        var oc = outboundRes.counts;
                                        var elMr = document.getElementById('outbound-total-mr');
                                        if (elMr) elMr.textContent = oc.total_mr + ' MR';
                                        var elPck = document.getElementById('sub-packing-count');
                                        if (elPck) elPck.textContent = oc.total_packed + ' Unit';
                                        var elShp = document.getElementById('sub-shipped-count');
                                        if (elShp) elShp.textContent = oc.total_shipped + ' Unit';
                                        var elTiba = document.getElementById('outbound-terkirim');
                                        if (elTiba) elTiba.textContent = oc.tiba_di_lokasi + ' Selesai';
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
                                $('#storageUtilModalBadge').text('Total Penggunaan').removeClass().addClass('badge badge-warning px-2 py-0.5 font-weight-bold ml-2');
                            } else if (filterType === 'available') {
                                $('#btn-modal-filter-available').addClass('active');
                                $('#storageUtilModalBadge').text('Total Tersedia').removeClass().addClass('badge badge-success px-2 py-0.5 font-weight-bold ml-2');
                            } else {
                                $('#btn-modal-filter-all').addClass('active');
                                $('#storageUtilModalBadge').text('Total Capacity').removeClass().addClass('badge badge-primary px-2 py-0.5 font-weight-bold ml-2');
                            }
                        }

                        function fetchAndRenderUtilisasiModal(filterType) {
                            var tbody = $('#tableStorageUtilisasiBody');

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

                            if (!periodMonth || !periodYear) {
                                rawUtilisasiRows = [];
                                tbody.html('<tr><td colspan="2" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                                renderEmptyDots();
                                return;
                            }

                            tbody.html('<tr><td colspan="2" class="py-4 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data Utilisasi Area / Rack...</td></tr>');

                            var utilisasiUrl = 'api/get_rack_utilisasi.php?month=' + encodeURIComponent(periodMonth) + '&year=' + encodeURIComponent(periodYear);

                            $.ajax({
                                url: utilisasiUrl,
                                type: 'GET',
                                dataType: 'json',
                                success: function (res) {
                                    if (res.status === 'success' && res.data && res.data.length > 0) {
                                        rawUtilisasiRows = res.data;
                                        renderUtilisasiModalTable();
                                    } else {
                                        tbody.html('<tr><td colspan="2" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
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