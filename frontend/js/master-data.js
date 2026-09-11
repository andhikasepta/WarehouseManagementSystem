// js/master-data.js
if (window.jQuery && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.errMode = 'none';
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            processing: "Data Is Processing Please Wait"
        }
    });
}

if (window.jQuery) {
    function closeDtLoading() {
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }
        $('.dataTables_processing').hide();
    }

    // Close any lingering alerts and ensure processing overlay is always dismissed on table events
    $(document).on('xhr.dt error.dt draw.dt init.dt processing.dt', function (e, settings, processing) {
        if (processing === false || e.type !== 'processing') {
            closeDtLoading();
        }
    });

    // Global jQuery AJAX error handler: guarantee no stuck processing indicators
    $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
        $('.dataTables_processing').hide();
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }
        if (jqXHR.status === 401 || jqXHR.status === 403) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Berakhir',
                    text: 'Sesi login Anda telah berakhir atau tidak memiliki izin akses. Silakan login kembali.',
                    confirmButtonText: 'Login'
                }).then(function () {
                    window.location.href = 'login.php';
                });
            }
        }
    });

    // Master Data menu does not use the navbar period selector
    $('#nav-item-period-selector, #periodDropdown').closest('.nav-item').hide();
}

/**
 * Robust fetch wrapper with timeout, HTTP status validation, and clean error messages.
 */
function safeFetchJson(url, options, timeoutMs) {
    timeoutMs = timeoutMs || 120000;
    var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
    var timer = controller ? setTimeout(function () { controller.abort(); }, timeoutMs) : null;
    var opts = Object.assign({}, options);
    if (controller) opts.signal = controller.signal;

    return fetch(url, opts)
        .then(function (r) {
            if (timer) clearTimeout(timer);
            if (!r.ok) {
                throw new Error('Server mengembalikan error HTTP ' + r.status + ' (' + r.statusText + ')');
            }
            return r.json();
        })
        .catch(function (err) {
            if (timer) clearTimeout(timer);
            if (err.name === 'AbortError') {
                throw new Error('Waktu proses melebihi batas (Timeout ' + Math.round(timeoutMs / 1000) + ' detik). Silakan coba lagi.');
            }
            throw err;
        });
}
var inboundTable = null;
var assetTable = null;
var rackTable = null;
var outboundTable = null;
var forwarderTable = null;
var kpiMasterTable = null;
var existingInboundPeriods = [];

// Refresh outbound filter dropdowns from server
function refreshOutboundFilters() {
    $.getJSON('api/get_outbound_filters.php', function (json) {
        if (json.status === 'success' && json.data) {
            if (json.data.periods) {
                var $pSel = $('#filter-outbound-periode');
                $pSel.find('option:not(:first)').remove();
                json.data.periods.forEach(function (p) {
                    $pSel.append('<option value="' + p + '">' + p + '</option>');
                });
            }
            if (json.data.site_destinations) {
                var $destSel = $('#filter-tujuan-site-outbound');
                $destSel.find('option:not(:first)').remove();
                json.data.site_destinations.forEach(function (d) {
                    $destSel.append('<option value="' + d + '">' + d + '</option>');
                });
            }
            if (json.data.mr_statuses) {
                var $mrSel = $('#filter-mr-status-outbound');
                $mrSel.find('option:not(:first)').remove();
                json.data.mr_statuses.forEach(function (s) {
                    $mrSel.append('<option value="' + s + '">' + s + '</option>');
                });
            }
            if (json.data.dn_statuses) {
                var $dnSel = $('#filter-dn-status-outbound');
                $dnSel.find('option:not(:first)').remove();
                json.data.dn_statuses.forEach(function (s) {
                    $dnSel.append('<option value="' + s + '">' + s + '</option>');
                });
            }
        }
    });
}

function initOutboundTable() {
    if (outboundTable || $('#dataTableOutbound').length === 0) return;

    refreshOutboundFilters();

    outboundTable = $('#dataTableOutbound').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: true,
        ajax: {
            url: 'api/get_outbound_master.php',
            type: 'GET',
            data: function (d) {
                d.periode = $('#filter-outbound-periode').val();
                d.site_destination = $('#filter-tujuan-site-outbound').val();
                d.mr_status = $('#filter-mr-status-outbound').val();
                d.dn_status = $('#filter-dn-status-outbound').val();
                d.mr_no = $('#filter-outbound-mr').val();
            }
        },
        columns: [
            { data: 'mr_no', defaultContent: '-' },
            { data: 'mr_type', defaultContent: '-' },
            { data: 'mr_desc', defaultContent: '-' },
            { data: 'mr_status', defaultContent: '-' },
            { data: 'pck_no', defaultContent: '-' },
            { data: 'pck_detail', defaultContent: '-' },
            { data: 'pck_status', defaultContent: '-' },
            { data: 'awb', defaultContent: '-' },
            { data: 'dn_no', defaultContent: '-' },
            { data: 'pr_no', defaultContent: '-' },
            { data: 'po_no', defaultContent: '-' },
            { data: 'origin_from', defaultContent: '-' },
            { data: 'site_origin', defaultContent: '-' },
            { data: 'site_origin_addr', defaultContent: '-' },
            { data: 'destination_to', defaultContent: '-' },
            { data: 'site_destination', defaultContent: '-' },
            { data: 'site_destination_addr', defaultContent: '-' },
            { data: 'pickup_type', defaultContent: '-' },
            { data: 'via', defaultContent: '-' },
            { data: 'lt', defaultContent: '-' },
            { data: 'delivery_target', defaultContent: '-' },
            { data: 'dn_status', defaultContent: '-' },
            { data: 'last_log', defaultContent: '-' },
            { data: 'periode_group', defaultContent: '-' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "Search:",
            searchPlaceholder: "Search...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            emptyTable: "Belum ada data Master Outbound.",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        initComplete: function () {
            var api = this.api();
            var $input = $('#dataTableOutbound_filter input');
            if ($input.length) {
                $input.attr('placeholder', 'Search...');
                $input.unbind();
                $input.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        api.search(this.value).draw();
                    }
                });
            }
        }
    });

    // Reactive dropdown filters
    $('#filter-outbound-periode, #filter-tujuan-site-outbound, #filter-mr-status-outbound, #filter-dn-status-outbound').on('change', function () {
        outboundTable.ajax.reload();
    });

    // Outbound MR / Document Search on Enter key press
    $('#filter-outbound-mr').off('keyup input change keydown').on('keydown', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            if (outboundTable) {
                outboundTable.ajax.reload();
            }
        }
    });

    $('#btn-reset-filter-outbound').on('click', function () {
        $('#filter-outbound-periode').val('');
        $('#filter-tujuan-site-outbound').val('');
        $('#filter-mr-status-outbound').val('');
        $('#filter-dn-status-outbound').val('');
        $('#filter-outbound-mr').val('');
        outboundTable.search('').columns().search('').ajax.reload();
    });
}

// Refresh PR forwarder filter dropdowns from server
function refreshForwarderFilters() {
    $.getJSON('api/get_outbound_forwarder_filters.php', function (json) {
        if (json.status === 'success' && json.data && json.data.periods) {
            var $pSel = $('#filter-forwarder-periode');
            $pSel.find('option:not(:first)').remove();
            json.data.periods.forEach(function (p) {
                $pSel.append('<option value="' + p + '">' + p + '</option>');
            });
        }
    });
}

function initForwarderTable() {
    if (forwarderTable || $('#dataTablePrForwarder').length === 0) return;

    refreshForwarderFilters();

    forwarderTable = $('#dataTablePrForwarder').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        scrollX: true,
        order: [],
        ordering: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: 'api/get_outbound_forwarder.php',
            type: 'GET',
            data: function (d) {
                d.periode = $('#filter-forwarder-periode').val();
            }
        },
        columns: [
            { data: 'no_dn', defaultContent: '-', className: 'text-center' },
            { data: 'print_status', defaultContent: '-', className: 'text-center' },
            { data: 'dn_status', defaultContent: '-', className: 'text-center' },
            { data: 'asal_pengirim', defaultContent: '-' },
            { data: 'asal_code', defaultContent: '-', className: 'text-center' },
            { data: 'asal_site', defaultContent: '-' },
            { data: 'asal_alamat', defaultContent: '-' },
            { data: 'tujuan_penerima', defaultContent: '-' },
            { data: 'tujuan_code', defaultContent: '-', className: 'text-center' },
            { data: 'tujuan_site', defaultContent: '-' },
            { data: 'tujuan_alamat', defaultContent: '-' },
            { data: 'proc_vendor_mode', defaultContent: '-', className: 'text-center' },
            { data: 'proc_vendor_name', defaultContent: '-' },
            { data: 'koli', defaultContent: '-', className: 'text-center' },
            { data: 'mata_anggaran', defaultContent: '-', className: 'text-center' },
            { data: 'sr_no', defaultContent: '-', className: 'text-center' },
            { data: 'sr_tgl', defaultContent: '-', className: 'text-center' },
            { data: 'pr_no', defaultContent: '-', className: 'text-center' },
            { data: 'pr_tgl', defaultContent: '-', className: 'text-center' },
            { data: 'valuation_price', defaultContent: '-', className: 'text-center' },
            { data: 'suggestion', defaultContent: '-' },
            { data: 'purpose', defaultContent: '-' },
            { data: 'po_no', defaultContent: '-', className: 'text-center' },
            { data: 'po_tgl', defaultContent: '-', className: 'text-center' },
            { data: 'po_price', defaultContent: '-', className: 'text-center' },
            { data: 'po_vendor', defaultContent: '-' },
            { data: 'po_target_dlv', defaultContent: '-', className: 'text-center' },
            { data: 'po_buyer', defaultContent: '-' },
            { data: 'doc', defaultContent: '-', className: 'text-center' },
            { data: 'note', defaultContent: '-' },
            { data: 'delivery_type', defaultContent: '-', className: 'text-center' },
            { data: 'delivery_via', defaultContent: '-', className: 'text-center' },
            { data: 'delivery_nama', defaultContent: '-' },
            { data: 'delivery_awb', defaultContent: '-', className: 'text-center' },
            { data: 'delivery_pickup', defaultContent: '-', className: 'text-center' },
            { data: 'delivery_lead_time', defaultContent: '-', className: 'text-center' },
            { data: 'delivery_target_dlv', defaultContent: '-', className: 'text-center' },
            { data: 'approval_status', defaultContent: '-', className: 'text-center' },
            { data: 'approval_approver', defaultContent: '-' },
            { data: 'approval_date', defaultContent: '-', className: 'text-center' },
            { data: 'periode_group', defaultContent: '-', className: 'text-center' }
        ],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "Search:",
            searchPlaceholder: "Search...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            emptyTable: "Belum ada data Master PR Forwarder.",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        initComplete: function () {
            var api = this.api();
            var $input = $('#dataTablePrForwarder_filter input');
            if ($input.length) {
                $input.attr('placeholder', 'Search...');
                $input.unbind();
                $input.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        api.search(this.value).draw();
                    }
                });
            }
        }
    });

    // Reactive Periode dropdown filter (identical to KPI Master Data)
    $('#filter-forwarder-periode').on('change', function () {
        if (forwarderTable) {
            forwarderTable.ajax.reload();
        }
    });

    // Reset filter
    $('#btn-reset-filter-forwarder').on('click', function () {
        $('#filter-forwarder-periode').val('');
        if (forwarderTable) {
            forwarderTable.search('').columns().search('').ajax.reload();
        }
    });
}

