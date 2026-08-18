<?php
require_once __DIR__ . '/../../backend/auth.php';
checkModuleAccess('master_data');

$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? 'admin';

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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

    .nav-tabs .nav-link {
        font-weight: bold;
    }

    .nav-tabs .nav-link.active {
        color: #4e73df;
    }

    .table-responsive {
        overflow-x: auto;
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
                            <i class="fas fa-map-marked-alt mr-2 text-success"></i>Site Location Warehouse
                        </h1>
                    </div>

                    <!-- Action Bar -->
                    <?php if ($userRole !== 'head_warehouse_admin'): ?>
                        <div
                            class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 bg-white p-3 rounded shadow-sm border">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-map-marked-alt mr-2"></i>Menu Site Location Warehouse
                            </h6>
                            <div class="mt-2 mt-sm-0">
                                <button class="btn btn-success btn-sm shadow-sm font-weight-bold mr-2" data-toggle="modal"
                                    data-target="#uploadSiteLocationModal">
                                    <i class="fas fa-file-import mr-1"></i> Import Excel
                                </button>
                                <button class="btn btn-danger btn-sm shadow-sm font-weight-bold" data-toggle="modal"
                                    data-target="#deleteSiteLocationModal">
                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Semua Data
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4" style="min-height: calc(100vh - 380px);">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Tabel Site Location</h6>
                            <span class="badge badge-light text-muted font-weight-normal" id="siteLocationCount">0
                                records</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" id="dataTableSiteLocation" width="100%"
                                    cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Site ID</th>
                                            <th>Category</th>
                                            <th>Intan</th>
                                            <th>Region</th>
                                            <th>Area Cluster</th>
                                            <th>Address</th>
                                            <th>Province</th>
                                            <th>City</th>
                                            <th>Sub District</th>
                                            <th>Village</th>
                                            <th>Postal Code</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
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
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

            <!-- Import Excel Site Location Modal -->
            <div class="modal fade" id="uploadSiteLocationModal" tabindex="-1" role="dialog"
                aria-labelledby="uploadSiteLocationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header bg-success text-white">
                            <h5 class="modal-title font-weight-bold" id="uploadSiteLocationModalLabel">
                                <i class="fas fa-file-excel mr-2"></i>Import Site Location Data
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body upload-modal-body p-4">
                            <div class="upload-drop-zone border rounded p-4 text-center bg-light"
                                id="site-upload-drop-zone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-success mb-3"></i>
                                <h5 class="font-weight-bold">Drag & Drop Excel File</h5>
                                <p class="text-muted small">atau pilih file dari komputer Anda</p>
                                <input type="file" id="excel-file-site-input" accept=".xlsx,.xls,.csv" class="d-none" />
                                <button class="btn btn-success btn-sm px-3 font-weight-bold" type="button"
                                    id="btn-browse-site"
                                    onclick="document.getElementById('excel-file-site-input').click();">
                                    <i class="fas fa-folder-open mr-1"></i> Browse File
                                </button>
                                <div class="small text-muted mt-2">Formats: .xlsx, .xls, .csv &bull; Max 100MB</div>
                            </div>

                            <!-- Upload progress & status -->
                            <div id="site-upload-status" class="mt-3" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-file-excel text-success mr-2"></i>
                                    <span class="font-weight-bold text-gray-800 small" id="site-upload-filename"></span>
                                    <span class="text-muted small ml-2" id="site-upload-filesize"></span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 3px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"
                                        id="site-upload-progress"></div>
                                </div>
                                <div class="small text-muted mt-1" id="site-upload-progress-text"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Site Location Data Modal -->
            <div class="modal fade" id="deleteSiteLocationModal" tabindex="-1" role="dialog"
                aria-labelledby="deleteSiteLocationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content upload-modal-content">
                        <div class="modal-header upload-modal-header"
                            style="background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);">
                            <h5 class="modal-title text-white" id="deleteSiteLocationModalLabel">
                                <i class="fas fa-trash-alt mr-2 text-white"></i>Hapus Data Site Location
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
                                    <p class="mb-0" style="font-size: 1.1rem;">Semua data Site Location akan dihapus
                                        secara permanen dari sistem dan tidak dapat dikembalikan.</p>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-light px-4 mr-2" type="button" data-dismiss="modal"
                                        style="border-radius: 6px; font-weight: 600;">Batal</button>
                                    <button class="btn btn-danger px-4" type="button" id="btn-confirm-delete-site"
                                        style="border-radius: 6px; font-weight: 600; box-shadow: 0 4px 10px rgba(231,74,59,0.3);">
                                        <i class="fas fa-trash mr-1"></i> Hapus Semua Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SheetJS (xlsx) for Excel import -->
            <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <script>
                (function () {
                    'use strict';

                    var siteLocationTable = null;
                    var MAX_FILE_SIZE = 100 * 1024 * 1024;
                    var ALLOWED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];

                    // ── Known header mappings (case-insensitive, fuzzy) ──
                    // Supports both flat headers (ID, Category) and combined multi-row headers (NAME INTAN, REGIONAL REGION, LOCATION ADDR)
                    var FIELD_MAP = {
                        'site_id': ['id', 'site_id', 'siteid', 'site id', 'site_code', 'sitecode', 'kode_site', 'kodesite'],
                        'category': ['category', 'kategori', 'cat'],
                        'intan': ['intan', 'intan_code', 'intancode', 'kode_intan', 'name intan', 'nameintan'],
                        'region': ['region', 'wilayah', 'regional region', 'regionalregion', 'regional', 'reg'],
                        'area_cluster': ['area_cluster', 'areacluster', 'area cluster', 'area', 'regional area', 'regionalarea'],
                        'address': ['addr', 'address', 'alamat', 'addrs', 'location addr', 'locationaddr'],
                        'province': ['province', 'provinsi', 'prov', 'location province', 'locationprovince'],
                        'city': ['city', 'kota', 'kabupaten', 'kab_kota', 'kabkota', 'location city', 'locationcity'],
                        'sub_district': ['sub_dis', 'sub_district', 'subdistrict', 'kecamatan', 'subdis', 'sub dis', 'location sub dis', 'location sub_dis', 'location sub district', 'locationsubdis'],
                        'village': ['village', 'kelurahan', 'desa', 'kel', 'location village', 'locationvillage'],
                        'postal_code': ['postal', 'postal_code', 'postalcode', 'kodepos', 'kode_pos', 'zip', 'zipcode', 'location postal', 'locationpostal'],
                        'latitude': ['lat', 'latitude', 'lintang', 'location lat', 'locationlat'],
                        'longitude': ['long', 'longitude', 'lng', 'bujur', 'location long', 'locationlong']
                    };

                    function normalizeHeader(str) {
                        return String(str).toLowerCase().replace(/[^a-z0-9]/g, '');
                    }

                    function matchField(header) {
                        var norm = normalizeHeader(header);
                        for (var field in FIELD_MAP) {
                            var aliases = FIELD_MAP[field];
                            for (var i = 0; i < aliases.length; i++) {
                                if (normalizeHeader(aliases[i]) === norm) {
                                    return field;
                                }
                            }
                        }
                        return null;
                    }

                    function formatBytes(bytes) {
                        if (bytes < 1024) return bytes + ' B';
                        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                    }

                    /**
                     * Build flattened headers from a sheet that may have multi-row headers.
                     * Handles both Excel merged cells AND CSV/TSV with empty parent cells.
                     *
                     * Example Excel structure:
                     *   Row 0: ACTIVE | ID | CATEGORY | NAME |      |     | ORGANIZATIONS | MANAGER | REGIONAL |      |         | LOCATION |          |
                     *   Row 1:        |    |          | INTAN| EPROC| IMS |               |         | REGION   | AREA | CLUSTER | ADDR     | PROVINCE | CITY
                     *
                     * Result: ACTIVE, ID, CATEGORY, NAME INTAN, NAME EPROC, NAME IMS, ORGANIZATIONS, MANAGER,
                     *         REGIONAL REGION, REGIONAL AREA, REGIONAL CLUSTER, LOCATION ADDR, LOCATION PROVINCE, LOCATION CITY
                     */
                    function buildFlatHeaders(sheet) {
                        var range = XLSX.utils.decode_range(sheet['!ref']);
                        var merges = sheet['!merges'] || [];

                        // Read first 2 rows as raw values
                        function cellVal(r, c) {
                            var addr = XLSX.utils.encode_cell({ r: r, c: c });
                            var cell = sheet[addr];
                            return cell ? String(cell.v || '').trim() : '';
                        }

                        var row0 = [], row1 = [];
                        for (var c = range.s.c; c <= range.e.c; c++) {
                            row0.push(cellVal(0, c));
                            row1.push(cellVal(1, c));
                        }

                        // Step 1: Expand merged cells in row 0
                        merges.forEach(function (m) {
                            if (m.s.r === 0) {
                                var parentVal = cellVal(m.s.r, m.s.c);
                                for (var mc = m.s.c; mc <= m.e.c; mc++) {
                                    row0[mc - range.s.c] = parentVal;
                                }
                            }
                        });

                        // Step 2: Detect if row 1 is a sub-header row
                        var hasSubHeaders = false;

                        // Check for merged parent cells with different sub-headers
                        for (var ci = 0; ci < row1.length; ci++) {
                            if (row1[ci] && row0[ci] && row1[ci] !== row0[ci]) {
                                var isUnderMerge = merges.some(function (m) {
                                    return m.s.r === 0 && (ci + range.s.c) >= m.s.c && (ci + range.s.c) <= m.e.c && m.s.c !== m.e.c;
                                });
                                if (isUnderMerge) {
                                    hasSubHeaders = true;
                                    break;
                                }
                            }
                        }

                        // Check for sub-headers where row0 is empty (CSV/TSV without merges)
                        if (!hasSubHeaders) {
                            for (var ci2 = 0; ci2 < row1.length; ci2++) {
                                if (row1[ci2] && !row0[ci2]) {
                                    hasSubHeaders = true;
                                    break;
                                }
                            }
                        }

                        // Step 3: Forward-fill empty cells in row0 (for CSV/TSV files without merge info)
                        // E.g. [NAME, '', '', REGIONAL, '', ''] → [NAME, NAME, NAME, REGIONAL, REGIONAL, REGIONAL]
                        if (hasSubHeaders && merges.length === 0) {
                            var lastNonEmpty = '';
                            for (var fi = 0; fi < row0.length; fi++) {
                                if (row0[fi]) {
                                    lastNonEmpty = row0[fi];
                                } else if (row1[fi] && lastNonEmpty) {
                                    // Only fill if there's a sub-header below (not just an empty gap)
                                    row0[fi] = lastNonEmpty;
                                }
                            }
                        }

                        var headers = [];
                        var dataStartRow = 1; // default: data starts at row 1

                        if (hasSubHeaders) {
                            dataStartRow = 2; // data starts at row 2
                            for (var hi = 0; hi < row0.length; hi++) {
                                var parent = row0[hi] || '';
                                var child = row1[hi] || '';
                                if (child && parent && child !== parent) {
                                    // Combined: "REGIONAL REGION", "NAME INTAN", "LOCATION ADDR"
                                    headers.push(parent + ' ' + child);
                                } else if (child && !parent) {
                                    headers.push(child);
                                } else if (parent && !child) {
                                    headers.push(parent);
                                } else {
                                    headers.push(parent || child || ('Col' + hi));
                                }
                            }
                        } else {
                            headers = row0.map(function (h, i) { return h || ('Col' + i); });
                        }

                        return { headers: headers, dataStartRow: dataStartRow };
                    }

                    // ── Initialize DataTable ──
                    function initSiteLocationTable() {
                        if (siteLocationTable) return;
                        if ($('#dataTableSiteLocation').length === 0) return;

                        siteLocationTable = $('#dataTableSiteLocation').DataTable({
                            processing: true,
                            serverSide: true,
                            deferRender: true,
                            ajax: {
                                url: 'api/get_site_location.php',
                                type: 'GET',
                                error: function (xhr, error, thrown) {
                                    if (typeof Swal !== 'undefined') Swal.close();
                                }
                            },
                            columns: [
                                { data: 'site_id', defaultContent: '' },
                                { data: 'category', defaultContent: '' },
                                { data: 'intan', defaultContent: '' },
                                { data: 'region', defaultContent: '' },
                                { data: 'area_cluster', defaultContent: '' },
                                { data: 'address', defaultContent: '' },
                                { data: 'province', defaultContent: '' },
                                { data: 'city', defaultContent: '' },
                                { data: 'sub_district', defaultContent: '' },
                                { data: 'village', defaultContent: '' },
                                { data: 'postal_code', defaultContent: '' },
                                { data: 'latitude', defaultContent: '' },
                                { data: 'longitude', defaultContent: '' }
                            ],
                            order: [[0, 'asc']],
                            pageLength: 25,
                            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                            language: {
                                processing: "Data Is Processing Please Wait",
                                emptyTable: "Belum ada data Site Location. Silakan import Excel.",
                                zeroRecords: "Tidak ada data yang cocok dengan pencarian."
                            },
                            drawCallback: function (settings) {
                                var info = this.api().page.info();
                                $('#siteLocationCount').text(info.recordsTotal + ' records');
                            }
                        });
                    }

                    // ── Excel Upload Handler (supports multi-row headers) ──
                    function handleSiteFile(file) {
                        // Validate extension
                        var fileName = file.name;
                        var ext = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
                        if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
                            Swal.fire('Format Tidak Valid', 'File harus berformat .xlsx, .xls, atau .csv', 'error');
                            return;
                        }
                        if (file.size > MAX_FILE_SIZE) {
                            Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 100 MB', 'error');
                            return;
                        }

                        // Show file info
                        $('#site-upload-status').show();
                        $('#site-upload-filename').text(fileName);
                        $('#site-upload-filesize').text('(' + formatBytes(file.size) + ')');
                        $('#site-upload-progress').css('width', '10%');
                        $('#site-upload-progress-text').text('Membaca file Excel...');

                        var reader = new FileReader();
                        reader.onload = function (e) {
                            $('#site-upload-progress').css('width', '40%');
                            $('#site-upload-progress-text').text('Parsing data & scanning headers...');

                            try {
                                var data = new Uint8Array(e.target.result);
                                var workbook = XLSX.read(data, { type: 'array', cellDates: true });
                                var sheetName = workbook.SheetNames[0];
                                var sheet = workbook.Sheets[sheetName];

                                // Build flattened headers from multi-row structure
                                var headerInfo = buildFlatHeaders(sheet);
                                var flatHeaders = headerInfo.headers;
                                var dataStartRow = headerInfo.dataStartRow;

                                console.log('[SiteLocation] Detected headers:', flatHeaders);
                                console.log('[SiteLocation] Data starts at row:', dataStartRow);

                                // Read all rows as raw arrays
                                var rawRows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

                                // Extract data rows (skip header rows)
                                var dataRows = rawRows.slice(dataStartRow);

                                if (!dataRows || dataRows.length === 0) {
                                    Swal.fire('Data Kosong', 'File Excel tidak berisi data.', 'warning');
                                    resetUploadUI();
                                    return;
                                }

                                $('#site-upload-progress').css('width', '60%');
                                $('#site-upload-progress-text').text('Mapping ' + dataRows.length + ' baris...');

                                // Build the header → field mapping
                                var headerMapping = {};
                                flatHeaders.forEach(function (h, idx) {
                                    var field = matchField(h);
                                    if (field && !(field in headerMapping)) {
                                        headerMapping[field] = idx;
                                    }
                                });

                                console.log('[SiteLocation] Field mapping:', headerMapping);

                                // Map data rows
                                var mappedRows = [];
                                dataRows.forEach(function (rowArr) {
                                    if (!Array.isArray(rowArr)) return;
                                    // Skip completely empty rows
                                    var hasData = rowArr.some(function (v) { return v !== '' && v !== null && v !== undefined; });
                                    if (!hasData) return;

                                    var mapped = {};
                                    for (var field in FIELD_MAP) {
                                        var colIdx = headerMapping[field];
                                        mapped[field] = (colIdx !== undefined && rowArr[colIdx] !== undefined)
                                            ? String(rowArr[colIdx] || '')
                                            : '';
                                    }

                                    // Build raw object with all flattened headers
                                    var rawObj = {};
                                    flatHeaders.forEach(function (h, idx) {
                                        rawObj[h] = (rowArr[idx] !== undefined) ? rowArr[idx] : '';
                                    });
                                    mapped['_raw'] = rawObj;

                                    mappedRows.push(mapped);
                                });

                                if (mappedRows.length === 0) {
                                    Swal.fire('Data Kosong', 'Tidak ada baris data yang valid ditemukan.', 'warning');
                                    resetUploadUI();
                                    return;
                                }

                                $('#site-upload-progress-text').text('Mengirim ' + mappedRows.length + ' baris ke server...');

                                // Send to server in batch
                                sendBatchData(mappedRows);

                            } catch (err) {
                                Swal.fire('Error Parsing', 'Gagal memproses file Excel: ' + err.message, 'error');
                                resetUploadUI();
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    }

                    function sendBatchData(rows) {
                        var BATCH_SIZE = 500;
                        var totalRows = rows.length;
                        var batches = [];
                        for (var i = 0; i < totalRows; i += BATCH_SIZE) {
                            batches.push(rows.slice(i, i + BATCH_SIZE));
                        }

                        var batchIdx = 0;
                        var totalInserted = 0;

                        function sendNext() {
                            if (batchIdx >= batches.length) {
                                // Done
                                $('#site-upload-progress').css('width', '100%');
                                $('#site-upload-progress-text').text('Selesai! ' + totalInserted + ' baris berhasil diimport.');

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Import Berhasil!',
                                    text: totalInserted + ' baris data Site Location berhasil diimport.',
                                    confirmButtonColor: '#1cc88a'
                                });

                                $('#uploadSiteLocationModal').modal('hide');
                                if (siteLocationTable) {
                                    siteLocationTable.ajax.reload();
                                }
                                setTimeout(resetUploadUI, 1000);
                                return;
                            }

                            var batch = batches[batchIdx];
                            var progress = Math.round(60 + (batchIdx / batches.length) * 35);
                            $('#site-upload-progress').css('width', progress + '%');
                            $('#site-upload-progress-text').text('Mengirim batch ' + (batchIdx + 1) + ' dari ' + batches.length + '...');

                            fetch('api/save_site_location.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'append',
                                    data: batch
                                })
                            })
                                .then(function (resp) { return resp.json(); })
                                .then(function (res) {
                                    if (res.status === 'success') {
                                        totalInserted += (res.inserted || batch.length);
                                        batchIdx++;
                                        sendNext();
                                    } else {
                                        Swal.fire('Error', res.message || 'Gagal menyimpan data.', 'error');
                                        resetUploadUI();
                                    }
                                })
                                .catch(function (err) {
                                    Swal.fire('Error', 'Gagal menghubungi server: ' + err.message, 'error');
                                    resetUploadUI();
                                });
                        }

                        sendNext();
                    }

                    function resetUploadUI() {
                        $('#site-upload-status').hide();
                        $('#site-upload-progress').css('width', '0%');
                        $('#site-upload-progress-text').text('');
                        $('#site-upload-filename').text('');
                        $('#site-upload-filesize').text('');
                        var fileInput = document.getElementById('excel-file-site-input');
                        if (fileInput) fileInput.value = '';
                    }

                    // ── Event Bindings ──
                    $(document).ready(function () {
                        initSiteLocationTable();

                        // File input change
                        var fileInput = document.getElementById('excel-file-site-input');
                        if (fileInput) {
                            fileInput.addEventListener('change', function () {
                                if (this.files && this.files[0]) {
                                    handleSiteFile(this.files[0]);
                                }
                            });
                        }

                        // Drag & drop
                        var dropZone = document.getElementById('site-upload-drop-zone');
                        if (dropZone) {
                            dropZone.addEventListener('dragover', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                dropZone.style.borderColor = '#1cc88a';
                                dropZone.style.backgroundColor = '#e8faf1';
                            });
                            dropZone.addEventListener('dragleave', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                dropZone.style.borderColor = '';
                                dropZone.style.backgroundColor = '';
                            });
                            dropZone.addEventListener('drop', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                dropZone.style.borderColor = '';
                                dropZone.style.backgroundColor = '';
                                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                                    handleSiteFile(e.dataTransfer.files[0]);
                                }
                            });
                        }

                        // Delete all data
                        var btnDelete = document.getElementById('btn-confirm-delete-site');
                        if (btnDelete) {
                            btnDelete.addEventListener('click', function () {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Apakah Anda YAKIN?',
                                        text: 'Semua data Site Location akan dihapus permanen!',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#e74a3b',
                                        cancelButtonColor: '#858796',
                                        confirmButtonText: 'Ya, Hapus!'
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            executeDeleteAll();
                                        }
                                    });
                                } else {
                                    if (confirm('Apakah Anda YAKIN ingin menghapus semua data Site Location?')) {
                                        executeDeleteAll();
                                    }
                                }
                            });
                        }

                        function executeDeleteAll() {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Data Is Processing Please Wait',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: function () { Swal.showLoading(); }
                                });
                            }

                            fetch('api/delete_site_location.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'delete_all' })
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (res) {
                                    if (res.status === 'success') {
                                        Swal.fire('Berhasil!', res.message || 'Semua data Site Location berhasil dihapus.', 'success');
                                        $('#deleteSiteLocationModal').modal('hide');
                                        if (siteLocationTable) {
                                            siteLocationTable.ajax.reload();
                                        }
                                    } else {
                                        Swal.fire('Error', 'Gagal menghapus data: ' + (res.message || ''), 'error');
                                    }
                                })
                                .catch(function (err) {
                                    Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                                });
                        }

                        // Reset upload UI on modal close
                        $('#uploadSiteLocationModal').on('hidden.bs.modal', function () {
                            resetUploadUI();
                        });
                    });
                })();
            </script>

            </html>