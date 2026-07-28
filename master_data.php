<?php
require_once __DIR__ . '/auth.php';
checkModuleAccess('master_data');

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';
$userModules = is_array($currentUser['allowed_modules'] ?? null) ? $currentUser['allowed_modules'] : [];

$canAccessInboundMaster = false;
$canAccessStorageMaster = false;
$canAccessOutboundMaster = false;

if ($userRole === 'head_warehouse_admin' || $userRole === 'superadmin') {
    $canAccessInboundMaster = true;
    $canAccessStorageMaster = true;
    $canAccessOutboundMaster = true;
} elseif ($userRole === 'inbound_admin') {
    $canAccessInboundMaster = true;
} elseif ($userRole === 'warehouse_admin') {
    $canAccessStorageMaster = true;
} elseif ($userRole === 'outbound_admin') {
    $canAccessOutboundMaster = true;
} else {
    // Custom / Admin Operasional
    $canAccessInboundMaster = in_array('inbound', $userModules);
    $canAccessStorageMaster = in_array('warehouse', $userModules);
    $canAccessOutboundMaster = in_array('outbound', $userModules);
    
    if (!$canAccessInboundMaster && !$canAccessStorageMaster && !$canAccessOutboundMaster) {
        $canAccessInboundMaster = true;
        $canAccessStorageMaster = true;
        $canAccessOutboundMaster = true;
    }
}

$defaultMasterSegment = '';
if ($canAccessStorageMaster) {
    $defaultMasterSegment = 'storage';
} elseif ($canAccessInboundMaster) {
    $defaultMasterSegment = 'inbound';
} elseif ($canAccessOutboundMaster) {
    $defaultMasterSegment = 'outbound';
}

