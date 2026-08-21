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
            <div class="modal fade" id="poStatusDetailModal" tabindex="-1" role="dialog"
                aria-labelledby="poStatusDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow"
                        style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                        <!-- Header with distinct light background -->
                        <div class="modal-header border-bottom py-3 px-4 align-items-center"
                            style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                            <h5 class="modal-title font-weight-bold text-gray-800 my-auto d-flex align-items-center flex-wrap"
                                id="poStatusDetailModalLabel" style="line-height: 1.5; margin-top: 2px;">
                                <span>Detail Status PO:</span>
                                <span id="modalStatusTitleText" class="font-weight-bold text-primary ml-1"></span>
                                <span class="badge badge-primary px-2.5 py-1 font-weight-bold ml-2"
                                    id="inbound-modal-total-badge" style="font-size: 0.8rem; border-radius: 6px;">
                                    <i class="fas fa-file-invoice mr-1"></i> Total: <span id="inbound-modal-count-display">0</span> Data
                                </span>
                            </h5>
                            <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal"
                                aria-label="Close" style="padding: 0.5rem; margin: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <!-- Clean White Body -->
                        <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                            <!-- Filter Controls Container -->
                            <div class="card mb-3 border bg-light shadow-sm"
                                style="border-radius: 8px; border-color: #eaecf4 !important;">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex flex-wrap align-items-center w-100" id="modalDynamicFilterRow"
                                        style="gap: 8px;">
                                        <!-- Dynamic dropdown filters & search bar will be injected here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Table Container (Clean Minimalist White) -->
                            <div class="table-responsive border rounded shadow-sm"
                                style="border-color: #eaecf4 !important; background: #ffffff;">
                                <table class="table table-hover table-striped text-center mb-0 w-100"
                                    id="tablePoStatusDetail" style="font-size: 0.84rem;">
                                    <thead class="thead-light text-gray-800 font-weight-bold"
                                        id="tablePoStatusDetailHead" style="border-bottom: 2px solid #e3e6f0;">
                                        <tr>
                                            <th class="py-2 border-top-0">No. PO</th>
                                            <th class="py-2 border-top-0">Deskripsi PO</th>
                                            <th class="py-2 border-top-0">PIC Asset Planner</th>
                                            <th class="py-2 border-top-0">Department</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablePoStatusDetailBody">
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
    var statusColumnConfig = {
        'TOTAL PO INBOUND': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Tgl Generate', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'tgl_generate', 'target_delivery']
        },
        'TOTAL INBOUND': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Tgl Generate', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'tgl_generate', 'target_delivery']
        },
        'PO ONTIME DELIVERY': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'target_delivery']
        },
        'PO TERLAMBAT DELIVERY': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'vendor', 'qty', 'target_delivery']
        },
        'PO SUDAH GR': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor / PIC GR', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_gr', 'qty', 'target_delivery']
        },
        'TOTAL GR': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'Vendor / PIC GR', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_gr', 'qty', 'target_delivery']
        },
        'PO SUDAH REGISTRASI': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC Registrasi', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_registrasi', 'qty', 'target_delivery']
        },
        'TOTAL REGISTRASI': {
            headers: ['No. PO', 'Deskripsi PO', 'PIC Asset Planner', 'Department', 'PIC Registrasi', 'Qty', 'Target Delivery'],
            keys: ['no_po', 'deskripsi_po', 'pic_asset_planner', 'department', 'pic_registrasi', 'qty', 'target_delivery']
        },
        'GR NON PO': {
            headers: ['Deskripsi Perangkat', 'PIC Asset Planner', 'Department', 'Vendor', 'Qty'],
            keys: ['deskripsi_perangkat', 'pic_asset_planner', 'department', 'vendor', 'qty']
        }
    };

    var statusFilterMap = {
        'TOTAL PO INBOUND': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
            { key: 'vendor', label: 'Vendor' }
        ],
        'PO ONTIME DELIVERY': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
            { key: 'vendor', label: 'Vendor' }
        ],
        'PO TERLAMBAT DELIVERY': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
            { key: 'vendor', label: 'Vendor' }
        ],
        'PO SUDAH GR': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
            { key: 'pic_gr', label: 'PIC GR' }
        ],
        'TOTAL GR': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' },
            { key: 'pic_gr', label: 'PIC GR' }
        ],
        'PO SUDAH REGISTRASI': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
        ],
        'TOTAL REGISTRASI': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
        ],
        'GR NON PO': [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
        ]
    };

    var currentModalRows = [];
    var currentConfig = statusColumnConfig['TOTAL PO INBOUND'];
    var currentStatusName = 'TOTAL PO INBOUND';
    var currentSort = { key: null, dir: 'asc' };
    var currentPeriod = '';

    function renderTableHeader() {
        var thead = $('#tablePoStatusDetailHead');
        var tr = $('<tr></tr>');
        currentConfig.headers.forEach(function (h, idx) {
            var key = currentConfig.keys[idx];
            var sortIcon = '<i class="fas fa-sort text-gray-400 ml-1" style="font-size: 0.72rem;"></i>';
            if (currentSort.key === key) {
                sortIcon = currentSort.dir === 'asc'
                    ? '<i class="fas fa-sort-up text-primary ml-1" style="font-size: 0.78rem;"></i>'
                    : '<i class="fas fa-sort-down text-primary ml-1" style="font-size: 0.78rem;"></i>';
            }
            var th = $('<th class="py-2 border-top-0 sortable-modal-header user-select-none" style="cursor: pointer;" data-key="' + key + '">' + h + ' ' + sortIcon + '</th>');
            tr.append(th);
        });
        thead.html(tr);
    }

    function renderModalRows(rows) {
        var tbody = $('#tablePoStatusDetailBody');
        var colCount = currentConfig.headers.length;
        if (!rows || rows.length === 0) {
            tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk filter ini.</td></tr>');
            $('#inbound-modal-count-display').text('0');
            return;
        }

        $('#inbound-modal-count-display').text(rows.length);
        var html = '';
        rows.forEach(function (r) {
            html += '<tr>';
            currentConfig.keys.forEach(function (k) {
                var val = r[k] !== undefined && r[k] !== null && r[k] !== '' ? r[k] : '-';
                var alignClass = (k === 'no_po' || k === 'deskripsi_po' || k === 'deskripsi_perangkat') ? 'text-left' : 'text-center';
                html += '<td class="py-2 px-2 align-middle ' + alignClass + '" style="font-size: 0.82rem;">' + $('<div>').text(val).html() + '</td>';
            });
            html += '</tr>';
        });
        tbody.html(html);
    }

    function applyModalFilters() {
        var filterValues = {};
        $('.inbound-modal-col-filter').each(function () {
            var key = $(this).attr('data-key');
            var val = $(this).val();
            if (val) {
                filterValues[key] = val.toString().trim().toLowerCase();
            }
        });

        var searchTerm = ($('#filter-modal-search').val() || '').trim().toLowerCase();

        var filtered = currentModalRows.filter(function (row) {
            for (var k in filterValues) {
                var cellVal = (row[k] || '').toString().trim().toLowerCase();
                if (cellVal !== filterValues[k]) {
                    return false;
                }
            }

            if (searchTerm) {
                var matchSearch = currentConfig.keys.some(function (k) {
                    var cellVal = (row[k] || '').toString().toLowerCase();
                    return cellVal.indexOf(searchTerm) !== -1;
                });
                if (!matchSearch) return false;
            }

            return true;
        });

        if (currentSort.key) {
            filtered.sort(function (a, b) {
                var valA = (a[currentSort.key] || '').toString().trim();
                var valB = (b[currentSort.key] || '').toString().trim();
                return currentSort.dir === 'asc'
                    ? valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' })
                    : valB.localeCompare(valA, undefined, { numeric: true, sensitivity: 'base' });
            });
        }

        renderModalRows(filtered);
    }

    function updateModalFilters(statusName, rawData) {
        var filterRow = $('#modalDynamicFilterRow');
        var filterDefs = statusFilterMap[statusName] || [
            { key: 'department', label: 'Department' },
            { key: 'pic_asset_planner', label: 'PIC Asset Planner' }
        ];
        var html = '';

        filterDefs.forEach(function (f) {
            var key = f.key;
            var label = f.label;
            var uniqueVals = [];
            (rawData || []).forEach(function (r) {
                var v = (r[key] || '').toString().trim();
                if (v && v !== '-' && uniqueVals.indexOf(v) === -1) {
                    uniqueVals.push(v);
                }
            });
            uniqueVals.sort();

            html += '<div style="flex: 1 1 140px; max-width: 200px; min-width: 120px;">';
            html += '<select class="form-control form-control-sm custom-select custom-select-sm inbound-modal-col-filter" data-key="' + key + '" style="font-size: 0.78rem; border-radius: 6px;">';
            html += '<option value="">Semua ' + label + '</option>';
            uniqueVals.forEach(function (uv) {
                html += '<option value="' + $('<div>').text(uv).html() + '">' + $('<div>').text(uv).html() + '</option>';
            });
            html += '</select>';
            html += '</div>';
        });

        html += '<div style="flex: 1 1 170px; max-width: 230px; min-width: 140px;">';
        html += '<div class="input-group input-group-sm">';
        html += '<div class="input-group-prepend"><span class="input-group-text bg-white border-right-0 text-muted" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search" style="font-size: 0.72rem;"></i></span></div>';
        html += '<input type="text" class="form-control form-control-sm border-left-0" id="filter-modal-search" placeholder="Search..." style="font-size: 0.78rem; border-radius: 0 6px 6px 0;">';
        html += '</div>';
        html += '</div>';

        html += '<div>';
        html += '<button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" id="btn-reset-modal-filter" title="Reset Filter" style="border-radius: 6px; font-size: 0.75rem; padding: 0.25rem 0.65rem;">';
        html += '<i class="fas fa-undo mr-1"></i> Reset';
        html += '</button>';
        html += '</div>';

        filterRow.html(html);

        $('.inbound-modal-col-filter').off('change').on('change', applyModalFilters);
        $('#filter-modal-search').off('keyup input change').on('keyup input change', applyModalFilters);
        $('#btn-reset-modal-filter').off('click').on('click', function () {
            $('.inbound-modal-col-filter').val('');
            $('#filter-modal-search').val('');
            currentSort = { key: null, dir: 'asc' };
            renderTableHeader();
            applyModalFilters();
        });
    }

    $('#tablePoStatusDetailHead').off('click', '.sortable-modal-header').on('click', '.sortable-modal-header', function () {
        var key = $(this).attr('data-key');
        if (currentSort.key === key) {
            currentSort.dir = (currentSort.dir === 'asc') ? 'desc' : 'asc';
        } else {
            currentSort.key = key;
            currentSort.dir = 'asc';
        }
        renderTableHeader();
        applyModalFilters();
    });

    function loadModalStatusTable(statusName) {
        var statusKey = (statusName || '').trim().toUpperCase();
        var cfg = statusColumnConfig[statusKey] || statusColumnConfig['TOTAL PO INBOUND'];
        currentConfig = cfg;
        currentStatusName = statusKey;
        currentSort = { key: null, dir: 'asc' };
        currentModalRows = [];

        renderTableHeader();

        var tbody = $('#tablePoStatusDetailBody');
        var colCount = cfg.headers.length;
        tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2 d-block"></i>Memuat data detail status...</td></tr>');
        $('#modalDynamicFilterRow').empty();
        $('#inbound-modal-count-display').text('Memuat...');

        $.ajax({
            url: 'api/get_inbound_status_detail.php',
            type: 'GET',
            data: { status: statusName, periode: currentPeriod },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    currentModalRows = res.data;
                    updateModalFilters(statusKey, res.data);
                    renderModalRows(res.data);
                } else {
                    updateModalFilters(statusKey, []);
                    tbody.html('<tr><td colspan="' + colCount + '" class="py-5 text-center text-muted bg-white"><i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>Belum ada data tersedia untuk status ini.</td></tr>');
                    $('#inbound-modal-count-display').text('0');
                }
            },
            error: function () {
                tbody.html('<tr><td colspan="' + colCount + '" class="py-4 text-center text-danger bg-white"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>Gagal memuat data dari server.</td></tr>');
                $('#inbound-modal-count-display').text('0');
            }
        });
    }

    $('.status-card-clickable').on('click', function() {
        var statusName = $(this).attr('data-status');
        $('#modalStatusTitleText').text(statusName);
        loadModalStatusTable(statusName);
    });

    var ALL_MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    function updateLoadButton() {
        var m = document.getElementById('period-month-select');
        var b = document.getElementById('period-batch-select');
        var y = document.getElementById('period-year-select');
        var btn = document.getElementById('btn-load-period');
        if (btn) {
            btn.disabled = !(m && m.value && b && b.value && y && y.value);
        }
    }

    var monthSel = document.getElementById('period-month-select');
    var batchSel = document.getElementById('period-batch-select');
    var yearSel = document.getElementById('period-year-select');
    if (monthSel) monthSel.addEventListener('change', updateLoadButton);
    if (batchSel) batchSel.addEventListener('change', updateLoadButton);
    if (yearSel) yearSel.addEventListener('change', updateLoadButton);

    function populateSelect(selectId, items, placeholder) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        sel.replaceChildren();
        var defOpt = document.createElement('option');
        defOpt.value = '';
        defOpt.textContent = placeholder;
        sel.appendChild(defOpt);
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item.toUpperCase();
            sel.appendChild(opt);
        });
    }

    function resetInboundCards() {
        $('#card-total-po-inbound').text(0);
        $('#card-po-ontime-delivery').text(0);
        $('#card-po-terlambat-delivery').text(0);
        $('#card-po-sudah-gr').text(0);
        $('#card-po-sudah-registrasi').text(0);
        $('#card-gr-non-po').text(0);
        $('#card-total-gr').text(0);
        $('#card-total-registrasi').text(0);
        $('.progress-bar').css('width', '0%');
        if (window.updateInboundCharts) {
            window.updateInboundCharts({
                po_ontime_delivery: 0,
                po_terlambat_delivery: 0,
                po_sudah_gr: 0,
                dept_chart: { labels: [], sudah_gr: [], belum_gr: [] },
                trend_chart: { ontime: Array(12).fill(0), terlambat: Array(12).fill(0) }
            });
        }
    }

    function loadStatusCardCounts(period) {
        if (!period) {
            resetInboundCards();
            return;
        }

        $.ajax({
            url: 'api/get_inbound_status_detail.php',
            type: 'GET',
            data: { action: 'counts', periode: period },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success' && res.counts) {
                    var c = res.counts;
                    var total = c.total_po_inbound || 0;

                    $('#card-total-po-inbound').text(total);
                    $('#card-po-ontime-delivery').text(c.po_ontime_delivery || 0);
                    $('#card-po-terlambat-delivery').text(c.po_terlambat_delivery || 0);
                    $('#card-po-sudah-gr').text(c.po_sudah_gr || 0);
                    $('#card-po-sudah-registrasi').text(c.po_sudah_registrasi || 0);
                    $('#card-gr-non-po').text(c.gr_non_po || 0);
                    $('#card-total-gr').text(c.total_gr || 0);
                    $('#card-total-registrasi').text(c.total_registrasi || 0);

                    // Update progress bars
                    if (total > 0) {
                        $('#bar-total-po-inbound').css('width', '100%');
                        $('#bar-po-ontime-delivery').css('width', Math.min(100, Math.round((c.po_ontime_delivery / total) * 100)) + '%');
                        $('#bar-po-terlambat-delivery').css('width', Math.min(100, Math.round((c.po_terlambat_delivery / total) * 100)) + '%');
                        $('#bar-po-sudah-gr').css('width', Math.min(100, Math.round((c.po_sudah_gr / total) * 100)) + '%');
                        $('#bar-po-sudah-registrasi').css('width', Math.min(100, Math.round((c.po_sudah_registrasi / total) * 100)) + '%');
                    } else {
                        $('.progress-bar').css('width', '0%');
                    }

                    // Update Inbound Charts
                    if (window.updateInboundCharts) {
                        window.updateInboundCharts(c);
                    }
                }
            }
        });
    }

    function loadPeriods() {
        fetch('api/get_periods.php')
            .then(function (r) { return r.json(); })
            .then(function (result) {
                var yearsSet = {};
                if (result.status === 'success' && result.data) {
                    result.data.forEach(function (pg) {
                        if (!pg || pg === 'Unknown Period') return;
                        var match = pg.match(/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/);
                        if (match) {
                            yearsSet[match[2]] = true;
                        }
                    });
                }
                var availableYears = (result.years && result.years.length > 0) ? result.years : Object.keys(yearsSet).sort();
                populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');

                var pText = document.getElementById('selected-period-text');
                if (pText) pText.textContent = "PILIH PERIODE DATA";
                currentPeriod = '';
                resetInboundCards();
            })
            .catch(function (err) {
                console.error('Error fetching periods:', err);
            });
    }

    var btnLoad = document.getElementById('btn-load-period');
    if (btnLoad) {
        btnLoad.addEventListener('click', function () {
            var m = document.getElementById('period-month-select');
            var b = document.getElementById('period-batch-select');
            var y = document.getElementById('period-year-select');
            if (m && m.value && b && b.value && y && y.value) {
                currentPeriod = m.value + ' ' + y.value + '-Batch' + b.value;
                var pText = document.getElementById('selected-period-text');
                if (pText) pText.textContent = currentPeriod.toUpperCase();
                loadStatusCardCounts(currentPeriod);
                if (window.jQuery) {
                    $('#periodDropdown').dropdown('toggle');
                }
            }
        });
    }

    var btnReset = document.getElementById('btn-reset-period');
    if (btnReset) {
        btnReset.addEventListener('click', function () {
            if (monthSel) monthSel.value = '';
            if (batchSel) batchSel.value = '';
            if (yearSel) yearSel.value = '';
            updateLoadButton();
            currentPeriod = '';
            var pText = document.getElementById('selected-period-text');
            if (pText) pText.textContent = "PILIH PERIODE DATA";
            resetInboundCards();
        });
    }

    loadPeriods();
});
</script>

</body>
</html>


