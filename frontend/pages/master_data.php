<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('master_data');

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

$canAccessInboundMaster = hasPermission('master_data_inbound', 'view');
$canAccessStorageMaster = hasPermission('master_data_storage', 'view');
$canAccessOutboundMaster = hasPermission('master_data_outbound', 'view');

// Fallback: If user has main module access and no other master data sub-modules assigned
if (!$canAccessInboundMaster && hasPermission('inbound', 'view') && !in_array('master_data_storage', $userModules) && !in_array('master_data_outbound', $userModules)) {
    $canAccessInboundMaster = true;
}
if (!$canAccessStorageMaster && hasPermission('warehouse', 'view') && !in_array('master_data_inbound', $userModules) && !in_array('master_data_outbound', $userModules)) {
    $canAccessStorageMaster = true;
}
if (!$canAccessOutboundMaster && hasPermission('outbound', 'view') && !in_array('master_data_inbound', $userModules) && !in_array('master_data_storage', $userModules)) {
    $canAccessOutboundMaster = true;
}

// Superadmin has full access to all 3 tabs
if ($userRole === 'superadmin') {
    $canAccessInboundMaster = true;
    $canAccessStorageMaster = true;
    $canAccessOutboundMaster = true;
}

$defaultMasterSegment = '';
if ($canAccessInboundMaster) {
    $defaultMasterSegment = 'inbound';
} elseif ($canAccessStorageMaster) {
    $defaultMasterSegment = 'storage';
} elseif ($canAccessOutboundMaster) {
    $defaultMasterSegment = 'outbound';
}

$canAddInbound = ($userRole === 'superadmin') || canAdd('master_data_inbound') || canAdd('inbound');
$canDeleteInbound = ($userRole === 'superadmin') || canDelete('master_data_inbound') || canDelete('inbound');

$canAddStorage = ($userRole === 'superadmin') || canAdd('master_data_storage') || canAdd('warehouse');
$canDeleteStorage = ($userRole === 'superadmin') || canDelete('master_data_storage') || canDelete('warehouse');

$canAddOutbound = ($userRole === 'superadmin') || canAdd('master_data_outbound') || canAdd('outbound');
$canDeleteOutbound = ($userRole === 'superadmin') || canDelete('master_data_outbound') || canDelete('outbound');

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>
<script>
    window.currentUserRole = <?php echo json_encode($userRole); ?>;
    window.userCanAddStorage = <?php echo $canAddStorage ? 'true' : 'false'; ?>;
    window.userCanDeleteStorage = <?php echo $canDeleteStorage ? 'true' : 'false'; ?>;
    window.userCanAddInbound = <?php echo $canAddInbound ? 'true' : 'false'; ?>;
    window.userCanDeleteInbound = <?php echo $canDeleteInbound ? 'true' : 'false'; ?>;
    window.userCanAddOutbound = <?php echo $canAddOutbound ? 'true' : 'false'; ?>;
    window.userCanDeleteOutbound = <?php echo $canDeleteOutbound ? 'true' : 'false'; ?>;
</script>

<!-- DataTables CSS -->
<link href="frontend/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Basic Select2 Bootstrap 4 overrides */
    .select2-container .select2-selection--single {
        height: 31px;
        border: 1px solid #d1d3e2;
        border-radius: 0.2rem;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px;
    }
</style>

<style>
    .nav-tabs .nav-link {
        font-weight: bold;
    }

    .nav-tabs .nav-link.active {
        color: #4e73df;
    }

    .nav-pills .nav-link {
        color: #5a5c69;
        border-radius: 0.35rem;
        transition: all 0.2s ease-in-out;
    }

    .nav-pills .nav-link.active {
        background-color: #4e73df;
        color: #fff;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .table-responsive {
        overflow-x: auto;
    }

    #dataTableAsset td:nth-child(1) {
        white-space: normal !important;
        min-width: 200px;
    }
</style>