$pageTitle = 'Master Data - Dashboard Warehouse';
include 'components/header.php';
?>
    
    <!-- DataTables CSS -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Basic Select2 Bootstrap 4 overrides */
        .select2-container .select2-selection--single { height: 31px; border: 1px solid #d1d3e2; border-radius: 0.2rem; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 29px; }
    </style>
    
    <style>
        .nav-tabs .nav-link { font-weight: bold; }
        .nav-tabs .nav-link.active { color: #4e73df; }
        .nav-pills .nav-link { color: #5a5c69; border-radius: 0.35rem; transition: all 0.2s ease-in-out; }
        .nav-pills .nav-link.active { background-color: #4e73df; color: #fff; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .table-responsive { overflow-x: auto; }
        #dataTableAsset th, #dataTableAsset td { white-space: nowrap; }
        #dataTableAsset td:nth-child(1) { white-space: normal !important; min-width: 200px; }
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
                include 'components/navbar.php'; 
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
                    <ul class="nav nav-pills nav-justified mb-4 p-2 bg-light rounded shadow-sm" id="masterSegmentTabs" role="tablist" style="border: 1px solid #e3e6f0;">
                        <!-- Inbound Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessInboundMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'inbound') ? 'active' : ''; ?>" id="seg-inbound-tab" data-toggle="pill" href="#seg-inbound" role="tab" aria-controls="seg-inbound" aria-selected="<?php echo ($defaultMasterSegment === 'inbound') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-box-open mr-2"></i> Inbound Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted" id="seg-inbound-tab" href="javascript:void(0)" style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;" title="Modul ini terkunci (Khusus Hak Akses Inbound Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Inbound Master Data
                                </a>
                            <?php endif; ?>
                        </li>

                        <!-- Storage Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessStorageMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'storage') ? 'active' : ''; ?>" id="seg-storage-tab" data-toggle="pill" href="#seg-storage" role="tab" aria-controls="seg-storage" aria-selected="<?php echo ($defaultMasterSegment === 'storage') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-warehouse mr-2"></i> Storage Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted" id="seg-storage-tab" href="javascript:void(0)" style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;" title="Modul ini terkunci (Khusus Hak Akses Warehouse Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Storage Master Data
                                </a>
                            <?php endif; ?>
                        </li>

                        <!-- Outbound Segment Tab -->
                        <li class="nav-item" role="presentation">
                            <?php if ($canAccessOutboundMaster): ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 <?php echo ($defaultMasterSegment === 'outbound') ? 'active' : ''; ?>" id="seg-outbound-tab" data-toggle="pill" href="#seg-outbound" role="tab" aria-controls="seg-outbound" aria-selected="<?php echo ($defaultMasterSegment === 'outbound') ? 'true' : 'false'; ?>">
                                    <i class="fas fa-truck-loading mr-2"></i> Outbound Master Data
                                </a>
                            <?php else: ?>
                                <a class="nav-link font-weight-bold text-uppercase py-2 disabled text-muted" id="seg-outbound-tab" href="javascript:void(0)" style="cursor: not-allowed; opacity: 0.55; background-color: #f1f3f9;" title="Modul ini terkunci (Khusus Hak Akses Outbound Administrator)">
                                    <i class="fas fa-lock mr-2 text-secondary"></i> Outbound Master Data
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>

                    <!-- Master Segments Content -->
                    <div class="tab-content" id="masterSegmentTabsContent">

                        <?php if ($canAccessInboundMaster): ?>
                        <!-- 1. INBOUND MASTER DATA -->
                        <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'inbound') ? 'show active' : ''; ?>" id="seg-inbound" role="tabpanel" aria-labelledby="seg-inbound-tab">
                            <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
                                <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-box-open mr-2"></i>Menu Master Data Inbound
                                    </h6>
                                    <div class="mt-2 mt-sm-0">
                                        <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2" data-toggle="modal" data-target="#uploadExcelModalInbound">
                                            <i class="fas fa-file-import mr-1"></i> Import Excel Inbound
                                        </button>
                                        <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal" data-target="#deleteDataModalInbound">
                                            <i class="fas fa-trash-alt mr-1"></i> Hapus Data Inbound
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body text-center py-5">
                                    <div class="mb-3">
                                        <span class="fa-stack fa-2x text-gray-400">
                                            <i class="fas fa-circle fa-stack-2x text-light"></i>
                                            <i class="fas fa-box-open fa-stack-1x text-secondary"></i>
                                        </span>
                                    </div>
                                    <h4 class="font-weight-bold text-gray-800 mb-2">Master Data Inbound</h4>
                                    <p class="text-muted max-width-500 mx-auto mb-4" style="max-width: 500px;">
                                        Belum ada data master untuk modul Inbound.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($canAccessStorageMaster): ?>
                        <!-- 2. STORAGE MASTER DATA (FUNCTIONAL SUB MASTER DATA) -->
                        <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'storage') ? 'show active' : ''; ?>" id="seg-storage" role="tabpanel" aria-labelledby="seg-storage-tab">
                            
                            <!-- Storage Action Buttons Header Bar -->
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-warehouse mr-2"></i>Menu Master Data Storage
                                </h6>
                                <div class="mt-2 mt-sm-0">
                                    <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2" data-toggle="modal" data-target="#uploadExcelModal">
                                        <i class="fas fa-file-import mr-1"></i> Import Excel Storage
                                    </button>
                                    <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal" data-target="#deleteDataModal">
                                        <i class="fas fa-trash-alt mr-1"></i> Hapus Data Storage
                                    </button>
                                </div>
                            </div>

                            <!-- Sub Master Data Tabs for Storage -->
                            <ul class="nav nav-tabs mb-4" id="masterDataTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="asset-tab" data-toggle="tab" href="#asset-data" role="tab" aria-controls="asset-data" aria-selected="true">
                                        <i class="fas fa-boxes mr-1"></i> Data Asset
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="rack-tab" data-toggle="tab" href="#rack-data" role="tab" aria-controls="rack-data" aria-selected="false">
                                        <i class="fas fa-th mr-1"></i> Data Utilisasi Rack
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="utilisasi-tab" data-toggle="tab" href="#utilisasi-data" role="tab" aria-controls="utilisasi-data" aria-selected="false">
                                        <i class="fas fa-chart-pie mr-1"></i> Utilisasi Area/Rack
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="masterDataTabsContent">
                                
                                <!-- Asset Data Tab -->
                                <div class="tab-pane fade show active" id="asset-data" role="tabpanel" aria-labelledby="asset-tab">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Tabel Data Asset</h6>
                                        </div>
                                        <div class="card-body">
                                            
                                            <!-- Custom Filters for Asset -->
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label>Periode (Month/Year):</label>
                                                    <select id="filterAssetPeriode" class="form-control form-control-sm">
                                                        <option value="">All Periods</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label>Sub Location:</label>
                                                    <select id="filterAssetSubLocation" class="form-control form-control-sm">
                                                        <option value="">All Sub Locations</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end justify-content-end" id="assetSearchContainer">
                                                </div>
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm" id="dataTableAsset" width="100%" cellspacing="0">
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
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">Tabel Master Utilisasi Rack</h6>
                                        </div>
                                        <div class="card-body">
                                            
                                            <!-- Custom Filters for Rack -->
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label>Category:</label>
                                                    <select id="filterRackCategory" class="form-control form-control-sm">
                                                        <option value="">All Categories</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label>Rack Name:</label>
                                                    <select id="filterRackName" class="form-control form-control-sm">
                                                        <option value="">All Racks</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end justify-content-end" id="rackSearchContainer">
                                                </div>
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm" id="dataTableRack" width="100%" cellspacing="0">
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
                                <div class="tab-pane fade" id="utilisasi-data" role="tabpanel" aria-labelledby="utilisasi-tab">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Data Utilisasi Area / Rack</h6>
                                            <button class="btn btn-success btn-sm" type="button" id="btn-save-utilisasi-all" disabled>
                                                <i class="fas fa-save mr-1"></i> Simpan Semua
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            
                                            <!-- Period Selector -->
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label class="small font-weight-bold text-gray-600">Bulan <span class="text-danger">*</span></label>
                                                    <select id="utilisasi-month-select" class="form-control form-control-sm">
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
                                                    <label class="small font-weight-bold text-gray-600">Tahun <span class="text-danger">*</span></label>
                                                    <select id="utilisasi-year-select" class="form-control form-control-sm">
                                                        <option value="">-- Pilih Tahun --</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-end">
                                                    <button class="btn btn-primary btn-sm btn-block" id="btn-load-utilisasi" disabled>
                                                        <i class="fas fa-search mr-1"></i> Tampilkan Data
                                                    </button>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-end justify-content-end" id="utilisasiSearchContainer">
                                                </div>
                                            </div>

                                            <div id="utilisasi-table-info" class="text-center text-gray-500 py-4" style="display:block;">
                                                <i class="fas fa-info-circle fa-2x mb-2 text-gray-300"></i>
                                                <p class="mb-0">Pilih <strong>Bulan</strong> dan <strong>Tahun</strong> lalu klik <strong>Tampilkan Data</strong> untuk memuat data utilisasi.</p>
                                            </div>
                                            
                                            <div class="table-responsive" id="utilisasi-table-wrapper" style="display:none; max-height: 500px; overflow-y: auto;">
                                                <table class="table table-bordered table-sm table-hover" id="dataTableUtilisasi" width="100%" cellspacing="0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">Label (Sub Location)</th>
                                                            <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">Rack Group</th>
                                                            <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1;">Category</th>
                                                            <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1; width: 120px;">Qty (Unit)</th>
                                                            <th style="position: sticky; top: 0; background-color: #f8f9fc; z-index: 1; width: 140px;">Capacity (%)</th>
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
                        <div class="tab-pane fade <?php echo ($defaultMasterSegment === 'outbound') ? 'show active' : ''; ?>" id="seg-outbound" role="tabpanel" aria-labelledby="seg-outbound-tab">
                            <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
                                <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-truck-loading mr-2"></i>Menu Master Data Outbound
                                    </h6>
                                    <div class="mt-2 mt-sm-0">
                                        <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2" data-toggle="modal" data-target="#uploadExcelModalOutbound">
                                            <i class="fas fa-file-import mr-1"></i> Import Excel Outbound
                                        </button>
                                        <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal" data-target="#deleteDataModalOutbound">
                                            <i class="fas fa-trash-alt mr-1"></i> Hapus Data Outbound
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body text-center py-5">
                                    <div class="mb-3">
                                        <span class="fa-stack fa-2x text-gray-400">
                                            <i class="fas fa-circle fa-stack-2x text-light"></i>
                                            <i class="fas fa-truck-loading fa-stack-1x text-secondary"></i>
                                        </span>
                                    </div>
                                    <h4 class="font-weight-bold text-gray-800 mb-2">Master Data Outbound</h4>
                                    <p class="text-muted max-width-500 mx-auto mb-4" style="max-width: 500px;">
                                        Belum ada data master untuk modul Outbound. Silakan gunakan tombol Import Excel di atas untuk mengunggah master data Outbound.
                                    </p>
                                    <span class="badge badge-light border px-3 py-2 text-muted font-weight-bold" style="font-size: 0.85rem;">
                                        <i class="fas fa-info-circle mr-1"></i> Segmen Active Menu: Outbound Master Data
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

