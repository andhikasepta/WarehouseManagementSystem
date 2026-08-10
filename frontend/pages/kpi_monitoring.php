<?php
// frontend/pages/kpi_monitoring.php - Key Performance Indicators (KPI) Monitoring Dashboard
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('kpi_monitoring');

$currentUser = getCurrentUser();
$pageTitle = 'KPI Monitoring - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<!-- Custom Styling for KPI Monitoring Page -->
<style>
    /* KPI Card Aesthetics */
    .kpi-metric-card {
        border-radius: 10px;
        transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.06);
        background: #ffffff;
    }

    .kpi-metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        border-color: rgba(30, 62, 98, 0.25);
    }

    .kpi-metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3.5px;
        background: #e2e8f0;
    }

    .kpi-metric-card.card-receiving::before { background: linear-gradient(90deg, #4e73df, #224abe); }
    .kpi-metric-card.card-registration::before { background: linear-gradient(90deg, #36b9cc, #1a8997); }
    .kpi-metric-card.card-stock-opname::before { background: linear-gradient(90deg, #1cc88a, #13855c); }
    .kpi-metric-card.card-slow-moving::before { background: linear-gradient(90deg, #f6c23e, #dda20a); }
    .kpi-metric-card.card-capacity::before { background: linear-gradient(90deg, #6f42c1, #59359a); }
    .kpi-metric-card.card-delivery-eff::before { background: linear-gradient(90deg, #e83e8c, #d63384); }
    .kpi-metric-card.card-delivery-idr::before { background: linear-gradient(90deg, #20c997, #0f9f75); }

    .kpi-metric-card .icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        transition: transform 0.25s ease;
    }

    .kpi-metric-card:hover .icon-badge {
        transform: scale(1.1);
    }

    .kpi-value-text {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.5px;
    }

    .kpi-unit-badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .kpi-target-tag {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
    }

    .kpi-trend-pill {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
    }

    /* Filter Toolbar */
    .kpi-toolbar-card {
        border-radius: 10px;
        background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);
        color: #ffffff;
        border: none;
    }

    .kpi-toolbar-select {
        background-color: rgba(255, 255, 255, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .kpi-toolbar-select option {
        background-color: #112236;
        color: #ffffff;
    }

    .btn-kpi-refresh {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }

    .btn-kpi-refresh:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
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

    /* Modal Styling */
    .kpi-detail-badge {
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
            <div id="content" class="flex-grow-1">

                <!-- Topbar Navigation -->
                <?php 
                $activePage = 'kpi_monitoring'; 
                include FRONTEND_PATH . 'components/navbar.php'; 
                ?>

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">

                    <!-- Top Filter & Header Toolbar -->
                    <div class="card shadow-sm kpi-toolbar-card mb-4">
                        <div class="card-body py-3 px-4">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                                <div class="mb-3 mb-lg-0">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle p-2 mr-3 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); width: 44px; height: 44px;">
                                            <i class="fas fa-tachometer-alt fa-lg text-white"></i>
                                        </div>
                                        <div>
                                            <h1 class="h4 mb-0 text-white font-weight-bold" style="letter-spacing: -0.3px;">KPI Monitoring Dashboard</h1>
                                            <span class="text-white-50 small">PT. Aplikanusa Lintasarta — Warehouse Management Performance</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter Controls -->
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="d-flex align-items-center mr-2 mb-2 mb-sm-0">
                                        <label for="kpi-filter-month" class="small text-white-50 font-weight-bold mr-2 mb-0">Bulan:</label>
                                        <select class="custom-select custom-select-sm kpi-toolbar-select" id="kpi-filter-month" style="min-width: 120px;">
                                            <option value="January">Januari</option>
                                            <option value="February">Februari</option>
                                            <option value="March">Maret</option>
                                            <option value="April">April</option>
                                            <option value="May">Mei</option>
                                            <option value="June">Juni</option>
                                            <option value="July">Juli</option>
                                            <option value="August">Agustus</option>
                                            <option value="September">September</option>
                                            <option value="October">Oktober</option>
                                            <option value="November">November</option>
                                            <option value="December">Desember</option>
                                        </select>
                                    </div>

                                    <div class="d-flex align-items-center mr-2 mb-2 mb-sm-0">
                                        <label for="kpi-filter-year" class="small text-white-50 font-weight-bold mr-2 mb-0">Tahun:</label>
                                        <select class="custom-select custom-select-sm kpi-toolbar-select" id="kpi-filter-year" style="min-width: 90px;">
                                            <option value="2026">2026</option>
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                        </select>
                                    </div>

                                    <div class="d-flex align-items-center mr-2 mb-2 mb-sm-0">
                                        <label for="kpi-filter-site" class="small text-white-50 font-weight-bold mr-2 mb-0">Site:</label>
                                        <select class="custom-select custom-select-sm kpi-toolbar-select" id="kpi-filter-site" style="min-width: 140px;">
                                            <option value="">Semua Site / HUB</option>
                                            <option value="Tekno Hub">Tekno Hub</option>
                                            <option value="TB Simatupang">TB Simatupang</option>
                                            <option value="Gajah Mada">Gajah Mada</option>
                                            <option value="Bintaro">Bintaro</option>
                                            <option value="Regional Barat">Regional Barat</option>
                                            <option value="Regional Tengah">Regional Tengah</option>
                                            <option value="Regional Timur">Regional Timur</option>
                                        </select>
                                    </div>

                                    <button class="btn btn-sm btn-kpi-refresh px-3 py-1 shadow-sm font-weight-bold" id="btn-refresh-kpi" title="Muat Ulang Data KPI">
                                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 1: The 7 KPI Metric Cards (Grid 4 + 3) -->
                    <!-- Section Title -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary px-2.5 py-1 mr-2" style="font-size: 0.72rem; font-weight: 700;">
                                <i class="fas fa-cubes mr-1"></i>7 Key Performance Indicators
                            </span>
                            <span class="text-muted small font-weight-bold" id="kpi-period-indicator">Periode: Memuat...</span>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-info-circle mr-1 text-primary"></i>Klik card untuk melihat detail kalkulasi &amp; formula
                        </div>
                    </div>

                    <!-- 7 KPI Cards Grid -->
                    <div class="row">
                        <!-- Card 1: Receiving (GR) SLA (%) -->
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-receiving" onclick="openKpiModal('receiving_sla')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-primary text-uppercase" style="letter-spacing: 0.5px;">Receiving (GR) SLA</div>
                                            <span class="kpi-target-tag">Target: ≥ 95.0%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #eef2ff; color: #4e73df;">
                                            <i class="fas fa-clipboard-check"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-receiving">0.0%</div>
                                        <span class="badge badge-success kpi-unit-badge">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-primary" id="bar-receiving" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Adherence PO Inbound</span>
                                            <span class="text-success font-weight-bold" style="font-size: 0.7rem;" id="diff-receiving">+1.5% vs target</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Registration SLA (%) -->
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-registration" onclick="openKpiModal('registration_sla')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-info text-uppercase" style="letter-spacing: 0.5px;">Registration SLA</div>
                                            <span class="kpi-target-tag">Target: ≥ 98.0%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #e0f7fa; color: #00838f;">
                                            <i class="fas fa-barcode"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-registration">0.0%</div>
                                        <span class="badge badge-info kpi-unit-badge">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-info" id="bar-registration" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Tagging &amp; Barcode SLA</span>
                                            <span class="text-info font-weight-bold" style="font-size: 0.7rem;" id="diff-registration">+0.2% on-time</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Stock Opname (%) -->
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-stock-opname" onclick="openKpiModal('stock_opname')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-success text-uppercase" style="letter-spacing: 0.5px;">Stock Opname</div>
                                            <span class="kpi-target-tag">Target: ≥ 99.5%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #e8f5e9; color: #2e7d32;">
                                            <i class="fas fa-boxes"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-stock-opname">0.0%</div>
                                        <span class="badge badge-success kpi-unit-badge">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-success" id="bar-stock-opname" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Akurasi Fisik vs Sistem</span>
                                            <span class="text-success font-weight-bold" style="font-size: 0.7rem;" id="diff-stock-opname">Audit Match Rate</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Slow Moving (%) -->
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-slow-moving" onclick="openKpiModal('slow_moving')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-warning text-uppercase" style="letter-spacing: 0.5px;">Slow Moving</div>
                                            <span class="kpi-target-tag">Maksimal: ≤ 15.0%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #fffde7; color: #f57f17;">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-slow-moving">0.0%</div>
                                        <span class="badge badge-warning kpi-unit-badge">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-warning" id="bar-slow-moving" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Rasio Aging &gt; 12 Bulan</span>
                                            <span class="text-warning font-weight-bold" style="font-size: 0.7rem;" id="diff-slow-moving">Good Control</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: Capacity (%) -->
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-capacity" onclick="openKpiModal('capacity')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-purple text-uppercase" style="color: #6f42c1; letter-spacing: 0.5px;">Capacity</div>
                                            <span class="kpi-target-tag">Optimal: 70.0% - 80.0%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #f3e8ff; color: #6f42c1;">
                                            <i class="fas fa-warehouse"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-capacity">0.0%</div>
                                        <span class="badge badge-purple kpi-unit-badge" style="background-color: #6f42c1; color: #fff;">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar" id="bar-capacity" role="progressbar" style="width: 0%; background-color: #6f42c1;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Utilisasi Rak &amp; Staging Area</span>
                                            <span class="font-weight-bold" style="color: #6f42c1; font-size: 0.7rem;" id="diff-capacity">Optimal Space</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: Delivery Effectiveness (%) -->
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-delivery-eff" onclick="openKpiModal('delivery_effectiveness')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase" style="color: #e83e8c; letter-spacing: 0.5px;">Delivery Effectiveness</div>
                                            <span class="kpi-target-tag">Target: ≥ 95.0%</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #fce4ec; color: #e83e8c;">
                                            <i class="fas fa-truck-fast"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-delivery-eff">0.0%</div>
                                        <span class="badge kpi-unit-badge" style="background-color: #e83e8c; color: #fff;">Persentase</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar" id="bar-delivery-eff" role="progressbar" style="width: 0%; background-color: #e83e8c;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Ketepatan MR &amp; DO Outbound</span>
                                            <span class="font-weight-bold" style="color: #e83e8c; font-size: 0.7rem;" id="diff-delivery-eff">+2.4% On-Time</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 7: Efisiensi Delivery (Idr Rupiah) -->
                        <div class="col-xl-4 col-md-12 mb-3">
                            <div class="card shadow-sm h-100 kpi-metric-card card-delivery-idr" onclick="openKpiModal('delivery_efficiency')">
                                <div class="card-body d-flex flex-column justify-content-between p-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase" style="color: #0f9f75; letter-spacing: 0.5px;">Efisiensi Delivery</div>
                                            <span class="kpi-target-tag">Target: ≥ Rp 130.000.000</span>
                                        </div>
                                        <div class="icon-badge" style="background-color: #e6fffa; color: #0f9f75;">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-baseline justify-content-between my-1">
                                        <div class="kpi-value-text text-gray-800" id="card-val-delivery-idr" style="font-size: 1.3rem;">Rp 0</div>
                                        <span class="badge badge-success kpi-unit-badge" style="background-color: #0f9f75;">IDR RUPIAH</span>
                                    </div>

                                    <div>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar" id="bar-delivery-idr" role="progressbar" style="width: 0%; background-color: #0f9f75;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.68rem;">Penghematan Biaya Logistik</span>
                                            <span class="font-weight-bold" style="color: #0f9f75; font-size: 0.7rem;" id="diff-delivery-idr">+14.2% Cost Saved</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Visual KPI Trend Charts -->
                    <div class="row">
                        <!-- Chart 1: SLA Performance Trend -->
                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line mr-2"></i>Tren Service Level Agreement (SLA) &amp; Delivery (%)
                                    </h6>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-light text-muted border px-2 py-1" style="font-size: 0.7rem;">12 Bulan</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area" style="height: 300px; position: relative;">
                                        <canvas id="kpiSlaTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart 2: Logistics Cost Efficiency (IDR Rupiah) -->
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-hand-holding-usd mr-2"></i>Efisiensi Biaya Delivery (IDR)
                                    </h6>
                                    <span class="badge badge-success font-weight-bold" style="font-size: 0.7rem;">Juta Rupiah</span>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="height: 300px; position: relative;">
                                        <canvas id="kpiEfficiencyIdrChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Inventory Health & Capacity Chart + Matrix Table -->
                    <div class="row">
                        <!-- Chart 3: Storage & Inventory Metrics -->
                        <div class="col-xl-5 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-gray-800">
                                        <i class="fas fa-cubes mr-2 text-primary"></i>Metrik Inventori &amp; Kapasitas (%)
                                    </h6>
                                    <span class="badge badge-primary" style="font-size: 0.7rem;">Stock vs Capacity</span>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area" style="height: 280px; position: relative;">
                                        <canvas id="kpiStorageMetricsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Matrix Table: All 7 KPIs Summary -->
                        <div class="col-xl-7 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-gray-800">
                                        <i class="fas fa-table mr-2 text-primary"></i>Matriks Evaluasi 7 KPI
                                    </h6>
                                    <span class="badge badge-info" id="badge-total-kpi">7 KPI Terpantau</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover kpi-matrix-table mb-0 w-100" id="tableKpiMatrix">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 45px;">No</th>
                                                    <th>Indikator KPI</th>
                                                    <th class="text-center">Target</th>
                                                    <th class="text-right">Realisasi</th>
                                                    <th class="text-center">Satuan</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center" style="width: 60px;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="kpi-matrix-body">
                                                <!-- Populated via Javascript -->
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
            <div class="modal fade" id="kpiDetailModal" tabindex="-1" role="dialog" aria-labelledby="kpiDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%); color: #ffffff;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 mr-3 d-flex align-items-center justify-content-center bg-white" id="modal-kpi-icon-wrapper" style="width: 40px; height: 40px;">
                                    <i class="fas fa-chart-pie fa-lg" id="modal-kpi-icon" style="color: #1e3e62;"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title font-weight-bold text-white mb-0" id="modal-kpi-title">Detail Indikator KPI</h5>
                                    <span class="text-white-50 small" id="modal-kpi-code">KPI-IN-01</span>
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
                                    <div class="h5 font-weight-bold text-gray-800 mt-1 mb-0" id="modal-kpi-target">-</div>
                                </div>
                                <div class="col-4 border-right">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Realisasi Aktual</div>
                                    <div class="h5 font-weight-bold text-primary mt-1 mb-0" id="modal-kpi-actual">-</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-xs text-muted text-uppercase font-weight-bold">Pencapaian</div>
                                    <div class="h5 font-weight-bold text-success mt-1 mb-0" id="modal-kpi-achievement">-</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="card bg-light border-0 mb-3" style="border-radius: 8px;">
                                <div class="card-body p-3">
                                    <h6 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.85rem;">
                                        <i class="fas fa-info-circle mr-1 text-primary"></i>Deskripsi &amp; Batasan Operasional
                                    </h6>
                                    <p class="text-muted small mb-0" id="modal-kpi-desc">-</p>
                                </div>
                            </div>

                            <!-- Calculation Formula -->
                            <div class="card bg-light border-0 mb-3" style="border-radius: 8px;">
                                <div class="card-body p-3">
                                    <h6 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.85rem;">
                                        <i class="fas fa-square-root-alt mr-1 text-primary"></i>Formula &amp; Rumus Perhitungan
                                    </h6>
                                    <div class="p-2 bg-white rounded border text-monospace font-weight-bold text-gray-800 small" id="modal-kpi-formula">-</div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 0.78rem; border-radius: 8px;">
                                <i class="fas fa-check-circle mr-1"></i> Data dihitung secara realtime dari pencatatan logistik, registrasi barcode, audit inventori, dan pemanfaatan kapasitas warehouse.
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-4 justify-content-between">
                            <span class="text-muted small">WMS Performance Monitoring • PT. Aplikanusa Lintasarta</span>
                            <button type="button" class="btn btn-secondary btn-sm px-3 font-weight-bold" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

<?php include FRONTEND_PATH . 'components/footer.php'; ?>

<!-- Chart.js Plugin -->
<script src="frontend/vendor/chart.js/Chart.min.js"></script>

<!-- KPI Monitoring Page Scripts -->
<script>
(function() {
    'use strict';

    var kpiDataCache = null;
    var chartSlaTrend = null;
    var chartEfficiencyIdr = null;
    var chartStorageMetrics = null;

    // Helper: Safe Number Format
    function formatNumberId(val) {
        return new Intl.NumberFormat('id-ID').format(val);
    }

    // Helper: Format Rupiah
    function formatRupiah(val) {
        return 'Rp ' + formatNumberId(val);
    }

    // Initialize Page
    document.addEventListener('DOMContentLoaded', function() {
        // Set Current Month & Year defaults in dropdowns
        var now = new Date();
        var monthNamesEng = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var currentMonthEng = monthNamesEng[now.getMonth()];
        var currentYear = now.getFullYear().toString();

        var monthSelect = document.getElementById('kpi-filter-month');
        var yearSelect = document.getElementById('kpi-filter-year');
        var siteSelect = document.getElementById('kpi-filter-site');

        if (monthSelect) monthSelect.value = currentMonthEng;
        if (yearSelect) yearSelect.value = currentYear;

        // Load KPI Data
        loadKpiData();

        // Event listeners for filters
        if (monthSelect) monthSelect.addEventListener('change', function() { loadKpiData(); });
        if (yearSelect) yearSelect.addEventListener('change', function() { loadKpiData(); });
        if (siteSelect) siteSelect.addEventListener('change', function() { loadKpiData(); });

        var refreshBtn = document.getElementById('btn-refresh-kpi');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                var icon = refreshBtn.querySelector('i');
                if (icon) icon.classList.add('fa-spin');
                loadKpiData(function() {
                    if (icon) icon.classList.remove('fa-spin');
                });
            });
        }
    });

    // Fetch KPI Data from API
    function loadKpiData(callback) {
        var monthVal = document.getElementById('kpi-filter-month') ? document.getElementById('kpi-filter-month').value : 'June';
        var yearVal = document.getElementById('kpi-filter-year') ? document.getElementById('kpi-filter-year').value : '2026';
        var siteVal = document.getElementById('kpi-filter-site') ? document.getElementById('kpi-filter-site').value : '';

        var apiUrl = 'api/get_kpi_data.php?month=' + encodeURIComponent(monthVal) + '&year=' + encodeURIComponent(yearVal);
        if (siteVal) {
            apiUrl += '&site=' + encodeURIComponent(siteVal);
        }

        fetch(apiUrl)
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.status === 'success') {
                    kpiDataCache = res;
                    renderKpiCards(res.cards, res.period);
                    renderKpiMatrixTable(res.kpi_list);
                    renderKpiCharts(res.trends);
                } else {
                    console.error('KPI Data Error:', res.message);
                }
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Failed to fetch KPI data:', err);
                if (callback) callback();
            });
    }

    // Render 7 KPI Cards
    function renderKpiCards(cards, period) {
        if (!cards) return;

        // Update Period Badge Indicator
        var periodInd = document.getElementById('kpi-period-indicator');
        if (periodInd && period) {
            periodInd.textContent = 'Periode: ' + (period.month_indo || period.month) + ' ' + period.year;
        }

        // 1. Receiving (GR) SLA (%)
        var cRec = cards.receiving_sla;
        if (cRec) {
            var elRecVal = document.getElementById('card-val-receiving');
            var elRecBar = document.getElementById('bar-receiving');
            var elRecDiff = document.getElementById('diff-receiving');
            if (elRecVal) elRecVal.textContent = cRec.value_formatted;
            if (elRecBar) elRecBar.style.width = Math.min(100, Math.max(0, cRec.value)) + '%';
            if (elRecDiff) elRecDiff.textContent = cRec.trend_diff;
        }

        // 2. Registration SLA (%)
        var cReg = cards.registration_sla;
        if (cReg) {
            var elRegVal = document.getElementById('card-val-registration');
            var elRegBar = document.getElementById('bar-registration');
            var elRegDiff = document.getElementById('diff-registration');
            if (elRegVal) elRegVal.textContent = cReg.value_formatted;
            if (elRegBar) elRegBar.style.width = Math.min(100, Math.max(0, cReg.value)) + '%';
            if (elRegDiff) elRegDiff.textContent = cReg.trend_diff;
        }

        // 3. Stock Opname (%)
        var cSo = cards.stock_opname;
        if (cSo) {
            var elSoVal = document.getElementById('card-val-stock-opname');
            var elSoBar = document.getElementById('bar-stock-opname');
            var elSoDiff = document.getElementById('diff-stock-opname');
            if (elSoVal) elSoVal.textContent = cSo.value_formatted;
            if (elSoBar) elSoBar.style.width = Math.min(100, Math.max(0, cSo.value)) + '%';
            if (elSoDiff) elSoDiff.textContent = cSo.trend_diff;
        }

        // 4. Slow Moving (%)
        var cSm = cards.slow_moving;
        if (cSm) {
            var elSmVal = document.getElementById('card-val-slow-moving');
            var elSmBar = document.getElementById('bar-slow-moving');
            var elSmDiff = document.getElementById('diff-slow-moving');
            if (elSmVal) elSmVal.textContent = cSm.value_formatted;
            if (elSmBar) elSmBar.style.width = Math.min(100, Math.max(0, (cSm.value / 25) * 100)) + '%';
            if (elSmDiff) elSmDiff.textContent = cSm.trend_diff;
        }

        // 5. Capacity (%)
        var cCap = cards.capacity;
        if (cCap) {
            var elCapVal = document.getElementById('card-val-capacity');
            var elCapBar = document.getElementById('bar-capacity');
            var elCapDiff = document.getElementById('diff-capacity');
            if (elCapVal) elCapVal.textContent = cCap.value_formatted;
            if (elCapBar) elCapBar.style.width = Math.min(100, Math.max(0, cCap.value)) + '%';
            if (elCapDiff) elCapDiff.textContent = cCap.trend_diff;
        }

        // 6. Delivery Effectiveness (%)
        var cDelEff = cards.delivery_effectiveness;
        if (cDelEff) {
            var elDelVal = document.getElementById('card-val-delivery-eff');
            var elDelBar = document.getElementById('bar-delivery-eff');
            var elDelDiff = document.getElementById('diff-delivery-eff');
            if (elDelVal) elDelVal.textContent = cDelEff.value_formatted;
            if (elDelBar) elDelBar.style.width = Math.min(100, Math.max(0, cDelEff.value)) + '%';
            if (elDelDiff) elDelDiff.textContent = cDelEff.trend_diff;
        }

        // 7. Efisiensi Delivery (Idr Rupiah)
        var cDelIdr = cards.delivery_efficiency;
        if (cDelIdr) {
            var elIdrVal = document.getElementById('card-val-delivery-idr');
            var elIdrBar = document.getElementById('bar-delivery-idr');
            var elIdrDiff = document.getElementById('diff-delivery-idr');
            if (elIdrVal) elIdrVal.textContent = cDelIdr.value_formatted;
            if (elIdrBar) elIdrBar.style.width = '88%';
            if (elIdrDiff) elIdrDiff.textContent = cDelIdr.trend_diff;
        }
    }

    // Render Matrix Table
    function renderKpiMatrixTable(kpiList) {
        var tbody = document.getElementById('kpi-matrix-body');
        if (!tbody || !kpiList) return;

        tbody.replaceChildren();

        kpiList.forEach(function(item, idx) {
            var tr = document.createElement('tr');

            // No
            var tdNo = document.createElement('td');
            tdNo.className = 'text-center font-weight-bold text-muted';
            tdNo.textContent = (idx + 1).toString();
            tr.appendChild(tdNo);

            // Name & Code
            var tdName = document.createElement('td');
            var nameDiv = document.createElement('div');
            nameDiv.className = 'font-weight-bold text-gray-800';
            nameDiv.textContent = item.name;

            var metaDiv = document.createElement('div');
            metaDiv.className = 'd-flex align-items-center mt-1';
            
            var codeSpan = document.createElement('span');
            codeSpan.className = 'badge badge-light border text-muted mr-2';
            codeSpan.style.fontSize = '0.68rem';
            codeSpan.textContent = item.code;

            var catSpan = document.createElement('span');
            catSpan.className = 'text-muted';
            catSpan.style.fontSize = '0.72rem';
            catSpan.textContent = item.category;

            metaDiv.appendChild(codeSpan);
            metaDiv.appendChild(catSpan);
            tdName.appendChild(nameDiv);
            tdName.appendChild(metaDiv);
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

            // Unit
            var tdUnit = document.createElement('td');
            tdUnit.className = 'text-center';
            var unitBadge = document.createElement('span');
            if (item.is_currency) {
                unitBadge.className = 'badge badge-success px-2 py-1';
                unitBadge.textContent = 'IDR Rupiah';
            } else {
                unitBadge.className = 'badge badge-info px-2 py-1';
                unitBadge.textContent = 'Persen (%)';
            }
            tdUnit.appendChild(unitBadge);
            tr.appendChild(tdUnit);

            // Status
            var tdStatus = document.createElement('td');
            tdStatus.className = 'text-center';
            var statusBadge = document.createElement('span');
            if (item.status === 'Achieved') {
                statusBadge.className = 'badge badge-success px-2 py-1';
                statusBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Tercapai';
            } else if (item.status === 'Warning') {
                statusBadge.className = 'badge badge-warning px-2 py-1';
                statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Perhatian';
            } else {
                statusBadge.className = 'badge badge-danger px-2 py-1';
                statusBadge.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Di Bawah';
            }
            tdStatus.appendChild(statusBadge);
            tr.appendChild(tdStatus);

            // Action button
            var tdAction = document.createElement('td');
            tdAction.className = 'text-center';
            var btnDetail = document.createElement('button');
            btnDetail.className = 'btn btn-outline-primary btn-sm rounded-circle';
            btnDetail.style.width = '30px';
            btnDetail.style.height = '30px';
            btnDetail.style.padding = '0';
            btnDetail.title = 'Detail KPI ' + item.name;
            btnDetail.innerHTML = '<i class="fas fa-eye" style="font-size: 0.75rem;"></i>';
            btnDetail.onclick = function() {
                openKpiModal(item.id);
            };
            tdAction.appendChild(btnDetail);
            tr.appendChild(tdAction);

            tbody.appendChild(tr);
        });
    }

    // Render Visual Charts
    function renderKpiCharts(trends) {
        if (!trends || typeof Chart === 'undefined') return;

        // Chart 1: SLA Performance Trend (Receiving, Registration, Delivery SLA)
        var ctxSla = document.getElementById('kpiSlaTrendChart');
        if (ctxSla) {
            if (chartSlaTrend) chartSlaTrend.destroy();
            chartSlaTrend = new Chart(ctxSla, {
                type: 'line',
                data: {
                    labels: trends.labels,
                    datasets: [
                        {
                            label: 'Receiving (GR) SLA (%)',
                            data: trends.receiving_sla,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#4e73df',
                            tension: 0.25
                        },
                        {
                            label: 'Registration SLA (%)',
                            data: trends.registration_sla,
                            borderColor: '#36b9cc',
                            backgroundColor: 'transparent',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#36b9cc',
                            tension: 0.25
                        },
                        {
                            label: 'Delivery Effectiveness (%)',
                            data: trends.delivery_effectiveness,
                            borderColor: '#e83e8c',
                            backgroundColor: 'transparent',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#e83e8c',
                            tension: 0.25
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                                return datasetLabel + ': ' + tooltipItem.yLabel + '%';
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                min: 80,
                                max: 100,
                                callback: function(val) { return val + '%'; }
                            },
                            gridLines: { color: '#eaecf4' }
                        }],
                        xAxes: [{
                            gridLines: { display: false }
                        }]
                    }
                }
            });
        }

        // Chart 2: Efisiensi Delivery (IDR Rupiah in Millions)
        var ctxEff = document.getElementById('kpiEfficiencyIdrChart');
        if (ctxEff) {
            if (chartEfficiencyIdr) chartEfficiencyIdr.destroy();
            var idrInMillions = trends.delivery_efficiency.map(function(val) {
                return Math.round(val / 1000000);
            });

            chartEfficiencyIdr = new Chart(ctxEff, {
                type: 'bar',
                data: {
                    labels: trends.labels,
                    datasets: [{
                        label: 'Penghematan (Juta Rp)',
                        data: idrInMillions,
                        backgroundColor: 'rgba(32, 201, 151, 0.75)',
                        borderColor: '#20c997',
                        borderWidth: 1.5,
                        borderRadius: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'Efisiensi: Rp ' + formatNumberId(tooltipItem.yLabel * 1000000);
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(val) { return 'Rp ' + val + 'M'; }
                            },
                            gridLines: { color: '#eaecf4' }
                        }],
                        xAxes: [{
                            gridLines: { display: false }
                        }]
                    }
                }
            });
        }

        // Chart 3: Storage Metrics (Stock Opname, Slow Moving, Capacity)
        var ctxStore = document.getElementById('kpiStorageMetricsChart');
        if (ctxStore) {
            if (chartStorageMetrics) chartStorageMetrics.destroy();
            chartStorageMetrics = new Chart(ctxStore, {
                type: 'bar',
                data: {
                    labels: trends.labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Stock Opname (%)',
                            data: trends.stock_opname,
                            borderColor: '#1cc88a',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 2.5,
                            yAxisID: 'y-axis-1'
                        },
                        {
                            type: 'bar',
                            label: 'Capacity (%)',
                            data: trends.capacity,
                            backgroundColor: 'rgba(111, 66, 193, 0.65)',
                            borderColor: '#6f42c1',
                            borderWidth: 1,
                            yAxisID: 'y-axis-1'
                        },
                        {
                            type: 'bar',
                            label: 'Slow Moving (%)',
                            data: trends.slow_moving,
                            backgroundColor: 'rgba(246, 194, 62, 0.65)',
                            borderColor: '#f6c23e',
                            borderWidth: 1,
                            yAxisID: 'y-axis-1'
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        yAxes: [{
                            id: 'y-axis-1',
                            ticks: {
                                beginAtZero: true,
                                max: 100,
                                callback: function(val) { return val + '%'; }
                            },
                            gridLines: { color: '#eaecf4' }
                        }],
                        xAxes: [{
                            gridLines: { display: false }
                        }]
                    }
                }
            });
        }
    }

    // Open Drill-Down Modal
    window.openKpiModal = function(kpiId) {
        if (!kpiDataCache || !kpiDataCache.kpi_list) return;

        var kpiItem = kpiDataCache.kpi_list.find(function(k) { return k.id === kpiId; });
        if (!kpiItem) return;

        var titleEl = document.getElementById('modal-kpi-title');
        var codeEl = document.getElementById('modal-kpi-code');
        var targetEl = document.getElementById('modal-kpi-target');
        var actualEl = document.getElementById('modal-kpi-actual');
        var achEl = document.getElementById('modal-kpi-achievement');
        var descEl = document.getElementById('modal-kpi-desc');
        var formulaEl = document.getElementById('modal-kpi-formula');
        var iconEl = document.getElementById('modal-kpi-icon');

        if (titleEl) titleEl.textContent = kpiItem.name;
        if (codeEl) codeEl.textContent = kpiItem.code + ' • ' + kpiItem.category;
        if (targetEl) targetEl.textContent = kpiItem.target_display;
        if (actualEl) actualEl.textContent = kpiItem.actual_display;
        if (achEl) achEl.textContent = kpiItem.achievement + '% of Target';
        if (descEl) descEl.textContent = kpiItem.description;
        if (formulaEl) formulaEl.textContent = kpiItem.formula;

        if (iconEl && kpiItem.icon) {
            iconEl.className = 'fas ' + kpiItem.icon + ' fa-lg';
            iconEl.style.color = kpiItem.color || '#1e3e62';
        }

        if (typeof $ !== 'undefined') {
            $('#kpiDetailModal').modal('show');
        }
    };

})();
</script>
