<?php
require_once __DIR__ . '/../../backend/auth.php';
if (!defined('SPA_MODE'))
    checkModuleAccess('master_data');

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

$canAccessInboundMaster = hasPermission('master_data_inbound', 'view');
$canAccessStorageMaster = hasPermission('master_data_storage', 'view');
$canAccessOutboundMaster = hasPermission('master_data_outbound', 'view');
$canAccessKpiMaster = hasPermission('master_data_kpi', 'view');

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
if (!$canAccessKpiMaster && hasPermission('kpi_monitoring', 'view')) {
    $canAccessKpiMaster = true;
}

// Superadmin has full access to all tabs
if ($userRole === 'superadmin') {
    $canAccessInboundMaster = true;
    $canAccessStorageMaster = true;
    $canAccessOutboundMaster = true;
    $canAccessKpiMaster = true;
}

$defaultMasterSegment = '';
if ($canAccessInboundMaster) {
    $defaultMasterSegment = 'inbound';
} elseif ($canAccessStorageMaster) {
    $defaultMasterSegment = 'storage';
} elseif ($canAccessOutboundMaster) {
    $defaultMasterSegment = 'outbound';
} elseif ($canAccessKpiMaster) {
    $defaultMasterSegment = 'kpi';
}

$canAddInbound = ($userRole === 'superadmin') || canAdd('master_data_inbound') || canAdd('inbound');
$canDeleteInbound = ($userRole === 'superadmin') || canDelete('master_data_inbound') || canDelete('inbound');

$canAddStorage = ($userRole === 'superadmin') || canAdd('master_data_storage') || canAdd('warehouse');
$canDeleteStorage = ($userRole === 'superadmin') || canDelete('master_data_storage') || canDelete('warehouse');

$canAddOutbound = ($userRole === 'superadmin') || canAdd('master_data_outbound') || canAdd('outbound');
$canDeleteOutbound = ($userRole === 'superadmin') || canDelete('master_data_outbound') || canDelete('outbound');

$canAddKpi = ($userRole === 'superadmin') || canAdd('master_data_kpi') || canAdd('kpi_monitoring');
$canDeleteKpi = ($userRole === 'superadmin') || canDelete('master_data_kpi') || canDelete('kpi_monitoring');

if (!defined('SPA_MODE')) {
    $pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
    include FRONTEND_PATH . 'components/header.php';
}
?>
<script>
    window.currentUserRole = <?php echo json_encode($userRole); ?>;
    window.userCanAddStorage = <?php echo $canAddStorage ? 'true' : 'false'; ?>;
    window.userCanDeleteStorage = <?php echo $canDeleteStorage ? 'true' : 'false'; ?>;
    window.userCanAddInbound = <?php echo $canAddInbound ? 'true' : 'false'; ?>;
    window.userCanDeleteInbound = <?php echo $canDeleteInbound ? 'true' : 'false'; ?>;
    window.userCanAddOutbound = <?php echo $canAddOutbound ? 'true' : 'false'; ?>;
    window.userCanDeleteOutbound = <?php echo $canDeleteOutbound ? 'true' : 'false'; ?>;
    window.userCanAddKpi = <?php echo $canAddKpi ? 'true' : 'false'; ?>;
    window.userCanDeleteKpi = <?php echo $canDeleteKpi ? 'true' : 'false'; ?>;
</script>

<!-- DataTables CSS -->
<link href="frontend/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Excel Upload & Process Checklist CSS -->
<link href="frontend/css/excel-upload.css?v=<?= time() ?>" rel="stylesheet">
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

    #nav-item-period-selector,
    #periodDropdown {
        display: none !important;
    }