// Refresh inbound filter dropdowns from server (called after upload/delete)
function refreshInboundFilters() {
    $.getJSON('api/get_inbound_filters.php', function (json) {
        if (json.status === 'success' && json.filters) {
            if (json.filters.periode) {
                existingInboundPeriods = json.filters.periode || [];
                var $periodeSel = $('#filter-inbound-periode');
                $periodeSel.find('option:not(:first)').remove();
                json.filters.periode.forEach(function (p) {
                    $periodeSel.append('<option value="' + p + '">' + p + '</option>');
                });
                checkInboundPeriodStatus();
            }
            if (json.filters.bagian) {
                var $bagianSel = $('#filter-inbound-bagian');
                $bagianSel.find('option:not(:first)').remove();
                json.filters.bagian.forEach(function (b) {
                    $bagianSel.append('<option value="' + b + '">' + b + '</option>');
                });
            }
            if (json.filters.pic) {
                var $picSel = $('#filter-inbound-pic');
                $picSel.find('option:not(:first)').remove();
                json.filters.pic.forEach(function (p) {
                    $picSel.append('<option value="' + p + '">' + p + '</option>');
                });
            }
            if (json.filters.kategori) {
                var $kategoriSel = $('#filter-inbound-kategori');
                $kategoriSel.find('option:not(:first)').remove();
                json.filters.kategori.forEach(function (k) {
                    $kategoriSel.append('<option value="' + k + '">' + k + '</option>');
                });
            }
        }
    });
}

function checkInboundPeriodStatus() {
    var month = $('#uploadInboundMonthSelect').val();
    var year = $('#uploadInboundYearSelect').val();
    var $statusDiv = $('#inbound-period-status');
    var $browseBtn = $('#btn-browse-inbound');

    if (!month || !year) {
        $statusDiv.hide().empty();
        $browseBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
        return;
    }

    var selectedPeriod = month.trim() + ' ' + year.trim();
    var exists = existingInboundPeriods.some(function (p) {
        return p && p.trim().toLowerCase() === selectedPeriod.toLowerCase();
    });

    if (exists) {
        $statusDiv.html(
            '<div class="alert alert-danger py-2 px-3 mb-0 small font-weight-bold d-flex align-items-center" style="border-radius: 6px;">' +
            '<i class="fas fa-exclamation-triangle text-danger mr-2 fa-lg"></i>' +
            '<div>Periode <span class="badge badge-danger px-2 py-1 ml-1">' + selectedPeriod + '</span> Sudah Ada di System!</div>' +
            '</div>'
        ).show();
        $browseBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
    } else {
        $statusDiv.html(
            '<div class="alert alert-success py-2 px-3 mb-0 small font-weight-bold d-flex align-items-center" style="border-radius: 6px;">' +
            '<i class="fas fa-check-circle text-success mr-2 fa-lg"></i>' +
            '<div>Periode <span class="badge badge-success px-2 py-1 ml-1">' + selectedPeriod + '</span> Tersedia' +
            '<span class="font-weight-normal text-muted ml-1">- Silakan pilih file Excel.</span></div>' +
            '</div>'
        ).show();
        $browseBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
    }
}

function initInboundTable() {
    if (inboundTable || $('#dataTableInbound').length === 0) return;

    // Load filter options from separate lightweight endpoint (once)
    $.getJSON('api/get_inbound_filters.php', function (json) {
        if (json.status === 'success' && json.filters) {
            var $periodeSel = $('#filter-inbound-periode');
            var $bagianSel = $('#filter-inbound-bagian');
            var $picSel = $('#filter-inbound-pic');
            var $kategoriSel = $('#filter-inbound-kategori');

            if (json.filters.periode) {
                existingInboundPeriods = json.filters.periode || [];
                $periodeSel.find('option:not(:first)').remove();
                json.filters.periode.forEach(function (p) {
                    $periodeSel.append('<option value="' + p + '">' + p + '</option>');
                });
                checkInboundPeriodStatus();
            }
            if (json.filters.bagian) {
                $bagianSel.find('option:not(:first)').remove();
                json.filters.bagian.forEach(function (b) {
                    $bagianSel.append('<option value="' + b + '">' + b + '</option>');
                });
            }
            if (json.filters.pic) {
                $picSel.find('option:not(:first)').remove();
                json.filters.pic.forEach(function (p) {
                    $picSel.append('<option value="' + p + '">' + p + '</option>');
                });
            }
            if (json.filters.kategori) {
                $kategoriSel.find('option:not(:first)').remove();
                json.filters.kategori.forEach(function (k) {
                    $kategoriSel.append('<option value="' + k + '">' + k + '</option>');
                });
            }
        }
    });

    // Server-side DataTables — only fetches the visible page from the server
    inboundTable = $('#dataTableInbound').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: 'api/get_inbound_master.php',
            type: 'GET'
        },
        columns: [
            { data: 'pr_nomor', defaultContent: '-' },
            { data: 'pr_kode_site', defaultContent: '-' },
            { data: 'pr_nama_site', defaultContent: '-' },
            { data: 'pr_item_kategori', defaultContent: '-' },
            { data: 'pr_pic_teknis_nama', defaultContent: '-' },
            { data: 'pr_nama_bagian', defaultContent: '-' },
            { data: 'pr_nama_divisi', defaultContent: '-' },
            { data: 'pr_regional', defaultContent: '-' },
            { data: 'pr_jenis_ma', defaultContent: '-' },
            { data: 'po_nomor', defaultContent: '-' },
            { data: 'po_deskripsi', defaultContent: '-' },
            { data: 'po_vendor', defaultContent: '-' },
            { data: 'po_tgl_generate', defaultContent: '-' },
            { data: 'po_nama_item', defaultContent: '-' },
            {
                data: 'po_qty_item',
                render: function (data) {
                    return (typeof data === 'number') ? data.toLocaleString('id-ID') : (data || 0);
                }
            },
            { data: 'po_uom_item', defaultContent: '-' },
            { data: 'po_target_delivery', defaultContent: '-' },
            { data: 'project_id', defaultContent: '-' },
            {
                data: 'periode_group',
                render: function (data) {
                    return data ? data : 'Unknown Period';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Search:",
            searchPlaceholder: "Search...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            zeroRecords: "Tidak ada data yang cocok",
            emptyTable: "Belum ada data Master Inbound.",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        initComplete: function () {
            var api = this.api();
            var $input = $('#dataTableInbound_filter input');
            if ($input.length) {
                $input.attr('placeholder', 'Search...');
                $input.unbind();
                $input.on('keydown', function (e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        api.search(this.value).draw();
                    }
                });
            }
        }
    });

    // Populate Year dropdowns for All Upload & Delete Modals (Storage, Inbound, Outbound)
    function populateAllYearDropdowns() {
        var currentYear = new Date().getFullYear();
        var yearSelectIds = [
            'upload-tahun-select',
            'uploadInboundYearSelect',
            'uploadOutboundYearSelect',
            'deleteYearSelect',
            'deleteInboundYearSelect',
            'deleteOutboundYearSelect'
        ];
        yearSelectIds.forEach(function (id) {
            var ySel = document.getElementById(id);
            if (ySel) {
                var currentVal = ySel.value;
                if (ySel.options.length <= 1) {
                    for (var y = 2024; y <= currentYear + 5; y++) {
                        var opt = document.createElement('option');
                        opt.value = String(y);
                        opt.textContent = String(y);
                        ySel.appendChild(opt);
                    }
                    if (!currentVal) {
                        ySel.value = String(currentYear);
                    }
                }
            }
        });
    }
    populateAllYearDropdowns();

    $('#uploadExcelModal, #uploadExcelModalRack, #uploadExcelModalInbound, #uploadExcelModalOutbound, #uploadExcelModalForwarder, #uploadExcelModalKpi, #deleteDataModal, #deleteDataModalInbound, #deleteDataModalOutbound, #deleteDataModalForwarder, #deleteDataModalKpi').on('show.bs.modal', function () {
        populateAllYearDropdowns();
    });

    $('#uploadInboundMonthSelect, #uploadInboundYearSelect').off('change.inbound').on('change.inbound', checkInboundPeriodStatus);

    // Per-column filters — send search to server via DataTables column().search()
    $('#filter-inbound-periode').off('change.inbound').on('change.inbound', function () {
        var val = $(this).val();
        inboundTable.column(18).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#filter-inbound-bagian').off('change.inbound').on('change.inbound', function () {
        var val = $(this).val();
        inboundTable.column(5).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#filter-inbound-pic').off('change.inbound').on('change.inbound', function () {
        var val = $(this).val();
        inboundTable.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#filter-inbound-kategori').off('change.inbound').on('change.inbound', function () {
        var val = $(this).val();
        inboundTable.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // PR/PO search on Enter key press only
    $('#filter-inbound-po').off('keyup input change keydown').on('keydown', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            var val = $(this).val();
            if (inboundTable) {
                inboundTable.search(val).draw();
            }
        }
    });

    $('#btn-reset-filter-inbound').off('click.inbound').on('click.inbound', function () {
        $('#filter-inbound-periode').val('');
        $('#filter-inbound-bagian').val('');
        $('#filter-inbound-pic').val('');
        $('#filter-inbound-kategori').val('');
        $('#filter-inbound-po').val('');
        if (inboundTable) {
            inboundTable.search('').columns().search('').draw();
        }
    });
}

function initAssetTable() {
    if (assetTable || $('#dataTableAsset').length === 0) return;

    var assetFiltersLoaded = false;

    assetTable = $('#dataTableAsset').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: 'api/get_master_assets.php',
            type: 'GET',
            dataSrc: function (json) {
                // Populate filter dropdowns from server response (once)
                if (!assetFiltersLoaded && json.filters) {
                    assetFiltersLoaded = true;
                    var $periodeSelect = $('#filterAssetPeriode');
                    var $subLocSelect = $('#filterAssetSubLocation');

                    if (json.filters.periodes) {
                        $periodeSelect.find('option:not(:first)').remove();
                        json.filters.periodes.forEach(function (d) {
                            if (d) $periodeSelect.append('<option value="' + d + '">' + d + '</option>');
                        });
                    }
                    if (json.filters.subLocations) {
                        $subLocSelect.find('option:not(:first)').remove();
                        json.filters.subLocations.forEach(function (d) {
                            if (d) $subLocSelect.append('<option value="' + d + '">' + d + '</option>');
                        });
                    }

                    $('#filterAssetPeriode, #filterAssetSubLocation').select2({ width: '100%' });
                }
                return json.data || [];
            }
        },
        columns: [
            { data: 'spec_code' },
            { data: 'spec_name' },
            { data: 'reg_no' },
            { data: 'asset_planner_organization' },
            { data: 'nbv', render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ') },
            { data: 'so_result' },
            { data: 'so_location' },
            { data: 'range' },
            { data: 'sub_location' },
            { data: 'category' },
            { data: 'periode_group' },
            {
                data: 'status',
                render: function (data, type, row) {
                    if (data === 'IN') {
                        return '<span class="badge badge-success px-2 py-1">IN</span>';
                    } else if (data === 'OUT') {
                        return '<span class="badge badge-danger px-2 py-1">OUT</span>';
                    } else if (data === '-') {
                        return '<span class="badge badge-secondary px-2 py-1">-</span>';
                    }
                    return data ? data : '';
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            search: "Search:",
            searchPlaceholder: "Search..."
        },
        initComplete: function () {
            var api = this.api();
            var $searchBar = $('#dataTableAsset_filter');
            $searchBar.detach().appendTo('#assetSearchContainer');
            $searchBar.css({ 'text-align': 'right', 'width': '100%' });
            $searchBar.find('label').css({ 'margin-bottom': '0', 'display': 'inline-flex', 'align-items': 'center' });
            var $input = $searchBar.find('input');
            $input.css('margin-left', '0.5em').attr('placeholder', 'Search...');
            $input.unbind();
            $input.on('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    api.search(this.value).draw();
                }
            });
        }
    });

    // Per-column filters — send search to server
    $('#filterAssetPeriode').off('change.asset').on('change.asset', function () {
        var val = $(this).val();
        assetTable.column(10).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#filterAssetSubLocation').off('change.asset').on('change.asset', function () {
        var val = $(this).val();
        assetTable.column(8).search(val ? '^' + val + '$' : '', true, false).draw();
    });
}

function formatCapCell(data) {
    if (data === null || data === undefined || data === '') {
        return '<span class="text-muted font-weight-bold">-</span>';
    }
    var num = parseFloat(data);
    if (isNaN(num)) return '<span class="text-muted font-weight-bold">-</span>';
    // If decimal percentage from Excel/DB (e.g. 1.0 = 100%, 0.85 = 85%), convert to percentage scale
    if (num > 0 && num <= 1.0) {
        num = num * 100;
    }
    var color = num >= 90 ? '#1cc88a' : (num >= 70 ? '#36b9cc' : (num >= 50 ? '#f6c23e' : '#e74a3b'));
    return '<span style="font-weight: 600; color: ' + color + ';">' + num.toFixed(1) + '%</span>';
}

