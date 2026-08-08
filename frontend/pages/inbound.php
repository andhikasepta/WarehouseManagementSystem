<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('inbound');

$pageTitle = 'Inbound - Dashboard Warehouse';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'inbound'; 
                include FRONTEND_PATH . 'components/navbar.php'; 
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
                        .custom-select {
                            background: #ffffff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3e%3cpath fill='%235a5c69' d='M0 0l5 6 5-6z'/%3e%3c/svg%3e") no-repeat right 0.75rem center/10px 6px !important;
                            padding-right: 1.75rem;
                        }
                        .status-card-clickable {
                            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                            cursor: pointer;
                            border-radius: 6px;
                            padding: 0.5rem 0.65rem !important;
                            margin: 0 0.2rem;
                        }
                        .status-card-clickable:hover {
                            background-color: #f1f5f9 !important;
                            box-shadow: inset 0 0 0 1px #cbd5e1 !important;
                        }
                        @media (max-width: 767.98px) {
                            .border-right-divider {
                                border-right: none;
                                border-bottom: 1px solid #e3e6f0;
                                padding-bottom: 12px;
                            }
                        }
                    </style>

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
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="PO DITERBITKAN">
                                            <div class="h3 font-weight-bold text-primary mb-1" id="card-po-diterbitkan">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO DITERBITKAN</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar" id="bar-po-diterbitkan" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="JATUH TEMPO ≤14 HARI">
                                            <div class="h3 font-weight-bold text-warning mb-1" id="card-jatuh-tempo-14">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">JATUH TEMPO ≤14 HARI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-warning" role="progressbar" id="bar-jatuh-tempo-14" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="TERLAMBAT (BELUM GR)">
                                            <div class="h3 font-weight-bold text-danger mb-1" id="card-terlambat-belum-gr">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TERLAMBAT (BELUM GR)</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-danger" role="progressbar" id="bar-terlambat-belum-gr" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- Sudah GR -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="SUDAH GR">
                                            <div class="h3 font-weight-bold text-success mb-1" id="card-sudah-gr">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">SUDAH GR</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" id="bar-sudah-gr" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- Sudah Registrasi -->
                                        <div class="col-md mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="SUDAH REGISTRASI">
                                            <div class="h3 font-weight-bold text-info mb-1" id="card-sudah-registrasi">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">SUDAH REGISTRASI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-info" role="progressbar" id="bar-sudah-registrasi" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row (1 Single Inline Row of 6 Cards with Tight Spacing) -->
                    <div class="row mx-n1 mb-4">
                        <!-- Total PO Aktif -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-primary shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        TOTAL PO AKTIF</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-total-po-aktif">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- PO Terlambat (Belum GR) -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-danger shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        PO TERLAMBAT (BELUM GR)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-po-terlambat">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- GR Tepat Waktu -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-success shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        GR TEPAT WAKTU</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-gr-tepat-waktu">0%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Rata-rata Keterlambatan GR -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-warning shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        RATA-RATA KETERLAMBATAN GR</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-avg-keterlambatan-gr">0 Hari</div>
                                </div>
                            </div>
                        </div>

                        <!-- Nilai Barang Diterima -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-info shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        NILAI BARANG DITERIMA</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-nilai-barang-diterima">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <!-- Menunggu Registrasi -->
                        <div class="col-xl-2 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-secondary shadow h-100 py-1 inbound-metric-card">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        MENUNGGU REGISTRASI</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-menunggu-registrasi">0 Unit</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inbound Charts Row (3 Cards: Distribusi Status PO, Nilai PO per Bagian, Tren GR Bulanan) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Distribusi Status PO (Circle Graph / Doughnut Chart) -->
                        <div class="col-xl-4 col-lg-4 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Status PO</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-2 pb-2" style="height: 300px; position: relative;">
                                        <canvas id="distribusiStatusPoChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nilai PO per Bagian (Bar Chart) -->
                        <div class="col-xl-4 col-lg-4 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Nilai PO per Bagian</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar pt-2 pb-2" style="height: 300px; position: relative;">
                                        <canvas id="nilaiPoBagianChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tren GR Bulanan (Line Graph) -->
                        <div class="col-xl-4 col-lg-4 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Tren GR Bulanan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area pt-2 pb-2" style="height: 300px; position: relative;">
                                        <canvas id="trenGrBulananChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- PO Status Detail Modal -->
            <div class="modal fade" id="poStatusDetailModal" tabindex="-1" role="dialog" aria-labelledby="poStatusDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow" style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                        <!-- Header with distinct light background -->
                        <div class="modal-header border-bottom py-3 px-4 align-items-center" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                            <h5 class="modal-title font-weight-bold text-gray-800 my-auto" id="poStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                <span id="modalStatusTitleText" class="font-weight-bold text-primary"></span>
                            </h5>
                            <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal" aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <!-- Clean White Body -->
                        <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                            <!-- Table Container (Clean Minimalist White) -->
                            <div class="table-responsive border rounded" style="border-color: #eaecf4 !important;">
                                <table class="table text-center mb-0" id="tablePoStatusDetail" style="font-size: 0.85rem;">
                                    <thead class="bg-light text-gray-700 font-weight-bold" style="border-bottom: 2px solid #eaecf4;">
                                        <tr>
                                            <th class="py-2 border-top-0">No. PO</th>
                                            <th class="py-2 border-top-0">Item / Deskripsi</th>
                                            <th class="py-2 border-top-0">Bagian</th>
                                            <th class="py-2 border-top-0">PIC PO</th>
                                            <th class="py-2 border-top-0">Tanggal PO</th>
                                            <th class="py-2 border-top-0">Jatuh Tempo</th>
                                            <th class="py-2 border-top-0">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="py-5 text-muted bg-white">
                                                <i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
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

<?php include FRONTEND_PATH . 'components/footer.php'; ?>

<!-- Page level plugins & Custom Chart script -->
<script src="frontend/vendor/chart.js/Chart.min.js"></script>
<script src="frontend/js/demo/chart-inbound-demo.js"></script>

<script>
$(document).ready(function() {
    $('.status-card-clickable').on('click', function() {
        var statusName = $(this).attr('data-status');
        $('#modalStatusTitleText').text(statusName);
    });

    window.addEventListener('dateRangeChanged', function(e) {
        if (e.detail && e.detail.displayRange) {
            $('#large-card-date').text('(' + e.detail.displayRange + ')');
        } else {
            $('#large-card-date').text('(<?php echo $currentDateStr; ?>)');
        }
    });
});
</script>

</body>
</html>