</style>
<?php if (!defined('SPA_MODE')) { ?>
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
                <?php } ?>

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

                        <!-- KPI Master Data Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessKpiMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'kpi') ? 'active' : ''; ?>"
                                    id="seg-kpi-tab" data-toggle="pill" href="#seg-kpi" role="tab" aria-controls="seg-kpi"
                                    aria-selected="<?php echo ($defaultMasterSegment === 'kpi') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-tachometer-alt mr-2"></i> KPI Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted"
                                    id="seg-kpi-tab" href="javascript:void(0)"
                                    style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;"
                                    title="Modul ini terkunci (Khusus Hak Akses KPI Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> KPI Master Data
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
                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border"
                                        id="inbound-action-buttons">
                                        <h6 class="m-0 font-weight-bold text-primary" id="inbound-menu-title">
                                            <i class="fas fa-box-open mr-2"></i>Menu Master Data Inbound (Data PR to PO And Delivery Plan)
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <!-- Action Buttons for Data PR to PO And Delivery Plan -->
                                            <span id="btn-group-inbound-prpo-actions">
                                                <?php if ($canAddInbound): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModalInbound">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel PR to PO And Delivery Plan
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteInbound): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                        data-target="#deleteDataModalInbound">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data PR to PO And Delivery Plan
                                                    </button>
                                                <?php endif; ?>
                                            </span>

                                            <!-- Action Buttons for Data GR -->
                                            <span id="btn-group-inbound-gr-actions" style="display: none;">
                                                <?php if ($canAddInbound): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModalInboundGr">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel Data GR
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteInbound): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                        data-target="#deleteDataModalInboundGr">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data GR
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Sub Master Data Tabs for Inbound -->
                                <ul class="nav nav-tabs mb-4" id="inboundSubTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" id="tab-inbound-prpo" data-toggle="tab"
                                            href="#pane-inbound-prpo" role="tab" aria-controls="pane-inbound-prpo"
                                            aria-selected="true">
                                            <i class="fas fa-file-invoice mr-1"></i> Data PR to PO And Delivery Plan
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="tab-inbound-gr" data-toggle="tab"
                                            href="#pane-inbound-gr" role="tab" aria-controls="pane-inbound-gr"
                                            aria-selected="false">
                                            <i class="fas fa-clipboard-check mr-1"></i> Data GR
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="inboundSubTabsContent">
                                    <!-- 1.1 Pane PR to PO And Delivery Plan -->
                                    <div class="tab-pane fade show active" id="pane-inbound-prpo" role="tabpanel" aria-labelledby="tab-inbound-prpo">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data PR to PO And Delivery Plan</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Filter Control Bar (Dropdowns & Reset) -->
                                                <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                                    <div class="card-body py-3 px-4">
                                                        <div class="form-row align-items-end">
                                                            <!-- Periode Group Dropdown -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-periode"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Periode Group</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-periode">
                                                                    <option value="">Semua Periode</option>
                                                                </select>
                                                            </div>

                                                            <!-- Bagian Dropdown -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-bagian"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Bagian</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-bagian">
                                                                    <option value="">Semua Bagian</option>
                                                                </select>
                                                            </div>

                                                            <!-- PIC Teknis Dropdown -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-pic"
                                                                    class="small font-weight-bold text-gray-700 mb-1">PIC Teknis</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-pic">
                                                                    <option value="">Semua PIC Teknis</option>
                                                                </select>
                                                            </div>

                                                            <!-- Item Kategori Dropdown -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-kategori"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Item Kategori</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-kategori">
                                                                    <option value="">Semua Kategori</option>
                                                                </select>
                                                            </div>

                                                            <!-- No. PR / PO Search Input -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-po"
                                                                    class="small font-weight-bold text-gray-700 mb-1">No. PR / PO</label>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    id="filter-inbound-po" placeholder="Search...">
                                                            </div>

                                                            <!-- Reset Filter Button -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <button class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                                    type="button" id="btn-reset-filter-inbound">
                                                                    <i class="fas fa-undo mr-1"></i> Reset Filter
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm" id="dataTableInbound" width="100%" cellspacing="0">
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

                                    <!-- 1.2 Pane Data GR -->
                                    <div class="tab-pane fade" id="pane-inbound-gr" role="tabpanel" aria-labelledby="tab-inbound-gr">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data GR</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Filter Control Bar for Data GR -->
                                                <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                                    <div class="card-body py-3 px-4">
                                                        <div class="form-row align-items-end">
                                                            <!-- Periode Group Dropdown -->
                                                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-gr-periode"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Periode Group</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-gr-periode">
                                                                    <option value="">Semua Periode</option>
                                                                </select>
                                                            </div>

                                                            <!-- Vendor Name Dropdown -->
                                                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-gr-vendor"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Vendor Name</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-gr-vendor">
                                                                    <option value="">Semua Vendor</option>
                                                                </select>
                                                            </div>

                                                            <!-- Project Dropdown -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-gr-project"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Nama Project</label>
                                                                <select class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-inbound-gr-project">
                                                                    <option value="">Semua Project</option>
                                                                </select>
                                                            </div>

                                                            <!-- Search Input -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-inbound-gr-search"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Cari Data</label>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    id="filter-inbound-gr-search" placeholder="Search...">
                                                            </div>

                                                            <!-- Reset Filter Button -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <button class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                                    type="button" id="btn-reset-filter-inbound-gr">
                                                                    <i class="fas fa-undo mr-1"></i> Reset Filter
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm" id="dataTableInboundGr" width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>No Reg</th>
                                                                <th>KD Spec</th>
                                                                <th>SN</th>
                                                                <th>PN</th>
                                                                <th>Product Name</th>
                                                                <th>Price</th>
                                                                <th>PR No</th>
                                                                <th>PR Date</th>
                                                                <th>PO No</th>
                                                                <th>PO Date</th>
                                                                <th>DO No</th>
                                                                <th>DO Date</th>
                                                                <th>GR No</th>
                                                                <th>GR Date</th>
                                                                <th>PO Value</th>
                                                                <th>Nama Project</th>
                                                                <th>Term of Payment</th>
                                                                <th>Kode Site Penerimaan</th>
                                                                <th>Qty</th>
                                                                <th>UoM</th>
                                                                <th>Is Unique Item</th>
                                                                <th>Warranty</th>
                                                                <th>Warranty Unit</th>
                                                                <th>Manufacturer</th>
                                                                <th>Vendor Name</th>
                                                                <th>Vendor Address</th>
                                                                <th>Jenis Kepemilikan</th>
                                                                <th>Pemilik</th>
                                                                <th>Capex Opex</th>
                                                                <th>LOI No</th>
                                                                <th>IsSentToARTISCode</th>
                                                                <th>ARTIS Date</th>
                                                                <th>ARTIS Message</th>
                                                                <th>IsSentToIIPSCode</th>
                                                                <th>IIPS Date</th>
                                                                <th>IIPS Message</th>
                                                                <th>PIC Submit GR</th>
                                                                <th>PIC Registration</th>
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
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canAccessStorageMaster): ?>
                            <!-- 2. STORAGE MASTER DATA (FUNCTIONAL SUB MASTER DATA) -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'storage') ? 'show active' : ''; ?>"
                                id="seg-storage" role="tabpanel" aria-labelledby="seg-storage-tab">

                                <!-- Storage Action Buttons Header Bar -->
                                <?php if ($canAddStorage || $canDeleteStorage): ?>
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border"
                                        id="storage-action-buttons">
                                        <h6 class="m-0 font-weight-bold text-primary" id="storage-menu-title">
                                            <i class="fas fa-boxes mr-2"></i>Menu Master Data Storage (Data Asset)
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <!-- Action Buttons for Data Asset -->
                                            <span id="btn-group-asset-actions">
                                                <?php if ($canAddStorage): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModal">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel Asset
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteStorage): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold"
                                                        data-toggle="modal" data-target="#deleteDataModal">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data Asset
                                                    </button>
                                                <?php endif; ?>
                                            </span>

                                            <!-- Action Buttons for Data Utilisasi Rack -->
                                            <span id="btn-group-rack-actions" style="display: none;">
                                                <?php if ($canAddStorage): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModalRack">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel Rack
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteStorage): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold"
                                                        data-toggle="modal" data-target="#deleteDataModalRack">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data Rack
                                                    </button>
                                                <?php endif; ?>
                                            </span>
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
                                            <div
                                                class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">
                                                    <i class="fas fa-th mr-2"></i>Tabel Master Utilisasi Rack
                                                </h6>
                                            </div>
                                            <div class="card-body">

                                                <!-- Custom Filters for Rack -->
                                                <div class="row mb-3">
                                                    <div class="col-md-2">
                                                        <label>Tahun:</label>
                                                        <select id="filterRackYear"
                                                            class="form-control form-control-sm font-weight-bold">
                                                        </select>
                                                    </div>
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
                                                    <div class="col-md-4 d-flex align-items-end justify-content-end"
                                                        id="rackSearchContainer">
                                                    </div>
                                                </div>

                                                <div class="table-responsive"
                                                    style="max-height: 550px; overflow-x: auto; overflow-y: auto;">
                                                    <table class="table table-bordered table-sm table-hover text-nowrap"
                                                        id="dataTableRack" width="100%" cellspacing="0">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;">
                                                                    BARCODE</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;">
                                                                    NAME</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;">
                                                                    LABEL</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;">
                                                                    ACTIVE</th>
                                                                <th
                                                                    style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;">
                                                                    CATEGORY</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP JAN</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP FEB</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP MAR</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP APR</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP MEI</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP JUN</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP JUL</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP AGU</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP SEP</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP OKT</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP NOV</th>
                                                                <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 2;"
                                                                    class="text-center">CAP DES</th>
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

                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canAccessOutboundMaster): ?>
                            <!-- 3. OUTBOUND MASTER DATA -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'outbound') ? 'show active' : ''; ?>"
                                id="seg-outbound" role="tabpanel" aria-labelledby="seg-outbound-tab">
                                <?php if ($canAddOutbound || $canDeleteOutbound): ?>
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border"
                                        id="outbound-action-buttons">
                                        <h6 class="m-0 font-weight-bold text-primary" id="outbound-menu-title">
                                            <i class="fas fa-truck-loading mr-2"></i>Menu Master Data Outbound
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <!-- Action Buttons for Pending List -->
                                            <span id="btn-group-pending-actions">
                                                <?php if ($canAddOutbound): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModalOutbound">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel Pending List
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteOutbound): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold"
                                                        data-toggle="modal" data-target="#deleteDataModalOutbound">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data Pending List
                                                    </button>
                                                <?php endif; ?>
                                            </span>

                                            <!-- Action Buttons for PR Forwarder -->
                                            <span id="btn-group-forwarder-actions" style="display: none;">
                                                <?php if ($canAddOutbound): ?>
                                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                        data-toggle="modal" data-target="#uploadExcelModalForwarder">
                                                        <i class="fas fa-file-import mr-1"></i> Import Excel PR Forwarder
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDeleteOutbound): ?>
                                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold"
                                                        data-toggle="modal" data-target="#deleteDataModalForwarder">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data PR Forwarder
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Sub Master Data Tabs for Outbound (Just like Storage Master Data) -->
                                <ul class="nav nav-tabs mb-4" id="outboundSubTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" id="tab-outbound-pending" data-toggle="tab"
                                            href="#pane-outbound-pending" role="tab" aria-controls="pane-outbound-pending"
                                            aria-selected="true">
                                            <i class="fas fa-list-alt mr-1"></i> Data Pending List
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="tab-outbound-forwarder" data-toggle="tab"
                                            href="#pane-outbound-forwarder" role="tab"
                                            aria-controls="pane-outbound-forwarder" aria-selected="false">
                                            <i class="fas fa-shipping-fast mr-1"></i> Data PR Forwarder
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="outboundSubTabContent">
                                    <!-- PANE 1: PENDING LIST -->
                                    <div class="tab-pane fade show active" id="pane-outbound-pending" role="tabpanel"
                                        aria-labelledby="tab-outbound-pending">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div
                                                class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data Outbound
                                                    (Pending List)</h6>
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
                                                                    class="small font-weight-bold text-gray-700 mb-1">Search</label>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    id="filter-outbound-mr" placeholder="Search...">
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
                                                    <table class="table table-bordered table-sm text-nowrap"
                                                        id="dataTableOutbound" width="100%" cellspacing="0">
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

                                    <!-- PANE 2: PR FORWARDER -->
                                    <div class="tab-pane fade" id="pane-outbound-forwarder" role="tabpanel"
                                        aria-labelledby="tab-outbound-forwarder">
                                        <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                            <div
                                                class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">Tabel Master Data Outbound (PR
                                                    Forwarder)</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Filter Control Bar PR Forwarder (Matching KPI Master Data Style) -->
                                                <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                                    <div class="card-body py-3 px-4">
                                                        <div class="form-row align-items-end">
                                                            <!-- Periode Dropdown -->
                                                            <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                                                <label for="filter-forwarder-periode"
                                                                    class="small font-weight-bold text-gray-700 mb-1">Periode</label>
                                                                <select
                                                                    class="form-control form-control-sm custom-select custom-select-sm"
                                                                    id="filter-forwarder-periode">
                                                                    <option value="">Semua Periode</option>
                                                                </select>
                                                            </div>

                                                            <!-- Reset Filter Button -->
                                                            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                                <button
                                                                    class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                                    type="button" id="btn-reset-filter-forwarder">
                                                                    <i class="fas fa-undo mr-1"></i> Reset Filter
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- PR Forwarder Table with 3-Row Nested Headers -->
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm text-nowrap"
                                                        id="dataTablePrForwarder" width="100%" cellspacing="0">
                                                        <thead>
                                                            <!-- Row 1: Super Headers -->
                                                            <tr>
                                                                <th rowspan="3" class="text-center align-middle">NO DN</th>
                                                                <th rowspan="3" class="text-center align-middle">PRINT</th>
                                                                <th rowspan="3" class="text-center align-middle">DN STATUS
                                                                </th>
                                                                <th colspan="4" class="text-center align-middle">ASAL</th>
                                                                <th colspan="4" class="text-center align-middle">TUJUAN</th>
                                                                <th colspan="17" class="text-center align-middle">
                                                                    PROCUREMENT</th>
                                                                <th rowspan="3" class="text-center align-middle">DOC</th>
                                                                <th rowspan="3" class="text-center align-middle">NOTE</th>
                                                                <th colspan="7" class="text-center align-middle">DELIVERY
                                                                </th>
                                                                <th colspan="3" class="text-center align-middle">APPROVAL
                                                                </th>
                                                                <th rowspan="3" class="text-center align-middle">PERIODE
                                                                    GROUP</th>
                                                            </tr>
                                                            <!-- Row 2: Sub Headers -->
                                                            <tr>
                                                                <!-- Under ASAL -->
                                                                <th rowspan="2" class="text-center align-middle">PENGIRIM
                                                                </th>
                                                                <th colspan="3" class="text-center align-middle">SITE</th>
                                                                <!-- Under TUJUAN -->
                                                                <th rowspan="2" class="text-center align-middle">PENERIMA
                                                                </th>
                                                                <th colspan="3" class="text-center align-middle">SITE</th>
                                                                <!-- Under PROCUREMENT -->
                                                                <th colspan="2" class="text-center align-middle">VENDOR</th>
                                                                <th rowspan="2" class="text-center align-middle">KOLI</th>
                                                                <th rowspan="2" class="text-center align-middle">MATA
                                                                    ANGGARAN</th>
                                                                <th colspan="2" class="text-center align-middle">SR</th>
                                                                <th colspan="2" class="text-center align-middle">PR</th>
                                                                <th rowspan="2" class="text-center align-middle">VALUATION
                                                                    PRICE</th>
                                                                <th rowspan="2" class="text-center align-middle">SUGGESTION
                                                                </th>
                                                                <th rowspan="2" class="text-center align-middle">PURPOSE
                                                                </th>
                                                                <th colspan="6" class="text-center align-middle">PO</th>
                                                                <!-- Under DELIVERY -->
                                                                <th rowspan="2" class="text-center align-middle">TYPE</th>
                                                                <th rowspan="2" class="text-center align-middle">VIA</th>
                                                                <th rowspan="2" class="text-center align-middle">NAMA</th>
                                                                <th rowspan="2" class="text-center align-middle">AWB</th>
                                                                <th rowspan="2" class="text-center align-middle">PICKUP</th>
                                                                <th rowspan="2" class="text-center align-middle">LEAD TIME
                                                                </th>
                                                                <th rowspan="2" class="text-center align-middle">TARGET DLV
                                                                </th>
                                                                <!-- Under APPROVAL -->
                                                                <th rowspan="2" class="text-center align-middle">STATUS</th>
                                                                <th rowspan="2" class="text-center align-middle">APPROVER
                                                                </th>
                                                                <th rowspan="2" class="text-center align-middle">DATE</th>
                                                            </tr>
                                                            <!-- Row 3: Sub-Sub Headers (Leaf Columns) -->
                                                            <tr>
                                                                <!-- Under ASAL SITE -->
                                                                <th class="text-center align-middle">CODE</th>
                                                                <th class="text-center align-middle">SITE</th>
                                                                <th class="text-center align-middle">ALAMAT</th>
                                                                <!-- Under TUJUAN SITE -->
                                                                <th class="text-center align-middle">CODE</th>
                                                                <th class="text-center align-middle">SITE</th>
                                                                <th class="text-center align-middle">ALAMAT</th>
                                                                <!-- Under VENDOR -->
                                                                <th class="text-center align-middle">MODE</th>
                                                                <th class="text-center align-middle">NAME</th>
                                                                <!-- Under SR -->
                                                                <th class="text-center align-middle">NO</th>
                                                                <th class="text-center align-middle">TGL</th>
                                                                <!-- Under PR -->
                                                                <th class="text-center align-middle">NO</th>
                                                                <th class="text-center align-middle">TGL</th>
                                                                <!-- Under PO -->
                                                                <th class="text-center align-middle">NO</th>
                                                                <th class="text-center align-middle">TGL</th>
                                                                <th class="text-center align-middle">PRICE</th>
                                                                <th class="text-center align-middle">VENDOR</th>
                                                                <th class="text-center align-middle">TARGET DLV</th>
                                                                <th class="text-center align-middle">BUYER</th>
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
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canAccessKpiMaster): ?>
                            <!-- 4. KPI MASTER DATA -->
                            <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'kpi') ? 'show active' : ''; ?>"
                                id="seg-kpi" role="tabpanel" aria-labelledby="seg-kpi-tab">
                                <?php if ($canAddKpi || $canDeleteKpi): ?>
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border"
                                        id="kpi-action-buttons">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-tachometer-alt mr-2"></i>Menu KPI Master Data
                                        </h6>
                                        <div class="mt-2 mt-sm-0">
                                            <?php if ($canAddKpi): ?>
                                                <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2"
                                                    data-toggle="modal" data-target="#uploadExcelModalKpi">
                                                    <i class="fas fa-file-import mr-1"></i> Import Excel KPI
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($canDeleteKpi): ?>
                                                <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                                    data-target="#deleteDataModalKpi">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Data KPI
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                                    <div
                                        class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">Tabel KPI Master Data</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Year Filter -->
                                        <div class="card shadow-sm border mb-4" style="border-radius: 8px;">
                                            <div class="card-body py-3 px-4">
                                                <div class="form-row align-items-end">
                                                    <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                                        <label for="filter-kpi-year"
                                                            class="small font-weight-bold text-gray-700 mb-1">Periode
                                                            Tahun</label>
                                                        <select
                                                            class="form-control form-control-sm custom-select custom-select-sm"
                                                            id="filter-kpi-year">
                                                            <?php
                                                            $curYear = (int) date('Y');
                                                            $minKpiYear = 2026;
                                                            $startYear = max($minKpiYear, $curYear);
                                                            for ($y = $minKpiYear; $y <= $curYear + 5; $y++): ?>
                                                                <option value="<?php echo $y; ?>" <?php echo ($y === $curYear) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                                                        <button
                                                            class="btn btn-outline-secondary btn-sm font-weight-bold btn-block"
                                                            type="button" id="btn-reset-filter-kpi">
                                                            <i class="fas fa-undo mr-1"></i> Reset Filter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm text-nowrap" id="dataTableKpi"
                                                width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" class="align-middle text-center"
                                                            style="min-width:110px;">Bulan</th>
                                                        <th colspan="2" class="text-center">GR</th>
                                                        <th colspan="2" class="text-center">Registrasi</th>
                                                        <th colspan="2" class="text-center">Slow Moving</th>
                                                        <th colspan="2" class="text-center">Utilisasi Space</th>
                                                        <th colspan="2" class="text-center">Stok Opname Hub &amp; Outlet
                                                            Warehouse</th>
                                                        <th colspan="2" class="text-center">Delivery Effectiveness</th>
                                                        <th colspan="2" class="text-center">MR Closing (Akumulatif)</th>
                                                        <th colspan="2" class="text-center">Efisiensi Delivery</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
                                                        <th class="text-center" style="min-width:80px;">Target</th>
                                                        <th class="text-center" style="min-width:100px;">Achievement</th>
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
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
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
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

            <!-- Delete Data Modal Rack -->
            <div class="modal fade" id="deleteDataModalRack" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalRackLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white font-weight-bold" id="deleteDataModalRackLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Data Utilisasi Rack
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"
                                style="opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body">
                            <div class="p-3">
                                <div class="text-center text-gray-600 mb-4">
                                    <h3 class="text-danger font-weight-bold mb-3"><i
                                            class="fas fa-exclamation-triangle mr-2"></i>Peringatan</h3>
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteRackScopeSelect"
                                        class="small font-weight-bold text-gray-700">Jenis Penghapusan</label>
                                    <select class="form-control form-control-sm" id="deleteRackScopeSelect">
                                        <option value="year">Hapus Utilisasi Berdasarkan Tahun</option>
                                        <option value="all">Hapus Semua Data (Master Rak & Utilisasi)</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4" id="deleteRackYearContainer">
                                    <label for="deleteRackYearSelect" class="small font-weight-bold text-gray-700">Pilih
                                        Tahun</label>
                                    <select class="form-control form-control-sm font-weight-bold"
                                        id="deleteRackYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-rack"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Delete Data Rack
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Storage Data Asset Modal -->
            <div class="modal fade" id="uploadExcelModal" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data Asset (Storage)
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body p-4">
                            <!-- Download Template Section -->
                            <div
                                class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold text-gray-700">
                                    <i class="fas fa-download mr-1 text-success"></i> Download Template:
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success font-weight-bold"
                                        id="btn-template-asset">
                                        <i class="fas fa-file-excel mr-1"></i> Template Asset
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="asset-col-left">
                                    <!-- Periode Group Selectors (Month, Batch & Year) -->
                                    <div class="form-row mb-3" id="upload-period-selectors">
                                        <div class="col-4">
                                            <label for="upload-bulan-select"
                                                class="small font-weight-bold text-gray-700 mb-1">Bulan Periode <span
                                                    class="text-danger">*</span></label>
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
                                            <label for="upload-batch-select"
                                                class="small font-weight-bold text-gray-700 mb-1">Batch <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="upload-batch-select">
                                                <option value="">-- Pilih Batch --</option>
                                                <option value="1">Batch 1</option>
                                                <option value="2">Batch 2</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="upload-tahun-select"
                                                class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="upload-tahun-select">
                                                <option value="">-- Pilih Tahun --</option>
                                                <?php
                                                $curY = (int) date('Y');
                                                for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Drop Zone -->
                                    <div class="upload-drop-zone" id="upload-drop-zone">
                                        <input type="file" id="excel-file-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File Asset</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" id="btn-browse-file" type="button"
                                            onclick="document.getElementById('excel-file-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="asset-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="asset-file-name">-</div>
                                                    <div class="file-size" id="asset-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="asset-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="asset-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="asset-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="asset-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="asset-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="asset-progress-text">0 / 0 baris</span>
                                                            <span id="asset-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="asset-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="asset-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="asset-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="asset-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1" id="asset-sheet-badge">0
                                                        Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data asset:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="asset-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="asset-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="asset-sheet-info-text">Silakan pilih sheet di atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="asset-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Rack Modal -->
            <div class="modal fade" id="uploadExcelModalRack" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalRackLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalRackDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
                            <h5 class="modal-title font-weight-bold text-white" id="uploadExcelModalRackLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Data Utilisasi Rack
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body p-4">
                            <!-- Download Template Section -->
                            <div
                                class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold text-gray-700">
                                    <i class="fas fa-download mr-1 text-success"></i> Download Template:
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold"
                                    id="btn-template-rack-modal">
                                    <i class="fas fa-file-excel mr-1"></i> Template Rack
                                </button>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="rack-col-left">
                                    <div class="form-group mb-3">
                                        <label class="small font-weight-bold text-gray-700 mb-1" for="upload-rack-year">
                                            <i class="fas fa-calendar-alt mr-1 text-success"></i> Pilih Tahun Target
                                            Utilisasi
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select id="upload-rack-year"
                                            class="form-control form-control-sm font-weight-bold">
                                            <?php
                                            $curY = (int) date('Y');
                                            for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="upload-drop-zone" id="upload-rack-drop-zone">
                                        <input type="file" id="excel-rack-file-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt text-success"></i>
                                        </div>
                                        <h5>Drag &amp; Drop File Excel Rack</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" id="btn-browse-rack-file" type="button"
                                            onclick="document.getElementById('excel-rack-file-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="rack-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon text-success"><i class="fas fa-file-excel"></i>
                                                </div>
                                                <div class="file-text">
                                                    <div class="file-name" id="rack-file-name">-</div>
                                                    <div class="file-size" id="rack-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="rack-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="rack-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="rack-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="rack-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="rack-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="rack-progress-text">0 / 0 baris</span>
                                                            <span id="rack-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="rack-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="rack-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="rack-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="rack-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-success mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-success px-2 py-1" id="rack-sheet-badge">0
                                                        Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    Data Utilisasi Rack:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="rack-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="rack-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="rack-sheet-info-text">Silakan pilih sheet di atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-success btn-block font-weight-bold py-2 shadow-sm"
                                                    id="rack-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
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
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalInboundDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalInboundLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data PR to PO And Delivery Plan
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
                                        <i class="fas fa-file-excel mr-1"></i> Template PR to PO And Delivery Plan
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="inbound-col-left">
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
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Period Availability Status Indicator -->
                                    <div id="inbound-period-status" class="mb-3" style="display: none;"></div>

                                    <div class="upload-drop-zone" id="inbound-upload-drop-zone">
                                        <input type="file" id="excel-file-inbound-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File Inbound</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" type="button" id="btn-browse-inbound"
                                            onclick="document.getElementById('excel-file-inbound-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="inbound-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="inbound-file-name">-</div>
                                                    <div class="file-size" id="inbound-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="inbound-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="inbound-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="inbound-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="inbound-progress-text">0 / 0 baris</span>
                                                            <span id="inbound-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="inbound-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="inbound-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="inbound-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1"
                                                        id="inbound-sheet-badge">0 Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data Inbound:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="inbound-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="inbound-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="inbound-sheet-info-text">Silakan pilih sheet di atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="inbound-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Data GR Modal -->
            <div class="modal fade" id="uploadExcelModalInboundGr" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalInboundGrLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalInboundGrDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalInboundGrLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data GR
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
                                        id="btn-template-inbound-gr">
                                        <i class="fas fa-file-excel mr-1"></i> Template Data GR
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="inbound-gr-col-left">
                                    <!-- Periode Group Selectors (Month, Batch & Year) -->
                                    <div class="form-row mb-2">
                                        <div class="col-4">
                                            <label for="uploadInboundGrMonthSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Bulan Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="uploadInboundGrMonthSelect">
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
                                            <label for="uploadInboundGrBatchSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Batch <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="uploadInboundGrBatchSelect">
                                                <option value="">-- Pilih Batch --</option>
                                                <option value="1">Batch 1</option>
                                                <option value="2">Batch 2</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="uploadInboundGrYearSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="uploadInboundGrYearSelect">
                                                <option value="">-- Pilih Tahun --</option>
                                                <?php
                                                $curY = (int) date('Y');
                                                for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Period Availability Status Indicator -->
                                    <div id="inbound-gr-period-status" class="mb-3" style="display: none;"></div>

                                    <div class="upload-drop-zone" id="inbound-gr-upload-drop-zone">
                                        <input type="file" id="excel-file-inbound-gr-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File Data GR</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" type="button" id="btn-browse-inbound-gr"
                                            onclick="document.getElementById('excel-file-inbound-gr-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="inbound-gr-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="inbound-gr-file-name">-</div>
                                                    <div class="file-size" id="inbound-gr-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="inbound-gr-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="inbound-gr-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-gr-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-gr-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="inbound-gr-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="inbound-gr-progress-text">0 / 0 baris</span>
                                                            <span id="inbound-gr-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="inbound-gr-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="inbound-gr-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="inbound-gr-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="inbound-gr-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1"
                                                        id="inbound-gr-sheet-badge">0 Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data Data GR:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="inbound-gr-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="inbound-gr-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="inbound-gr-sheet-info-text">Silakan pilih sheet di atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="inbound-gr-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel Outbound Modal -->
            <div class="modal fade" id="uploadExcelModalOutbound" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalOutboundLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalOutboundDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
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

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="outbound-col-left">
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
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="upload-drop-zone" id="outbound-upload-drop-zone">
                                        <input type="file" id="excel-file-outbound-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File Outbound</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" type="button" id="btn-browse-outbound"
                                            onclick="document.getElementById('excel-file-outbound-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="outbound-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="outbound-file-name">-</div>
                                                    <div class="file-size" id="outbound-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="outbound-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="outbound-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="outbound-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="outbound-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="outbound-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="outbound-progress-text">0 / 0 baris</span>
                                                            <span id="outbound-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="outbound-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="outbound-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="outbound-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="outbound-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1"
                                                        id="outbound-sheet-badge">0 Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data Outbound:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="outbound-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="outbound-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="outbound-sheet-info-text">Silakan pilih sheet di
                                                        atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="outbound-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel PR Forwarder Modal -->
            <div class="modal fade" id="uploadExcelModalForwarder" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalForwarderLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalForwarderDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalForwarderLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Master Data PR Forwarder
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
                                        id="btn-template-forwarder">
                                        <i class="fas fa-file-excel mr-1"></i> Template PR Forwarder
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="forwarder-col-left">
                                    <!-- Periode Group Selectors (Month, Batch & Year) -->
                                    <div class="form-row mb-2">
                                        <div class="col-4">
                                            <label for="uploadForwarderMonthSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Bulan Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm"
                                                id="uploadForwarderMonthSelect">
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
                                            <label for="uploadForwarderBatchSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Batch <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm"
                                                id="uploadForwarderBatchSelect">
                                                <option value="">-- Pilih Batch --</option>
                                                <option value="1">Batch 1</option>
                                                <option value="2">Batch 2</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="uploadForwarderYearSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="uploadForwarderYearSelect">
                                                <option value="">-- Pilih Tahun --</option>
                                                <?php
                                                $curY = (int) date('Y');
                                                for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="upload-drop-zone" id="forwarder-upload-drop-zone">
                                        <input type="file" id="excel-file-forwarder-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File PR Forwarder</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" type="button" id="btn-browse-forwarder"
                                            onclick="document.getElementById('excel-file-forwarder-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="forwarder-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="forwarder-file-name">-</div>
                                                    <div class="file-size" id="forwarder-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="forwarder-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="forwarder-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="forwarder-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="forwarder-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="forwarder-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="forwarder-progress-text">0 / 0 baris</span>
                                                            <span id="forwarder-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill"
                                                                id="forwarder-progress-fill"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="forwarder-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="forwarder-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="forwarder-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1"
                                                        id="forwarder-sheet-badge">0 Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data PR Forwarder:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="forwarder-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="forwarder-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="forwarder-sheet-info-text">Silakan pilih sheet di
                                                        atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="forwarder-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Data Inbound PR to PO And Delivery Plan Modal -->
            <div class="modal fade" id="deleteDataModalInbound" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalInboundLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalInboundLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Master Data PR to PO And Delivery Plan
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteInboundMonthSelect"
                                        class="small font-weight-bold text-gray-600">Bulan</label>
                                    <select class="form-control form-control-sm" id="deleteInboundMonthSelect">
                                        <option value="">-- Pilih Bulan (Kosongkan untuk Hapus Semua) --
                                        </option>
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
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-inbound"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Data PR to PO And Delivery Plan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Data GR Modal -->
            <div class="modal fade" id="deleteDataModalInboundGr" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalInboundGrLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalInboundGrLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Master Data GR
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteInboundGrMonthSelect"
                                        class="small font-weight-bold text-gray-600">Bulan</label>
                                    <select class="form-control form-control-sm" id="deleteInboundGrMonthSelect">
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
                                    <label for="deleteInboundGrBatchSelect"
                                        class="small font-weight-bold text-gray-600">Batch</label>
                                    <select class="form-control form-control-sm" id="deleteInboundGrBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="deleteInboundGrYearSelect"
                                        class="small font-weight-bold text-gray-600">Tahun</label>
                                    <select class="form-control form-control-sm" id="deleteInboundGrYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-inbound-gr"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Data GR
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
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

            <!-- Delete Data PR Forwarder Modal -->
            <div class="modal fade" id="deleteDataModalForwarder" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalForwarderLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalForwarderLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Master Data PR Forwarder
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="deleteForwarderMonthSelect"
                                        class="small font-weight-bold text-gray-600">Bulan</label>
                                    <select class="form-control form-control-sm" id="deleteForwarderMonthSelect">
                                        <option value="">-- Pilih Bulan (Kosongkan untuk Hapus Semua) --
                                        </option>
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
                                    <label for="deleteForwarderBatchSelect"
                                        class="small font-weight-bold text-gray-600">Batch</label>
                                    <select class="form-control form-control-sm" id="deleteForwarderBatchSelect">
                                        <option value="">-- Pilih Batch --</option>
                                        <option value="1">Batch 1</option>
                                        <option value="2">Batch 2</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="deleteForwarderYearSelect"
                                        class="small font-weight-bold text-gray-600">Tahun</label>
                                    <select class="form-control form-control-sm" id="deleteForwarderYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2024; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-forwarder"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Data PR Forwarder
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Excel KPI Modal -->
            <div class="modal fade" id="uploadExcelModalKpi" tabindex="-1" role="dialog"
                aria-labelledby="uploadExcelModalKpiLabel" aria-hidden="true">
                <div class="modal-dialog upload-modal-dialog modal-dialog-centered" role="document"
                    id="uploadExcelModalKpiDialog">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header">
                            <h5 class="modal-title font-weight-bold" id="uploadExcelModalKpiLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import KPI Master Data
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
                                        id="btn-template-kpi">
                                        <i class="fas fa-file-excel mr-1"></i> Template KPI
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Periode, Dropzone & Process Steps -->
                                <div class="col-12" id="kpi-col-left">
                                    <!-- Tahun Periode Selector Only (No Bulan/Batch) -->
                                    <div class="form-row mb-3">
                                        <div class="col-12">
                                            <label for="uploadKpiYearSelect"
                                                class="small font-weight-bold text-gray-700 mb-1">Tahun Periode <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control form-control-sm" id="uploadKpiYearSelect">
                                                <option value="">-- Pilih Tahun --</option>
                                                <?php
                                                $curY = (int) date('Y');
                                                $minKpiY = 2026;
                                                for ($y = $minKpiY; $y <= $curY + 5; $y++): ?>
                                                    <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                        <?php echo $y; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="upload-drop-zone" id="kpi-upload-drop-zone">
                                        <input type="file" id="excel-file-kpi-input" accept=".xlsx,.xls,.csv"
                                            class="d-none" />
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h5>Drag &amp; Drop Excel File KPI</h5>
                                        <p>atau klik untuk memilih file dari komputer Anda</p>
                                        <button class="btn-browse" type="button" id="btn-browse-kpi"
                                            onclick="document.getElementById('excel-file-kpi-input').click();">
                                            <i class="fas fa-folder-open mr-1"></i> Browse File
                                        </button>
                                        <div class="file-types">
                                            Supported: .xlsx, .xls, .csv &bull; Max 200MB
                                        </div>
                                    </div>

                                    <!-- Process Steps Container -->
                                    <div id="kpi-process-container" style="display: none;">
                                        <div class="upload-file-card mb-3">
                                            <div class="file-details">
                                                <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                                <div class="file-text">
                                                    <div class="file-name" id="kpi-file-name">-</div>
                                                    <div class="file-size" id="kpi-file-size">-</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="kpi-btn-change-file" title="Ganti File">
                                                <i class="fas fa-redo-alt mr-1"></i> Ganti
                                            </button>
                                        </div>

                                        <div class="upload-steps-list">
                                            <div class="upload-step-item" id="kpi-step-read">
                                                <div class="step-icon-container"><i class="fas fa-file"></i></div>
                                                <div class="step-label-container"><span class="step-label">1. Upload
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="kpi-step-parse">
                                                <div class="step-icon-container"><i class="fas fa-table"></i></div>
                                                <div class="step-label-container"><span class="step-label">2. Validasi
                                                        File...</span></div>
                                            </div>
                                            <div class="upload-step-item" id="kpi-step-upload">
                                                <div class="step-icon-container"><i class="fas fa-database"></i></div>
                                                <div class="step-label-container" style="flex-grow: 1;">
                                                    <span class="step-label">3. Uploading Database...</span>
                                                    <div class="batch-progress-wrapper" id="kpi-batch-progress"
                                                        style="display: none;">
                                                        <div class="batch-progress-details">
                                                            <span id="kpi-progress-text">0 / 0 baris</span>
                                                            <span id="kpi-progress-percent">0%</span>
                                                        </div>
                                                        <div class="batch-progress-bar-bg">
                                                            <div class="batch-progress-bar-fill" id="kpi-progress-fill">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="upload-step-item" id="kpi-step-finalize">
                                                <div class="step-icon-container"><i class="fas fa-sync-alt"></i></div>
                                                <div class="step-label-container"><span class="step-label">4. Finalisasi
                                                        Data...</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Sheet Selection & Action -->
                                <div class="col-lg-6" id="kpi-col-right" style="display: none;">
                                    <div class="card border h-100 shadow-none bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label for="kpi-sheet-select"
                                                        class="small font-weight-bold text-gray-800 mb-0">
                                                        <i class="fas fa-layer-group text-primary mr-1"></i> Pilih Sheet
                                                        Excel
                                                    </label>
                                                    <span class="badge badge-primary px-2 py-1" id="kpi-sheet-badge">0
                                                        Sheet</span>
                                                </div>
                                                <p class="small text-muted mb-2">Pilih lembar kerja (sheet) yang berisi
                                                    data KPI:</p>
                                                <select class="form-control form-control-sm font-weight-bold mb-3"
                                                    id="kpi-sheet-select"></select>

                                                <div class="alert alert-light border py-2 px-3 small mb-3"
                                                    id="kpi-sheet-info">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> <span
                                                        id="kpi-sheet-info-text">Silakan pilih sheet di atas.</span>
                                                </div>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm"
                                                    id="kpi-btn-submit">
                                                    <i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Data KPI Modal -->
            <div class="modal fade" id="deleteDataModalKpi" tabindex="-1" role="dialog"
                aria-labelledby="deleteDataModalKpiLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteDataModalKpiLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus KPI Master Data
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus
                                        permanen</p>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="deleteKpiYearSelect" class="small font-weight-bold text-gray-600">Tahun
                                        Periode</label>
                                    <select class="form-control form-control-sm" id="deleteKpiYearSelect">
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php
                                        $curY = (int) date('Y');
                                        for ($y = 2026; $y <= $curY + 5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y === $curY) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-kpi"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Data KPI
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!defined('SPA_MODE')) {
                include FRONTEND_PATH . 'components/footer.php';
            } ?>

            <!-- DataTables JS -->
            <script src="frontend/vendor/datatables/jquery.dataTables.min.js"></script>
            <script src="frontend/vendor/datatables/dataTables.bootstrap4.min.js"></script>

            <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                    if (currentSeg && currentSeg !== '#seg-kpi') {
                        $('#kpi-action-buttons').hide();
                    } else {
                        $('#kpi-action-buttons').show();
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
                        if (targetSeg === '#seg-kpi') {
                            $('#kpi-action-buttons').fadeIn(200);
                        } else {
                            $('#kpi-action-buttons').fadeOut(200);
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
                fetch('api/get_periods.php?all=1')
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

                // 2b. Confirm Delete Data GR
                var btnConfirmDeleteInboundGr = document.getElementById('btn-confirm-delete-inbound-gr');
                if (btnConfirmDeleteInboundGr) {
                    btnConfirmDeleteInboundGr.addEventListener('click', function () {
                        var m = document.getElementById('deleteInboundGrMonthSelect') ? document.getElementById('deleteInboundGrMonthSelect').value : '';
                        var b = document.getElementById('deleteInboundGrBatchSelect') ? document.getElementById('deleteInboundGrBatchSelect').value : '';
                        var y = document.getElementById('deleteInboundGrYearSelect') ? document.getElementById('deleteInboundGrYearSelect').value : '';
                        var period = (m && y) ? (m + ' ' + y + (b ? '-Batch' + b : '')) : null;

                        var msg = period
                            ? "Ingin menghapus Data GR untuk periode " + period.toUpperCase() + "?"
                            : "Ingin menghapus SEMUA Data GR dari database?";

                        var executeInboundGrDelete = function () {
                            showProcessingModal();
                            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
                            fetch('api/delete_inbound_gr.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body: JSON.stringify({ action: 'delete_period', periode: period, month: m, year: y, batch: b, csrf_token: csrfToken })
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Berhasil!', res.message || 'Data GR berhasil dihapus.', 'success');
                                        } else {
                                            alert(res.message || 'Data GR berhasil dihapus.');
                                        }
                                        $('#deleteDataModalInboundGr').modal('hide');
                                        if ($.fn.DataTable && $('#dataTableInboundGr').length) {
                                            $('#dataTableInboundGr').DataTable().ajax.reload();
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
                                    executeInboundGrDelete();
                                }
                            });
                        } else {
                            if (confirm("Apakah Anda YAKIN " + msg)) {
                                executeInboundGrDelete();
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

                // 4. Confirm Delete PR Forwarder Data
                var btnConfirmDeleteForwarder = document.getElementById('btn-confirm-delete-forwarder');
                if (btnConfirmDeleteForwarder) {
                    btnConfirmDeleteForwarder.addEventListener('click', function () {
                        var m = document.getElementById('deleteForwarderMonthSelect') ? document.getElementById('deleteForwarderMonthSelect').value : '';
                        var b = document.getElementById('deleteForwarderBatchSelect') ? document.getElementById('deleteForwarderBatchSelect').value : '';
                        var y = document.getElementById('deleteForwarderYearSelect') ? document.getElementById('deleteForwarderYearSelect').value : '';
                        var period = (m && y) ? (m + ' ' + y + (b ? '-Batch' + b : '')) : null;

                        var isTruncate = (!m && !y && !b);
                        var msg = isTruncate
                            ? "Ingin menghapus SEMUA Data Master PR Forwarder dari database?"
                            : (period ? "Ingin menghapus data PR Forwarder untuk periode " + period.toUpperCase() + "?" : "Ingin menghapus data PR Forwarder untuk periode yang dipilih?");

                        var executeForwarderDelete = function () {
                            showProcessingModal();
                            fetch('api/delete_outbound_forwarder.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: isTruncate ? 'truncate_all' : 'delete_period',
                                    periode: period,
                                    month: m,
                                    year: y,
                                    batch: b
                                })
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Berhasil!', res.message || 'Data PR Forwarder berhasil dihapus.', 'success');
                                        } else {
                                            alert(res.message || 'Data PR Forwarder berhasil dihapus.');
                                        }
                                        $('#deleteDataModalForwarder').modal('hide');
                                        if (typeof forwarderTable !== 'undefined' && forwarderTable) {
                                            forwarderTable.ajax.reload();
                                            if (typeof refreshForwarderFilters === 'function') refreshForwarderFilters();
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
                                    executeForwarderDelete();
                                }
                            });
                        } else {
                            if (confirm("Apakah Anda YAKIN " + msg)) {
                                executeForwarderDelete();
                            }
                        }
                    });
                }
            </script>
            <?php if (!defined('SPA_MODE')): ?>
    </body>

    </html>
<?php endif; ?>