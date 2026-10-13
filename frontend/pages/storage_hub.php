<?php
require_once __DIR__ . '/../../backend/auth.php';
if (!defined('SPA_MODE')) checkModuleAccess('warehouse');

if (!defined('SPA_MODE')) {
    $pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
    include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">
                <?php
                $activePage = 'storage_hub';
                include FRONTEND_PATH . 'components/navbar.php';
                ?>
<?php } ?>
                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="mb-4">
                        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Storage Management-HUB &amp; Outlet Warehouse</h1>
                        <div class="text-muted small font-weight-medium" id="storage-hub-selected-site"></div>
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
                                        style="max-height: 420px; overflow-y: auto; overflow-x: hidden;">
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
                                            style="font-size: 0.7rem;">Tahun</span>
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
                                            style="font-size: 0.7rem;">Tahun</span>
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
<?php if (!defined('SPA_MODE')) { include FRONTEND_PATH . 'components/footer.php'; } ?>

                    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
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
                    <script src="frontend/vendor/chart.js/Chart.min.js"></script>
                    <script
                        src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@0.7.0/dist/chartjs-plugin-datalabels.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

                    <!-- Page level custom scripts -->
                    <script src="frontend/js/formula-controller.js?v=<?= @filemtime((defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2) . '/') . 'frontend/js/formula-controller.js') ?>"></script>
                    <script src="frontend/js/demo/chart-bar-demo.js?v=8"></script>
                    <script src="frontend/js/demo/chart-horizontal-bar-demo.js?v=5"></script>

                    <!-- Fetch data from database on load -->
                    <script>
                        $(function () {
                            var ALL_MONTHS = [
                                "January", "February", "March", "April", "May", "June",
                                "July", "August", "September", "October", "November", "December"
                            ];

                            var periodMenu = document.getElementById('period-dropdown-menu');
                            if (periodMenu) {
                                periodMenu.addEventListener('click', function (e) {
                                    e.stopPropagation();
                                });
                            }

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

                                        var availableYears = (result.years && result.years.length > 0) ? result.years : Object.keys(yearsSet).sort();
                                        availableYears = (window.WMSPeriodManager)
                                            ? window.WMSPeriodManager.ensureCurrentYear(availableYears)
                                            : availableYears;

                                        var availableSites = (result.sites && result.sites.length > 0)
                                            ? result.sites
                                            : ['HUB TEKNO', 'HUB JAKARTA', 'OUTLET BANDUNG', 'OUTLET SURABAYA'];

                                        if (window.WMSPeriodManager) {
                                            window.WMSPeriodManager.populateSelect('period-site-select', availableSites, '-- Pilih HUB/Outlet Warehouse --');
                                            window.WMSPeriodManager.populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                                            window.WMSPeriodManager.populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');
                                            window.WMSPeriodManager.populateSelect('deleteMonthSelect', ALL_MONTHS, '-- Pilih Bulan --');
                                            window.WMSPeriodManager.populateSelect('deleteYearSelect', availableYears, '-- Pilih Tahun --');
                                        } else {
                                            populateSelect('period-site-select', availableSites, '-- Pilih HUB/Outlet Warehouse --');
                                            populateSelect('period-month-select', ALL_MONTHS, '-- Pilih Bulan --');
                                            populateSelect('period-year-select', availableYears, '-- Pilih Tahun --');
                                            populateSelect('deleteMonthSelect', ALL_MONTHS, '-- Pilih Bulan --');
                                            populateSelect('deleteYearSelect', availableYears, '-- Pilih Tahun --');
                                        }

                                        var savedDetails = (window.WMSPeriodManager) ? window.WMSPeriodManager.getActivePeriodDetails() : null;
                                        var savedSite = (savedDetails && savedDetails.site) ? savedDetails.site : '';

                                        var targetPeriod = selectPeriod;
                                        if (!targetPeriod && window.WMSPeriodManager) {
                                            targetPeriod = window.WMSPeriodManager.getActivePeriod(result.data, availableYears);
                                        }

                                        if (targetPeriod) {
                                            if (window.WMSPeriodManager) {
                                                window.WMSPeriodManager.applyPeriodToNavbar(targetPeriod, savedSite);
                                            } else {
                                                preselectPeriod(targetPeriod);
                                            }
                                            loadDataForPeriod(targetPeriod);
                                        } else {
                                            if (window.WMSPeriodManager) {
                                                window.WMSPeriodManager.applyPeriodToNavbar(null);
                                            }
                                            resetStorageHubCards();
                                        }
                                    })
                                    .catch(function (err) {
                                        console.error('Error fetching periods:', err);
                                    });
                            }

                            function resetStorageHubCards() {
                                var pText = document.getElementById('selected-period-text');
                                if (pText) pText.textContent = "PILIH PERIODE DATA";
                                var siteBadge = document.getElementById('storage-hub-selected-site');
                                if (siteBadge) siteBadge.textContent = '';
                                if (window.FormulaController) {
                                    window.FormulaController.updateDashboardCards([], []);
                                }
                                if (window.perangkatInChart && window.perangkatInChart.data) {
                                    window.perangkatInChart.data.datasets[0].data = [];
                                    window.perangkatInChart.update(0);
                                }
                                if (window.perangkatOutChart && window.perangkatOutChart.data) {
                                    window.perangkatOutChart.data.datasets[0].data = [];
                                    window.perangkatOutChart.update(0);
                                }
                            }

                            if (window.WMSPeriodManager) {
                                window.WMSPeriodManager.registerPageHandler({
                                    onLoad: function (period, site) {
                                        loadDataForPeriod(period, true);
                                    },
                                    onReset: function () {
                                        _loadedPeriod = null;
                                        resetStorageHubCards();
                                    }
                                });
                            }

                            function applyStorageHubData(period, result, resData) {
                                if (result && result.status === 'success' && (result.summary || (result.data && result.data.length > 0))) {
                                    var headers = (result.data && result.data.length > 0) ? Object.keys(result.data[0]) : [];
                                    window.currentDashboardData = result.data || [];
                                    window.currentDashboardHeaders = headers;
                                    if (window.FormulaController) {
                                        window.FormulaController.updateDashboardCards(result.data || [], headers, null, result.summary);
                                    }
                                    var cardUpdate = document.getElementById('card-last-update');
                                    if (cardUpdate) cardUpdate.textContent = period.toUpperCase();
                                } else if (result && window.FormulaController) {
                                    window.FormulaController.updateDashboardCards([], []);
                                }

                                if (resData && resData.status === 'success' && resData.data) {
                                    var mLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    [['perangkatInChart', 'in', 'Perangkat IN'], ['perangkatOutChart', 'out', 'Perangkat OUT']].forEach(function (cfg) {
                                        var ch = window[cfg[0]];
                                        if (!ch || !ch.data) return;
                                        try {
                                            if (!ch.$datalabels) ch.$datalabels = { _listened: true };
                                            ch.data.labels = mLabels;
                                            ch.data.datasets[0].data = resData.data[cfg[1]];
                                            ch._recordsPerIndex = resData.data[cfg[1] + '_details'] || [];
                                            ch._chartTitle = cfg[2];
                                            ch.update(0);
                                            if (window.FormulaController) window.FormulaController.makeChartClickable(ch, cfg[2]);
                                        } catch (e) { console.warn(cfg[0] + ' error:', e); }
                                    });
                                }
                            }

                            var _loadedPeriod = null;
                            var _reqToken = 0;
                            function loadDataForPeriod(period, force) {
                                if (!period) return;
                                if (!force && _loadedPeriod === period) return; // skip duplicate (eager + loadPeriods)
                                _loadedPeriod = period;
                                var myToken = ++_reqToken;

                                var sSel = document.getElementById('period-site-select');
                                var siteVal = (sSel && sSel.value) ? sSel.value : '';
                                if (window.WMSPeriodManager) {
                                    window.WMSPeriodManager.applyPeriodToNavbar(period, siteVal);
                                }
                                var siteBadge = document.getElementById('storage-hub-selected-site');
                                if (siteBadge) siteBadge.textContent = siteVal ? siteVal.toUpperCase() : '';
                                var pTextEl = document.getElementById('selected-period-text');
                                if (pTextEl) pTextEl.textContent = period.toUpperCase();

                                var mMatch = period.match(/\b(Januari|January|Jan|Februari|February|Feb|Maret|March|Mar|April|Apr|Mei|May|Juni|June|Jun|Juli|July|Jul|Agustus|August|Agu|Aug|September|Sep|Oktober|October|Okt|Oct|November|Nov|Nop|Desember|December|Des|Dec)\b/i);
                                var yMatch = period.match(/\b(20\d{2})\b/);
                                var month = mMatch ? mMatch[1] : '';
                                var yr = yMatch ? yMatch[1] : '';

                                var cacheKeyDash = 'storage_data_' + period;
                                var cacheKeyYearly = 'yearly_in_out_' + yr;

                                // Instant 0ms render from client cache (cards + charts in the same frame)
                                if (window.WMSDataCache) {
                                    var cDash = window.WMSDataCache.get(cacheKeyDash);
                                    var cYear = yr ? window.WMSDataCache.get(cacheKeyYearly) : null;
                                    if (cDash || cYear) applyStorageHubData(period, cDash, cYear);
                                }

                                var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
                                var timeoutTimer = controller ? setTimeout(function () { controller.abort(); }, 60000) : null;

                                var fetchDashboard = fetch('api/get_data.php?periode=' + encodeURIComponent(period), { signal: controller ? controller.signal : undefined })
                                    .then(function (response) {
                                        if (!response.ok) throw new Error('HTTP ' + response.status + ' on get_data');
                                        return response.json();
                                    });

                                var fetchYearly = yr
                                    ? fetch('api/get_yearly_in_out.php?year=' + encodeURIComponent(yr), { signal: controller ? controller.signal : undefined }).then(function (response) {
                                        if (!response.ok) throw new Error('HTTP ' + response.status + ' on get_yearly_in_out');
                                        return response.json();
                                    })
                                    : Promise.resolve(null);

                                // Rack utilisasi fetched in parallel (not after the other two)
                                if (window.FormulaController && window.FormulaController.loadRackUtilisasi && month && yr) {
                                    window.FormulaController.loadRackUtilisasi(month, yr);
                                }

                                Promise.all([fetchDashboard, fetchYearly])
                                    .then(function (results) {
                                        if (myToken !== _reqToken) return; // stale response
                                        if (window.WMSDataCache) {
                                            if (results[0]) window.WMSDataCache.set(cacheKeyDash, results[0]);
                                            if (results[1]) window.WMSDataCache.set(cacheKeyYearly, results[1]);
                                        }
                                        applyStorageHubData(period, results[0], results[1]);
                                    })
                                    .catch(function (error) {
                                        console.error('Error fetching data:', error);
                                    })
                                    .finally(function () {
                                        if (timeoutTimer) clearTimeout(timeoutTimer);
                                    });
                            }

                            window.loadPeriods = function (selectPeriod) {
                                _loadedPeriod = null;
                                if (window.WMSDataCache) { window.WMSDataCache.clear('storage_data_'); window.WMSDataCache.clear('yearly_in_out_'); }
                                loadPeriods(selectPeriod);
                            };


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
                                            if (currentPeriodText.toLowerCase().indexOf(periodToDelete.toLowerCase()) === 0) {
                                                var siteBadge = document.getElementById('storage-hub-selected-site');
                                                if (siteBadge) { siteBadge.textContent = ''; }
                                                if (window.FormulaController) {
                                                    window.FormulaController.updateDashboardCards([], []);
                                                }
                                            }
                                            window.loadPeriods();
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

                            // Initialize periods and active month on load
                            var eagerPeriod = window.WMSPeriodManager ? window.WMSPeriodManager.getActivePeriod() : null;
                            if (eagerPeriod) {
                                loadDataForPeriod(eagerPeriod);
                            }
                            loadPeriods();
                        });
                    </script>

</body>

</html>
