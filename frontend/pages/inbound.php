<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('inbound');

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
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

                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow border-0 py-2">
                                <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Alur Status PO Inbound
                                    </h6>
                                </div>
                                <div class="card-body py-3 px-4">
                                    <div class="row text-center align-items-center">
                                        <!-- 1. Total PO Inbound -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="TOTAL PO INBOUND">
                                            <div class="h3 font-weight-bold text-primary mb-1" id="card-total-po-inbound">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">TOTAL PO INBOUND</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-primary" role="progressbar" id="bar-total-po-inbound" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 2. PO Ontime Delivery -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="PO ONTIME DELIVERY">
                                            <div class="h3 font-weight-bold text-success mb-1" id="card-po-ontime-delivery">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO ONTIME DELIVERY</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-success" role="progressbar" id="bar-po-ontime-delivery" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 3. PO Terlambat Delivery -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="PO TERLAMBAT DELIVERY">
                                            <div class="h3 font-weight-bold text-danger mb-1" id="card-po-terlambat-delivery">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO TERLAMBAT DELIVERY</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-danger" role="progressbar" id="bar-po-terlambat-delivery" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 4. PO Sudah GR -->
                                        <div class="col-md border-right-divider mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="PO SUDAH GR">
                                            <div class="h3 font-weight-bold text-info mb-1" id="card-po-sudah-gr">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO SUDAH GR</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar bg-info" role="progressbar" id="bar-po-sudah-gr" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <!-- 5. PO Sudah Registrasi -->
                                        <div class="col-md mb-3 mb-md-0 status-card-clickable py-2 px-2 rounded" data-toggle="modal" data-target="#poStatusDetailModal" data-status="PO SUDAH REGISTRASI">
                                            <div class="h3 font-weight-bold mb-1" style="color: #6f42c1;" id="card-po-sudah-registrasi">0</div>
                                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">PO SUDAH REGISTRASI</div>
                                            <div class="progress progress-sm" style="height: 6px; border-radius: 4px;">
                                                <div class="progress-bar" role="progressbar" id="bar-po-sudah-registrasi" style="width: 0%; background-color: #6f42c1;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric Cards Row (1 Single Inline Row of 3 Cards: GR Non PO, Total GR, Total Registrasi) -->
                    <div class="row mx-n1 mb-4">
                        <!-- 1. GR Non PO -->
                        <div class="col-xl-4 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-primary shadow h-100 py-1 inbound-metric-card status-card-clickable" data-toggle="modal" data-target="#poStatusDetailModal" data-status="GR NON PO">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        GR NON PO</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-gr-non-po">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Total GR -->
                        <div class="col-xl-4 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-success shadow h-100 py-1 inbound-metric-card status-card-clickable" data-toggle="modal" data-target="#poStatusDetailModal" data-status="TOTAL GR">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        TOTAL GR</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-total-gr">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Total Registrasi -->
                        <div class="col-xl-4 col-md-4 col-sm-6 px-1 mb-2 mb-xl-0">
                            <div class="card border-left-warning shadow h-100 py-1 inbound-metric-card status-card-clickable" data-toggle="modal" data-target="#poStatusDetailModal" data-status="TOTAL REGISTRASI">
                                <div class="card-body p-2 d-flex flex-column justify-content-between">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.68rem; line-height: 1.15;">
                                        TOTAL REGISTRASI</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 mt-auto" id="card-total-registrasi">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inbound Charts Row (3 Cards: Distribusi Status Delivery, QTY PO For Department, Tren GR Bulanan) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Distribusi Status Delivery (Circle Graph / Doughnut Chart) -->
                        <div class="col-xl-4 col-lg-4 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Status Delivery</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-2 pb-2" style="height: 300px; position: relative;">
                                        <canvas id="distribusiStatusPoChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QTY PO For Department (Bar Chart) -->
                        <div class="col-xl-4 col-lg-4 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100 py-2">
                                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">QTY PO For Department</h6>
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
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow" style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                        <!-- Header with distinct light background -->
                        <div class="modal-header border-bottom py-3 px-4 align-items-center" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                            <h5 class="modal-title font-weight-bold text-gray-800 my-auto" id="poStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                <i class="fas fa-stream text-primary mr-2"></i>Detail <span id="modalStatusTitleText" class="font-weight-bold text-primary"></span>
                            </h5>
                            <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal" aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <!-- Clean White Body -->
                        <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                            <!-- Filter Controls Container -->
                            <div class="card mb-3 border bg-light shadow-sm" style="border-radius: 8px; border-color: #eaecf4 !important;">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex flex-wrap align-items-center w-100" id="modalDynamicFilterRow" style="gap: 8px;">
                                        <!-- Dynamic dropdown filters & search bar will be injected here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Table Container (Clean Minimalist White) -->
                            <div class="table-responsive border rounded shadow-sm" style="border-color: #eaecf4 !important; background: #ffffff;">
                                <table class="table table-hover table-striped text-center mb-0" id="tablePoStatusDetail" style="font-size: 0.84rem;">
                                    <thead class="thead-light text-gray-800 font-weight-bold" style="border-bottom: 2px solid #e3e6f0;">
                                        <tr>
                                            <th class="py-2 border-top-0">No. PO</th>
                                            <th class="py-2 border-top-0">Deskripsi PO</th>
                                            <th class="py-2 border-top-0">PIC Asset Planner</th>
                                            <th class="py-2 border-top-0">Department</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="py-5 text-muted bg-white">
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
<script src="frontend/js/demo/chart-inbound-demo.js?v=<?php echo time(); ?>"></script>