function initRackTable() {
    if ($('#dataTableRack').length === 0) return;
    if (rackTable) {
        rackTable.ajax.reload(null, false);
        return;
    }

    rackTable = $('#dataTableRack').DataTable({
        processing: true,
        deferRender: true,
        scrollX: true,
        ajax: {
            url: 'api/get_rack_data.php',
            data: function (d) {
                var y = $('#filterRackYear').val();
                if (y) d.year = y;
            },
            dataSrc: function (json) {
                if (json.available_years && json.available_years.length > 0) {
                    var $ySel = $('#filterRackYear');
                    var curVal = $ySel.val();
                    if (!$ySel.data('populated')) {
                        $ySel.empty();
                        json.available_years.forEach(function (yr) {
                            var isSelected = curVal ? (yr == curVal) : (yr == json.selected_year);
                            $ySel.append('<option value="' + yr + '"' + (isSelected ? ' selected' : '') + '>' + yr + '</option>');
                        });
                        $ySel.data('populated', true);
                    }
                }
                return json.data || [];
            }
        },
        columns: [
            { data: 'barcode', defaultContent: '-' },
            { data: 'name', defaultContent: '-' },
            { data: 'label', defaultContent: '-' },
            { data: 'active', defaultContent: 'ACTIVE' },
            { data: 'category', defaultContent: '-' },
            { data: 'CAP JAN', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP FEB', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP MAR', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP APR', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP MEI', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP JUN', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP JUL', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP AGU', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP SEP', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP OKT', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP NOV', defaultContent: '-', className: 'text-center', render: formatCapCell },
            { data: 'CAP DES', defaultContent: '-', className: 'text-center', render: formatCapCell }
        ],
        language: {
            search: "Search:",
            searchPlaceholder: "Search..."
        },
        drawCallback: function () {
            var api = this.api();
            var categories = api.column(4).data().unique().sort();
            var $categorySelect = $('#filterRackCategory');
            var curCat = $categorySelect.val();
            $categorySelect.find('option:not(:first)').remove();
            categories.each(function (d) {
                if (d && d !== '-') $categorySelect.append('<option value="' + d + '">' + d + '</option>');
            });
            if (curCat) $categorySelect.val(curCat);

            var racks = api.column(1).data().unique().sort();
            var $rackSelect = $('#filterRackName');
            var curRack = $rackSelect.val();
            $rackSelect.find('option:not(:first)').remove();
            racks.each(function (d) {
                if (d && d !== '-') $rackSelect.append('<option value="' + d + '">' + d + '</option>');
            });
            if (curRack) $rackSelect.val(curRack);
        },
        initComplete: function () {
            var api = this.api();
            $('#filterRackCategory, #filterRackName').select2({ width: '100%' });

            var $searchBar = $('#dataTableRack_filter');
            $searchBar.detach().appendTo('#rackSearchContainer');
            $searchBar.css({ 'text-align': 'right', 'width': '100%' });
            $searchBar.find('label').css({ 'margin-bottom': '0', 'display': 'inline-flex', 'align-items': 'center' });
            var $input = $searchBar.find('input');
            $input.css('margin-left', '0.5em').attr('placeholder', 'Search...');
            $input.unbind();
            $input.on('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    api.search(this.value).draw();
                }
            });
        }
    });
    window.rackTable = rackTable;

    $('#filterRackYear').off('change.rack').on('change.rack', function () {
        if (rackTable) {
            rackTable.ajax.reload(null, false);
        }
    });

    $('#filterRackCategory').off('change.rack').on('change.rack', function () {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        rackTable.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#filterRackName').off('change.rack').on('change.rack', function () {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        rackTable.column(1).search(val ? '^' + val + '$' : '', true, false).draw();
    });
}

function loadActiveMasterTabTable() {
    var activeSeg = $('#masterSegmentTabs a.active').attr('href');
    if (activeSeg === '#seg-inbound') {
        initInboundTable();
        if (inboundTable) {
            setTimeout(function () { inboundTable.columns.adjust(); }, 150);
        }
    } else if (activeSeg === '#seg-storage') {
        var activeSubTab = $('#masterDataTabs a.active').attr('href');
        if (activeSubTab === '#asset-data' || !activeSubTab) {
            initAssetTable();
            if (assetTable) {
                setTimeout(function () { assetTable.columns.adjust(); }, 150);
            }
        } else if (activeSubTab === '#rack-data') {
            initRackTable();
            if (rackTable) {
                setTimeout(function () { rackTable.columns.adjust(); }, 150);
            }
        }
    } else if (activeSeg === '#seg-outbound') {
        var activeOutSub = $('#outboundSubTabs a.active').attr('href');
        if (activeOutSub === '#pane-outbound-forwarder') {
            initForwarderTable();
            if (forwarderTable) {
                setTimeout(function () { forwarderTable.columns.adjust(); }, 150);
            }
        } else {
            initOutboundTable();
            if (outboundTable) {
                setTimeout(function () { outboundTable.columns.adjust(); }, 150);
            }
        }
    } else if (activeSeg === '#seg-kpi') {
        initKpiMasterTable();
        if (kpiMasterTable) {
            setTimeout(function () { kpiMasterTable.columns.adjust(); }, 150);
        }
    }
}

// NOTE: Custom client-side search for Inbound PR/PO removed — now handled server-side

