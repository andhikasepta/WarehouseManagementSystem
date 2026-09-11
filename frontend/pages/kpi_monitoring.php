<?php
// frontend/pages/kpi_monitoring.php - Key Performance Indicators (KPI) Monitoring Dashboard
require_once __DIR__ . '/../../backend/auth.php';
if (!defined('SPA_MODE'))
    checkModuleAccess('kpi_monitoring');

$currentUser = getCurrentUser();
if (!defined('SPA_MODE')) {
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
                <?php } ?>

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

                    /* On KPI Monitoring, PILIH PERIODE DATA dropdown displays ONLY Tahun */
                    #month-select-group,
                    #batch-select-group,
                    #site-select-group,
                    #period-dropdown-menu .form-group:has(#period-month-select),
                    #period-dropdown-menu .form-group:has(#period-batch-select),
                    #period-dropdown-menu .form-group:has(#period-site-select) {
                        display: none !important;
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

                        <!-- Card 3: MR Closing (Akumulatif) SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('mr_closing')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        MR CLOSING (AKUMULATIF) SLA</div>
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

                        <!-- Card 6: Slow Moving SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('slow_moving')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        SLOW MOVING SLA</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-slow-moving">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 7: Capacity SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('capacity')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        CAPACITY SLA (UTILISASI SPACE)</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-capacity">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 8: Delivery Effectiveness SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-danger shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_effectiveness')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        DELIVERY EFFECTIVENESS SLA</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-delivery-eff">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 9: Efisiensi Delivery SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_efficiency')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"
                                        style="font-size: 0.72rem; line-height: 1.15;">
                                        EFISIENSI DELIVERY SLA</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800 mt-auto"
                                        style="line-height: 1.1;" id="card-val-delivery-idr">0.0%</div>
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
                                    <div>
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-chart-line mr-2"></i>Tren Evaluasi KPI Bulanan
                                        </h6>
                                        <span class="text-xs text-muted">Grafik menampilkan data achievement setiap
                                            bulan vs Target</span>
                                    </div>
                                    <div class="d-flex align-items-center mt-2 mt-sm-0">
                                        <div class="d-flex align-items-center mr-3">
                                            <span
                                                style="display:inline-block; width: 18px; height: 0px; border-top: 2px dashed #858796; margin-right: 6px;"></span>
                                            <span class="small font-weight-bold text-gray-700">Target</span>
                                        </div>
                                        <div class="d-flex align-items-center mr-3">
                                            <span
                                                style="display:inline-block; width: 14px; height: 3px; background-color: #4e73df; border-radius: 2px; margin-right: 6px;"></span>
                                            <span class="small font-weight-bold text-gray-700">Achievement
                                                Bulanan</span>
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
                                                        style="font-size: 0.7rem;">Target: ≥ 98.0%</span>
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
                                                    <span class="badge badge-info px-2 py-1 text-white"
                                                        style="font-size: 0.7rem;">Target: ≥ 98.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-registration_sla"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 3: MR Closing (Akumulatif) SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        MR Closing (Akumulatif) SLA</h6>
                                                    <span class="badge badge-success px-2 py-1"
                                                        style="font-size: 0.7rem;">Target: ≥ 90.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-mr_closing"></canvas>
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
                                                        Stock Opname Warehouse Hub</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #20c997;">Target: ≥
                                                        85.0%</span>
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
                                                        Stock Opname Outlet Warehouse</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #0dcaf0;">Target: ≥
                                                        85.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-stock_opname_outlet"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 6: Slow Moving SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Slow Moving SLA</h6>
                                                    <span class="badge badge-warning px-2 py-1 text-white"
                                                        style="font-size: 0.7rem;">Target: ≥ 85.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-slow_moving"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 7: Capacity SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Capacity SLA (Utilisasi Space)</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #6f42c1;">Target: ≥
                                                        90.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-capacity"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 8: Delivery Effectiveness SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Delivery Effectiveness SLA</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #e83e8c;">Target: ≥
                                                        97.0%</span>
                                                </div>
                                                <div class="card-body p-2 bg-white">
                                                    <div style="height: 180px; position: relative;">
                                                        <canvas id="kpi-chart-delivery_effectiveness"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chart 9: Efisiensi Delivery SLA (%) -->
                                        <div class="col-xl-4 col-md-6 col-12 mb-3"
                                            style="padding-left: 4px; padding-right: 4px;">
                                            <div class="card shadow-sm h-100 border-0" style="border-radius: 8px;">
                                                <div
                                                    class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                    <h6 class="m-0 font-weight-bold text-gray-800 small text-truncate">
                                                        Efisiensi Delivery SLA</h6>
                                                    <span class="badge px-2 py-1 text-white"
                                                        style="font-size: 0.7rem; background-color: #17a2b8;">Target: ≥
                                                        10.0%</span>
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
                            <!-- Metrics Quick Bar (Target vs Realisasi) -->
                            <div class="row text-center mb-4">
                                <div class="col-6 border-right">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Target</div>
                                    <div class="h5 font-weight-bold text-gray-800 mt-1 mb-0" id="modal-kpi-target">-
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold"
                                        id="modal-kpi-actual-lbl">Achievement
                                    </div>
                                    <div class="h5 font-weight-bold text-primary mt-1 mb-0" id="modal-kpi-actual">-
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

            <?php if (!defined('SPA_MODE')) {
                include FRONTEND_PATH . 'components/footer.php';
            } ?>

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
                        'receiving_sla': { name: 'Receiving (GR) SLA', code: 'KPI-IN-01', target: 98.0, target_display: '≥ 98.0%', color: '#4e73df', icon: 'fa-clipboard-check' },
                        'registration_sla': { name: 'Registration SLA', code: 'KPI-IN-02', target: 98.0, target_display: '≥ 98.0%', color: '#36b9cc', icon: 'fa-barcode' },
                        'mr_closing': { name: 'MR Closing (Akumulatif) SLA', code: 'KPI-OB-03', target: 90.0, target_display: '≥ 90.0%', color: '#1cc88a', icon: 'fa-check-double' },
                        'stock_opname': { name: 'MR Closing (Akumulatif) SLA', code: 'KPI-OB-03', target: 90.0, target_display: '≥ 90.0%', color: '#1cc88a', icon: 'fa-check-double' },
                        'stock_opname_hub': { name: 'Stock Opname Warehouse Hub', code: 'KPI-ST-01A', target: 85.0, target_display: '≥ 85.0%', color: '#20c997', icon: 'fa-warehouse' },
                        'stock_opname_outlet': { name: 'Stock Opname Outlet Warehouse', code: 'KPI-ST-01B', target: 85.0, target_display: '≥ 85.0%', color: '#0dcaf0', icon: 'fa-store' },
                        'slow_moving': { name: 'Slow Moving SLA', code: 'KPI-ST-02', target: 85.0, target_display: '≥ 85.0%', color: '#f6c23e', icon: 'fa-hourglass-half' },
                        'capacity': { name: 'Capacity SLA (Utilisasi Space)', code: 'KPI-ST-03', target: 90.0, target_display: '≥ 90.0%', color: '#6f42c1', icon: 'fa-warehouse' },
                        'delivery_effectiveness': { name: 'Delivery Effectiveness SLA', code: 'KPI-OB-01', target: 97.0, target_display: '≥ 97.0%', color: '#e83e8c', icon: 'fa-truck-fast' },
                        'delivery_efficiency': { name: 'Efisiensi Delivery SLA', code: 'KPI-OB-02', target: 10.0, target_display: '≥ 10.0%', color: '#17a2b8', icon: 'fa-percentage' }
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
                    $(function () {
                        // On KPI Monitoring, PILIH PERIODE DATA dropdown displays ONLY Tahun
                        $('#month-select-group, #period-month-select').closest('.form-group').hide();
                        $('#batch-select-group, #period-batch-select').closest('.form-group').hide();
                        $('#site-select-group, #period-site-select').closest('.form-group').hide();

                        var periodMenu = document.getElementById('period-dropdown-menu');
                        if (periodMenu) {
                            periodMenu.addEventListener('click', function (e) {
                                e.stopPropagation();
                            });
                        }

                        var yearSel = document.getElementById('period-year-select');
                        if (yearSel) yearSel.addEventListener('change', updateLoadButton);

                        var btnLoad = document.getElementById('btn-load-period');
                        if (btnLoad) {
                            btnLoad.addEventListener('click', function () {
                                var y = document.getElementById('period-year-select');
                                if (y && y.value) {
                                    loadDataForPeriod(y.value);
                                    if (periodMenu && typeof $ !== 'undefined') {
                                        $(periodMenu).closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
                                    }
                                }
                            });
                        }

                        var btnReset = document.getElementById('btn-reset-period');
                        if (btnReset) {
                            btnReset.addEventListener('click', function () {
                                if (yearSel) yearSel.value = '';
                                updateLoadButton();
                                resetKpiState();
                                var periodText = document.getElementById('selected-period-text');
                                if (periodText) periodText.textContent = 'PILIH PERIODE DATA';
                            });
                        }

                        loadPeriods();
                    });

                    // Populate selects helper
                    function populateSelect(selectId, items, placeholder) {
                        var sel = document.getElementById(selectId);
                        if (!sel) return;
                        sel.replaceChildren();
                        var defOpt = document.createElement('option');
                        defOpt.value = '';
                        defOpt.textContent = placeholder;
                        sel.appendChild(defOpt);

                        (items || []).forEach(function (item) {
                            var opt = document.createElement('option');
                            opt.value = item;
                            opt.textContent = String(item).toUpperCase();
                            sel.appendChild(opt);
                        });
                    }

                    function updateLoadButton() {
                        var y = document.getElementById('period-year-select');
                        var btn = document.getElementById('btn-load-period');
                        if (btn) {
                            // On KPI Monitoring, selecting Year is sufficient to view the full year trend
                            btn.disabled = !(y && y.value);
                        }
                    }

                    function loadPeriods(selectPeriod) {
                        fetch('api/get_periods.php?type=kpi')
                            .then(function (r) { return r.json(); })
                            .then(function (result) {
                                // Strictly use grouping years from uploaded KPI Master Data
                                var availableYears = (result.kpi_years && result.kpi_years.length > 0)
                                    ? result.kpi_years
                                    : ((result.years && result.years.length > 0) ? result.years : []);
                                availableYears = (availableYears || []).map(function (y) { return String(y); });

                                populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');

                                if (selectPeriod) {
                                    var yMatch = selectPeriod.toString().match(/(\d{4})/);
                                    if (yMatch) {
                                        var ySel = document.getElementById('period-year-select');
                                        if (ySel) ySel.value = yMatch[1];
                                        updateLoadButton();
                                        loadDataForPeriod(yMatch[1]);
                                    } else {
                                        loadDataForPeriod(selectPeriod);
                                    }
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
                        var cardIds = [
                            'card-val-receiving',
                            'card-val-registration',
                            'card-val-stock-opname',
                            'card-val-so-hub',
                            'card-val-so-outlet',
                            'card-val-slow-moving',
                            'card-val-capacity',
                            'card-val-delivery-eff',
                            'card-val-delivery-idr'
                        ];

                        cardIds.forEach(function (id) {
                            var el = document.getElementById(id);
                            if (el) el.textContent = '0.0%';
                        });

                        renderAllKpiCharts(getDefaultTrends());
                    }

                    // Fetch & render KPI Data for a selected period (Annual / Year)
                    function loadDataForPeriod(period) {
                        var rawYear = (period || '').toString().replace(/^TAHUN\s+/i, '').trim();
                        var periodText = document.getElementById('selected-period-text');
                        if (periodText) periodText.textContent = rawYear ? ('TAHUN ' + rawYear) : 'PILIH PERIODE DATA';

                        var apiUrl = 'api/get_kpi_data.php?year=' + encodeURIComponent(rawYear);

                        fetch(apiUrl)
                            .then(function (res) { return res.json(); })
                            .then(function (res) {
                                if (res.status === 'success') {
                                    kpiDataCache = res;
                                    var cards = res.cards || (res.data ? res.data.cards : null);
                                    var trends = res.monthly_trends || (res.data ? res.data.monthly_trends : null);
                                    if (cards) {
                                        renderKpiCards(cards);
                                    }
                                    if (trends) {
                                        renderAllKpiCharts(trends);
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

                    // Render 9 Top KPI Summary Metric Cards (Summary of the selected Year period)
                    function renderKpiCards(cards) {
                        if (!cards) return;

                        function setCardVal(id, item) {
                            var el = document.getElementById(id);
                            if (!el) return;
                            if (item && item.value_formatted) {
                                el.textContent = item.value_formatted;
                            } else {
                                el.textContent = '0.0%';
                            }
                        }

                        setCardVal('card-val-receiving', cards.receiving_sla);
                        setCardVal('card-val-registration', cards.registration_sla);
                        setCardVal('card-val-stock-opname', cards.mr_closing || cards.stock_opname);
                        setCardVal('card-val-so-hub', cards.stock_opname_hub);
                        setCardVal('card-val-so-outlet', cards.stock_opname_outlet);
                        setCardVal('card-val-slow-moving', cards.slow_moving);
                        setCardVal('card-val-capacity', cards.capacity);
                        setCardVal('card-val-delivery-eff', cards.delivery_effectiveness);
                        setCardVal('card-val-delivery-idr', cards.delivery_efficiency);
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
                            var realisasiData = kpiSeries.achievement || kpiSeries.realisasi || Array(12).fill(0);
                            var themeColor = kpiSeries.color || cfg.color;

                            // Destroy existing chart instance if any
                            if (kpiChartInstances[kpiId]) {
                                kpiChartInstances[kpiId].destroy();
                            }

                            var ctx = canvas.getContext('2d');

                            // Create gradient for achievement line fill
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
                                            label: 'Achievement',
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
                                                return cfg.name + ' • ' + tooltipItems[0].xLabel + ' (Data Bulanan — Klik titik untuk detail)';
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

                    // Open Drill-Down Modal for a specific Month or Period Summary
                    window.openKpiModal = function (kpiId, monthIndex) {
                        var meta = KPI_CONFIGS[kpiId];
                        if (!meta) return;

                        var titleEl = document.getElementById('modal-kpi-title');
                        var targetEl = document.getElementById('modal-kpi-target');
                        var actualEl = document.getElementById('modal-kpi-actual');
                        var achLblEl = document.getElementById('modal-kpi-actual-lbl');
                        var alertEl = document.getElementById('modal-kpi-alert');

                        var isSpecificMonth = (typeof monthIndex === 'number' && monthIndex >= 0 && monthIndex < 12);
                        var monthName = isSpecificMonth ? ALL_MONTHS[monthIndex] : '';

                        var currentYear = '2026';
                        var yearSel = document.getElementById('period-year-select');
                        if (yearSel && yearSel.value) {
                            currentYear = yearSel.value;
                        } else if (kpiDataCache && kpiDataCache.period && kpiDataCache.period.year) {
                            currentYear = kpiDataCache.period.year;
                        }

                        if (titleEl) {
                            titleEl.textContent = meta.name + (isSpecificMonth ? (' — ' + monthName + ' ' + currentYear) : (' — Summary Periode ' + currentYear));
                        }

                        if (achLblEl) {
                            achLblEl.textContent = isSpecificMonth ? ('Realisasi (' + monthName + ')') : ('Summary Realisasi (' + currentYear + ')');
                        }

                        var targetVal = meta.target;
                        var targetDisplay = meta.target_display;
                        var actualVal = 0;
                        var actualDisplay = '-';
                        var isAchieved = false;

                        if (isSpecificMonth && kpiDataCache && kpiDataCache.monthly_trends && kpiDataCache.monthly_trends[kpiId]) {
                            var series = kpiDataCache.monthly_trends[kpiId];
                            if (series.target && series.target[monthIndex] !== undefined) targetVal = series.target[monthIndex];
                            if (series.achievement && series.achievement[monthIndex] !== undefined) {
                                actualVal = series.achievement[monthIndex];
                            } else if (series.realisasi && series.realisasi[monthIndex] !== undefined) {
                                actualVal = series.realisasi[monthIndex];
                            }
                            actualDisplay = actualVal + '%';

                            if (actualVal > 0) {
                                isAchieved = (actualVal >= targetVal);
                            }
                        } else if (kpiDataCache && kpiDataCache.cards && kpiDataCache.cards[kpiId]) {
                            var cardItem = kpiDataCache.cards[kpiId];
                            targetVal = (typeof cardItem.target === 'number') ? cardItem.target : parseFloat(cardItem.target || 0);
                            targetDisplay = '≥ ' + targetVal.toFixed(1) + '%';
                            actualVal = (typeof cardItem.value === 'number') ? cardItem.value : parseFloat(cardItem.value || 0);
                            actualDisplay = cardItem.value_formatted || (actualVal.toFixed(1) + '%');
                            isAchieved = (actualVal >= targetVal);
                        } else if (kpiDataCache && kpiDataCache.kpi_list) {
                            var kpiItem = kpiDataCache.kpi_list.find(function (k) { return k.id === kpiId; });
                            if (kpiItem) {
                                targetDisplay = kpiItem.target_display;
                                actualDisplay = kpiItem.actual_display;
                                var s = (kpiItem.status || '').toLowerCase();
                                isAchieved = (s === 'sla tercapai' || s === 'tercapai' || s === 'achieved');
                            }
                        }

                        if (targetEl) targetEl.textContent = targetDisplay;
                        if (actualEl) actualEl.textContent = actualDisplay;

                        if (alertEl) {
                            var periodLabel = isSpecificMonth ? (monthName + ' ' + currentYear) : ('Periode ' + currentYear);
                            if (actualDisplay === '-' || actualVal === 0) {
                                alertEl.className = 'alert alert-info py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-info-circle mr-2"></i><strong>Status Data Info:</strong> ' + periodLabel + ' Belum Ada Data';
                            } else if (isAchieved) {
                                alertEl.className = 'alert alert-success py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i><strong>Status Data Info:</strong> ' + periodLabel + ' SLA Tercapai';
                            } else {
                                alertEl.className = 'alert alert-danger py-2 px-3 mb-0';
                                alertEl.innerHTML = '<i class="fas fa-times-circle mr-2"></i><strong>Status Data Info:</strong> ' + periodLabel + ' Tidak Tercapai';
                            }
                        }

                        if (typeof $ !== 'undefined') {
                            $('#kpiDetailModal').modal('show');
                        }
                    };

                })();
            </script>
            <?php if (!defined('SPA_MODE')): ?>

    </body>

    </html>
<?php endif; ?>