</head>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <!-- Topbar -->
                <?php
                $activePage = 'master_data';
                $hidePeriodSelector = true;
                include FRONTEND_PATH . 'components/navbar.php';
                ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid" style="padding-top: 100px;">
                    <div class="mb-4 pb-3 border-bottom">
                        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                            Master Data
                        </h1>
                    </div>

                    <!-- Top-Level Segment Navigation (All 3 segments displayed, disabled if non-assigned) -->
                    <ul class="nav nav-pills nav-justified mb-4 p-2 bg-light rounded shadow-sm" id="masterSegmentTabs"
                        role="tablist" style="border: 1px solid #e3e6f0;">
                        <!-- Inbound Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessInboundMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'inbound') ? 'active' : ''; ?>"
                                    id="seg-inbound-tab" data-toggle="pill" href="#seg-inbound" role="tab"
                                    aria-controls="seg-inbound"
                                    aria-selected="<?php echo ($defaultMasterSegment === 'inbound') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-box-open mr-2"></i> Inbound Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted"
                                    id="seg-inbound-tab" href="javascript:void(0)"
                                    style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;"
                                    title="Modul ini terkunci (Khusus Hak Akses Inbound Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Inbound Master Data
                                </a>
                            <?php endif; ?>
                        </li>

                        <!-- Storage Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessStorageMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'storage') ? 'active' : ''; ?>"
                                    id="seg-storage-tab" data-toggle="pill" href="#seg-storage" role="tab"
                                    aria-controls="seg-storage"
                                    aria-selected="<?php echo ($defaultMasterSegment === 'storage') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-warehouse mr-2"></i> Storage Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted"
                                    id="seg-storage-tab" href="javascript:void(0)"
                                    style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;"
                                    title="Modul ini terkunci (Khusus Hak Akses Storage Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Storage Master Data
                                </a>
                            <?php endif; ?>
                        </li>

                        <!-- Outbound Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessOutboundMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'outbound') ? 'active' : ''; ?>"
                                    id="seg-outbound-tab" data-toggle="pill" href="#seg-outbound" role="tab"
                                    aria-controls="seg-outbound"
                                    aria-selected="<?php echo ($defaultMasterSegment === 'outbound') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-truck-loading mr-2"></i> Outbound Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted"
                                    id="seg-outbound-tab" href="javascript:void(0)"
                                    style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;"
                                    title="Modul ini terkunci (Khusus Hak Akses Outbound Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Outbound Master Data
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>

                    <!-- Master Segments Content -->
                    <div class="tab-content" id="masterSegmentTabsContent">

                        <?php if ($canAccessInboundMaster): ?>
                            <!-- 1. INBOUND MASTER DATA -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'inbound') ? 'show active' : ''; ?>"
                                id="seg-inbound" role="tabpanel" aria-labelledby="seg-inbound-tab">
                                <?php if ($canAddInbound || $canDeleteInbound): ?>
                                    <div
                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-box-open mr-2"></i>Menu Master Data Inbound
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <?php if ($canAddInbound): ?>
                                                <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                    data-toggle="modal" data-target="#uploadExcelModalInbound">
                                                    <i class="fas fa-file-import mr-1"></i> Import Excel Inbound
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($canDeleteInbound): ?>
                                                <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                    data-target="#deleteDataModalInbound">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Data Inbound
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                    <div
                                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data Inbound</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Filter Control Bar (Dropdowns & Reset) -->
                                        <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                            <div class="card-body py-3 px-4">
                                                <div class="form-row align-items-end">
                                                    <!-- Periode Group Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-inbound-periode"
                                                            class="small font-weight-bold text-gray-700 mb-1">Periode
                                                            Group</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-inbound-periode">
                                                            <option value="">Semua Periode</option>
                                                        </select>
                                                    </div>

                                                    <!-- Bagian Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-inbound-bagian"
                                                            class="small font-weight-bold text-gray-700 mb-1">Bagian</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-inbound-bagian">
                                                            <option value="">Semua Bagian</option>
                                                        </select>
                                                    </div>

                                                    <!-- PIC Teknis Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-inbound-pic"
                                                            class="small font-weight-bold text-gray-700 mb-1">PIC
                                                            Teknis</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-inbound-pic">
                                                            <option value="">Semua PIC Teknis</option>
                                                        </select>
                                                    </div>

                                                    <!-- Item Kategori Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-inbound-kategori"
                                                            class="small font-weight-bold text-gray-700 mb-1">Item
                                                            Kategori</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-inbound-kategori">
                                                            <option value="">Semua Kategori</option>
                                                        </select>
                                                    </div>

                                                    <!-- No. PR / PO Search Input -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-inbound-po"
                                                            class="small font-weight-bold text-gray-700 mb-1">No. PR /
                                                            PO</label>
                                                        <input type="text" class="form-control form-control-sm"
                                                            id="filter-inbound-po" placeholder="Search...">
                                                    </div>

                                                    <!-- Reset Filter Button -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <button
                                                            class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                            type="button" id="btn-reset-filter-inbound">
                                                            <i class="fas fa-undo mr-1"></i> Reset Filter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="dataTableInbound" width="100%"
                                                cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>PR Nomor</th>
                                                        <th>PR Kode Site</th>
                                                        <th>PR Nama Site</th>
                                                        <th>PR Item Kategori</th>
                                                        <th>PR PIC Teknis Nama</th>
                                                        <th>PR Nama Bagian</th>
                                                        <th>PR Nama Divisi</th>
                                                        <th>PR Regional</th>
                                                        <th>PR Jenis MA</th>
                                                        <th>PO Nomor</th>
                                                        <th>PO Deskripsi</th>
                                                        <th>PO Vendor</th>
                                                        <th>PO Tgl. Generate</th>
                                                        <th>PO Nama Item</th>
                                                        <th>PO Qty Item</th>
                                                        <th>PO UoM Item</th>
                                                        <th>PO Target Delivery</th>
                                                        <th>Project ID</th>
                                                        <th>Periode Group</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Populated dynamically via DataTables -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canAccessStorageMaster): ?>
                            <!-- 2. STORAGE MASTER DATA (FUNCTIONAL SUB MASTER DATA) -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'storage') ? 'show active' : ''; ?>"
                                id="seg-storage" role="tabpanel" aria-labelledby="seg-storage-tab">

                                <!-- Storage Action Buttons Header Bar -->
                                <?php if ($canAddStorage || $canDeleteStorage): ?>
                                    <div
                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border" id="storage-action-buttons">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-warehouse mr-2"></i>Menu Master Data Storage
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <?php if ($canAddStorage): ?>
                                                <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                    data-toggle="modal" data-target="#uploadExcelModal">
                                                    <i class="fas fa-file-import mr-1"></i> Import Excel Storage
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($canDeleteStorage): ?>
                                                <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                    data-target="#deleteDataModal">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Data Storage
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Sub Master Data Tabs for Storage -->
                                <ul class="nav nav-tabs mb-4" id="masterDataTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" id="asset-tab" data-toggle="tab" href="#asset-data"
                                            role="tab" aria-controls="asset-data" aria-selected="true">
                                            <i class="fas fa-boxes mr-1"></i> Data Asset
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="rack-tab" data-toggle="tab" href="#rack-data" role="tab"
                                            aria-controls="rack-data" aria-selected="false">
                                            <i class="fas fa-th mr-1"></i> Data Utilisasi Rack
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="utilisasi-tab" data-toggle="tab" href="#utilisasi-data"
                                            role="tab" aria-controls="utilisasi-data" aria-selected="false">
                                            <i class="fas fa-chart-pie mr-1"></i> Utilisasi Area/Rack
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="masterDataTabsContent">

                                    <!-- Asset Data Tab -->
                                    <div class="tab-pane fade show active" id="asset-data" role="tabpanel"
                                        aria-labelledby="asset-tab">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div
                                                class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Data Asset</h6>
                                            </div>
                                            <div class="card-body">

                                                <!-- Custom Filters for Asset -->
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <label>Periode (Month/Year):</label>
                                                        <select id="filterAssetPeriode"
                                                            class="form-control form-control-sm">
                                                            <option value="">All Periods</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Sub Location:</label>
                                                        <select id="filterAssetSubLocation"
                                                            class="form-control form-control-sm">
                                                            <option value="">All Sub Locations</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-end justify-content-end"
                                                        id="assetSearchContainer">
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm" id="dataTableAsset"
                                                        width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Spec Code</th>
                                                                <th>Spec Name</th>
                                                                <th>Reg No</th>
                                                                <th>Asset Planner Org</th>
                                                                <th>NBV</th>
                                                                <th>SO Result</th>
                                                                <th>SO Location</th>
                                                                <th>Range</th>
                                                                <th>Sub Location</th>
                                                                <th>Category</th>
                                                                <th>Periode Group</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Populated by JS -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rack Data Tab -->
                                    <div class="tab-pane fade" id="rack-data" role="tabpanel" aria-labelledby="rack-tab">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Master Utilisasi Rack
                                                </h6>
                                            </div>
                                            <div class="card-body">

                                                <!-- Custom Filters for Rack -->
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <label>Category:</label>
                                                        <select id="filterRackCategory"
                                                            class="form-control form-control-sm">
                                                            <option value="">All Categories</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Rack Name:</label>
                                                        <select id="filterRackName" class="form-control form-control-sm">
                                                            <option value="">All Racks</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-end justify-content-end"
                                                        id="rackSearchContainer">
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm" id="dataTableRack"
                                                        width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Label (Sub Location)</th>
                                                                <th>Rack Group</th>
                                                                <th>Category</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Populated by JS -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Utilisasi Area/Rack Tab -->
                                    <div class="tab-pane fade" id="utilisasi-data" role="tabpanel"
                                        aria-labelledby="utilisasi-tab">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div
                                                class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Data Utilisasi Area / Rack
                                                </h6>
                                                <?php if ($canAddStorage): ?>
                                                    <button class="btn btn-success btn-sm" type="button"
                                                        id="btn-save-utilisasi-all" disabled>
                                                        <i class="fas fa-save mr-1"></i> Simpan Semua
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">

                                                <!-- Period Selector -->
                                                <div class="row mb-3">
                                                    <div class="col-md-3">
                                                        <label class="small font-weight-bold text-gray-600">Bulan <span
                                                                class="text-danger">*</span></label>
                                                        <select id="utilisasi-month-select"
                                                            class="form-control form-control-sm">
                                                            <option value="">-- Pilih Bulan --</option>
                                                            <option value="January">January</option>
                                                            <option value="February">February</option>
                                                            <option value="March">March</option>
                                                            <option value="April">April</option>
                                                            <option value="May">May</option>
                                                            <option value="June">June</option>
                                                            <option value="July">July</option>
                                                            <option value="August">August</option>
                                                            <option value="September">September</option>
                                                            <option value="October">October</option>
                                                            <option value="November">November</option>
                                                            <option value="December">December</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="small font-weight-bold text-gray-600">Tahun <span
                                                                class="text-danger">*</span></label>
                                                        <select id="utilisasi-year-select"
                                                            class="form-control form-control-sm">
                                                            <option value="">-- Pilih Tahun --</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 d-flex align-items-end">
                                                        <button class="btn btn-primary btn-sm btn-block"
                                                            id="btn-load-utilisasi" disabled>
                                                            <i class="fas fa-search mr-1"></i> Tampilkan Data
                                                        </button>
                                                    </div>
                                                    <div class="col-md-3 d-flex align-items-end justify-content-end"
                                                        id="utilisasiSearchContainer">
                                                    </div>
                                                </div>

                                                <div id="utilisasi-table-info" class="text-center text-gray-500 py-4"
                                                    style="display:block;">
                                                    <i class="fas fa-info-circle fa-2x mb-2 text-gray-300"></i>
                                                    <p class="mb-0">Pilih <strong>Bulan</strong> dan <strong>Tahun</strong>
                                                        lalu klik <strong>Tampilkan Data</strong> untuk memuat data
                                                        utilisasi.</p>
                                                </div>

                                                <div class="table-responsive" id="utilisasi-table-wrapper"
                                                    style="display:none; max-height: 500px; overflow-y: auto;">
                                                    <table class="table table-bordered table-sm table-hover"
                                                        id="dataTableUtilisasi" width="100%" cellspacing="0">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">
                                                                    Label (Sub Location)</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">
                                                                    Rack Group</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">
                                                                    Category</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1; width: 120px;">
                                                                    Qty (Unit)</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1; width: 140px;">
                                                                    Capacity (%)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="utilisasi-table-body">
                                                            <!-- Populated by JS -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canAccessOutboundMaster): ?>
                            <!-- 3. OUTBOUND MASTER DATA -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'outbound') ? 'show active' : ''; ?>"
                                id="seg-outbound" role="tabpanel" aria-labelledby="seg-outbound-tab">
                                <?php if ($canAddOutbound || $canDeleteOutbound): ?>
                                    <div
                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-truck-loading mr-2"></i>Menu Master Data Outbound
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <?php if ($canAddOutbound): ?>
                                                <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                    data-toggle="modal" data-target="#uploadExcelModalOutbound">
                                                    <i class="fas fa-file-import mr-1"></i> Import Excel Outbound
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($canDeleteOutbound): ?>
                                                <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                    data-target="#deleteDataModalOutbound">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Data Outbound
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                    <div
                                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data Outbound</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Filter Control Bar (Dropdowns & Reset) -->
                                        <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                            <div class="card-body py-3 px-4">
                                                <div class="form-row align-items-end">
                                                    <!-- Periode Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-outbound-periode"
                                                            class="small font-weight-bold text-gray-700 mb-1">Periode</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-outbound-periode">
                                                            <option value="">Semua Periode</option>
                                                        </select>
                                                    </div>

                                                    <!-- Site Destination Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-tujuan-site-outbound"
                                                            class="small font-weight-bold text-gray-700 mb-1">Site
                                                            Destination</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-tujuan-site-outbound">
                                                            <option value="">Semua Site Destination</option>
                                                        </select>
                                                    </div>

                                                    <!-- MR Status Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-mr-status-outbound"
                                                            class="small font-weight-bold text-gray-700 mb-1">MR
                                                            Status</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-mr-status-outbound">
                                                            <option value="">Semua MR Status</option>
                                                        </select>
                                                    </div>

                                                    <!-- DN Status Dropdown -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-dn-status-outbound"
                                                            class="small font-weight-bold text-gray-700 mb-1">DN
                                                            Status</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-dn-status-outbound">
                                                            <option value="">Semua DN Status</option>
                                                        </select>
                                                    </div>

                                                    <!-- Search No. MR / PCK / DN / PO -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-outbound-mr"
                                                            class="small font-weight-bold text-gray-700 mb-1">Cari
                                                            Dokumen</label>
                                                        <input type="text" class="form-control form-control-sm"
                                                            id="filter-outbound-mr"
                                                            placeholder="Search...">
                                                    </div>

                                                    <!-- Reset Filter Button -->
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <button
                                                            class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                            type="button" id="btn-reset-filter-outbound">
                                                            <i class="fas fa-undo mr-1"></i> Reset Filter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm text-nowrap" id="dataTableOutbound"
                                                width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>MR NO</th>
                                                        <th>MR TYPE</th>
                                                        <th>MR DESC</th>
                                                        <th>MR STATUS</th>
                                                        <th>PCK NO</th>
                                                        <th>PCK DETAIL</th>
                                                        <th>PCK STATUS</th>
                                                        <th>AWB</th>
                                                        <th>DN NO</th>
                                                        <th>PR NO</th>
                                                        <th>PO NO</th>
                                                        <th>FROM</th>
                                                        <th>SITE ORIGIN</th>
                                                        <th>SITE ORIGIN ADDR</th>
                                                        <th>TO</th>
                                                        <th>SITE DESTINATION</th>
                                                        <th>SITE DESTINATION ADDR</th>
                                                        <th>PICKUP TYPE</th>
                                                        <th>VIA</th>
                                                        <th>LT</th>
                                                        <th>DELIVERY TARGET</th>
                                                        <th>DN STATUS</th>
                                                        <th>LAST LOG</th>
                                                        <th>PERIODE GROUP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Populated dynamically via DataTables -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

            <!-- Delete Data Modal-->
            <div class="modal fade" id="deleteDataModal" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Data
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"
                                style="opacity: 0.8;">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body">
                            <div class="p-3">
                                <div class="text-center text-gray-600 mb-4">
                                    <h3 class="text-danger font-weight-bold mb-3"><i
                                            class="fas fa-exclamation-triangle mr-2"></i>Peringatan</h3>
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus secara
                                        permanen dari sistem dan tidak dapat dikembalikan.</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteMonthSelect"
                                        class="small font-weight-bold text-gray-600">Bulan</label>
                                    <select class="form-control form-control-sm" id="deleteMonthSelect">
                                        <option value="">-- Pilih Bulan --</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteBatchSelect"
                                        class="small font-weight-bold text-gray-600">Batch</label>
                                    <select class="form-control form-control-sm" id="deleteBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="deleteYearSelect"
                                        class="small font-weight-bold text-gray-600">Tahun</label>
                                    <select class="form-control form-control-sm" id="deleteYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Cancel</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Delete Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="uploadExcelModal" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document" id="uploadExcelModalDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header bg-success text-white">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Excel Data
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body">
                            <!-- Download Template Section -->
                            <div
                                class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold text-gray-700">
                                    <i class="fas fa-download mr-1 text-success"></i> Download Template:
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success font-weight-bold mr-1"
                                        id="btn-template-asset">
                                        <i class="fas fa-file-excel mr-1"></i> Template Asset
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info font-weight-bold"
                                        id="btn-template-rack">
                                        <i class="fas fa-file-excel mr-1"></i> Template Rack
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" id="uploadModalLeftCol">
                                    <div class="form-group mb-3">
                                        <label for="upload-data-type" class="small font-weight-bold text-gray-600">Tipe
                                            Data</label>
                                        <select class="form-control form-control-sm" id="upload-data-type">
                                            <option value="asset">Data Asset</option>
                                            <option value="rack">Data Utilisasi Rack</option>
                                        </select>
                                    </div>
                                    <div class="form-row mb-3" id="upload-period-selectors">
                                        <div class="col-4">
                                            <label for="upload-bulan-select" class="small font-weight-bold text-gray-600">Bulan <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="upload-bulan-select">
                                                <option value="">-- Pilih Bulan --</option>
                                                <option value="January">January</option>
                                                <option value="February">February</option>
                                                <option value="March">March</option>
                                                <option value="April">April</option>
                                                <option value="May">May</option>
                                                <option value="June">June</option>
                                                <option value="July">July</option>
                                                <option value="August">August</option>
                                                <option value="September">September</option>
                                                <option value="October">October</option>
                                                <option value="November">November</option>
                                                <option value="December">December</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="upload-batch-select" class="small font-weight-bold text-gray-600">Batch <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="upload-batch-select">
                                                <option value="">-- Pilih Batch --</option>
                                                <option value="1">Batch 1</option>
                                                <option value="2">Batch 2</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="upload-tahun-select" class="small font-weight-bold text-gray-600">Tahun <span class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="upload-tahun-select">
                                                <option value="">-- Pilih Tahun --</option>
                                                <?php
                                                $curY = (int) date('Y');
                                                for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="upload-drop-zone" id="upload-drop-zone">
                                        <input type="file" id="excel-file-input" accept=".xlsx,.xls,.csv"
                                            style="display:none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File</h5>
                                        <p>or click to browse your computer</p>
                                        <button class="btn-browse" id="btn-browse-file" type="button">
                                            <i class="fas fa-folder-open mr-1"></i> Browse Files
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 100MB
                                        </div>
                                    </div>

                                    <div class="upload-progress-container" id="upload-progress">
                                        <div class="upload-progress-bar">
                                            <div class="progress-fill" id="upload-progress-fill"></div>
                                        </div>
                                        <div class="upload-file-info">
                                            <span class="file-name" id="upload-file-name"></span>
                                            <span class="file-size" id="upload-file-size"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6" id="uploadModalRightCol" style="display: none;">
                                    <div class="upload-controls" id="upload-controls">
                                        <div class="form-group">
                                            <label for="sheet-select">Pilih Sheet</label>
                                            <select class="form-control" id="sheet-select"></select>
                                        </div>
                                        <button class="btn-generate" id="btn-generate-charts" type="button" disabled>
                                            <i class="fas fa-check"></i> Submit Upload
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Inbound Modal -->
            <div class="modal fade" id="uploadExcelModalInbound" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalInboundLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header bg-success text-white">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalInboundLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data Inbound
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body p-4">
                            <div
                                class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold text-gray-700">
                                    <i class="fas fa-download mr-1 text-success"></i> Download Template:
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success font-weight-bold"
                                        id="btn-template-inbound">
                                        <i class="fas fa-file-excel mr-1"></i> Template Inbound
                                    </button>
                                </div>
                            </div>

                            <!-- Periode Group Selectors (Month, Batch & Year) -->
                            <div class="form-row mb-2">
                                <div class="col-4">
                                    <label for="uploadInboundMonthSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Bulan Periode <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadInboundMonthSelect">
                                        <option value="">-- Pilih Bulan --</option>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="uploadInboundBatchSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Batch <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadInboundBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="uploadInboundYearSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadInboundYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Period Availability Status Indicator -->
                            <div id="inbound-period-status" class="mb-3" style="display: none;"></div>

                            <div class="upload-drop-zone border rounded p-4 text-center bg-light">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5 class="font-weight-bold">Drag &amp; Drop Excel File Inbound</h5>
                                <p class="text-muted small">atau pilih file dari komputer Anda</p>
                                <input type="file" id="excel-file-inbound-input" accept=".xlsx,.xls,.csv"
                                    class="d-none" />
                                <button class="btn btn-primary btn-sm px-3 font-weight-bold" type="button"
                                    id="btn-browse-inbound"
                                    onclick="document.getElementById('excel-file-inbound-input').click();">
                                    <i class="fas fa-folder-open mr-1"></i> Browse File
                                </button>
                                <div class="small text-muted mt-2">Formats: .xlsx, .xls, .csv</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Outbound Modal -->
            <div class="modal fade" id="uploadExcelModalOutbound" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalOutboundLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header bg-success text-white">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalOutboundLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data Outbound
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body p-4">
                            <div
                                class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold text-gray-700">
                                    <i class="fas fa-download mr-1 text-success"></i> Download Template:
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success font-weight-bold"
                                        id="btn-template-outbound">
                                        <i class="fas fa-file-excel mr-1"></i> Template Outbound
                                    </button>
                                </div>
                            </div>

                            <!-- Periode Group Selectors (Month, Batch & Year) -->
                            <div class="form-row mb-2">
                                <div class="col-4">
                                    <label for="uploadOutboundMonthSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Bulan Periode <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadOutboundMonthSelect">
                                        <option value="">-- Pilih Bulan --</option>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="uploadOutboundBatchSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Batch <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadOutboundBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="uploadOutboundYearSelect"
                                        class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="uploadOutboundYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="upload-drop-zone border rounded p-4 text-center bg-light">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5 class="font-weight-bold">Drag &amp; Drop Excel File Outbound</h5>
                                <p class="text-muted small">atau pilih file dari komputer Anda</p>
                                <input type="file" id="excel-file-outbound-input" accept=".xlsx,.xls,.csv"
                                    class="d-none" />
                                <button class="btn btn-primary btn-sm px-3 font-weight-bold" type="button"
                                    onclick="document.getElementById('excel-file-outbound-input').click();">
                                    <i class="fas fa-folder-open mr-1"></i> Browse File
                                </button>
                                <div class="small text-muted mt-2">Formats: .xlsx, .xls, .csv</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Data Inbound Modal -->
            <div class="modal fade" id="deleteDataModalInbound" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalInboundLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalInboundLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Master Data Inbound
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"
                                style="opacity: 0.8;">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body">
                            <div class="p-3">
                                <div class="text-center text-gray-600 mb-4">
                                    <h3 class="text-danger font-weight-bold mb-3"><i
                                            class="fas fa-exclamation-triangle mr-2"></i>Peringatan</h3>
                                    <p class="mb-0" style="font-size: 1.1rem;">Data Inbound untuk periode yang Anda
                                        pilih akan dihapus secara permanen dari sistem.</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteInboundMonthSelect"
                                        class="small font-weight-bold text-gray-600">Bulan</label>
                                    <select class="form-control form-control-sm" id="deleteInboundMonthSelect">
                                        <option value="">-- Pilih Bulan (Kosongkan untuk Hapus Semua) --</option>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteInboundBatchSelect"
                                        class="small font-weight-bold text-gray-600">Batch</label>
                                    <select class="form-control form-control-sm" id="deleteInboundBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="deleteInboundYearSelect"
                                        class="small font-weight-bold text-gray-600">Tahun</label>
                                    <select class="form-control form-control-sm" id="deleteInboundYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-inbound"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Data Inbound
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Data Outbound Modal -->
            <div class="modal fade" id="deleteDataModalOutbound" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalOutboundLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalOutboundLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Master Data Outbound
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"
                                style="opacity: 0.8;">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body">
                            <div class="p-3">
                                <div class="text-center text-gray-600 mb-4">
                                    <h3 class="text-danger font-weight-bold mb-3"><i
                                            class="fas fa-exclamation-triangle mr-2"></i>Peringatan</h3>
                                    <p class="mb-0" style="font-size: 1.1rem;">Apakah Anda yakin ingin menghapus semua
                                        data dari sistem?</p>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-outbound"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Semua Data Outbound
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTables JS -->
            <script src="frontend/vendor/datatables/jquery.dataTables.min.js"></script>
            <script src="frontend/vendor/datatables/dataTables.bootstrap4.min.js"></script>

            <!-- SheetJS (xlsx) for Excel import/export -->
            <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script src="frontend/js/excel-upload.js?v=<?= time() ?>"></script>
            <script src="frontend/js/formula-controller.js?v=<?= time() ?>"></script>
            <script src="frontend/js/master-data.js?v=<?= time() ?>"></script>

            <script>
                $(document).ready(function () {
                    // Restore active segment tab on reload, or default to Inbound on fresh navigation
                    var isReload = false;
                    if (window.performance) {
                        if (performance.getEntriesByType) {
                            var navEntries = performance.getEntriesByType('navigation');
                            if (navEntries.length > 0 && navEntries[0].type === 'reload') {
                                isReload = true;
                            }
                        }
                        if (!isReload && performance.navigation && performance.navigation.type === 1) {
                            isReload = true;
                        }
                    }

                    if (isReload) {
                        var activeSeg = localStorage.getItem('activeMasterSegmentTab');
                        if (activeSeg && $(activeSeg + '-tab').length && !$(activeSeg + '-tab').hasClass('disabled')) {
                            $('#masterSegmentTabs a[href="' + activeSeg + '"]').tab('show');
                        }
                    } else {
                        localStorage.removeItem('activeMasterSegmentTab');
                    }

                    var currentSeg = $('#masterSegmentTabs a.active').attr('href');
                    if (currentSeg && currentSeg !== '#seg-storage') {
                        $('#storage-action-buttons').hide();
                    } else {
                        $('#storage-action-buttons').show();
                    }

                    // Save active segment tab on change
                    $('#masterSegmentTabs a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                        var targetSeg = $(e.target).attr("href");
                        localStorage.setItem('activeMasterSegmentTab', targetSeg);
                        if (targetSeg === '#seg-storage') {
                            $('#storage-action-buttons').fadeIn(200);
                        } else {
                            $('#storage-action-buttons').fadeOut(200);
                        }
                        if (typeof loadActiveMasterTabTable === 'function') {
                            loadActiveMasterTabTable();
                        }
                    });

                    // Restore active storage sub-tab from localStorage
                    var activeTab = localStorage.getItem('activeMasterDataTab');
                    if (activeTab) {
                        $('#masterDataTabs a[href="' + activeTab + '"]').tab('show');
                    }

                    // Save active storage sub-tab on click
                    $('#masterDataTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                        var targetTab = $(e.target).attr("href");
                        localStorage.setItem('activeMasterDataTab', targetTab);
                        if (typeof loadActiveMasterTabTable === 'function') {
                            loadActiveMasterTabTable();
                        }
                    });

                    if (typeof loadActiveMasterTabTable === 'function') {
                        loadActiveMasterTabTable();
                    }
                });

                // Delete Data Logic
                function fillDeletePeriodDropdowns(mSelId, ySelId, periods) {
                    var mSel = document.getElementById(mSelId);
                    var ySel = document.getElementById(ySelId);
                    if (!mSel || !ySel) return;

                    var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    if (mSel.options.length <= 1) {
                        months.forEach(function (item) {
                            var opt = document.createElement('option');
                            opt.value = item; opt.textContent = item; mSel.appendChild(opt);
                        });
                    }

                    if (periods && Array.isArray(periods)) {
                        periods.forEach(function (item) {
                            var parts = item.split(' ');
                            if (parts.length >= 2) {
                                var year = parts[1];
                                var existing = Array.from(ySel.options).find(opt => opt.value === year);
                                if (!existing) {
                                    var opt = document.createElement('option');
                                    opt.value = year; opt.textContent = year; ySel.appendChild(opt);
                                }
                            }
                        });
                    }
                }

                // Load periods for all delete dropdowns (Storage, Inbound, Outbound)
                fetch('api/get_periods.php')
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success' && result.data) {
                            fillDeletePeriodDropdowns('deleteMonthSelect', 'deleteYearSelect', result.data);
                            fillDeletePeriodDropdowns('deleteInboundMonthSelect', 'deleteInboundYearSelect', result.data);
                            fillDeletePeriodDropdowns('deleteOutboundMonthSelect', 'deleteOutboundYearSelect', result.data);
                        }
                    });

                // Helper function for showing SweetAlert loading modal
                function showProcessingModal() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Is Processing Please Wait',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () { Swal.showLoading(); }
                        });
                    }
                }

                // 1. Confirm Delete Storage Data
                var btnConfirmDelete = document.getElementById('btn-confirm-delete');
                if (btnConfirmDelete) {
                    btnConfirmDelete.addEventListener('click', function () {
                        var delMonth = document.getElementById('deleteMonthSelect');
                        var delBatch = document.getElementById('deleteBatchSelect');
                        var delYear = document.getElementById('deleteYearSelect');
                        if (!delMonth || !delMonth.value || !delYear || !delYear.value) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Peringatan', 'Silakan pilih Bulan dan Tahun untuk menghapus data Storage.', 'warning');
                            } else {
                                alert('Silakan pilih Bulan dan Tahun untuk menghapus data Storage.');
                            }
                            return;
                        }
                        var bVal = delBatch && delBatch.value ? delBatch.value : '';
                        var periodToDelete = delMonth.value + ' ' + delYear.value + (bVal ? '-Batch' + bVal : '');

                        var executeStorageDelete = function () {
                            showProcessingModal();
                            fetch('api/delete_data.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    periode: periodToDelete,
                                    month: delMonth.value,
                                    year: delYear.value,
                                    batch: bVal
                                })
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Berhasil!', 'Data Storage berhasil dihapus.', 'success');
                                        } else {
                                            alert('Data Storage berhasil dihapus.');
                                        }
                                        $('#deleteDataModal').modal('hide');
                                        if ($.fn.DataTable && $('#dataTableAsset').length) {
                                            $('#dataTableAsset').DataTable().ajax.reload();
                                        }
                                    } else {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Error', 'Gagal menghapus data: ' + res.message, 'error');
                                        } else {
                                            alert('Gagal menghapus data: ' + res.message);
                                        }
                                    }
                                })
                                .catch(err => {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                                    }
                                });
                        };

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Apakah Anda YAKIN?',
                                text: "Ingin menghapus data Storage untuk periode " + periodToDelete.toUpperCase() + "?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#e74a3b',
                                cancelButtonColor: '#858796',
                                confirmButtonText: 'Ya, Hapus!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    executeStorageDelete();
                                }
                            });
                        } else {
                            if (confirm("Apakah Anda YAKIN ingin menghapus data Storage untuk periode " + periodToDelete.toUpperCase() + "?")) {
                                executeStorageDelete();
                            }
                        }
                    });
                }

                // 2. Confirm Delete Inbound Data
                var btnConfirmDeleteInbound = document.getElementById('btn-confirm-delete-inbound');
                if (btnConfirmDeleteInbound) {
                    btnConfirmDeleteInbound.addEventListener('click', function () {
                        var m = document.getElementById('deleteInboundMonthSelect') ? document.getElementById('deleteInboundMonthSelect').value : '';
                        var b = document.getElementById('deleteInboundBatchSelect') ? document.getElementById('deleteInboundBatchSelect').value : '';
                        var y = document.getElementById('deleteInboundYearSelect') ? document.getElementById('deleteInboundYearSelect').value : '';
                        var period = (m && y) ? (m + ' ' + y + (b ? '-Batch' + b : '')) : null;

                        var msg = period
                            ? "Ingin menghapus data Inbound untuk periode " + period.toUpperCase() + "?"
                            : "Ingin menghapus SEMUA Data Master Inbound dari database?";

                        var executeInboundDelete = function () {
                            showProcessingModal();
                            fetch('api/delete_inbound_master.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'delete_period', periode: period, month: m, year: y, batch: b })
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Berhasil!', res.message || 'Data Master Inbound berhasil dihapus.', 'success');
                                        } else {
                                            alert(res.message || 'Data Master Inbound berhasil dihapus.');
                                        }
                                        $('#deleteDataModalInbound').modal('hide');
                                        if ($.fn.DataTable && $('#dataTableInbound').length) {
                                            $('#dataTableInbound').DataTable().ajax.reload();
                                        } else {
                                            location.reload();
                                        }
                                    } else {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Error', 'Gagal menghapus data: ' + res.message, 'error');
                                        } else {
                                            alert('Gagal menghapus data: ' + res.message);
                                        }
                                    }
                                })
                                .catch(err => {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                                    } else {
                                        alert('Terjadi kesalahan saat menghubungi server.');
                                    }
                                });
                        };

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Apakah Anda YAKIN?',
                                text: msg,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#e74a3b',
                                cancelButtonColor: '#858796',
                                confirmButtonText: 'Ya, Hapus!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    executeInboundDelete();
                                }
                            });
                        } else {
                            if (confirm("Apakah Anda YAKIN " + msg)) {
                                executeInboundDelete();
                            }
                        }
                    });
                }

                // 3. Confirm Delete Outbound Data
                var btnConfirmDeleteOutbound = document.getElementById('btn-confirm-delete-outbound');
                if (btnConfirmDeleteOutbound) {
                    btnConfirmDeleteOutbound.addEventListener('click', function () {
                        var executeOutboundDelete = function () {
                            showProcessingModal();
                            fetch('api/delete_outbound_master.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'truncate_all' })
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Berhasil!', res.message || 'Semua Data Outbound berhasil dihapus.', 'success');
                                        } else {
                                            alert(res.message || 'Semua Data Outbound berhasil dihapus.');
                                        }
                                        $('#deleteDataModalOutbound').modal('hide');
                                        if (typeof outboundTable !== 'undefined' && outboundTable) {
                                            outboundTable.ajax.reload();
                                            if (typeof refreshOutboundFilters === 'function') refreshOutboundFilters();
                                        } else {
                                            location.reload();
                                        }
                                    } else {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Error', 'Gagal menghapus data: ' + res.message, 'error');
                                        } else {
                                            alert('Gagal menghapus data: ' + res.message);
                                        }
                                    }
                                })
                                .catch(err => {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                                    } else {
                                        alert('Terjadi kesalahan saat menghubungi server.');
                                    }
                                });
                        };

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Apakah Anda YAKIN?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#e74a3b',
                                cancelButtonColor: '#858796',
                                confirmButtonText: 'Ya, Hapus Semua!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    executeOutboundDelete();
                                }
                            });
                        } else {
                            if (confirm("Apakah Anda YAKIN ingin menghapus SEMUA Data Master Outbound?")) {
                                executeOutboundDelete();
                            }
                        }
                    });
                }
            </script>

            </html>