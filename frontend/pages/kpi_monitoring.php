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
                    /* Big KPI Metric Cards Height with Small Standard Font Sizes */
                    body #wrapper .container-fluid .card.kpi-metric-card,
                    .kpi-metric-card {
                        cursor: pointer !important;
                        min-height: 120px !important;
                        max-height: none !important;
                        height: 120px !important;
                        overflow: visible !important;
                        border-radius: 8px !important;
                        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
                    }

                    body #wrapper .container-fluid .card.kpi-metric-card:hover,
                    .kpi-metric-card:hover {
                        transform: translateY(-2px) !important;
                        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
                    }

                    body #wrapper .container-fluid .card.kpi-metric-card .card-body,
                    .kpi-metric-card .card-body {
                        height: 100% !important;
                        padding: 1.25rem 1.35rem !important;
                        display: flex !important;
                        flex-direction: column !important;
                        justify-content: space-between !important;
                        align-items: flex-start !important;
                        overflow: visible !important;
                    }

                    body #wrapper .container-fluid .card.kpi-metric-card .text-xs,
                    .kpi-metric-card .text-xs {
                        font-size: 0.72rem !important;
                        letter-spacing: 0.5px !important;
                        font-weight: 700 !important;
                        margin-bottom: 0.25rem !important;
                        line-height: 1.2 !important;
                    }

                    body #wrapper .container-fluid .card.kpi-metric-card .h5,
                    .kpi-metric-card .h5 {
                        font-size: 1.25rem !important;
                        font-weight: 700 !important;
                        line-height: 1.2 !important;
                        margin-top: auto !important;
                        margin-bottom: 0 !important;
                    }

                    /* Matrix Table Styles */
                    .kpi-matrix-table th {
                        background-color: #f8fafc;
                        color: #475569;
                        font-weight: 700;
                        font-size: 0.78rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        border-top: none;
                        border-bottom: 2px solid #e2e8f0;
                    }

                    .kpi-matrix-table td {
                        vertical-align: middle;
                        font-size: 0.82rem;
                    }
                </style>

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">KPI Monitoring</h1>
                    </div>

                    <!-- 9 KPI Metric Cards Grid (3 Columns across) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Card 1: Receiving (GR) SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('receiving_sla')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        RECEIVING (GR) SLA</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-receiving">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Registration SLA (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('registration_sla')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        REGISTRATION SLA</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-registration">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Stock Opname (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        STOCK OPNAME</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-stock-opname">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Stock Opname Warehouse Hub (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname_hub')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        STOCK OPNAME WAREHOUSE HUB</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-so-hub">0.0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: Stock Opname Outlet Warehouse (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('stock_opname_outlet')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        STOCK OPNAME OUTLET WAREHOUSE</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-so-outlet">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: Slow Moving (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('slow_moving')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        SLOW MOVING</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-slow-moving">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 7: Capacity (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('capacity')" title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        CAPACITY</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-capacity">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 8: Delivery Effectiveness (%) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-danger shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_effectiveness')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        DELIVERY EFFECTIVENESS</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-delivery-eff">0.0%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 9: Efisiensi Delivery (Idr Rupiah) -->
                        <div class="col-xl-4 col-md-4 col-sm-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2 kpi-metric-card"
                                onclick="openKpiModal('delivery_efficiency')"
                                title="Klik untuk detail kalkulasi &amp; formula">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        EFISIENSI DELIVERY</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-val-delivery-idr">Rp 0
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row: Matrix Table: All 9 KPIs Summary -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow mb-4">
                                <div
                                    class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-table mr-2"></i>Matriks Evaluasi KPI
                                    </h6>
                                    <span class="badge badge-info" id="badge-total-kpi">9 Indikator KPI</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover kpi-matrix-table mb-0 w-100"
                                            id="tableKpiMatrix">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 45px;">No</th>
                                                    <th>Indikator KPI</th>
                                                    <th class="text-center">Target</th>
                                                    <th class="text-right">Realisasi</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="kpi-matrix-body">
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">1</td>
                                                    <td class="font-weight-bold text-gray-800">Receiving (GR) SLA</td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 95.0%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">2</td>
                                                    <td class="font-weight-bold text-gray-800">Registration SLA</td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 98.0%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">3</td>
                                                    <td class="font-weight-bold text-gray-800">Stock Opname</td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 99.5%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">4</td>
                                                    <td class="font-weight-bold text-gray-800">Stock Opname Warehouse
                                                        Hub</td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 99.5%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">5</td>
                                                    <td class="font-weight-bold text-gray-800">Stock Opname Outlet
                                                        Warehouse</td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 99.5%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">6</td>
                                                    <td class="font-weight-bold text-gray-800">Slow Moving</td>
                                                    <td class="text-center font-weight-bold text-muted">≤ 15.0%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">7</td>
                                                    <td class="font-weight-bold text-gray-800">Capacity</td>
                                                    <td class="text-center font-weight-bold text-muted">70.0% - 80.0%
                                                    </td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">8</td>
                                                    <td class="font-weight-bold text-gray-800">Delivery Effectiveness
                                                    </td>
                                                    <td class="text-center font-weight-bold text-muted">≥ 95.0%</td>
                                                    <td class="text-right font-weight-bold text-primary">0%</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center font-weight-bold text-muted">9</td>
                                                    <td class="font-weight-bold text-gray-800">Efisiensi Delivery</td>
                                                    <td class="text-center font-weight-bold text-muted">Rp 130.000.000
                                                    </td>
                                                    <td class="text-right font-weight-bold text-primary">Rp 0</td>
                                                    <td class="text-center"><span
                                                            class="badge badge-secondary px-2 py-1">-</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
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
                        <div class="modal-header py-3 px-4"
                            style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); color: #ffffff;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 mr-3 d-flex align-items-center justify-content-center bg-white"
                                    id="modal-kpi-icon-wrapper" style="width: 40px; height: 40px;">
                                    <i class="fas fa-chart-pie fa-lg" id="modal-kpi-icon" style="color: #1e3e62;"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title font-weight-bold text-white mb-0" id="modal-kpi-title">Detail
                                        Indikator KPI</h5>
                                </div>
                            </div>
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

                            <!-- Alert (Empty text) -->
                            <div class="alert alert-info py-2 px-3 mb-0" id="modal-kpi-alert"
                                style="font-size: 0.78rem; border-radius: 8px; min-height: 38px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

            <!-- KPI Monitoring Page Scripts -->
            <script>
                (function () {
                    'use strict';

                    var kpiDataCache = null;
                    var ALL_MONTHS = [
                        "January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];

                    // Helper: Safe Number Format
                    function formatNumberId(val) {
                        return new Intl.NumberFormat('id-ID').format(val);
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

                    // Enable / disable "Tampilkan Data" button
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

                    // Load available periods from API
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

                    // Default 9 KPI List for Initial Page Load / Reset State (Realisasi: 0)
                    var DEFAULT_KPI_LIST = [
                        {
                            id: 'receiving_sla',
                            code: 'KPI-IN-01',
                            name: 'Receiving (GR) SLA',
                            category: 'Inbound Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 95.0%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'registration_sla',
                            code: 'KPI-IN-02',
                            name: 'Registration SLA',
                            category: 'Inbound Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 98.0%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'stock_opname',
                            code: 'KPI-ST-01',
                            name: 'Stock Opname',
                            category: 'Storage & Warehouse Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 99.5%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'stock_opname_hub',
                            code: 'KPI-ST-01A',
                            name: 'Stock Opname Warehouse Hub',
                            category: 'Storage & Warehouse Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 99.5%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'stock_opname_outlet',
                            code: 'KPI-ST-01B',
                            name: 'Stock Opname Outlet Warehouse',
                            category: 'Storage & Warehouse Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 99.5%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'slow_moving',
                            code: 'KPI-ST-02',
                            name: 'Slow Moving',
                            category: 'Storage & Warehouse Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≤ 15.0%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'capacity',
                            code: 'KPI-ST-03',
                            name: 'Capacity',
                            category: 'Storage & Warehouse Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '70.0% - 80.0%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'delivery_effectiveness',
                            code: 'KPI-OB-01',
                            name: 'Delivery Effectiveness',
                            category: 'Outbound Management',
                            unit: '%',
                            is_currency: false,
                            target_display: '≥ 95.0%',
                            actual_display: '0%',
                            status: '-'
                        },
                        {
                            id: 'delivery_efficiency',
                            code: 'KPI-OB-02',
                            name: 'Efisiensi Delivery',
                            category: 'Outbound Management',
                            unit: 'IDR',
                            is_currency: true,
                            target_display: 'Rp 130.000.000',
                            actual_display: 'Rp 0',
                            status: '-'
                        }
                    ];

                    // Reset KPI state to default (Realisasi: 0)
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

                        renderKpiMatrixTable(DEFAULT_KPI_LIST);
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
                                    renderKpiMatrixTable(res.kpi_list);
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

                    // Render 9 KPI Cards
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

                    // Render Matrix Table
                    function renderKpiMatrixTable(kpiList) {
                        var tbody = document.getElementById('kpi-matrix-body');
                        if (!tbody) return;

                        tbody.replaceChildren();

                        if (!kpiList || kpiList.length === 0) {
                            var trEmpty = document.createElement('tr');
                            var tdEmpty = document.createElement('td');
                            tdEmpty.colSpan = 5;
                            tdEmpty.className = 'text-center py-4 text-muted';
                            tdEmpty.textContent = 'Tidak ada data KPI untuk periode ini.';
                            trEmpty.appendChild(tdEmpty);
                            tbody.appendChild(trEmpty);
                            return;
                        }

                        kpiList.forEach(function (item, idx) {
                            var tr = document.createElement('tr');

                            // No
                            var tdNo = document.createElement('td');
                            tdNo.className = 'text-center font-weight-bold text-muted';
                            tdNo.textContent = (idx + 1).toString();
                            tr.appendChild(tdNo);

                            // Name
                            var tdName = document.createElement('td');
                            tdName.className = 'font-weight-bold text-gray-800';
                            tdName.textContent = item.name;
                            tr.appendChild(tdName);

                            // Target
                            var tdTarget = document.createElement('td');
                            tdTarget.className = 'text-center font-weight-bold text-muted';
                            tdTarget.textContent = item.target_display;
                            tr.appendChild(tdTarget);

                            // Actual
                            var tdActual = document.createElement('td');
                            tdActual.className = 'text-right font-weight-bold text-primary';
                            tdActual.textContent = item.actual_display;
                            tr.appendChild(tdActual);

                            // Status
                            var tdStatus = document.createElement('td');
                            tdStatus.className = 'text-center';
                            var statusBadge = document.createElement('span');
                            var s = (item.status || '').toLowerCase();
                            if (s === 'tercapai' || s === 'achieved') {
                                statusBadge.className = 'badge badge-success px-2 py-1';
                                statusBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Tercapai';
                            } else if (s === 'perhatian' || s === 'warning') {
                                statusBadge.className = 'badge badge-warning px-2 py-1';
                                statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Perhatian';
                            } else if (s === 'di bawah target' || s === 'critical' || s === 'di bawah' || s === 'below target' || s === 'not achieved') {
                                statusBadge.className = 'badge badge-danger px-2 py-1';
                                statusBadge.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Di Bawah Target';
                            } else {
                                statusBadge.className = 'badge badge-secondary px-2 py-1';
                                statusBadge.textContent = '-';
                            }
                            tdStatus.appendChild(statusBadge);
                            tr.appendChild(tdStatus);

                            tbody.appendChild(tr);
                        });
                    }

                    // Open Drill-Down Modal
                    window.openKpiModal = function (kpiId) {
                        if (!kpiDataCache || !kpiDataCache.kpi_list) {
                            // Fallback metadata dictionary if modal opened before selecting period
                            var defaultMetadata = {
                                'receiving_sla': { name: 'Receiving (GR) SLA', code: 'KPI-IN-01 • Inbound Management', target: '≥ 95.0%', icon: 'fa-clipboard-check', color: '#4e73df' },
                                'registration_sla': { name: 'Registration SLA', code: 'KPI-IN-02 • Inbound Management', target: '≥ 98.0%', icon: 'fa-barcode', color: '#36b9cc' },
                                'stock_opname': { name: 'Stock Opname', code: 'KPI-ST-01 • Storage & Warehouse Management', target: '≥ 99.5%', icon: 'fa-boxes', color: '#1cc88a' },
                                'stock_opname_hub': { name: 'Stock Opname Warehouse Hub', code: 'KPI-ST-01A • Storage & Warehouse Management', target: '≥ 99.5%', icon: 'fa-warehouse', color: '#1cc88a' },
                                'stock_opname_outlet': { name: 'Stock Opname Outlet Warehouse', code: 'KPI-ST-01B • Storage & Warehouse Management', target: '≥ 99.5%', icon: 'fa-store', color: '#36b9cc' },
                                'slow_moving': { name: 'Slow Moving', code: 'KPI-ST-02 • Storage & Warehouse Management', target: '≤ 15.0%', icon: 'fa-hourglass-half', color: '#f6c23e' },
                                'capacity': { name: 'Capacity', code: 'KPI-ST-03 • Storage & Warehouse Management', target: '70.0% - 80.0%', icon: 'fa-warehouse', color: '#6f42c1' },
                                'delivery_effectiveness': { name: 'Delivery Effectiveness', code: 'KPI-OB-01 • Outbound Management', target: '≥ 95.0%', icon: 'fa-truck-fast', color: '#e83e8c' },
                                'delivery_efficiency': { name: 'Efisiensi Delivery', code: 'KPI-OB-02 • Outbound Management', target: 'Rp 130.000.000', icon: 'fa-money-bill-wave', color: '#20c997' }
                            };

                            var meta = defaultMetadata[kpiId];
                            if (meta) {
                                var titleEl = document.getElementById('modal-kpi-title');
                                var codeEl = document.getElementById('modal-kpi-code');
                                var targetEl = document.getElementById('modal-kpi-target');
                                var actualEl = document.getElementById('modal-kpi-actual');
                                var achEl = document.getElementById('modal-kpi-achievement');
                                var iconEl = document.getElementById('modal-kpi-icon');

                                if (titleEl) titleEl.textContent = meta.name;
                                if (codeEl) codeEl.textContent = meta.code;
                                if (targetEl) targetEl.textContent = meta.target;
                                if (actualEl) actualEl.textContent = '-';
                                if (achEl) achEl.textContent = '-';
                                if (iconEl) {
                                    iconEl.className = 'fas ' + meta.icon + ' fa-lg';
                                    iconEl.style.color = meta.color;
                                }
                                if (typeof $ !== 'undefined') {
                                    $('#kpiDetailModal').modal('show');
                                }
                            }
                            return;
                        }

                        var kpiItem = kpiDataCache.kpi_list.find(function (k) { return k.id === kpiId; });
                        if (!kpiItem) return;

                        var titleEl = document.getElementById('modal-kpi-title');
                        var codeEl = document.getElementById('modal-kpi-code');
                        var targetEl = document.getElementById('modal-kpi-target');
                        var actualEl = document.getElementById('modal-kpi-actual');
                        var achEl = document.getElementById('modal-kpi-achievement');
                        var iconEl = document.getElementById('modal-kpi-icon');

                        if (titleEl) titleEl.textContent = kpiItem.name;
                        if (codeEl) codeEl.textContent = kpiItem.code + ' • ' + kpiItem.category;
                        if (targetEl) targetEl.textContent = kpiItem.target_display;
                        if (actualEl) actualEl.textContent = kpiItem.actual_display;
                        if (achEl) achEl.textContent = (itemAchievement(kpiItem));

                        if (iconEl && kpiItem.icon) {
                            iconEl.className = 'fas ' + kpiItem.icon + ' fa-lg';
                            iconEl.style.color = kpiItem.color || '#1e3e62';
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