<?php include 'components/footer.php'; ?>

    <!-- Delete Data Modal-->
    <div class="modal fade" id="deleteDataModal" tabindex="-1" role="dialog" aria-labelledby="deleteDataModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content upload-modal-content">
                <div class="modal-header upload-modal-header" style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                    <h5 class="modal-title text-white" id="deleteDataModalLabel">
                        <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Data
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body upload-modal-body">
                    <div class="p-3">
                        <div class="text-center text-gray-600 mb-4">
                            <h3 class="text-danger font-weight-bold mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Peringatan</h3>
                            <p class="mb-0" style="font-size: 1.1rem;">Data yang Anda pilih akan dihapus secara permanen dari sistem dan tidak dapat dikembalikan.</p>
                        </div>
                        <div class="form-group mb-3">
                            <label for="deleteMonthSelect" class="small font-weight-bold text-gray-600">Bulan</label>
                            <select class="form-control form-control-sm" id="deleteMonthSelect">
                                <option value="">-- Pilih Bulan --</option>
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label for="deleteYearSelect" class="small font-weight-bold text-gray-600">Tahun</label>
                            <select class="form-control form-control-sm" id="deleteYearSelect">
                                <option value="">-- Pilih Tahun --</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal" style="border-radius: 6px; font-weight: 600;">Cancel</button>
                            <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete" style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
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
                    <div class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                        <span class="small font-weight-bold text-gray-700">
                            <i class="fas fa-download mr-1 text-success"></i> Download Template:
                        </span>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold mr-1" id="btn-template-asset">
                                <i class="fas fa-file-excel mr-1"></i> Template Asset
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info font-weight-bold" id="btn-template-rack">
                                <i class="fas fa-file-excel mr-1"></i> Template Rack
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12" id="uploadModalLeftCol">
                            <div class="form-group mb-3">
                                <label for="upload-data-type" class="small font-weight-bold text-gray-600">Tipe Data</label>
                                <select class="form-control form-control-sm" id="upload-data-type">
                                    <option value="asset">Data Asset</option>
                                    <option value="rack">Data Utilisasi Rack</option>
                                </select>
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
    <div class="modal fade" id="uploadExcelModalInbound" tabindex="-1" role="dialog" aria-labelledby="uploadExcelModalInboundLabel" aria-hidden="true">
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
                    <div class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                        <span class="small font-weight-bold text-gray-700">
                            <i class="fas fa-download mr-1 text-success"></i> Download Template:
                        </span>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" id="btn-template-inbound">
                                <i class="fas fa-file-excel mr-1"></i> Template Inbound
                            </button>
                        </div>
                    </div>
                    <div class="upload-drop-zone border rounded p-4 text-center bg-light">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold">Drag &amp; Drop Excel File Inbound</h5>
                        <p class="text-muted small">atau pilih file dari komputer Anda</p>
                        <input type="file" id="excel-file-inbound-input" accept=".xlsx,.xls,.csv" class="d-none" />
                        <button class="btn btn-primary btn-sm px-3 font-weight-bold" type="button" onclick="document.getElementById('excel-file-inbound-input').click();">
                            <i class="fas fa-folder-open mr-1"></i> Browse File
                        </button>
                        <div class="small text-muted mt-2">Formats: .xlsx, .xls, .csv</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Excel Outbound Modal -->
    <div class="modal fade" id="uploadExcelModalOutbound" tabindex="-1" role="dialog" aria-labelledby="uploadExcelModalOutboundLabel" aria-hidden="true">
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
                    <div class="alert alert-light border mb-3 p-2 d-flex align-items-center justify-content-between">
                        <span class="small font-weight-bold text-gray-700">
                            <i class="fas fa-download mr-1 text-success"></i> Download Template:
                        </span>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" id="btn-template-outbound">
                                <i class="fas fa-file-excel mr-1"></i> Template Outbound
                            </button>
                        </div>
                    </div>
                    <div class="upload-drop-zone border rounded p-4 text-center bg-light">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold">Drag &amp; Drop Excel File Outbound</h5>
                        <p class="text-muted small">atau pilih file dari komputer Anda</p>
                        <input type="file" id="excel-file-outbound-input" accept=".xlsx,.xls,.csv" class="d-none" />
                        <button class="btn btn-primary btn-sm px-3 font-weight-bold" type="button" onclick="document.getElementById('excel-file-outbound-input').click();">
                            <i class="fas fa-folder-open mr-1"></i> Browse File
                        </button>
                        <div class="small text-muted mt-2">Formats: .xlsx, .xls, .csv</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Data Inbound Modal -->
    <div class="modal fade" id="deleteDataModalInbound" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-trash-alt mr-2"></i>Hapus Master Data Inbound</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h4 class="text-danger font-weight-bold mb-2">Hapus Data Master Inbound</h4>
                    <p class="text-muted mb-4">Apakah Anda yakin ingin menghapus data master Inbound?</p>
                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-danger px-4" type="button" onclick="alert('Data Master Inbound belum tersedia untuk dihapus.'); $('#deleteDataModalInbound').modal('hide');">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Data Outbound Modal -->
    <div class="modal fade" id="deleteDataModalOutbound" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-trash-alt mr-2"></i>Hapus Master Data Outbound</h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h4 class="text-danger font-weight-bold mb-2">Hapus Data Master Outbound</h4>
                    <p class="text-muted mb-4">Apakah Anda yakin ingin menghapus data master Outbound?</p>
                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-danger px-4" type="button" onclick="alert('Data Master Outbound belum tersedia untuk dihapus.'); $('#deleteDataModalOutbound').modal('hide');">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables JS -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <!-- SheetJS (xlsx) for Excel import/export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/excel-upload.js?v=<?= time() ?>"></script>
    <script src="js/formula-controller.js?v=<?= time() ?>"></script>
    <script src="js/master-data.js?v=<?= time() ?>"></script>
    
    <script>
        $(document).ready(function() {
            // Restore active segment tab from localStorage if available for current role
            var activeSegment = localStorage.getItem('activeMasterSegmentTab');
            if (activeSegment && $('#masterSegmentTabs a[href="' + activeSegment + '"]').length > 0) {
                $('#masterSegmentTabs a[href="' + activeSegment + '"]').tab('show');
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
                    setTimeout(function() {
                        if ($.fn.DataTable) {
                            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                        }
                    }, 150);
                } else {
                    $('#storage-action-buttons').fadeOut(200);
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
                setTimeout(function() {
                    if ($.fn.DataTable) {
                        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                    }
                }, 150);
            });
        });
        
        // Delete Data Logic
        // Load periods for the delete dropdown
        fetch('api/get_periods.php')
            .then(response => response.json())
            .then(result => {
                if(result.status === 'success' && result.data) {
                    var mSel = document.getElementById('deleteMonthSelect');
                    var ySel = document.getElementById('deleteYearSelect');
                    if (mSel && ySel) {
                        var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                        months.forEach(function (item) {
                            var opt = document.createElement('option');
                            opt.value = item; opt.textContent = item; mSel.appendChild(opt);
                        });
                        result.data.forEach(function (item) {
                            var parts = item.split(' ');
                            if(parts.length >= 2) {
                                var year = parts[1];
                                var existing = Array.from(ySel.options).find(opt => opt.value === year);
                                if(!existing) {
                                    var opt = document.createElement('option');
                                    opt.value = year; opt.textContent = year; ySel.appendChild(opt);
                                }
                            }
                        });
                    }
                }
            });

        var btnConfirmDelete = document.getElementById('btn-confirm-delete');
        if (btnConfirmDelete) {
            btnConfirmDelete.addEventListener('click', function () {
                var delMonth = document.getElementById('deleteMonthSelect');
                var delYear = document.getElementById('deleteYearSelect');
                if (!delMonth || !delMonth.value || !delYear || !delYear.value) {
                    alert('Please select both Month and Year to delete.');
                    return;
                }
                var periodToDelete = delMonth.value + ' ' + delYear.value;
                if (!confirm("Are you SURE you want to delete all data for " + periodToDelete.toUpperCase() + "? This cannot be undone.")) return;

                fetch('api/delete_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ periode: periodToDelete })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        alert('Data deleted successfully.');
                        $('#deleteDataModal').modal('hide');
                        $('#dataTableAsset').DataTable().ajax.reload();
                    } else {
                        alert('Failed to delete data: ' + res.message);
                    }
                });
            });
        }
    </script>
</html>
