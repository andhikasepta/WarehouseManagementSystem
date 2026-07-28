<?php
require_once __DIR__ . '/auth.php';
checkModuleAccess('inbound');

$pageTitle = 'Inbound - Dashboard Warehouse';
include 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'inbound'; 
                include 'components/navbar.php'; 
                ?>

                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Inbound Management</h1>
                    </div>

                    <style>
                        .border-right-divider {
                            border-right: 1px solid #e3e6f0;
                        }
                        @media (max-width: 767.98px) {
                            .border-right-divider {
                                border-right: none;
                                border-bottom: 1px solid #e3e6f0;
                                padding-bottom: 12px;
                            }
                        }
                    </style>

                    <!-- Filter Control Bar (On Top of Large Card) -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body py-3 px-4">
                            <div class="form-row align-items-center">
                                <!-- Bagian Dropdown -->
                                <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                    <label for="filter-bagian" class="small font-weight-bold text-gray-700 mb-1">Bagian</label>
                                    <select class="form-control form-control-sm custom-select custom-select-sm" id="filter-bagian">
                                        <option value="">-- Semua Bagian --</option>
                                    </select>
                                </div>

                                <!-- PIC PO Dropdown -->
                                <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                    <label for="filter-pic-po" class="small font-weight-bold text-gray-700 mb-1">PIC PO</label>
                                    <select class="form-control form-control-sm custom-select custom-select-sm" id="filter-pic-po">
                                        <option value="">-- Semua PIC PO --</option>
                                    </select>
                                </div>

                                <!-- Status Dropdown -->
                                <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                    <label for="filter-status" class="small font-weight-bold text-gray-700 mb-1">Status</label>
                                    <select class="form-control form-control-sm custom-select custom-select-sm" id="filter-status">
                                        <option value="">-- Semua Status --</option>
                                    </select>
                                </div>

                                <!-- Search Bar (No. PO / Item) -->
                                <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                                    <label for="search-po-item" class="small font-weight-bold text-gray-700 mb-1">Cari No. PO / Item</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm" id="search-po-item" placeholder="No. PO / Item...">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary btn-sm" type="button" id="btn-search-po">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php 
                    $monthsIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $currentDateStr = date('d') . ' ' . $monthsIndo[date('n') - 1] . ' ' . date('Y');
                    ?>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Alur Status PO Inbound <span class="text-muted font-weight-normal ml-1" id="large-card-date">(<?php echo $currentDateStr; ?>)</span>
                                    </h6>
                                </div>
                                <div class="card-body py-3 px-4">
                                    <div class="row text-center align-items-center">
                                        <div class="col-md border-right-divider mb-3 mb-md-0">
                                            <div class="h3 font-weight-bold text-primary mb-1" id="card-po-diterbitkan">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted text-nowrap mb-2">PO DITERBITKAN</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar" id="bar-po-diterbitkan" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0">
                                            <div class="h3 font-weight-bold text-warning mb-1" id="card-jatuh-tempo-14">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted text-nowrap mb-2">JATUH TEMPO ≤14 HARI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-warning" role="progressbar" id="bar-jatuh-tempo-14" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0">
                                            <div class="h3 font-weight-bold text-danger mb-1" id="card-terlambat-belum-gr">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted text-nowrap mb-2">TERLAMBAT (BELUM GR)</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-danger" role="progressbar" id="bar-terlambat-belum-gr" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- Sudah GR -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0">
                                            <div class="h3 font-weight-bold text-success mb-1" id="card-sudah-gr">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted text-nowrap mb-2">SUDAH GR</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" id="bar-sudah-gr" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- Sudah Registrasi -->
                                        <div class="col-md mb-3 mb-md-0">
                                            <div class="h3 font-weight-bold text-info mb-1" id="card-sudah-registrasi">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted text-nowrap mb-2">SUDAH REGISTRASI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-info" role="progressbar" id="bar-sudah-registrasi" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row (Inline 6 Cards, No Icons) -->
                    <div class="row">
                        <!-- Total PO Aktif -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        TOTAL PO AKTIF</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-total-po-aktif">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- PO Terlambat (Belum GR) -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        PO TERLAMBAT (BELUM GR)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-po-terlambat">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- GR Tepat Waktu -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        GR TEPAT WAKTU</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-gr-tepat-waktu">0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Rata-rata Keterlambatan GR -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        RATA-RATA KETERLAMBATAN GR</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-avg-keterlambatan-gr">0 Hari</div>
                                </div>
                            </div>
                        </div>

                        <!-- Nilai Barang Diterima -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        NILAI BARANG DITERIMA</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-nilai-barang-diterima">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <!-- Menunggu Registrasi -->
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body p-3">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1 text-nowrap" style="font-size: 0.68rem;">
                                        MENUNGGU REGISTRASI</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 text-nowrap" id="card-menunggu-registrasi">0 Unit</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

<?php include 'components/footer.php'; ?>

</body>
</html>
