<?php
// frontend/pages/kpi_monitoring.php - Key Performance Indicators (KPI) Monitoring Dashboard
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('kpi_monitoring');

$currentUser = getCurrentUser();
$pageTitle = 'KPI Monitoring - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
            <div id="content" class="flex-grow-1">

                <!-- Topbar Navigation -->
                <?php
                $activePage = 'kpi_monitoring';
                include FRONTEND_PATH . 'components/navbar.php';
                ?>

                <!-- Custom Styling for KPI Monitoring Page (Placed after Navbar for Priority) -->
                <style>
                    /* Standard Hover Effect for Clickable KPI Metric Cards */
                    .kpi-metric-card {
                        cursor: pointer;
                        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                    }

                    .kpi-metric-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
                    }
                </style>

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">KPI Monitoring</h1>
                    </div>

                    <!-- 9 KPI Metric Cards Grid (3 Columns across, Normal Compact Size) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Card 1: Receiving (GR) SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('receiving_sla')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        RECEIVING (GR) SLA</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-receiving">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Registration SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('registration_sla')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        REGISTRATION SLA</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-registration">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Stock Opname (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        STOCK OPNAME</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-stock-opname">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Stock Opname Warehouse Hub (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname_hub')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        STOCK OPNAME WAREHOUSE HUB</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-so-hub">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: Stock Opname Outlet Warehouse (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname_outlet')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        STOCK OPNAME OUTLET WAREHOUSE</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-so-outlet">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: Slow Moving (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('slow_moving')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        SLOW MOVING</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-slow-moving">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 7: Capacity (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('capacity')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        CAPACITY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-capacity">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 8: Delivery Effectiveness (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-danger shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_effectiveness')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        DELIVERY EFFECTIVENESS</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-delivery-eff">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 9: Efisiensi Delivery (Idr Rupiah) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_efficiency')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        EFISIENSI DELIVERY</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-delivery-idr">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row: Big Card for Tren Evaluasi KPI Bulanan (Grouping all 9 Line Charts) -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow mb-4">
                                <div
                                    class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line mr-2"></i>Tren Evaluasi KPI Bulanan
                                    </h6>
                                    <div class="d-flex align-items-center mt-2 mt-sm-0">
                                        <div class="d-flex align-items-center mr-3">
                                            <span
                                                style="display:inline-block; width: 18px; height: 0px; border-top: 2px dashed #858796; margin-right: 6px;"></span>
                                            <span class="small font-weight-bold text-gray-700">Target</span>
                                        </div>
                                        <div class="d-flex align-items-center mr-3">
                                            <span
                                                style="display:inline-block; width: 14px; height: 3px; background-color: #4e73df; border-radius: 2px; margin-right: 6px;"></span>
                                            <span class="small font-weight-bold text-gray-700">Realisasi</span>
                                        </div>
                                        <span class="badge badge-info">9 Indikator KPI</span>
                                    </div>
                                </div>
                                <div class="card-body p-3 bg-light">
                                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                                        <!-- Chart 1: Receiving (GR) SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Receiving (GR) SLA</h6>
                                                    <span class="badge badge-primary px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≥ 95.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-receiving_sla"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 2: Registration SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Registration SLA</h6>
                                                    <span class="badge badge-info px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≥ 98.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-registration_sla"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 3: Stock Opname (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Stock Opname</h6>
                                                    <span class="badge badge-success px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≥ 99.5%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-stock_opname"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 4: Stock Opname Warehouse Hub (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        SO Warehouse Hub</h6>
                                                    <span class="badge badge-success px-2 py-1"
                                                        style="font-size: 0.7rem; background-color: #20c997;">Target: ≥
                                                        99.5%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-stock_opname_hub"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 5: Stock Opname Outlet Warehouse (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        SO Outlet Warehouse</h6>
                                                    <span class="badge badge-info px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≥ 99.5%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-stock_opname_outlet"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 6: Slow Moving (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Slow Moving</h6>
                                                    <span class="badge badge-warning px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≤ 15.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-slow_moving"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 7: Capacity (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Capacity</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #6f42c1;">Target:
                                                        70-80%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-capacity"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 8: Delivery Effectiveness (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Delivery Effectiveness</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #e83e8c;">Target: ≥
                                                        95.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-delivery_effectiveness"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 9: Efisiensi Delivery (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Efisiensi Delivery</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #17a2b8;">Target:
                                                        100% (Rp 130Jt)</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-delivery_efficiency"></canvas>
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
                <!-- End Page Content -->

            </div>

            <!-- KPI Drill-Down Modal -->
            <div class="modal fade" id="kpiDetailModal" tabindex="-1" role="dialog"
                aria-labelledby="kpiDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header py-3 px-4 d-flex align-items-center justify-content-between"
                            style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); color: #ffffff;">
                            <h5 class="modal-title font-weight-bold text-white mb-0" id="modal-kpi-title">Detail
                                Indikator KPI</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4 bg-white">
                            <!-- Metrics Quick Bar -->
                            <div class="row text-center mb-4">
                                <div class="col-4 border-right">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Target Standar</div>
                                    <div class="h5 font-weight-bold text-gray-800 mt-1 mb-0" id="modal-kpi-target">-
                                    </div>
                                </div>
                                <div class="col-4 border-right">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Realisasi Aktual
                                    </div>
                                    <div class="h5 font-weight-bold text-primary mt-1 mb-0" id="modal-kpi-actual">-
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Pencapaian</div>
                                    <div class="h5 font-weight-bold text-success mt-1 mb-0" id="modal-kpi-achievement">-
                                    </div>
                                </div>
                            </div>

                            <!-- Alert status -->
                            <div class="alert alert-info py-2 px-3 mb-0" id="modal-kpi-alert"
                                style="font-size: 0.78rem; border-radius: 8px; min-height: 38px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

            <!-- Page level plugins & Chart Script -->
            <script src="frontend/vendor/chart.js/Chart.min.js"></script>

            <!-- KPI Monitoring Page Scripts -->
            <script>
                (function () {
                    'use strict';

                    var kpiDataCache = null;
                    var kpiChartInstances = {};
                    var ALL_MONTHS = [
                        "January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];
                    var MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

                    var KPI_CONFIGS = {
                        'receiving_sla': { name: 'Receiving (GR) SLA', code: 'KPI-IN-01', target: 95.0, target_display: '≥ 95.0%', color: '#4e73df', icon: 'fa-clipboard-check' },
                        'registration_sla': { name: 'Registration SLA', code: 'KPI-IN-02', target: 98.0, target_display: '≥ 98.0%', color: '#36b9cc', icon: 'fa-barcode' },
                        'stock_opname': { name: 'Stock Opname', code: 'KPI-ST-01', target: 99.5, target_display: '≥ 99.5%', color: '#1cc88a', icon: 'fa-boxes' },
                        'stock_opname_hub': { name: 'Stock Opname Warehouse Hub', code: 'KPI-ST-01A', target: 99.5, target_display: '≥ 99.5%', color: '#20c997', icon: 'fa-warehouse' },
                        'stock_opname_outlet': { name: 'Stock Opname Outlet Warehouse', code: 'KPI-ST-01B', target: 99.5, target_display: '≥ 99.5%', color: '#0dcaf0', icon: 'fa-store' },
                        'slow_moving': { name: 'Slow Moving', code: 'KPI-ST-02', target: 15.0, target_display: '≤ 15.0%', color: '#f6c23e', icon: 'fa-hourglass-half' },
                        'capacity': { name: 'Capacity', code: 'KPI-ST-03', target: 80.0, target_display: '70-80%', color: '#6f42c1', icon: 'fa-warehouse' },
                        'delivery_effectiveness': { name: 'Delivery Effectiveness', code: 'KPI-OB-01', target: 95.0, target_display: '≥ 95.0%', color: '#e83e8c', icon: 'fa-truck-fast' },
                        'delivery_efficiency': { name: 'Efisiensi Delivery', code: 'KPI-OB-02', target: 100.0, target_display: '100% (Rp 130Jt)', color: '#17a2b8', icon: 'fa-money-bill-wave' }
                    };

                    // Global chart styling defaults
                    if (typeof Chart !== 'undefined') {
                        Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
                        Chart.defaults.global.defaultFontColor = '#858796';
                    }

                    // Helper to create RGBA color
                    function hexToRgba(hex, alpha) {
                        var c = hex.substring(1);
                        if (c.length === 3) c = c.split('').map(function (x) { return x + x; }).join('');
                        var num = parseInt(c, 16);
                        return 'rgba(' + ((num >> 16) & 255) + ',' + ((num >> 8) & 255) + ',' + (num & 255) + ',' + alpha + ')';
                    }

                    // Initialize Page & Period Selector from Navbar
                    document.addEventListener('DOMContentLoaded', function () {
                        var periodMenu = document.getElementById('period-dropdown-menu');
                        if (periodMenu) {
                            periodMenu.addEventListener('click', function (e) {
                                e.stopPropagation();
                            });
                        }

                        var monthSel = document.getElementById('period-month-select');
                        var yearSel = document.getElementById('period-year-select');
                        if (monthSel) {
                            monthSel.addEventListener('change', function () {
                                if (monthSel.value === 'DATA DUMMY' && yearSel) {
                                    if (!yearSel.value && yearSel.options.length > 1) {
                                        yearSel.value = yearSel.options[1].value;
                                    }
                                }
                                updateLoadButton();
                            });
                        }
                        if (yearSel) yearSel.addEventListener('change', updateLoadButton);

                        var btnLoad = document.getElementById('btn-load-period');
                        if (btnLoad) {
                            btnLoad.addEventListener('click', function () {
                                var m = document.getElementById('period-month-select');
                                var y = document.getElementById('period-year-select');
                                if (m && m.value === 'DATA DUMMY') {
                                    loadDataForPeriod('DATA DUMMY');
                                    if (periodMenu && typeof $ !== 'undefined') {
                                        $(periodMenu).closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
                                    }
                                } else if (m && m.value && y && y.value) {
                                    var period = m.value + ' ' + y.value;
                                    loadDataForPeriod(period);
                                    if (periodMenu && typeof $ !== 'undefined') {
                                        $(periodMenu).closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
                                    }
                                }
                            });
                        }

                        var btnReset = document.getElementById('btn-reset-period');
                        if (btnReset) {
                            btnReset.addEventListener('click', function () {
                                if (monthSel) monthSel.value = '';
                                if (yearSel) yearSel.value = '';
                                updateLoadButton();
                                resetKpiState();
                                var periodText = document.getElementById('selected-period-text');
                                if (periodText) periodText.textContent = 'PILIH PERIODE DATA';
                            });
                        }

                        loadPeriods();
                    });

                    // Populate selects helper with DATA DUMMY support
                    function populateSelect(selectId, items, placeholder, isMonth) {
                        var sel = document.getElementById(selectId);
                        if (!sel) return;
                        sel.replaceChildren();
                        var defOpt = document.createElement('option');
                        defOpt.value = '';
                        defOpt.textContent = placeholder;
                        sel.appendChild(defOpt);

                        if (isMonth) {
                            var dummyOpt = document.createElement('option');
                            dummyOpt.value = 'DATA DUMMY';
                            dummyOpt.textContent = 'DATA DUMMY (TESTER)';
                            dummyOpt.style.fontWeight = 'bold';
                            dummyOpt.style.color = '#4e73df';
                            sel.appendChild(dummyOpt);
                        }

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
                            if (m && m.value === 'DATA DUMMY') {
                                btn.disabled = false;
                            } else {
                                btn.disabled = !(m && m.value && y && y.value);
                            }
                        }
                    }

                    function loadPeriods(selectPeriod) {
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
                                if (availableYears.length === 0) {
                                    var currentY = new Date().getFullYear();
                                    availableYears = [currentY.toString()];
                                }

                                populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --', true);
                                populateSelect('period-year-select', availableYears, '-- Pilih Tahun --', false);

                                if (selectPeriod) {
                                    if (selectPeriod === 'DATA DUMMY') {
                                        var mSel = document.getElementById('period-month-select');
                                        if (mSel) mSel.value = 'DATA DUMMY';
                                        updateLoadButton();
                                    } else {
                                        var parts = selectPeriod.split(' ');
                                        if (parts.length >= 2) {
                                            var mSel = document.getElementById('period-month-select');
                                            var ySel = document.getElementById('period-year-select');
                                            if (mSel) mSel.value = parts[0];
                                            if (ySel) ySel.value = parts[1];
                                            updateLoadButton();
                                        }
                                    }
                                    loadDataForPeriod(selectPeriod);
                                } else {
                                    var periodText = document.getElementById('selected-period-text');
                                    if (periodText) periodText.textContent = "PILIH PERIODE DATA";
                                    resetKpiState();
                                }
                            })
                            .catch(function (err) {
                                console.error('Error fetching periods:', err);
                            });
                    }

                    // Default baseline trends
                    function getDefaultTrends() {
                        var trends = { labels: MONTH_LABELS };
                        Object.keys(KPI_CONFIGS).forEach(function (kpiId) {
                            var cfg = KPI_CONFIGS[kpiId];
                            trends[kpiId] = {
                                name: cfg.name,
                                code: cfg.code,
                                color: cfg.color,
                                target: Array(12).fill(cfg.target),
                                realisasi: Array(12).fill(0)
                            };
                        });
                        return trends;
                    }

                    // Reset KPI state to default
                    function resetKpiState() {
                        kpiDataCache = null;
                        var elRecVal = document.getElementById('card-val-receiving');
                        var elRegVal = document.getElementById('card-val-registration');
                        var elSoVal = document.getElementById('card-val-stock-opname');
                        var elSoHubVal = document.getElementById('card-val-so-hub');
                        var elSoOutletVal = document.getElementById('card-val-so-outlet');
                        var elSmVal = document.getElementById('card-val-slow-moving');
                        var elCapVal = document.getElementById('card-val-capacity');
                        var elDelVal = document.getElementById('card-val-delivery-eff');
                        var elIdrVal = document.getElementById('card-val-delivery-idr');

                        if (elRecVal) elRecVal.textContent = '0.0%';
                        if (elRegVal) elRegVal.textContent = '0.0%';
                        if (elSoVal) elSoVal.textContent = '0.0%';
                        if (elSoHubVal) elSoHubVal.textContent = '0.0%';
                        if (elSoOutletVal) elSoOutletVal.textContent = '0.0%';
                        if (elSmVal) elSmVal.textContent = '0.0%';
                        if (elCapVal) elCapVal.textContent = '0.0%';
                        if (elDelVal) elDelVal.textContent = '0.0%';
                        if (elIdrVal) elIdrVal.textContent = 'Rp 0';

                        renderAllKpiCharts(getDefaultTrends());
                    }

                    // Fetch & render KPI Data for a selected period
                    function loadDataForPeriod(period) {
                        var periodText = document.getElementById('selected-period-text');
                        if (periodText) periodText.textContent = period.toUpperCase();

                        var apiUrl = 'api/get_kpi_data.php?periode=' + encodeURIComponent(period);

                        fetch(apiUrl)
                            .then(function (res) { return res.json(); })
                            .then(function (res) {
                                if (res.status === 'success') {
                                    kpiDataCache = res;
                                    renderKpiCards(res.cards);
                                    if (res.monthly_trends) {
                                        renderAllKpiCharts(res.monthly_trends);
                                    }
                                } else {
                                    console.error('KPI Data Error:', res.message);
                                    resetKpiState();
                                }
                            })
                            .catch(function (err) {
                                console.error('Failed to fetch KPI data:', err);
                                resetKpiState();
                            });
                    }

                    // Render 9 Top KPI Summary Metric Cards
                    function renderKpiCards(cards) {
                        if (!cards) return;

                        var elRecVal = document.getElementById('card-val-receiving');
                        var elRegVal = document.getElementById('card-val-registration');
                        var elSoVal = document.getElementById('card-val-stock-opname');
                        var elSoHubVal = document.getElementById('card-val-so-hub');
                        var elSoOutletVal = document.getElementById('card-val-so-outlet');
                        var elSmVal = document.getElementById('card-val-slow-moving');
                        var elCapVal = document.getElementById('card-val-capacity');
                        var elDelVal = document.getElementById('card-val-delivery-eff');
                        var elIdrVal = document.getElementById('card-val-delivery-idr');

                        if (elRecVal && cards.receiving_sla) elRecVal.textContent = cards.receiving_sla.value_formatted;
                        if (elRegVal && cards.registration_sla) elRegVal.textContent = cards.registration_sla.value_formatted;
                        if (elSoVal && cards.stock_opname) elSoVal.textContent = cards.stock_opname.value_formatted;
                        if (elSoHubVal && cards.stock_opname_hub) elSoHubVal.textContent = cards.stock_opname_hub.value_formatted;
                        if (elSoOutletVal && cards.stock_opname_outlet) elSoOutletVal.textContent = cards.stock_opname_outlet.value_formatted;
                        if (elSmVal && cards.slow_moving) elSmVal.textContent = cards.slow_moving.value_formatted;
                        if (elCapVal && cards.capacity) elCapVal.textContent = cards.capacity.value_formatted;
                        if (elDelVal && cards.delivery_effectiveness) elDelVal.textContent = cards.delivery_effectiveness.value_formatted;
                        if (elIdrVal && cards.delivery_efficiency) elIdrVal.textContent = cards.delivery_efficiency.value_formatted;
                    }

                    // Render All 9 Separate Line Charts
                    function renderAllKpiCharts(trendsData) {
                        if (!trendsData || typeof Chart === 'undefined') return;

                        var labels = trendsData.labels || MONTH_LABELS;

                        Object.keys(KPI_CONFIGS).forEach(function (kpiId) {
                            var canvasId = 'kpi-chart-' + kpiId;
                            var canvas = document.getElementById(canvasId);
                            if (!canvas) return;

                            var cfg = KPI_CONFIGS[kpiId];
                            var kpiSeries = trendsData[kpiId] || {};
                            var targetData = kpiSeries.target || Array(12).fill(cfg.target);
                            var realisasiData = kpiSeries.realisasi || Array(12).fill(0);
                            var themeColor = kpiSeries.color || cfg.color;

                            // Destroy existing chart instance if any
                            if (kpiChartInstances[kpiId]) {
                                kpiChartInstances[kpiId].destroy();
                            }

                            var ctx = canvas.getContext('2d');

                            // Create gradient for realisasi line fill
                            var gradient = ctx.createLinearGradient(0, 0, 0, 180);
                            gradient.addColorStop(0, hexToRgba(themeColor, 0.25));
                            gradient.addColorStop(1, hexToRgba(themeColor, 0.01));

                            kpiChartInstances[kpiId] = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [
                                        {
                                            label: 'Target',
                                            data: targetData,
                                            borderColor: '#858796',
                                            borderWidth: 1.8,
                                            borderDash: [5, 5],
                                            pointRadius: 0,
                                            pointHoverRadius: 0,
                                            pointHitRadius: 0,
                                            fill: false,
                                            order: 2
                                        },
                                        {
                                            label: 'Realisasi',
                                            data: realisasiData,
                                            borderColor: themeColor,
                                            backgroundColor: gradient,
                                            borderWidth: 2.5,
                                            pointRadius: 4,
                                            pointBackgroundColor: themeColor,
                                            pointBorderColor: '#ffffff',
                                            pointBorderWidth: 1.5,
                                            pointHoverRadius: 7,
                                            pointHoverBackgroundColor: themeColor,
                                            pointHoverBorderColor: '#ffffff',
                                            pointHoverBorderWidth: 2,
                                            pointHitRadius: 8,
                                            lineTension: 0.35,
                                            fill: true,
                                            order: 1
                                        }
                                    ]
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    responsive: true,
                                    legend: {
                                        display: false
                                    },
                                    layout: {
                                        padding: { left: 4, right: 6, top: 8, bottom: 0 }
                                    },
                                    onClick: function (evt, elements) {
                                        // Only trigger modal when clicking on Realisasi data point (not Target)
                                        if (elements && elements.length > 0) {
                                            var realisasiPoint = elements.find(function (el) {
                                                return el._datasetIndex === 1;
                                            });
                                            if (realisasiPoint) {
                                                var clickedIndex = realisasiPoint._index;
                                                openKpiModal(kpiId, clickedIndex);
                                            }
                                        }
                                    },
                                    hover: {
                                        mode: 'nearest',
                                        intersect: true,
                                        onHover: function (e, elements) {
                                            if (e && e.target) {
                                                var hasRealisasi = elements && elements.some(function (el) {
                                                    return el._datasetIndex === 1;
                                                });
                                                e.target.style.cursor = hasRealisasi ? 'pointer' : 'default';
                                            }
                                        }
                                    },
                                    scales: {
                                        xAxes: [{
                                            gridLines: {
                                                display: false,
                                                drawBorder: false
                                            },
                                            ticks: {
                                                fontSize: 10,
                                                fontColor: '#858796',
                                                padding: 4
                                            }
                                        }],
                                        yAxes: [{
                                            gridLines: {
                                                color: '#f1f3f9',
                                                zeroLineColor: '#e3e6f0',
                                                drawBorder: false,
                                                borderDash: [2]
                                            },
                                            ticks: {
                                                min: 0,
                                                max: 100,
                                                beginAtZero: true,
                                                fontSize: 10,
                                                fontColor: '#858796',
                                                padding: 4,
                                                callback: function (val) {
                                                    return val + '%';
                                                }
                                            }
                                        }]
                                    },
                                    tooltips: {
                                        backgroundColor: 'rgba(255, 255, 255, 0.96)',
                                        bodyFontColor: '#4a5568',
                                        titleFontColor: '#1a202c',
                                        titleFontSize: 11,
                                        bodyFontSize: 11,
                                        borderColor: '#e2e8f0',
                                        borderWidth: 1,
                                        xPadding: 10,
                                        yPadding: 8,
                                        displayColors: true,
                                        caretPadding: 6,
                                        callbacks: {
                                            title: function (tooltipItems) {
                                                return cfg.name + ' • ' + tooltipItems[0].xLabel + ' (Klik titik untuk detail)';
                                            },
                                            label: function (tooltipItem, data) {
                                                var dsLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                                                var val = tooltipItem.yLabel;
                                                return ' ' + dsLabel + ': ' + val + '%';
                                            }
                                        }
                                    }
                                }
                            });
                        });
                    }

                    // Open Drill-Down Modal for a specific Month
                    window.openKpiModal = function (kpiId, monthIndex) {
                        var meta = KPI_CONFIGS[kpiId];
                        if (!meta) return;

                        var titleEl = document.getElementById('modal-kpi-title');
                        var targetEl = document.getElementById('modal-kpi-target');
                        var actualEl = document.getElementById('modal-kpi-actual');
                        var achEl = document.getElementById('modal-kpi-achievement');
                        var alertEl = document.getElementById('modal-kpi-alert');

                        var isSpecificMonth = (typeof monthIndex === 'number' && monthIndex >= 0 && monthIndex < 12);
                        var monthName = isSpecificMonth ? ALL_MONTHS[monthIndex] : '';

                        var currentYear = '';
                        var yearSel = document.getElementById('period-year-select');
                        if (yearSel && yearSel.value) currentYear = ' ' + yearSel.value;

                        if (titleEl) {
                            titleEl.textContent = meta.name + (isSpecificMonth ? ' — ' + monthName + currentYear : '');
                        }

                        var targetVal = meta.target;
                        var targetDisplay = meta.target_display;
                        var actualVal = 0;
                        var actualDisplay = '-';
                        var achievement = '-';
                        var isAchieved = false;
                        var isCritical = false;

                        if (isSpecificMonth && kpiDataCache && kpiDataCache.monthly_trends && kpiDataCache.monthly_trends[kpiId]) {
                            var series = kpiDataCache.monthly_trends[kpiId];
                            if (series.target && series.target[monthIndex] !== undefined) targetVal = series.target[monthIndex];
                            if (series.realisasi && series.realisasi[monthIndex] !== undefined) actualVal = series.realisasi[monthIndex];
                            actualDisplay = actualVal + '%';

                            if (actualVal > 0) {
                                if (kpiId === 'slow_moving') {
                                    isAchieved = (actualVal <= targetVal);
                                    achievement = isAchieved ? '100% of Target' : Math.round((targetVal / actualVal) * 100) + '% of Target';
                                } else if (kpiId === 'capacity') {
                                    isAchieved = (actualVal >= 70.0 && actualVal <= 80.0);
                                    achievement = Math.round((actualVal / 80.0) * 100) + '% of Target';
                                } else {
                                    isAchieved = (actualVal >= targetVal);
                                    achievement = Math.round((actualVal / targetVal) * 100) + '% of Target';
                                }
                                isCritical = !isAchieved;
                            }
                        } else if (kpiDataCache && kpiDataCache.kpi_list) {
                            var kpiItem = kpiDataCache.kpi_list.find(function (k) { return k.id === kpiId; });
                            if (kpiItem) {
                                targetDisplay = kpiItem.target_display;
                                actualDisplay = kpiItem.actual_display;
                                achievement = itemAchievement(kpiItem);
                                var s = (kpiItem.status || '').toLowerCase();
                                isAchieved = (s === 'tercapai' || s === 'achieved');
                                isCritical = (s === 'di bawah target' || s === 'critical');
                            }
                        }

                        if (targetEl) targetEl.textContent = targetDisplay;
                        if (actualEl) actualEl.textContent = actualDisplay;
                        if (achEl) achEl.textContent = achievement;

                        if (alertEl) {
                            if (actualDisplay === '-' || actualVal === 0) {
                                alertEl.className = 'alert alert-info py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-info-circle mr-2"></i><strong>Status Data: Standby</strong> — Realisasi ' + (isSpecificMonth ? 'bulan ' + monthName : 'periode ini') + ' dalam monitoring sistem.';
                            } else if (isAchieved) {
                                alertEl.className = 'alert alert-success py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i><strong>Status Performa: Tercapai</strong> — Realisasi memenuhi target yang ditentukan (' + actualDisplay + ').';
                            } else if (isCritical) {
                                alertEl.className = 'alert alert-danger py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i><strong>Status Performa: Perlu Tindak Lanjut</strong> — Realisasi berada di bawah standar target.';
                            } else {
                                alertEl.className = 'alert alert-warning py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i><strong>Status Performa: Perhatian</strong> — Perlu pemantauan berkala.';
                            }
                        }

                        if (typeof $ !== 'undefined') {
                            $('#kpiDetailModal').modal('show');
                        }
                    };

                    function itemAchievement(item) {
                        if (!item || item.actual_display === '-' || item.achievement === 0) return '-';
                        return item.achievement + '% of Target';
                    }

                })();
            </script>

</body>

</html>