<?php
require_once __DIR__ . '/auth.php';
checkModuleAccess('warehouse');

$pageTitle = 'Storage - Dashboard Warehouse';
include 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">
                <?php
                $activePage = 'warehouse';
                include 'components/navbar.php';
                ?>
                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="mb-4">
                        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Storage Management</h1>
                        <div class="text-muted small font-weight-medium">50002003289 - Warehouse Tekno</div>
                    </div>
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Total Asset -->
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        TOTAL ASSET</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-total-asset">0</div>
                                </div>
                            </div>
                        </div>

                        <!-- Total NBV -->
                        <div class="col-xl-3 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        TOTAL NBV</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="card-total-nbv">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <!-- Utilisasi Space -->
                        <div class="col-xl-2 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        UTILISASI SPACE</div>
                                    <div class="d-flex align-items-center w-100">
                                        <div class="h5 mb-0 mr-2 font-weight-bold text-gray-800"
                                            id="card-utilisasi-space-text">0%</div>
                                        <div class="progress progress-sm flex-grow-1"
                                            style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-danger" role="progressbar"
                                                id="card-utilisasi-space-bar" style="width: 0%" aria-valuenow="0"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Free Space -->
                        <div class="col-xl-2 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        FREE SPACE</div>
                                    <div class="d-flex align-items-center w-100">
                                        <div class="h5 mb-0 mr-2 font-weight-bold text-gray-800"
                                            id="card-free-space-text">0%</div>
                                        <div class="progress progress-sm flex-grow-1"
                                            style="height: 6px; border-radius: 4px;">
                                            <div class="progress-bar bg-info" role="progressbar"
                                                id="card-free-space-bar" style="width: 0%" aria-valuenow="0"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last Update -->
                        <div class="col-xl-2 col-md-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        LAST UPDATE</div>
                                    <div class="mb-0 font-weight-bold text-gray-800" style="font-size: 0.72rem;"
                                        id="card-last-update">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Top Charts Row: Storage & Berdasarkan Asset Organization -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Storage (Bar Chart) -->
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100">
                                <div
                                    class="card-header py-2 px-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 0.8rem;">STORAGE<br>
                                        <span class="text-muted font-weight-normal"
                                            style="font-size: 0.7rem;">Berdasarkan Aging</span>
                                    </h6>
                                </div>
                                <div class="card-body" style="padding: 0.5rem;">
                                    <div style="height: 320px; position: relative; width: 100%;">
                                        <canvas id="myBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Berdasarkan Asset Organization (Horizontal Bar Chart) -->
                        <div class="col-12 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100">
                                <div
                                    class="card-header py-2 px-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 0.8rem;">BERDASARKAN
                                        ASSET ORGANIZATION<br>
                                        <span class="text-muted font-weight-normal"
                                            style="font-size: 0.7rem;">Department / Unit Pemilik Asset</span>
                                    </h6>
                                </div>
                                <div class="card-body" style="padding: 0.5rem;">
                                    <div id="horizontalBarScrollWrapper"
                                        style="max-height: 320px; overflow-y: auto; overflow-x: hidden;">
                                        <div class="chart-bar" id="horizontalBarChartContainer"
                                            style="height: 320px; position: relative; width: 100%;">
                                            <canvas id="myHorizontalBarChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle Charts Row: Perangkat IN & Perangkat OUT (6+6) -->
                    <div class="row" style="margin-left: -4px; margin-right: -4px;">
                        <!-- Perangkat IN -->
                        <div class="col-xl-6 col-lg-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100">
                                <div
                                    class="card-header py-2 px-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 0.8rem;">PERANGKAT
                                        IN<br>
                                        <span id="perangkat-in-title-period" class="text-muted font-weight-normal"
                                            style="font-size: 0.7rem;">Bulan X</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="height: 260px; overflow-x: auto; overflow-y: hidden;">
                                        <div style="min-width: 500px; height: 100%;">
                                            <canvas id="perangkatInChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Perangkat OUT -->
                        <div class="col-xl-6 col-lg-6 mb-4" style="padding-left: 4px; padding-right: 4px;">
                            <div class="card shadow h-100">
                                <div
                                    class="card-header py-2 px-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 0.8rem;">PERANGKAT
                                        OUT<br>
                                        <span id="perangkat-out-title-period" class="text-muted font-weight-normal"
                                            style="font-size: 0.7rem;">Bulan X</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="height: 260px; overflow-x: auto; overflow-y: hidden;">
                                        <div style="min-width: 500px; height: 100%;">
                                            <canvas id="perangkatOutChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Utilisasi Area / Rack (Full Width 12) -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow">
                                <div
                                    class="card-header py-2 px-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 0.8rem;">UTILISASI
                                        AREA / RACK<br>
                                        <span class="text-muted font-weight-normal" style="font-size: 0.7rem;">Kapasitas
                                            Rack & Area Storage</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="rack-status-dots" class="d-flex justify-content-left mb-2"></div>
                                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                        <table class="table mb-0" width="100%" cellspacing="0"
                                            style="font-size: 0.78rem;">
                                            <thead class="bg-light font-weight-bold">
                                                <tr>
                                                    <th class="py-2 text-left">RACK / AREA</th>
                                                    <th class="py-2 text-center">CAPACITY</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table-utilisasi-area-body">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php include 'components/footer.php'; ?>

                    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Logout</h5>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                                <div class="modal-body">Apakah Anda yakin ingin keluar?</div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                                    <a class="btn btn-primary" href="login.html">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Page level plugins -->
                    <script src="vendor/chart.js/Chart.min.js"></script>
                    <script
                        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@0.7.0/dist/chartjs-plugin-datalabels.min.js"></script>

                    <!-- SweetAlert2 for loading dialogs -->
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                    <!-- SheetJS (xlsx) for Excel parsing -->
                    <!-- TODO(security): Pin version and add SRI integrity hash for production -->
                    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

                    <!-- Page level custom scripts -->
                    <script src="js/formula-controller.js?v=23"></script>
                    <script src="js/demo/chart-bar-demo.js?v=8"></script>
                    <script src="js/demo/chart-horizontal-bar-demo.js?v=5"></script>

                    <!-- Fetch data from database on load -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var ALL_MONTHS = [
                                "January", "February", "March", "April", "May", "June",
                                "July", "August", "September", "October", "November", "December"
                            ];

                            // Keep the dropdown open when clicking inside the selects
                            var periodMenu = document.getElementById('period-dropdown-menu');
                            if (periodMenu) {
                                periodMenu.addEventListener('click', function (e) {
                                    e.stopPropagation();
                                });
                            }

                            // ── Populate Month & Year selects from DB ──
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

                                        // Build sorted year list ascending
                                        var availableYears = Object.keys(yearsSet).sort();

                                        // Populate navbar Month select (always all 12 months)
                                        populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                                        // Populate navbar Year select (dynamic from DB)
                                        populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');

                                        // Populate delete modal selects
                                        populateSelect('deleteMonthSelect', ALL_MONTHS, '-- Pilih Bulan --');
                                        populateSelect('deleteYearSelect', availableYears, '-- Pilih Tahun --');

                                        // If a specific period was requested (e.g. after upload), pre-select it
                                        if (selectPeriod) {
                                            preselectPeriod(selectPeriod);
                                            loadDataForPeriod(selectPeriod);
                                        } else {
                                            document.getElementById('selected-period-text').textContent = "PILIH PERIODE DATA";
                                            if (window.FormulaController) {
                                                window.FormulaController.updateDashboardCards([], []);
                                            }
                                        }
                                    })
                                    .catch(function (err) {
                                        console.error('Error fetching periods:', err);
                                    });
                            }

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

                            function preselectPeriod(period) {
                                var parts = period.split(' ');
                                if (parts.length >= 2) {
                                    var mSel = document.getElementById('period-month-select');
                                    var ySel = document.getElementById('period-year-select');
                                    if (mSel) mSel.value = parts[0];
                                    if (ySel) ySel.value = parts[1];
                                    updateLoadButton();
                                }
                            }

                            // ── Enable / disable the "Tampilkan Data" button ──
                            function updateLoadButton() {
                                var m = document.getElementById('period-month-select');
                                var y = document.getElementById('period-year-select');
                                var btn = document.getElementById('btn-load-period');
                                if (btn) {
                                    btn.disabled = !(m && m.value && y && y.value);
                                }
                            }

                            var monthSel = document.getElementById('period-month-select');
                            var yearSel = document.getElementById('period-year-select');
                            if (monthSel) monthSel.addEventListener('change', updateLoadButton);
                            if (yearSel) yearSel.addEventListener('change', updateLoadButton);

                            // ── Load button click ──
                            var btnLoad = document.getElementById('btn-load-period');
                            if (btnLoad) {
                                btnLoad.addEventListener('click', function () {
                                    var m = document.getElementById('period-month-select');
                                    var y = document.getElementById('period-year-select');
                                    if (m && m.value && y && y.value) {
                                        var period = m.value + ' ' + y.value;
                                        loadDataForPeriod(period);
                                        // Close the dropdown after selecting
                                        $(periodMenu).closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
                                    }
                                });
                            }

                            // ── Fetch & render data for a period ──
                            function loadDataForPeriod(period) {
                                document.getElementById('selected-period-text').textContent = period.toUpperCase();

                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Data Is Processing Please Wait',
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        }
                                    });
                                }

                                var parts = period.split(' ');
                                var yr = parts.length > 1 ? parts[parts.length - 1] : '';

                                var fetchDashboard = fetch('api/get_data.php?periode=' + encodeURIComponent(period))
                                    .then(function (response) { return response.json(); });

                                var fetchYearly = yr
                                    ? fetch('api/get_yearly_in_out.php?year=' + encodeURIComponent(yr)).then(function (response) { return response.json(); })
                                    : Promise.resolve(null);

                                // Execute both requests in parallel for maximum speed
                                Promise.all([fetchDashboard, fetchYearly])
                                    .then(function (results) {
                                        var result = results[0];
                                        var resData = results[1];

                                        // 1. Synchronously update dashboard cards & main charts (Bar, Horizontal, Aging)
                                        if (result && result.status === 'success' && result.data && result.data.length > 0) {
                                            console.log("Loaded data from database:", result.data.length, "rows for", period);
                                            var headers = Object.keys(result.data[0]);
                                            window.currentDashboardData = result.data;
                                            window.currentDashboardHeaders = headers;
                                            if (window.FormulaController) {
                                                window.FormulaController.updateDashboardCards(result.data, headers);
                                                var cardUpdate = document.getElementById('card-last-update');
                                                if (cardUpdate) {
                                                    cardUpdate.textContent = period.toUpperCase();
                                                }
                                            }
                                        } else {
                                            if (window.FormulaController) {
                                                window.FormulaController.updateDashboardCards([], []);
                                            }
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire('Empty', 'No data found for ' + period, 'info');
                                            }
                                        }

                                        // 2. Synchronously update Yearly IN & OUT charts at the exact same moment
                                        if (resData && resData.status === 'success' && resData.data) {
                                            var mLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                            if (window.perangkatInChart && window.perangkatInChart.data) {
                                                window.perangkatInChart.data.labels = mLabels;
                                                window.perangkatInChart.data.datasets[0].data = resData.data.in;
                                                window.perangkatInChart._recordsPerIndex = resData.data.in_details || [];
                                                window.perangkatInChart._chartTitle = "Perangkat IN";
                                                window.perangkatInChart.update(0);
                                                if (window.FormulaController) {
                                                    window.FormulaController.makeChartClickable(window.perangkatInChart, "Perangkat IN");
                                                }
                                            }
                                            if (window.perangkatOutChart && window.perangkatOutChart.data) {
                                                window.perangkatOutChart.data.labels = mLabels;
                                                window.perangkatOutChart.data.datasets[0].data = resData.data.out;
                                                window.perangkatOutChart._recordsPerIndex = resData.data.out_details || [];
                                                window.perangkatOutChart._chartTitle = "Perangkat OUT";
                                                window.perangkatOutChart.update(0);
                                                if (window.FormulaController) {
                                                    window.FormulaController.makeChartClickable(window.perangkatOutChart, "Perangkat OUT");
                                                }
                                            }
                                        }

                                        if (typeof Swal !== 'undefined') Swal.close();
                                    })
                                    .catch(function (error) {
                                        console.error('Error fetching data:', error);
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Error', 'Failed to load data. Please try again.', 'error');
                                        }
                                    });
                            }

                            // ── Initial load ──
                            loadPeriods();

                            // Expose globally so excel-upload.js can trigger after upload
                            window.loadPeriods = loadPeriods;

                            // ── Delete Data Logic ──
                            var btnConfirmDelete = document.getElementById('btn-confirm-delete');
                            if (btnConfirmDelete) {
                                btnConfirmDelete.addEventListener('click', function () {
                                    var delMonth = document.getElementById('deleteMonthSelect');
                                    var delYear = document.getElementById('deleteYearSelect');
                                    if (!delMonth || !delMonth.value || !delYear || !delYear.value) {
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Peringatan', 'Please select both Month and Year to delete.', 'warning');
                                        } else {
                                            alert('Please select both Month and Year to delete.');
                                        }
                                        return;
                                    }

                                    var periodToDelete = delMonth.value + ' ' + delYear.value;

                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: 'Are you sure?',
                                            text: "You want to delete all data for " + periodToDelete.toUpperCase() + "? This cannot be undone.",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#e74a3b',
                                            cancelButtonColor: '#858796',
                                            confirmButtonText: 'Yes, delete it!'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                executeDelete(periodToDelete);
                                            }
                                        });
                                    } else {
                                        if (confirm("Are you SURE you want to delete all data for " + periodToDelete.toUpperCase() + "? This cannot be undone.")) {
                                            executeDelete(periodToDelete);
                                        }
                                    }
                                });
                            }

                            function executeDelete(periodToDelete) {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Deleting Data...',
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        }
                                    });
                                }

                                fetch('api/delete_data.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ periode: periodToDelete })
                                })
                                    .then(function (response) { return response.json(); })
                                    .then(function (result) {
                                        if (result.status === 'success') {
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire('Deleted!', result.message, 'success');
                                            } else {
                                                alert(result.message);
                                            }
                                            $('#deleteDataModal').modal('hide');

                                            var currentPeriodText = document.getElementById('selected-period-text').textContent;
                                            if (currentPeriodText.toLowerCase() === periodToDelete.toLowerCase()) {
                                                document.getElementById('selected-period-text').textContent = "PILIH PERIODE DATA";
                                                if (window.FormulaController) {
                                                    window.FormulaController.updateDashboardCards([], []);
                                                }
                                            }
                                            // Refresh period selects
                                            loadPeriods();
                                        } else {
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire('Error', 'Error deleting data: ' + result.message, 'error');
                                            } else {
                                                alert("Error deleting data: " + result.message);
                                            }
                                        }
                                    })
                                    .catch(function (error) {
                                        console.error('Error:', error);
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire('Error', 'Failed to delete data.', 'error');
                                        } else {
                                            alert("Failed to delete data.");
                                        }
                                    });
                            }
                        });
                    </script>

</body>

</html>