<script>
$(document).ready(function() {
    // Dynamic column configuration per status
    var statusColumnMap = {
        'TOTAL PO INBOUND': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'],
        'TOTAL INBOUND': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'],
        'PO ONTIME DELIVERY': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'],
        'PO TERLAMBAT DELIVERY': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'],
        'PO SUDAH GR': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC GR'],
        'GR NON PO': ['Deskripsi Perangkat', 'PIC Asset Planner', 'Department'],
        'PO SUDAH REGISTRASI': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC Registrasi'],
        'TOTAL GR': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC GR'],
        'TOTAL REGISTRASI': ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC Registrasi']
    };

    function updateModalFilters(statusName) {
        var statusKey = (statusName || '').trim().toUpperCase();
        var cols = statusColumnMap[statusKey] || ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'];

        var html = '';
        cols.forEach(function(col) {
            var filterId = 'filter-modal-' + col.toLowerCase().replace(/[^a-z0-9]/g, '-');
            html += '<div class="flex-grow-1" style="min-width: 140px;">';
            html += '<select class="form-control form-control-sm custom-select custom-select-sm" id="' + filterId + '">';
            html += '<option value="">Semua ' + col + '</option>';
            html += '</select>';
            html += '</div>';
        });

        // Fixed compact Search Bar
        html += '<div style="flex: 0 0 180px; width: 180px; min-width: 150px;">';
        html += '<input type="text" class="form-control form-control-sm" id="filter-modal-search" placeholder="Search...">';
        html += '</div>';

        $('#modalDynamicFilterRow').html(html);
    }

    function updateModalTable(statusName) {
        var statusKey = (statusName || '').trim().toUpperCase();
        var cols = statusColumnMap[statusKey] || ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department'];

        var theadHtml = '<tr>';
        cols.forEach(function(col) {
            theadHtml += '<th class="py-2 border-top-0">' + col + '</th>';
        });
        theadHtml += '</tr>';
        $('#tablePoStatusDetail thead').html(theadHtml);

        var tbodyHtml = '<tr>' +
            '<td colspan="' + cols.length + '" class="py-5 text-muted bg-white">' +
                '<i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>' +
                'Belum ada data tersedia untuk status ini.' +
            '</td>' +
        '</tr>';
        $('#tablePoStatusDetail tbody').html(tbodyHtml);
    }

    $('.status-card-clickable').on('click', function() {
        var statusName = $(this).attr('data-status');
        $('#modalStatusTitleText').text(statusName);
        updateModalFilters(statusName);
        updateModalTable(statusName);
    });
});
</script>

</body>
</html>