$(document).ready(function () {
    // Restore saved segment and subtab from localStorage
    try {
        var savedSeg = localStorage.getItem('activeMasterSegment');
        if (savedSeg && $('#masterSegmentTabs a[href="' + savedSeg + '"]:not(.disabled)').length) {
            $('#masterSegmentTabs a[href="' + savedSeg + '"]').tab('show');
        }

        var savedSub = localStorage.getItem('activeStorageSubTab');
        if (savedSub && $('#masterDataTabs a[href="' + savedSub + '"]').length) {
            $('#masterDataTabs a[href="' + savedSub + '"]').tab('show');
        }
    } catch (e) {
        console.warn('Could not restore active tabs from localStorage:', e);
    }

    // Load table for the currently active tab on page load
    setTimeout(loadActiveMasterTabTable, 100);

    // Bind tab switch events to lazy load as user navigates & save to localStorage
    $('#masterSegmentTabs a[data-toggle="pill"]').on('shown.bs.tab', function () {
        try {
            var href = $(this).attr('href');
            if (href) localStorage.setItem('activeMasterSegment', href);
        } catch (e) { }
        loadActiveMasterTabTable();
    });

    $('#masterDataTabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
        try {
            var href = $(this).attr('href');
            if (href) localStorage.setItem('activeStorageSubTab', href);
            if (href === '#rack-data') {
                $('#btn-group-asset-actions').hide();
                $('#btn-group-rack-actions').show();
                $('#storage-menu-title').html('<i class="fas fa-th mr-2"></i>Menu Master Data Storage (Data Utilisasi Rack)');
            } else {
                $('#btn-group-rack-actions').hide();
                $('#btn-group-asset-actions').show();
                $('#storage-menu-title').html('<i class="fas fa-boxes mr-2"></i>Menu Master Data Storage (Data Asset)');
            }
        } catch (e) { }
        loadActiveMasterTabTable();
    });

    $('#outboundSubTabs a').on('shown.bs.tab', function (e) {
        var targetPane = $(e.target).attr('href');
        if (targetPane === '#pane-outbound-forwarder') {
            $('#btn-group-pending-actions').hide();
            $('#btn-group-forwarder-actions').show();
            $('#outbound-active-tab-badge').html('<i class="fas fa-shipping-fast mr-1"></i> PR Forwarder Outbound');
            initForwarderTable();
            if (forwarderTable) {
                setTimeout(function () { forwarderTable.columns.adjust(); }, 100);
            }
        } else {
            $('#btn-group-forwarder-actions').hide();
            $('#btn-group-pending-actions').show();
            $('#outbound-active-tab-badge').html('<i class="fas fa-table mr-1"></i> Pending List Outbound');
            initOutboundTable();
            if (outboundTable) {
                setTimeout(function () { outboundTable.columns.adjust(); }, 100);
            }
        }
    });

    $('#filter-outbound-mr').on('keyup change input', function () {
        var val = $(this).val();
        if (typeof outboundTable !== 'undefined' && outboundTable) {
            outboundTable.column(0).search(val).draw();
        } else if ($.fn.DataTable && $('#dataTableOutbound').length) {
            $('#dataTableOutbound').DataTable().search(val).draw();
        }
    });

    $('#btn-reset-filter-outbound').on('click', function () {
        $('#filter-tujuan-site-outbound').val('');
        $('#filter-pic-mr-outbound').val('');
        $('#filter-status-outbound').val('');
        $('#filter-outbound-mr').val('');
        if (typeof outboundTable !== 'undefined' && outboundTable) {
            outboundTable.search('').columns().search('').draw();
        } else if ($.fn.DataTable && $('#dataTableOutbound').length) {
            $('#dataTableOutbound').DataTable().search('').columns().search('').draw();
        }
    });



    // ═══════════════════════════════════════════════════════════════
    // 4. EXPORT EXCEL HANDLER
    // ═══════════════════════════════════════════════════════════════
    $('#btn-export-excel').on('click', function () {
        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            }
            return;
        }

        var activeTab = $('#masterDataTabs a.active').attr('id');
        var wb = XLSX.utils.book_new();
        var dateStr = new Date().toISOString().slice(0, 10);

        if (activeTab === 'asset-tab') {
            var data = assetTable.rows({ search: 'applied' }).data().toArray();
            if (data.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Info', 'Tidak ada data Asset untuk di-export.', 'info');
                return;
            }
            var exportData = data.map(function (row) {
                return {
                    'Spec Code': row.spec_code || '',
                    'Spec Name': row.spec_name || '',
                    'Reg No': row.reg_no || '',
                    'Asset Planner Org': row.asset_planner_organization || '',
                    'NBV': parseFloat(row.nbv) || 0,
                    'SO Result': row.so_result || '',
                    'SO Location': row.so_location || '',
                    'Range': row.range || '',
                    'Sub Location': row.sub_location || '',
                    'Category': row.category || '',
                    'Periode': row.periode_group || '',
                    'Status': row.status || ''
                };
            });
            var ws = XLSX.utils.json_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, "Data Asset");
            XLSX.writeFile(wb, "Master_Data_Asset_" + dateStr + ".xlsx");

        } else if (activeTab === 'rack-tab') {
            var data = rackTable.rows({ search: 'applied' }).data().toArray();
            if (data.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Info', 'Tidak ada data Rack untuk di-export.', 'info');
                return;
            }
            var selYear = $('#filterRackYear').val() || new Date().getFullYear();
            var exportData = data.map(function (row) {
                return {
                    'BARCODE': row.barcode || '',
                    'NAME': row.name || '',
                    'LABEL': row.label || '',
                    'ACTIVE': row.active || 'ACTIVE',
                    'CATEGORY': row.category || '',
                    'CAP JAN': row['CAP JAN'] !== null && row['CAP JAN'] !== undefined ? row['CAP JAN'] : '',
                    'CAP FEB': row['CAP FEB'] !== null && row['CAP FEB'] !== undefined ? row['CAP FEB'] : '',
                    'CAP MAR': row['CAP MAR'] !== null && row['CAP MAR'] !== undefined ? row['CAP MAR'] : '',
                    'CAP APR': row['CAP APR'] !== null && row['CAP APR'] !== undefined ? row['CAP APR'] : '',
                    'CAP MEI': row['CAP MEI'] !== null && row['CAP MEI'] !== undefined ? row['CAP MEI'] : '',
                    'CAP JUN': row['CAP JUN'] !== null && row['CAP JUN'] !== undefined ? row['CAP JUN'] : '',
                    'CAP JUL': row['CAP JUL'] !== null && row['CAP JUL'] !== undefined ? row['CAP JUL'] : '',
                    'CAP AGU': row['CAP AGU'] !== null && row['CAP AGU'] !== undefined ? row['CAP AGU'] : '',
                    'CAP SEP': row['CAP SEP'] !== null && row['CAP SEP'] !== undefined ? row['CAP SEP'] : '',
                    'CAP OKT': row['CAP OKT'] !== null && row['CAP OKT'] !== undefined ? row['CAP OKT'] : '',
                    'CAP NOV': row['CAP NOV'] !== null && row['CAP NOV'] !== undefined ? row['CAP NOV'] : '',
                    'CAP DES': row['CAP DES'] !== null && row['CAP DES'] !== undefined ? row['CAP DES'] : ''
                };
            });
            var ws = XLSX.utils.json_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, "Data Utilisasi Rack " + selYear);
            XLSX.writeFile(wb, "Data_Utilisasi_Rack_" + selYear + "_" + dateStr + ".xlsx");
        }
    });

    $('#btn-template-asset').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [{
            'SPEC CODE': 'AST-001',
            'SPEC NAME': 'Server Rack Unit A1',
            'REG NO': 'REG-2026-001',
            'ASSET PLANNER ORGANIZATION': 'IT Infrastructure',
            'NBV': 15000000,
            'SO RESULT': 'FOUND',
            'SO LOCATION': 'DC Jakarta',
            'RANGE': 'RACK-01',
            'SUB LOCATION': 'DC Jakarta Tier 3',
            'CATEGORY': 'IT Equipment',
            'PERIODE': 'January 2026'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        XLSX.utils.book_append_sheet(wb, ws, "January 2026");
        XLSX.writeFile(wb, "Template_Import_Data_Asset.xlsx");
    });

    $('#btn-template-rack, #btn-template-rack-modal').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [
            {
                'BARCODE': 'SL221200182',
                'NAME': 'RACK-01',
                'LABEL': 'WHE-01/LANTAI-01/FASTMOVING/AISLE-01/RACK-01/ROW-01/SHELF-01',
                'ACTIVE': 'Checked',
                'CATEGORY': 'PROJECT',
                'CAP JAN': '100%',
                'CAP FEB': '100%',
                'CAP MAR': '100%',
                'CAP APR': '100%',
                'CAP MEI': '100%',
                'CAP JUN': '100%',
                'CAP JUL': '100%',
                'CAP AGU': '100%',
                'CAP SEP': '100%',
                'CAP OKT': '100%',
                'CAP NOV': '100%',
                'CAP DES': '100%'
            },
            {
                'BARCODE': 'SL221200183',
                'NAME': 'RACK-01',
                'LABEL': 'WHE-01/LANTAI-01/FASTMOVING/AISLE-01/RACK-01/ROW-01/SHELF-02',
                'ACTIVE': 'Checked',
                'CATEGORY': 'PROJECT',
                'CAP JAN': '100%',
                'CAP FEB': '100%',
                'CAP MAR': '100%',
                'CAP APR': '100%',
                'CAP MEI': '100%',
                'CAP JUN': '100%',
                'CAP JUL': '100%',
                'CAP AGU': '100%',
                'CAP SEP': '100%',
                'CAP OKT': '100%',
                'CAP NOV': '100%',
                'CAP DES': '100%'
            }
        ];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        ws['!cols'] = [
            { wch: 18 }, { wch: 16 }, { wch: 65 }, { wch: 14 }, { wch: 16 },
            { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
            { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
            { wch: 12 }, { wch: 12 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "Data Utilisasi Rack");
        XLSX.writeFile(wb, "Template_Import_Data_Rack.xlsx");
    });

    // ═════════════════════════════════════════════════════════════════════════
    // UNIVERSAL EXCEL UPLOAD & PROCESS CHECKING SYSTEM FOR ALL MASTER DATA
    // ═════════════════════════════════════════════════════════════════════════

    function setUploadStepState(stepElementOrId, state) {
        var $step = (typeof stepElementOrId === 'string') ? $('#' + stepElementOrId) : $(stepElementOrId);
        if (!$step.length) return;

        var $iconBox = $step.find('.step-icon-container');
        $step.removeClass('active completed');
        $iconBox.empty();

        var stepId = $step.attr('id') || '';
        var defIcon = 'fa-circle';
        if (stepId.indexOf('step-read') !== -1) defIcon = 'fa-file';
        else if (stepId.indexOf('step-parse') !== -1) defIcon = 'fa-table';
        else if (stepId.indexOf('step-upload') !== -1) defIcon = 'fa-database';
        else if (stepId.indexOf('step-finalize') !== -1) defIcon = 'fa-sync-alt';

        if (state === 'active') {
            $step.addClass('active');
            $iconBox.html('<div class="step-spinner"></div>');
        } else if (state === 'completed') {
            $step.addClass('completed');
            $iconBox.html('<i class="fas fa-check text-success"></i>');
        } else {
            $iconBox.html('<i class="fas ' + defIcon + '"></i>');
        }
    }

    function setupUniversalMasterImporter(config) {
        var prefix = config.prefix;
        var $modal = $(config.modalId);
        var $dialog = $(config.dialogId || (config.modalId + 'Dialog'));
        var $dropZone = $('#' + config.dropZoneId);
        var $fileInput = $('#' + config.fileInputId);
        var $colLeft = $('#' + prefix + '-col-left');
        var $colRight = $('#' + prefix + '-col-right');
        var $processContainer = $('#' + prefix + '-process-container');
        var $fileName = $('#' + prefix + '-file-name');
        var $fileSize = $('#' + prefix + '-file-size');
        var $btnChangeFile = $('#' + prefix + '-btn-change-file');
        var $sheetSelect = $('#' + prefix + '-sheet-select');
        var $sheetBadge = $('#' + prefix + '-sheet-badge');
        var $sheetInfoText = $('#' + prefix + '-sheet-info-text');
        var $btnSubmit = $('#' + prefix + '-btn-submit');

        var $batchProgress = $('#' + prefix + '-batch-progress');
        var $progressText = $('#' + prefix + '-progress-text');
        var $progressPercent = $('#' + prefix + '-progress-percent');
        var $progressFill = $('#' + prefix + '-progress-fill');

        var currentWorkbook = null;
        var isUploading = false;

        function resetImporter() {
            isUploading = false;
            currentWorkbook = null;
            $fileInput.val('');
            $dialog.removeClass('modal-expanded');
            $colLeft.removeClass('col-lg-6').addClass('col-12');
            $processContainer.hide();
            $dropZone.show();
            $colRight.hide();
            $sheetSelect.empty();
            $sheetBadge.text('0 Sheet');
            $sheetInfoText.html('Silakan pilih sheet di atas.');
            $btnSubmit.prop('disabled', false).html('<i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini');
            $btnChangeFile.prop('disabled', false);
            $sheetSelect.prop('disabled', false);
            if (config.periodSelectors) {
                Object.values(config.periodSelectors).forEach(function (sel) { $(sel).prop('disabled', false); });
            }
            $batchProgress.hide();
            if ($progressFill.length) $progressFill.css('width', '0%');
            if ($progressText.length) $progressText.text('0 / 0 baris');
            if ($progressPercent.length) $progressPercent.text('0%');

            setUploadStepState(prefix + '-step-read', 'idle');
            setUploadStepState(prefix + '-step-parse', 'idle');
            setUploadStepState(prefix + '-step-upload', 'idle');
            setUploadStepState(prefix + '-step-finalize', 'idle');
        }

        $modal.off('hidden.bs.modal.importer').on('hidden.bs.modal.importer', function () {
            resetImporter();
            setTimeout(function () {
                if ($('.modal.show').length === 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }, 150);
        });

        $btnChangeFile.off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (isUploading) return;
            resetImporter();
            $fileInput.trigger('click');
        });

        $fileInput.off('change.importer').on('change.importer', function (e) {
            var file = (e.target.files && e.target.files[0]) ? e.target.files[0] : null;
            if (!file) return;

            var ext = file.name.split('.').pop().toLowerCase();
            if (['xlsx', 'xls', 'csv'].indexOf(ext) === -1) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Format Tidak Didukung', 'Harap unggah file dengan format .xlsx, .xls, atau .csv', 'error');
                } else {
                    alert('Harap unggah file dengan format .xlsx, .xls, atau .csv');
                }
                $fileInput.val('');
                return;
            }

            if (file.size > 200 * 1024 * 1024) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 200MB.', 'error');
                } else {
                    alert('Ukuran file maksimum adalah 200MB.');
                }
                $fileInput.val('');
                return;
            }

            // Visual transition: Expand modal, switch to 2 columns
            $dropZone.hide();
            $processContainer.show();
            $fileName.text(file.name);
            $fileSize.text((file.size > 1024 * 1024) ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : (file.size / 1024).toFixed(1) + ' KB');
            $dialog.addClass('modal-expanded');
            $colLeft.removeClass('col-12').addClass('col-lg-6');
            $colRight.show();

            // Step 1: Upload & Read File
            setUploadStepState(prefix + '-step-read', 'active');
            setUploadStepState(prefix + '-step-parse', 'idle');
            setUploadStepState(prefix + '-step-upload', 'idle');
            setUploadStepState(prefix + '-step-finalize', 'idle');

            var reader = new FileReader();
            reader.onload = function (evt) {
                setUploadStepState(prefix + '-step-read', 'completed');
                setUploadStepState(prefix + '-step-parse', 'active');

                setTimeout(function () {
                    try {
                        var data = new Uint8Array(evt.target.result);
                        currentWorkbook = XLSX.read(data, {
                            type: 'array',
                            dense: true,
                            cellFormula: false,
                            cellHTML: false,
                            cellText: false
                        });

                        if (!currentWorkbook || !currentWorkbook.SheetNames || currentWorkbook.SheetNames.length === 0) {
                            throw new Error('File Excel tidak memiliki sheet.');
                        }

                        // Populate sheet select
                        $sheetSelect.empty();
                        currentWorkbook.SheetNames.forEach(function (sheetName) {
                            $sheetSelect.append($('<option></option>').attr('value', sheetName).text(sheetName));
                        });

                        $sheetBadge.text(currentWorkbook.SheetNames.length + ' Sheet');
                        setUploadStepState(prefix + '-step-parse', 'completed');

                        // Trigger sheet selection change to calculate row count immediately
                        $sheetSelect.trigger('change');
                    } catch (err) {
                        console.error('XLSX parse error:', err);
                        setUploadStepState(prefix + '-step-parse', 'idle');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', 'Gagal memproses file Excel: ' + err.message, 'error');
                        } else {
                            alert('Gagal memproses file Excel: ' + err.message);
                        }
                        resetImporter();
                    }
                }, 80);
            };
            reader.onerror = function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal membaca file.', 'error');
                } else {
                    alert('Gagal membaca file dari komputer.');
                }
                resetImporter();
            };
            reader.readAsArrayBuffer(file);
        });

        // When sheet selection dropdown changes
        $sheetSelect.off('change').on('change', function () {
            var chosenSheet = $(this).val();
            if (!currentWorkbook || !chosenSheet || !currentWorkbook.Sheets[chosenSheet]) {
                $sheetInfoText.html('Silakan pilih sheet di atas.');
                return;
            }

            var ws = currentWorkbook.Sheets[chosenSheet];
            var countInfo = 'siap diproses';
            if (ws && ws['!ref']) {
                try {
                    var range = XLSX.utils.decode_range(ws['!ref']);
                    var totalRows = Math.max(0, range.e.r - range.s.r);
                    countInfo = 'estimasi <b>' + totalRows.toLocaleString('id-ID') + ' baris data</b>';
                } catch (e) {
                    countInfo = 'siap diproses';
                }
            }

            $sheetInfoText.html('Sheet "<b>' + chosenSheet + '</b>" dipilih &bull; ' + countInfo + '.');
        });

        // Submit button handler
        $btnSubmit.off('click').on('click', function (e) {
            e.preventDefault();
            if (isUploading) return;

            var chosenSheet = $sheetSelect.val();
            if (!currentWorkbook || !chosenSheet || !currentWorkbook.Sheets[chosenSheet]) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Pilih Sheet', 'Silakan pilih sheet.', 'warning');
                } else {
                    alert('Silakan pilih sheet.');
                }
                return;
            }

            // Period Validation
            var monthVal = '', batchVal = '', yearVal = '';
            if (config.periodType === 'full') {
                monthVal = $(config.periodSelectors.month).val();
                batchVal = $(config.periodSelectors.batch).val();
                yearVal = $(config.periodSelectors.year).val();
                if (!monthVal || !batchVal || !yearVal) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Periode Belum Lengkap', 'Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu.', 'warning');
                    } else {
                        alert('Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu.');
                    }
                    return;
                }
            } else if (config.periodType === 'year') {
                yearVal = $(config.periodSelectors.year).val();
                if (!yearVal) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Tahun Belum Dipilih', 'Silakan pilih Tahun Periode terlebih dahulu.', 'warning');
                    } else {
                        alert('Silakan pilih Tahun Periode terlebih dahulu.');
                    }
                    return;
                }
            }

            // Lock UI & Start Upload
            isUploading = true;
            $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Membaca Data Sheet...');
            $btnChangeFile.prop('disabled', true);
            $sheetSelect.prop('disabled', true);
            if (config.periodSelectors) {
                Object.values(config.periodSelectors).forEach(function (sel) { $(sel).prop('disabled', true); });
            }

            setUploadStepState(prefix + '-step-upload', 'active');
            $batchProgress.show();

            // Yield execution to allow UI to paint the loading state
            setTimeout(function () {
                var parsedRows = [];
                try {
                    parsedRows = config.parseRows(currentWorkbook, chosenSheet);
                } catch (parseErr) {
                    console.error('Row parsing error:', parseErr);
                    isUploading = false;
                    $btnSubmit.prop('disabled', false).html('<i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini');
                    $btnChangeFile.prop('disabled', false);
                    $sheetSelect.prop('disabled', false);
                    if (config.periodSelectors) {
                        Object.values(config.periodSelectors).forEach(function (sel) { $(sel).prop('disabled', false); });
                    }
                    setUploadStepState(prefix + '-step-upload', 'idle');
                    $batchProgress.hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Gagal memetakan baris data sheet: ' + parseErr.message, 'error');
                    } else {
                        alert('Gagal memetakan baris data sheet: ' + parseErr.message);
                    }
                    return;
                }

                if (!parsedRows || parsedRows.length === 0) {
                    isUploading = false;
                    $btnSubmit.prop('disabled', false).html('<i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini');
                    $btnChangeFile.prop('disabled', false);
                    $sheetSelect.prop('disabled', false);
                    if (config.periodSelectors) {
                        Object.values(config.periodSelectors).forEach(function (sel) { $(sel).prop('disabled', false); });
                    }
                    setUploadStepState(prefix + '-step-upload', 'idle');
                    $batchProgress.hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Peringatan', 'Sheet "' + chosenSheet + '" tidak memiliki baris data yang valid.', 'warning');
                    } else {
                        alert('Sheet "' + chosenSheet + '" tidak memiliki baris data yang valid.');
                    }
                    return;
                }

                $btnSubmit.html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengimpor Data (' + parsedRows.length.toLocaleString('id-ID') + ' baris)...');

                function updateProgress(sent, total) {
                    var percent = total > 0 ? Math.min(100, Math.round((sent / total) * 100)) : 0;
                    $progressFill.css('width', percent + '%');
                    $progressText.text(sent.toLocaleString('id-ID') + ' / ' + total.toLocaleString('id-ID') + ' baris');
                    $progressPercent.text(percent + '%');
                }

                updateProgress(0, parsedRows.length);

                config.processUpload({
                    rows: parsedRows,
                    month: monthVal,
                    batch: batchVal,
                    year: yearVal,
                    chosenSheet: chosenSheet,
                    updateProgress: updateProgress,
                    setStepState: function (stepName, state) {
                        setUploadStepState(prefix + '-' + stepName, state);
                    }
                })
                    .then(function (result) {
                        updateProgress(parsedRows.length, parsedRows.length);
                        setUploadStepState(prefix + '-step-upload', 'completed');
                        setUploadStepState(prefix + '-step-finalize', 'active');

                        setTimeout(function () {
                            setUploadStepState(prefix + '-step-finalize', 'completed');

                            var successMsg = (result && result.message)
                                ? result.message
                                : ('Berhasil mengimpor ' + parsedRows.length.toLocaleString('id-ID') + ' baris data dari sheet "' + chosenSheet + '".');

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Import Berhasil!',
                                    text: successMsg,
                                    confirmButtonColor: '#4e73df'
                                });
                            }

                            $modal.modal('hide');
                            resetImporter();

                            if (typeof config.onSuccess === 'function') {
                                config.onSuccess(result, { month: monthVal, batch: batchVal, year: yearVal });
                            }
                        }, 300);
                    })
                    .catch(function (err) {
                        console.error('Import error (' + prefix + '):', err);
                        isUploading = false;
                        $btnSubmit.prop('disabled', false).html('<i class="fas fa-file-import mr-1"></i> Mulai Import Sheet Ini');
                        $btnChangeFile.prop('disabled', false);
                        $sheetSelect.prop('disabled', false);
                        if (config.periodSelectors) {
                            Object.values(config.periodSelectors).forEach(function (sel) { $(sel).prop('disabled', false); });
                        }
                        setUploadStepState(prefix + '-step-upload', 'idle');
                        $batchProgress.hide();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Import Gagal',
                                text: err.message || 'Terjadi kesalahan saat mengimpor data.',
                                confirmButtonColor: '#e74a3b'
                            });
                        } else {
                            alert('Import Gagal: ' + (err.message || 'Terjadi kesalahan saat mengimpor data.'));
                        }
                    });
            }, 50);
        });
    }

    $('#btn-template-inbound').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [
            {
                'PR Nomor': 'PR-70900/3020/1000/2025',
                'PR Kode Site': '50002003304',
                'PR Nama Site': 'INBOUND WAREHOUSE T TEKNO',
                'PR Item Kategori': 'Service',
                'PR PIC Teknis Nama': 'SOFIAN ARISSA PUTRO',
                'PR Nama Bagian': 'PROJECT MANAGEMENT',
                'PR Nama Divisi': '',
                'PR Regional': 'Wilayah Pusat (PUSAT)',
                'PR Jenis MA': 'OPEX',
                'PO Nomor': '21780/I/PO-LA/2025',
                'PO Deskripsi': 'Pertamina EP - Containment Data Center Zona 1 Jambi - Penarikan FO DC Containment IT Room',
                'PO Vendor': 'TRIGUNA AKSES TEKNOLOGI',
                'PO Tgl. Generate': '03/12/2025',
                'PO Nama Item': 'Retensi 5% selama 1 bulan',
                'PO Qty Item': 1,
                'PO UoM Item': 'Lots',
                'PO Target Delivery': '02/03/2026',
                'Project ID': 'PID-02160-04-2024'
            }
        ];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        ws['!cols'] = [
            { wch: 25 }, { wch: 15 }, { wch: 28 }, { wch: 12 },
            { wch: 22 }, { wch: 22 }, { wch: 15 }, { wch: 22 },
            { wch: 10 }, { wch: 22 }, { wch: 45 }, { wch: 28 },
            { wch: 14 }, { wch: 25 }, { wch: 12 }, { wch: 10 },
            { wch: 14 }, { wch: 20 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "Master Inbound");
        XLSX.writeFile(wb, "Template_Import_Master_Data_Inbound.xlsx");
    });

    // Reusable Drag & Drop zone binder for Excel upload modals
    function setupExcelDropZone(zoneId, inputId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        if (!zone || !input) return;
        if (zone._hasDropListener) return;
        zone._hasDropListener = true;

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                try {
                    input.files = e.dataTransfer.files;
                } catch (err) {
                    var dt = new DataTransfer();
                    for (var i = 0; i < e.dataTransfer.files.length; i++) {
                        dt.items.add(e.dataTransfer.files[i]);
                    }
                    input.files = dt.files;
                }
                $(input).trigger('change');
            }
        });

        zone.addEventListener('click', function (e) {
            if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                input.click();
            }
        });
    }

    function initAllDropZones() {
        setupExcelDropZone('upload-drop-zone', 'excel-file-input');
        setupExcelDropZone('upload-rack-drop-zone', 'excel-rack-file-input');
        setupExcelDropZone('inbound-upload-drop-zone', 'excel-file-inbound-input');
        setupExcelDropZone('outbound-upload-drop-zone', 'excel-file-outbound-input');
        setupExcelDropZone('forwarder-upload-drop-zone', 'excel-file-forwarder-input');
        setupExcelDropZone('kpi-upload-drop-zone', 'excel-file-kpi-input');
    }

    // Initialize drag & drop immediately and on modal open
    initAllDropZones();
    $(document).ready(initAllDropZones);
    $('#uploadExcelModal, #uploadExcelModalRack, #uploadExcelModalInbound, #uploadExcelModalOutbound, #uploadExcelModalForwarder, #uploadExcelModalKpi').on('show.bs.modal shown.bs.modal', function () {
        initAllDropZones();
    });

    /**
     * Reusable dialog to let user choose a sheet from a parsed workbook
     */
    function promptSelectSheet(workbook, title, defaultSheet, onSelect, onCancel) {
        if (!workbook || !workbook.SheetNames || workbook.SheetNames.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'File Excel tidak memiliki sheet data.', 'error');
            if (onCancel) onCancel();
            return;
        }

        var sheetOptions = {};
        workbook.SheetNames.forEach(function (name) {
            sheetOptions[name] = name;
        });

        var promptTitle = workbook.SheetNames.length > 1
            ? (title || 'Pilih Sheet Excel') + ' (' + workbook.SheetNames.length + ' Sheet)'
            : (title || 'Pilih Sheet Excel');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: promptTitle,
                html: '<p class="text-muted small mb-2">Pilih sheet data yang ingin Anda import:</p>',
                input: 'select',
                inputOptions: sheetOptions,
                inputValue: defaultSheet || workbook.SheetNames[0],
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Pilih & Lanjutkan',
                cancelButtonText: '<i class="fas fa-times mr-1"></i> Batal',
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#858796',
                allowOutsideClick: false,
                inputValidator: function (value) {
                    if (!value) {
                        return 'Silakan pilih sheet terlebih dahulu!';
                    }
                }
            }).then(function (result) {
                if (result.isConfirmed && result.value) {
                    onSelect(result.value);
                } else {
                    if (onCancel) onCancel();
                }
            });
        } else {
            var chosen = prompt('Pilih nama sheet:\n' + workbook.SheetNames.join('\n'), workbook.SheetNames[0]);
            if (chosen && workbook.Sheets[chosen]) {
                onSelect(chosen);
            } else {
                if (onCancel) onCancel();
            }
        }
    }

    // Handle Storage Data Asset Excel File Upload with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'asset',
        modalId: '#uploadExcelModal',
        dialogId: '#uploadExcelModalDialog',
        dropZoneId: 'upload-drop-zone',
        fileInputId: 'excel-file-input',
        periodType: 'full',
        periodSelectors: {
            month: '#upload-bulan-select',
            batch: '#upload-batch-select',
            year: '#upload-tahun-select'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];
            var rows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });
            for (var idx = 0; idx < rows.length; idx++) {
                rows[idx]['periode'] = sheetName;
            }
            return rows;
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var monthVal = ctx.month;
            var batchVal = ctx.batch;
            var yearVal = ctx.year;
            var periodGroup = monthVal + ' ' + yearVal + '-Batch' + batchVal;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var totalRows = rows.length;
            var BATCH_SIZE = 1500;

            return safeFetchJson('api/save_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    action: 'init',
                    csrf_token: csrfToken,
                    month: monthVal,
                    year: yearVal,
                    batch: batchVal,
                    periods: [periodGroup]
                })
            }, 60000)
                .then(function (initRes) {
                    if (initRes.status !== 'success') {
                        throw new Error(initRes.message || 'Gagal inisialisasi periode.');
                    }
                    return sendAssetBatches(0);
                })
                .then(function () {
                    return safeFetchJson('api/save_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ action: 'finalize', csrf_token: csrfToken })
                    }, 60000);
                })
                .then(function (finalRes) {
                    return {
                        success: true,
                        message: 'Berhasil mengimport ' + totalRows.toLocaleString('id-ID') + ' data asset (' + periodGroup + ').'
                    };
                });

            function sendAssetBatches(startIndex) {
                if (startIndex >= totalRows) {
                    return Promise.resolve();
                }
                var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
                var batchRows = rows.slice(startIndex, endIndex);

                return safeFetchJson('api/save_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        action: 'append',
                        csrf_token: csrfToken,
                        month: monthVal,
                        year: yearVal,
                        batch: batchVal,
                        data: batchRows
                    })
                }, 120000)
                    .then(function (appendRes) {
                        if (appendRes.status !== 'success') {
                            throw new Error(appendRes.message || 'Gagal menyimpan batch asset.');
                        }
                        ctx.updateProgress(endIndex, totalRows);
                        return sendAssetBatches(endIndex);
                    });
            }
        },
        onSuccess: function (res, period) {
            if (masterDataTable) {
                masterDataTable.ajax.reload();
            }
            if (typeof populateFilterPeriods === 'function') {
                populateFilterPeriods();
            }
        }
    });

    // Handle Storage Rack Utilisasi Excel File Upload with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'rack',
        modalId: '#uploadExcelModalRack',
        dialogId: '#uploadExcelModalRackDialog',
        dropZoneId: 'upload-rack-drop-zone',
        fileInputId: 'excel-rack-file-input',
        periodType: 'year',
        periodSelectors: {
            year: '#upload-rack-year'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];
            var sheetAOA = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
            if (!sheetAOA || sheetAOA.length === 0) return [];

            var headerKeywords = ['barcode', 'name', 'shelf', 'label', 'sub location', 'sub_location', 'active', 'category', 'status', 'kode', 'rack', 'project'];
            var headerRowIndex = -1;
            for (var r = 0; r < Math.min(sheetAOA.length, 5); r++) {
                var rowArr = sheetAOA[r];
                if (!rowArr || rowArr.length === 0) continue;
                var matches = 0;
                for (var c = 0; c < rowArr.length; c++) {
                    var cellVal = String(rowArr[c] || '').trim().toLowerCase();
                    if (headerKeywords.indexOf(cellVal) !== -1) {
                        matches++;
                    }
                }
                if (matches >= 2) {
                    headerRowIndex = r;
                    break;
                }
            }

            var mappedRows = [];
            if (headerRowIndex !== -1) {
                var headers = [];
                for (var hIdx = 0; hIdx < sheetAOA[headerRowIndex].length; hIdx++) {
                    headers[hIdx] = String(sheetAOA[headerRowIndex][hIdx] || '').trim() || ('col_' + hIdx);
                }
                for (var rIdx = headerRowIndex + 1; rIdx < sheetAOA.length; rIdx++) {
                    var curRow = sheetAOA[rIdx];
                    if (!curRow || curRow.length === 0) continue;
                    var hasVal = curRow.some(function (v) { return String(v || '').trim() !== ''; });
                    if (!hasVal) continue;
                    var rowObj = {};
                    for (var cIdx = 0; cIdx < headers.length; cIdx++) {
                        var hName = headers[cIdx];
                        var rawCell = curRow[cIdx];
                        if (rawCell !== undefined && rawCell !== null) {
                            if (typeof rawCell === 'number' && rawCell > 0 && rawCell <= 1.0 && /^CAP/i.test(hName)) {
                                rawCell = Math.round(rawCell * 10000) / 100;
                            }
                            rowObj[hName] = String(rawCell).trim();
                        } else {
                            rowObj[hName] = '';
                        }
                    }
                    mappedRows.push(rowObj);
                }
            } else {
                var defaultHeaders = ['BARCODE', 'NAME', 'LABEL', 'ACTIVE', 'CATEGORY'];
                for (var rIdx = 0; rIdx < sheetAOA.length; rIdx++) {
                    var curRow = sheetAOA[rIdx];
                    if (!curRow || curRow.length === 0) continue;
                    var hasVal = curRow.some(function (v) { return String(v || '').trim() !== ''; });
                    if (!hasVal) continue;
                    var rowObj = {};
                    for (var cIdx = 0; cIdx < curRow.length; cIdx++) {
                        var h = (cIdx < defaultHeaders.length) ? defaultHeaders[cIdx] : ('col_' + cIdx);
                        var rawCell = curRow[cIdx];
                        if (rawCell !== undefined && rawCell !== null) {
                            if (typeof rawCell === 'number' && rawCell > 0 && rawCell <= 1.0 && /^CAP/i.test(h)) {
                                rawCell = Math.round(rawCell * 10000) / 100;
                            }
                            rowObj[h] = String(rawCell).trim();
                        } else {
                            rowObj[h] = '';
                        }
                    }
                    mappedRows.push(rowObj);
                }
            }
            return mappedRows;
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var yearVal = ctx.year;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var totalRows = rows.length;
            var BATCH_SIZE = 1000;

            return safeFetchJson('api/save_rack_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    action: 'init',
                    csrf_token: csrfToken,
                    year: yearVal
                })
            }, 60000)
                .then(function (initRes) {
                    if (initRes.status !== 'success') {
                        throw new Error(initRes.message || 'Gagal inisialisasi master data rack.');
                    }
                    return sendRackBatches(0);
                })
                .then(function () {
                    return safeFetchJson('api/save_rack_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            action: 'finalize',
                            csrf_token: csrfToken,
                            year: yearVal
                        })
                    }, 60000);
                })
                .then(function (finalRes) {
                    return {
                        success: true,
                        message: 'Berhasil mengimport ' + totalRows.toLocaleString('id-ID') + ' data utilisasi rack (Tahun ' + yearVal + ').'
                    };
                });

            function sendRackBatches(startIndex) {
                if (startIndex >= totalRows) {
                    return Promise.resolve();
                }
                var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
                var batchRows = rows.slice(startIndex, endIndex);

                return safeFetchJson('api/save_rack_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        action: 'append',
                        csrf_token: csrfToken,
                        year: yearVal,
                        data: batchRows
                    })
                }, 120000)
                    .then(function (appendRes) {
                        if (appendRes.status !== 'success') {
                            throw new Error(appendRes.message || 'Gagal menyimpan batch rack.');
                        }
                        ctx.updateProgress(endIndex, totalRows);
                        return sendRackBatches(endIndex);
                    });
            }
        },
        onSuccess: function (res, period) {
            if (rackTable) {
                rackTable.ajax.reload(null, false);
            } else {
                initRackTable();
            }
        }
    });

    // Handle Inbound Excel File Upload with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'inbound',
        modalId: '#uploadExcelModalInbound',
        dialogId: '#uploadExcelModalInboundDialog',
        dropZoneId: 'inbound-upload-drop-zone',
        fileInputId: 'excel-file-inbound-input',
        periodType: 'full',
        periodSelectors: {
            month: '#uploadInboundMonthSelect',
            batch: '#uploadInboundBatchSelect',
            year: '#uploadInboundYearSelect'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];
            return XLSX.utils.sheet_to_json(worksheet, { defval: '' });
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var monthVal = ctx.month;
            var batchVal = ctx.batch;
            var yearVal = ctx.year;
            var periodGroup = monthVal + ' ' + yearVal + '-Batch' + batchVal;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var totalRows = rows.length;
            var BATCH_SIZE = 1500;

            return safeFetchJson('api/save_inbound_master.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    action: 'init',
                    csrf_token: csrfToken,
                    month: monthVal,
                    year: yearVal,
                    batch: batchVal,
                    periode_group: periodGroup
                })
            }, 60000)
                .then(function (initRes) {
                    if (initRes.status !== 'success') {
                        throw new Error(initRes.message || 'Gagal inisialisasi periode inbound.');
                    }
                    return sendInboundBatches(0);
                })
                .then(function () {
                    return safeFetchJson('api/save_inbound_master.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ action: 'finalize', csrf_token: csrfToken })
                    }, 60000);
                })
                .then(function () {
                    return {
                        success: true,
                        message: 'Berhasil mengimport ' + totalRows.toLocaleString('id-ID') + ' data inbound (' + periodGroup + ').'
                    };
                });

            function sendInboundBatches(startIndex) {
                if (startIndex >= totalRows) {
                    return Promise.resolve();
                }
                var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
                var batchRows = rows.slice(startIndex, endIndex);

                return safeFetchJson('api/save_inbound_master.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        action: 'append',
                        csrf_token: csrfToken,
                        month: monthVal,
                        year: yearVal,
                        batch: batchVal,
                        periode_group: periodGroup,
                        data: batchRows
                    })
                }, 120000)
                    .then(function (appendRes) {
                        if (appendRes.status !== 'success') {
                            throw new Error(appendRes.message || 'Gagal menyimpan batch inbound.');
                        }
                        ctx.updateProgress(endIndex, totalRows);
                        return sendInboundBatches(endIndex);
                    });
            }
        },
        onSuccess: function (res, period) {
            if (inboundTable) {
                inboundTable.ajax.reload();
                refreshInboundFilters();
            } else {
                location.reload();
            }
        }
    });

    // Outbound Excel Template Generator
    $('#btn-template-outbound').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [{
            'MR NO': 'MR-2026-0001',
            'MR TYPE': 'PROJECT',
            'MR DESC': 'Material Router & Switch Deployment',
            'MR STATUS': 'RELEASED',
            'PCK NO': 'PCK-2026-001',
            'PCK DETAIL': 'Box 1 of 2 (Router C9200)',
            'PCK STATUS': 'PACKED',
            'AWB': 'AWB-JNE-998822',
            'DN NO': 'DN-2026-0801',
            'PR NO': 'PR-100293',
            'PO NO': 'PO-450001234',
            'FROM': 'WH-CENTRAL-JKT',
            'SITE ORIGIN': 'JKT-HO-01',
            'SITE ORIGIN ADDR': 'Jl. Medan Merdeka Barat No. 1, Jakarta Pusat',
            'TO': 'SITE-SUB-01',
            'SITE DESTINATION': 'SUB-RNG-02',
            'SITE DESTINATION ADDR': 'Jl. Pemuda No. 45, Surabaya',
            'PICKUP TYPE': 'COURIER PICKUP',
            'VIA': 'JNE TRUCKING',
            'LT': '3 DAYS',
            'DELIVERY TARGET': '2026-08-25',
            'DN STATUS': 'IN TRANSIT',
            'LAST LOG': 'Departed from Jakarta Sorting Hub'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        ws['!cols'] = [
            { wch: 18 }, { wch: 14 }, { wch: 35 }, { wch: 14 },
            { wch: 18 }, { wch: 30 }, { wch: 14 }, { wch: 20 },
            { wch: 18 }, { wch: 16 }, { wch: 18 }, { wch: 18 },
            { wch: 18 }, { wch: 35 }, { wch: 18 }, { wch: 18 },
            { wch: 35 }, { wch: 18 }, { wch: 18 }, { wch: 12 },
            { wch: 18 }, { wch: 16 }, { wch: 35 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "Master Outbound");
        XLSX.writeFile(wb, "Template_Import_Master_Data_Outbound.xlsx");
    });

    // Handle Outbound Excel File Upload with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'outbound',
        modalId: '#uploadExcelModalOutbound',
        dialogId: '#uploadExcelModalOutboundDialog',
        dropZoneId: 'outbound-upload-drop-zone',
        fileInputId: 'excel-file-outbound-input',
        periodType: 'full',
        periodSelectors: {
            month: '#uploadOutboundMonthSelect',
            batch: '#uploadOutboundBatchSelect',
            year: '#uploadOutboundYearSelect'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];
            var rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '' });
            if (!rawJson || rawJson.length === 0) return [];

            var FIELD_MAP = {
                mr_no: ['mr no', 'mr_no', 'mrno', 'no mr', 'nomr', 'no. mr', 'mr number'],
                mr_type: ['mr type', 'mr_type', 'mrtype', 'tipe mr', 'type mr'],
                mr_desc: ['mr desc', 'mr_desc', 'mrdesc', 'mr description', 'deskripsi mr', 'keterangan mr'],
                mr_status: ['mr status', 'mr_status', 'mrstatus', 'status mr'],
                pck_no: ['pck no', 'pck_no', 'pckno', 'packing no', 'no pck', 'no. pck', 'package no'],
                pck_detail: ['pck detail', 'pck_detail', 'pckdetail', 'packing detail', 'detail pck'],
                pck_status: ['pck status', 'pck_status', 'pckstatus', 'packing status', 'status pck'],
                awb: ['awb', 'no awb', 'airwaybill', 'resi', 'no resi', 'no. awb'],
                dn_no: ['dn no', 'dn_no', 'dnno', 'delivery note no', 'no dn', 'no. dn', 'dn number'],
                pr_no: ['pr no', 'pr_no', 'prno', 'no pr', 'no. pr', 'purchase request no', 'pr number'],
                po_no: ['po no', 'po_no', 'pono', 'no po', 'no. po', 'purchase order no', 'po number'],
                origin_from: ['from', 'origin from', 'origin_from', 'dari', 'asal'],
                site_origin: ['site origin', 'site_origin', 'siteorigin', 'origin site', 'site asal'],
                site_origin_addr: ['site origin addr', 'site_origin_addr', 'site origin address', 'alamat origin', 'alamat site asal'],
                destination_to: ['to', 'destination to', 'destination_to', 'tujuan', 'ke'],
                site_destination: ['site destination', 'site_destination', 'sitedestination', 'destination site', 'tujuan site', 'site tujuan'],
                site_destination_addr: ['site destination addr', 'site_destination_addr', 'site destination address', 'alamat destination', 'alamat site tujuan', 'alamat tujuan'],
                pickup_type: ['pickup type', 'pickup_type', 'pickuptype', 'tipe pickup', 'jenis pickup'],
                via: ['via', 'pengiriman via', 'ekspedisi', 'kurir', 'transport'],
                lt: ['lt', 'lead time', 'leadtime', 'lead_time'],
                delivery_target: ['delivery target', 'delivery_target', 'target delivery', 'target_delivery', 'tgl target delivery'],
                dn_status: ['dn status', 'dn_status', 'dnstatus', 'status dn'],
                last_log: ['last log', 'last_log', 'lastlog', 'log terakhir', 'status log']
            };

            function normalizeKey(str) {
                return String(str || '').toLowerCase().replace(/[^a-z0-9]/g, ' ').trim().replace(/\s+/g, ' ');
            }

            return rawJson.map(function (rawRow) {
                var out = { _raw: rawRow };
                var rawKeys = Object.keys(rawRow);

                Object.keys(FIELD_MAP).forEach(function (canonical) {
                    var aliases = FIELD_MAP[canonical];
                    var foundKey = rawKeys.find(function (k) {
                        var norm = normalizeKey(k);
                        return aliases.includes(norm);
                    });
                    out[canonical] = foundKey ? String(rawRow[foundKey]).trim() : '';
                });

                return out;
            });
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var outMonthVal = ctx.month;
            var outBatchVal = ctx.batch;
            var outYearVal = ctx.year;
            var periodGroup = outMonthVal + ' ' + outYearVal + '-Batch' + outBatchVal;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var totalRows = rows.length;
            var BATCH_SIZE = 1500;

            return safeFetchJson('api/save_outbound_master.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    action: 'init',
                    csrf_token: csrfToken,
                    month: outMonthVal,
                    year: outYearVal,
                    batch: outBatchVal,
                    periode_group: periodGroup
                })
            }, 60000)
                .then(function (initRes) {
                    if (initRes.status !== 'success') {
                        throw new Error(initRes.message || 'Gagal inisialisasi periode outbound.');
                    }
                    return sendOutboundBatches(0);
                })
                .then(function () {
                    return safeFetchJson('api/save_outbound_master.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ action: 'finalize', csrf_token: csrfToken })
                    }, 60000);
                })
                .then(function () {
                    return {
                        success: true,
                        message: 'Berhasil mengimport ' + totalRows.toLocaleString('id-ID') + ' data outbound (' + periodGroup + ').'
                    };
                });

            function sendOutboundBatches(startIndex) {
                if (startIndex >= totalRows) {
                    return Promise.resolve();
                }
                var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
                var batchRows = rows.slice(startIndex, endIndex);

                return safeFetchJson('api/save_outbound_master.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        action: 'append',
                        csrf_token: csrfToken,
                        month: outMonthVal,
                        year: outYearVal,
                        batch: outBatchVal,
                        periode_group: periodGroup,
                        data: batchRows
                    })
                }, 120000)
                    .then(function (appendRes) {
                        if (appendRes.status !== 'success') {
                            throw new Error(appendRes.message || 'Gagal menyimpan batch outbound.');
                        }
                        ctx.updateProgress(endIndex, totalRows);
                        return sendOutboundBatches(endIndex);
                    });
            }
        },
        onSuccess: function (res, period) {
            if (outboundTable) {
                outboundTable.ajax.reload();
                refreshOutboundFilters();
            } else {
                location.reload();
            }
        }
    });

    // ═══════════════════════════════════════════════════
    // PR FORWARDER - Template Generator & Excel Upload
    // ═══════════════════════════════════════════════════

    // PR Forwarder Excel Template Generator
    $('#btn-template-forwarder').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var wsData = [
            // Row 1 (Header Level 1)
            [
                'NO', 'PRINT', 'DN STATUS',
                'ASAL', '', '', '',
                'TUJUAN', '', '', '',
                'PROCUREMENT', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                'DOC', 'NOTE',
                'DELIVERY', '', '', '', '', '', '',
                'APPROVAL', '', ''
            ],
            // Row 2 (Header Level 2)
            [
                '', '', '',
                'PENGIRIM', 'SITE', '', '',
                'PENERIMA', 'SITE', '', '',
                'VENDOR', '', 'KOLI', 'MATA ANGGARAN', 'SR', '', 'PR', '', 'VALUATION PRICE', 'SUGGESTION', 'PURPOSE', 'PO', '', '', '', '', '',
                '', '',
                'TYPE', 'VIA', 'NAMA', 'AWB', 'PICKUP', 'LEAD TIME', 'TARGET DLV',
                'STATUS', 'APPROVER', 'DATE'
            ],
            // Row 3 (Header Level 3)
            [
                '', '', '',
                '', 'CODE', 'SITE', 'ALAMAT',
                '', 'CODE', 'SITE', 'ALAMAT',
                'MODE', 'NAME', '', '', 'NO', 'TGL', 'NO', 'TGL', '', '', '', 'NO', 'TGL', 'PRICE', 'VENDOR', 'TARGET DLV', 'BUYER',
                '', '',
                '', '', '', '', '', '', '',
                '', '', ''
            ],
            // Row 4 (Sample Row)
            [
                'DN-2026-0001', 'PRINTED', 'DELIVERED',
                'PT LINTASARTA JKT', 'JKT01', 'JAKARTA HO', 'JL. TB SIMATUPANG NO. 10',
                'PT BANK MANDIRI SUB', 'SUB02', 'SURABAYA DC', 'JL. BASUKI RAHMAT NO. 12',
                'REGULAR', 'JNE EXPRESS', '5', 'ANGGARAN-IT-2026', 'SR-99881', '2026-02-01', 'PR-88123', '2026-02-03', '15000000', 'APPROVED ROUTE', 'PROJECT EXPANSION', 'PO-77112', '2026-02-05', '14500000', 'PT GLOBAL LOGISTICS', '2026-02-15', 'JOHN DOE',
                'DOC-COMPLETE', 'FRAGILE ELECTRONICS',
                'AIR CARGO', 'UDARA', 'GARUDA INDONESIA', 'AWB-88392019', '2026-02-06', '3 DAYS', '2026-02-09',
                'APPROVED', 'MANAGER LOGISTIK', '2026-02-06'
            ]
        ];
        var ws = XLSX.utils.aoa_to_sheet(wsData);
        ws['!cols'] = [
            { wch: 15 }, { wch: 12 }, { wch: 15 },
            { wch: 20 }, { wch: 12 }, { wch: 18 }, { wch: 30 },
            { wch: 20 }, { wch: 12 }, { wch: 18 }, { wch: 30 },
            { wch: 12 }, { wch: 18 }, { wch: 10 }, { wch: 20 },
            { wch: 15 }, { wch: 14 }, { wch: 15 }, { wch: 14 },
            { wch: 16 }, { wch: 18 }, { wch: 20 },
            { wch: 15 }, { wch: 14 }, { wch: 16 }, { wch: 22 }, { wch: 14 }, { wch: 18 },
            { wch: 15 }, { wch: 20 },
            { wch: 14 }, { wch: 14 }, { wch: 20 }, { wch: 18 }, { wch: 14 }, { wch: 12 }, { wch: 14 },
            { wch: 14 }, { wch: 20 }, { wch: 14 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "Master PR Forwarder");
        XLSX.writeFile(wb, "Template_Import_Master_Data_PR_Forwarder.xlsx");
    });

    // Handle PR Forwarder Excel File Upload with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'forwarder',
        modalId: '#uploadExcelModalForwarder',
        dialogId: '#uploadExcelModalForwarderDialog',
        dropZoneId: 'forwarder-upload-drop-zone',
        fileInputId: 'excel-file-forwarder-input',
        periodType: 'full',
        periodSelectors: {
            month: '#uploadForwarderMonthSelect',
            batch: '#uploadForwarderBatchSelect',
            year: '#uploadForwarderYearSelect'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];

            var sheetAOA = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
            if (!sheetAOA || sheetAOA.length === 0) return [];

            var FORWARDER_COLUMNS = [
                'no_dn', 'print_status', 'dn_status',
                'asal_pengirim', 'asal_code', 'asal_site', 'asal_alamat',
                'tujuan_penerima', 'tujuan_code', 'tujuan_site', 'tujuan_alamat',
                'proc_vendor_mode', 'proc_vendor_name', 'koli', 'mata_anggaran',
                'sr_no', 'sr_tgl', 'pr_no', 'pr_tgl',
                'valuation_price', 'suggestion', 'purpose',
                'po_no', 'po_tgl', 'po_price', 'po_vendor', 'po_target_dlv', 'po_buyer',
                'doc', 'note',
                'delivery_type', 'delivery_via', 'delivery_nama', 'delivery_awb',
                'delivery_pickup', 'delivery_lead_time', 'delivery_target_dlv',
                'approval_status', 'approval_approver', 'approval_date'
            ];

            var headerKeywords = [
                'no', 'no dn', 'no. dn', 'dn no', 'print', 'dn status',
                'asal', 'pengirim', 'site', 'code', 'alamat',
                'tujuan', 'penerima', 'procurement', 'vendor', 'mode', 'name', 'koli',
                'mata anggaran', 'sr', 'pr', 'valuation price', 'suggestion', 'purpose',
                'po', 'price', 'target dlv', 'buyer', 'doc', 'note', 'delivery',
                'type', 'via', 'nama', 'awb', 'pickup', 'lead time', 'approval', 'status', 'approver', 'date'
            ];

            function isHeaderRow(rowArr) {
                if (!rowArr || rowArr.length === 0) return true;
                var matchCount = 0;
                var totalCells = 0;
                for (var c = 0; c < Math.min(rowArr.length, 30); c++) {
                    var cellVal = String(rowArr[c] || '').trim().toLowerCase();
                    if (cellVal !== '') {
                        totalCells++;
                        if (headerKeywords.includes(cellVal)) {
                            matchCount++;
                        }
                    }
                }
                if (totalCells === 0) return true;
                return (matchCount / totalCells) >= 0.35;
            }

            var dataStartIndex = 0;
            while (dataStartIndex < sheetAOA.length && dataStartIndex < 6 && isHeaderRow(sheetAOA[dataStartIndex])) {
                dataStartIndex++;
            }

            var mappedRows = [];
            for (var rIdx = dataStartIndex; rIdx < sheetAOA.length; rIdx++) {
                var rowArr = sheetAOA[rIdx];
                if (!rowArr || rowArr.length === 0) continue;

                var hasContent = rowArr.some(function (cell) {
                    return String(cell || '').trim() !== '';
                });
                if (!hasContent) continue;

                if (isHeaderRow(rowArr)) continue;

                var rowObj = {};
                for (var colIdx = 0; colIdx < FORWARDER_COLUMNS.length; colIdx++) {
                    var colKey = FORWARDER_COLUMNS[colIdx];
                    var cellVal = (rowArr[colIdx] !== undefined && rowArr[colIdx] !== null) ? String(rowArr[colIdx]).trim() : '';
                    rowObj[colKey] = cellVal;
                }
                mappedRows.push(rowObj);
            }

            return mappedRows;
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var fwdMonthVal = ctx.month;
            var fwdBatchVal = ctx.batch;
            var fwdYearVal = ctx.year;
            var periodGroup = fwdMonthVal + ' ' + fwdYearVal + '-Batch' + fwdBatchVal;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var totalRows = rows.length;
            var BATCH_SIZE = 1500;

            return safeFetchJson('api/save_outbound_forwarder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    action: 'init',
                    csrf_token: csrfToken,
                    month: fwdMonthVal,
                    year: fwdYearVal,
                    batch: fwdBatchVal,
                    periode_group: periodGroup
                })
            }, 60000)
                .then(function (initRes) {
                    if (initRes.status !== 'success') {
                        throw new Error(initRes.message || 'Gagal inisialisasi periode PR Forwarder.');
                    }
                    return sendForwarderBatches(0);
                })
                .then(function () {
                    return safeFetchJson('api/save_outbound_forwarder.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ action: 'finalize', csrf_token: csrfToken })
                    }, 60000);
                })
                .then(function () {
                    return {
                        success: true,
                        message: 'Berhasil mengimpor ' + totalRows.toLocaleString('id-ID') + ' data PR Forwarder (' + periodGroup + ').'
                    };
                });

            function sendForwarderBatches(startIndex) {
                if (startIndex >= totalRows) {
                    return Promise.resolve();
                }
                var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
                var batchRows = rows.slice(startIndex, endIndex);

                return safeFetchJson('api/save_outbound_forwarder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        action: 'append',
                        csrf_token: csrfToken,
                        month: fwdMonthVal,
                        year: fwdYearVal,
                        batch: fwdBatchVal,
                        periode_group: periodGroup,
                        data: batchRows
                    })
                }, 120000)
                    .then(function (appendRes) {
                        if (appendRes.status !== 'success') {
                            throw new Error(appendRes.message || 'Gagal menyimpan batch PR Forwarder.');
                        }
                        ctx.updateProgress(endIndex, totalRows);
                        return sendForwarderBatches(endIndex);
                    });
            }
        },
        onSuccess: function (res, period) {
            if (forwarderTable) {
                forwarderTable.ajax.reload();
                refreshForwarderFilters();
            } else {
                location.reload();
            }
        }
    });

    // ═══════════════════════════════════════════════════
    // KPI MASTER DATA - DataTable, Template, Upload, Delete
    // ═══════════════════════════════════════════════════

    // KPI Master Data Table Initialization
    window.initKpiMasterTable = initKpiMasterTable;
    function initKpiMasterTable() {
        if (kpiMasterTable || $('#dataTableKpi').length === 0) return;

        kpiMasterTable = $('#dataTableKpi').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            scrollX: true,
            ajax: {
                url: 'api/get_kpi_master.php',
                type: 'GET',
                data: function (d) {
                    d.year = $('#filter-kpi-year').val();
                }
            },
            columns: [
                { data: 'bulan', defaultContent: '-' },
                { data: 'gr_target', defaultContent: '-', className: 'text-center' },
                { data: 'gr_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'registrasi_target', defaultContent: '-', className: 'text-center' },
                { data: 'registrasi_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'slow_moving_target', defaultContent: '-', className: 'text-center' },
                { data: 'slow_moving_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'utilisasi_space_target', defaultContent: '-', className: 'text-center' },
                { data: 'utilisasi_space_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'stok_opname_target', defaultContent: '-', className: 'text-center' },
                { data: 'stok_opname_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'delivery_effectiveness_target', defaultContent: '-', className: 'text-center' },
                { data: 'delivery_effectiveness_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'mr_closing_target', defaultContent: '-', className: 'text-center' },
                { data: 'mr_closing_achievement', defaultContent: '-', className: 'text-center' },
                { data: 'efisiensi_delivery_target', defaultContent: '-', className: 'text-center' },
                { data: 'efisiensi_delivery_achievement', defaultContent: '-', className: 'text-center' }
            ],
            order: [],
            ordering: false,
            pageLength: 12,
            lengthMenu: [[12, 25], [12, 25]],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                search: "Search:",
                searchPlaceholder: "Search...",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data yang cocok",
                emptyTable: "Belum ada data Master KPI.",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            initComplete: function () {
                var api = this.api();
                var $input = $('#dataTableKpi_filter input');
                if ($input.length) {
                    $input.attr('placeholder', 'Search...');
                    $input.unbind();
                    $input.on('keydown', function (e) {
                        if (e.key === 'Enter' || e.keyCode === 13) {
                            e.preventDefault();
                            api.search(this.value).draw();
                        }
                    });
                }
            }
        });
    }

    // KPI Year Filter Change
    $('#filter-kpi-year').on('change', function () {
        if (kpiMasterTable) {
            kpiMasterTable.ajax.reload();
        }
    });

    // KPI Reset Filter
    $('#btn-reset-filter-kpi').on('click', function () {
        var curYear = new Date().getFullYear();
        var minYear = 2026;
        var resetYear = Math.max(minYear, curYear);
        $('#filter-kpi-year').val(resetYear);
        if (kpiMasterTable) {
            kpiMasterTable.ajax.reload();
        }
    });

    // KPI Excel Template Generator
    $('#btn-template-kpi').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var months = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        var sampleData = [];
        months.forEach(function (m) {
            sampleData.push({
                'Bulan': m,
                'GR Target': 98,
                'GR Achievement': '',
                'Registrasi Target': 98,
                'Registrasi Achievement': '',
                'Slow Moving Target': 85,
                'Slow Moving Achievement': '',
                'Utilisasi Space Target': 90,
                'Utilisasi Space Achievement': '',
                'Stok Opname Hub & Outlet Target': 85,
                'Stok Opname Hub & Outlet Achievement': '',
                'Delivery Effectiveness Target': 97,
                'Delivery Effectiveness Achievement': '',
                'MR Closing Target': 90,
                'MR Closing Achievement': '',
                'Efisiensi Delivery Target': 10,
                'Efisiensi Delivery Achievement': ''
            });
        });
        var ws = XLSX.utils.json_to_sheet(sampleData);
        ws['!cols'] = [
            { wch: 14 }, { wch: 12 }, { wch: 16 },
            { wch: 16 }, { wch: 20 },
            { wch: 18 }, { wch: 22 },
            { wch: 20 }, { wch: 24 },
            { wch: 28 }, { wch: 32 },
            { wch: 26 }, { wch: 30 },
            { wch: 18 }, { wch: 22 },
            { wch: 22 }, { wch: 26 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "KPI Master Data");
        XLSX.writeFile(wb, "Template_Import_KPI_Master_Data.xlsx");
    });

    // KPI Excel File Upload Handler with Universal Importer
    setupUniversalMasterImporter({
        prefix: 'kpi',
        modalId: '#uploadExcelModalKpi',
        dialogId: '#uploadExcelModalKpiDialog',
        dropZoneId: 'kpi-upload-drop-zone',
        fileInputId: 'excel-file-kpi-input',
        periodType: 'year',
        periodSelectors: {
            year: '#uploadKpiYearSelect'
        },
        parseRows: function (workbook, sheetName) {
            var worksheet = workbook.Sheets[sheetName];
            if (!worksheet) return [];
            return XLSX.utils.sheet_to_json(worksheet, { defval: '' });
        },
        processUpload: function (ctx) {
            var rows = ctx.rows;
            var yearVal = ctx.year;
            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';

            ctx.updateProgress(Math.floor(rows.length / 2), rows.length);

            return safeFetchJson('api/save_kpi_master.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    action: 'batch',
                    csrf_token: csrfToken,
                    year: yearVal,
                    data: rows
                })
            }, 120000)
                .then(function (res) {
                    if (res.status !== 'success') {
                        throw new Error(res.message || 'Gagal import data KPI.');
                    }
                    ctx.updateProgress(rows.length, rows.length);
                    return {
                        success: true,
                        message: res.message || ('Berhasil mengimpor ' + rows.length.toLocaleString('id-ID') + ' data KPI.')
                    };
                });
        },
        onSuccess: function (res, period) {
            if (kpiMasterTable) {
                kpiMasterTable.ajax.reload();
            } else {
                location.reload();
            }
        }
    });

    // KPI Delete Handler
    $('#btn-confirm-delete-kpi').on('click', function () {
        var delYear = $('#deleteKpiYearSelect').val();
        if (!delYear) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Tahun Periode untuk menghapus data KPI.', 'warning');
            } else {
                alert('Silakan pilih Tahun Periode untuk menghapus data KPI.');
            }
            return;
        }

        var executeKpiDelete = function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Data Is Processing Please Wait',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () { Swal.showLoading(); }
                });
            }
            fetch('api/delete_kpi_master.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_by_year',
                    year: delYear
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status === 'success') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Berhasil!', res.message || 'Data KPI berhasil dihapus.', 'success');
                        } else {
                            alert(res.message || 'Data KPI berhasil dihapus.');
                        }
                        $('#deleteDataModalKpi').modal('hide');
                        if (kpiMasterTable) {
                            kpiMasterTable.ajax.reload();
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
                .catch(function (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                    }
                });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah Anda YAKIN?',
                text: 'Ingin menghapus data KPI untuk tahun ' + delYear + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Ya, Hapus!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    executeKpiDelete();
                }
            });
        } else {
            if (confirm('Apakah Anda YAKIN ingin menghapus data KPI untuk tahun ' + delYear + '?')) {
                executeKpiDelete();
            }
        }
    });

    // ── Delete Rack Data Logic ──
    $('#deleteRackScopeSelect').on('change', function () {
        var scope = $(this).val();
        if (scope === 'all') {
            $('#deleteRackYearContainer').slideUp(200);
        } else {
            $('#deleteRackYearContainer').slideDown(200);
        }
    });

    $('#btn-confirm-delete-rack').on('click', function () {
        var scope = $('#deleteRackScopeSelect').val();
        var delYear = $('#deleteRackYearSelect').val();

        if (scope === 'year' && !delYear) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Tahun untuk menghapus data utilisasi rack.', 'warning');
            } else {
                alert('Silakan pilih Tahun untuk menghapus data utilisasi rack.');
            }
            return;
        }

        var confirmMsg = (scope === 'all')
            ? 'PERINGATAN: Semua data master layout dan utilisasi rack akan DIHAPUS PERMANEN dari sistem!'
            : 'Ingin menghapus data utilisasi rack untuk tahun ' + delYear + '?';

        var executeRackDelete = function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Data Is Processing Please Wait',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () { Swal.showLoading(); }
                });
            }

            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';
            var payload = {
                action: (scope === 'all') ? 'delete_all' : 'delete_year',
                year: delYear,
                csrf_token: csrfToken
            };

            fetch('api/delete_rack_utilisasi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status === 'success') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Berhasil!', res.message || 'Data rack berhasil dihapus.', 'success');
                        } else {
                            alert(res.message || 'Data rack berhasil dihapus.');
                        }
                        $('#deleteDataModalRack').modal('hide');
                        if (rackTable) {
                            rackTable.ajax.reload();
                        } else {
                            initRackTable();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', 'Gagal menghapus data: ' + (res.message || 'Terjadi kesalahan.'), 'error');
                        } else {
                            alert('Gagal menghapus data: ' + (res.message || 'Terjadi kesalahan.'));
                        }
                    }
                })
                .catch(function (err) {
                    console.error('Delete rack error:', err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Terjadi kesalahan saat menghubungi server.', 'error');
                    }
                });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Konfirmasi Hapus Data Rack',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Ya, Hapus!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    executeRackDelete();
                }
            });
        } else {
            if (confirm(confirmMsg)) {
                executeRackDelete();
            }
        }
    });

    // Global backdrop & modal cleanup safeguard
    // Prevents stuck gray backdrop overlays when modals are dismissed or closed after errors/uploads
    $(document).on('hidden.bs.modal', '.modal', function () {
        setTimeout(function () {
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            }
        }, 150);
    });

});

