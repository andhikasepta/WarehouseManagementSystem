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
    }

    // Close any lingering alerts on table events
    $(document).on('xhr.dt error.dt draw.dt init.dt', closeDtLoading);

    // Master Data menu does not use the navbar period selector
    $('#nav-item-period-selector, #periodDropdown').closest('.nav-item').hide();
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

    $('#uploadExcelModal, #uploadExcelModalInbound, #uploadExcelModalOutbound, #deleteDataModal, #deleteDataModalInbound, #deleteDataModalOutbound').on('show.bs.modal', function () {
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

function initRackTable() {
    if ($('#dataTableRack').length === 0) return;
    if (rackTable) {
        rackTable.ajax.reload(null, false);
        return;
    }

    rackTable = $('#dataTableRack').DataTable({
        processing: true,
        deferRender: true,
        ajax: {
            url: 'api/get_rack_data.php',
            dataSrc: function (json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'barcode', defaultContent: '-' },
            { data: 'name', defaultContent: '-' },
            { data: 'label', defaultContent: '-' },
            { data: 'active', defaultContent: 'ACTIVE' },
            { data: 'category', defaultContent: '-' }
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
    // Initialize dedicated Rack upload handler
    initRackUpload();

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
        } catch (e) {}
        loadActiveMasterTabTable();
    });

    $('#masterDataTabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
        try {
            var href = $(this).attr('href');
            if (href) localStorage.setItem('activeStorageSubTab', href);
        } catch (e) {}
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
    // 3. UTILISASI AREA/RACK — Inline Editing
    // ═══════════════════════════════════════════════════════════════

    // Populate year dropdown (starting from 2026)
    var yearSel = document.getElementById('utilisasi-year-select');
    if (yearSel) {
        var currentYear = new Date().getFullYear();
        var maxYear = Math.max(2030, currentYear + 5);
        for (var y = 2026; y <= maxYear; y++) {
            var opt = document.createElement('option');
            opt.value = String(y);
            opt.textContent = String(y);
            yearSel.appendChild(opt);
        }
    }

    // Enable/disable "Tampilkan Data" button
    function updateLoadUtilisasiButton() {
        var m = document.getElementById('utilisasi-month-select');
        var yr = document.getElementById('utilisasi-year-select');
        var btn = document.getElementById('btn-load-utilisasi');
        if (btn) {
            btn.disabled = !(m && m.value && yr && yr.value);
        }
    }

    $('#utilisasi-month-select, #utilisasi-year-select').on('change', updateLoadUtilisasiButton);

    // Load data for selected period
    $('#btn-load-utilisasi').on('click', function () {
        var month = document.getElementById('utilisasi-month-select').value;
        var year = document.getElementById('utilisasi-year-select').value;
        if (!month || !year) return;
        loadUtilisasiData(month, year);
    });

    function loadUtilisasiData(month, year) {
        var tbody = document.getElementById('utilisasi-table-body');
        var infoDiv = document.getElementById('utilisasi-table-info');
        var tableWrapper = document.getElementById('utilisasi-table-wrapper');
        var btnSave = document.getElementById('btn-save-utilisasi-all');

        if (!tbody) return;

        // Show loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Data Is Processing Please Wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () { Swal.showLoading(); }
            });
        }

        fetch('api/get_rack_utilisasi.php?month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year))
            .then(function (r) { return r.json(); })
            .then(function (result) {
                // Clear table body
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }

                if (result.status === 'success' && result.data && result.data.length > 0) {
                    // Hide info, show table
                    if (infoDiv) infoDiv.style.display = 'none';
                    if (tableWrapper) tableWrapper.style.display = 'block';
                    if (btnSave) btnSave.disabled = false;

                    for (var i = 0; i < result.data.length; i++) {
                        var row = result.data[i];
                        var tr = document.createElement('tr');

                        // Label
                        var tdLabel = document.createElement('td');
                        tdLabel.textContent = row.label || '';
                        tdLabel.style.fontSize = '0.85rem';
                        tdLabel.setAttribute('data-label', row.label || '');
                        tr.appendChild(tdLabel);

                        // Rack Group
                        var tdRack = document.createElement('td');
                        tdRack.textContent = row.rack_group || '';
                        tdRack.style.fontSize = '0.85rem';
                        tr.appendChild(tdRack);

                        // Category
                        var tdCat = document.createElement('td');
                        tdCat.textContent = row.category || '';
                        tdCat.style.fontSize = '0.85rem';
                        tr.appendChild(tdCat);

                        // Qty (editable for admins, view-only for head_warehouse_admin)
                        var tdQty = document.createElement('td');
                        var inputQty = document.createElement('input');
                        inputQty.type = 'number';
                        inputQty.className = 'form-control form-control-sm utilisasi-qty-input';
                        inputQty.min = '0';
                        inputQty.value = parseInt(row.qty) || 0;
                        inputQty.style.textAlign = 'center';
                        inputQty.setAttribute('data-label', row.label || '');
                        var canEditStorage = (window.currentUserRole === 'superadmin' || window.userCanAddStorage === true) && window.currentUserRole !== 'head_warehouse_admin';
                        if (!canEditStorage) {
                            inputQty.disabled = true;
                            inputQty.style.backgroundColor = '#eaecf4';
                        }
                        tdQty.appendChild(inputQty);
                        tr.appendChild(tdQty);

                        // Capacity (editable for admins with Add/Edit permission)
                        var tdCap = document.createElement('td');
                        var inputCap = document.createElement('input');
                        inputCap.type = 'number';
                        inputCap.className = 'form-control form-control-sm utilisasi-cap-input';
                        inputCap.min = '0';
                        inputCap.max = '100';
                        inputCap.step = '0.01';
                        inputCap.value = parseFloat(row.capacity) || 0;
                        inputCap.style.textAlign = 'center';
                        inputCap.setAttribute('data-label', row.label || '');
                        if (!canEditStorage) {
                            inputCap.disabled = true;
                            inputCap.style.backgroundColor = '#eaecf4';
                        }
                        tdCap.appendChild(inputCap);
                        tr.appendChild(tdCap);

                        tbody.appendChild(tr);
                    }
                } else {
                    // No rack_master data at all
                    if (infoDiv) {
                        infoDiv.style.display = 'block';
                        infoDiv.textContent = 'Tidak ada data rack master. Upload Data Utilisasi Rack terlebih dahulu.';
                    }
                    if (tableWrapper) tableWrapper.style.display = 'none';
                    if (btnSave) btnSave.disabled = true;
                }

                if (typeof Swal !== 'undefined') Swal.close();
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal memuat data utilisasi.', 'error');
                }
            });
    }

    // Save All button
    $('#btn-save-utilisasi-all').on('click', function () {
        var month = document.getElementById('utilisasi-month-select').value;
        var year = document.getElementById('utilisasi-year-select').value;

        if (!month || !year) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Pilih Bulan dan Tahun terlebih dahulu.', 'warning');
            }
            return;
        }

        // Collect all rows from the table
        var rows = [];
        var tbody = document.getElementById('utilisasi-table-body');
        if (!tbody) return;

        var trs = tbody.getElementsByTagName('tr');
        for (var i = 0; i < trs.length; i++) {
            var labelCell = trs[i].querySelector('td[data-label]');
            var qtyInput = trs[i].querySelector('.utilisasi-qty-input');
            var capInput = trs[i].querySelector('.utilisasi-cap-input');

            if (labelCell && qtyInput && capInput) {
                var qtyVal = parseInt(qtyInput.value) || 0;
                var capVal = parseFloat(capInput.value) || 0;

                // Client-side clamp
                if (qtyVal < 0) qtyVal = 0;
                if (capVal < 0) capVal = 0;
                if (capVal > 100) capVal = 100;

                rows.push({
                    label: labelCell.getAttribute('data-label'),
                    qty: qtyVal,
                    capacity: capVal
                });
            }
        }

        if (rows.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Tidak ada data untuk disimpan.', 'warning');
            }
            return;
        }

        // Show loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () { Swal.showLoading(); }
            });
        }

        fetch('api/save_rack_utilisasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                month: month,
                year: year,
                rows: rows
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Berhasil!', res.message || 'Data utilisasi berhasil disimpan.', 'success');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Gagal menyimpan: ' + (res.message || 'Unknown error'), 'error');
                    }
                }
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data.', 'error');
                }
            });
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
            var exportData = data.map(function (row) {
                return {
                    'BARCODE': row.barcode || '',
                    'NAME': row.name || '',
                    'LABEL': row.label || '',
                    'ACTIVE': row.active || 'ACTIVE',
                    'CATEGORY': row.category || ''
                };
            });
            var ws = XLSX.utils.json_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, "Data Utilisasi Rack");
            XLSX.writeFile(wb, "Data_Utilisasi_Rack_" + dateStr + ".xlsx");

        } else if (activeTab === 'utilisasi-tab') {
            var tbody = document.getElementById('utilisasi-table-body');
            if (!tbody || tbody.children.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Info', 'Tampilkan data Utilisasi Area/Rack terlebih dahulu sebelum export.', 'info');
                return;
            }
            var exportData = [];
            var trs = tbody.getElementsByTagName('tr');
            for (var i = 0; i < trs.length; i++) {
                var tds = trs[i].getElementsByTagName('td');
                if (tds.length >= 5) {
                    var label = tds[0].textContent.trim();
                    var rackGroup = tds[1].textContent.trim();
                    var category = tds[2].textContent.trim();
                    var qtyInput = trs[i].querySelector('.utilisasi-qty-input');
                    var capInput = trs[i].querySelector('.utilisasi-cap-input');
                    exportData.push({
                        'Label Area/Rack': label,
                        'Rack Group': rackGroup,
                        'Category': category,
                        'Qty': qtyInput ? parseInt(qtyInput.value) || 0 : 0,
                        'Capacity (%)': capInput ? parseFloat(capInput.value) || 0 : 0
                    });
                }
            }
            var ws = XLSX.utils.json_to_sheet(exportData);
            var m = document.getElementById('utilisasi-month-select');
            var y = document.getElementById('utilisasi-year-select');
            var periodName = (m && m.value && y && y.value) ? (m.value + "_" + y.value) : dateStr;
            XLSX.utils.book_append_sheet(wb, ws, "Utilisasi Area");
            XLSX.writeFile(wb, "Utilisasi_Area_Rack_" + periodName + ".xlsx");
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
        var sampleData = [{
            'BARCODE': 'SL221200182',
            'NAME': 'SHELF-01',
            'LABEL': 'WHE-01/LANTAI-01/FASTMOVING/AISLE-01/RACK-01/ROW-01/SHELF-01',
            'ACTIVE': 'Checked',
            'CATEGORY': 'PROJECT'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        ws['!cols'] = [
            { wch: 18 }, { wch: 16 }, { wch: 65 }, { wch: 14 }, { wch: 16 }
        ];
        XLSX.utils.book_append_sheet(wb, ws, "Data Utilisasi Rack");
        XLSX.writeFile(wb, "Template_Import_Data_Rack.xlsx");
    });

    // Dedicated Rack Excel File Upload Handler
    function initRackUpload() {
        var $dropZone = $('#upload-rack-drop-zone');
        var $fileInput = $('#excel-rack-file-input');
        var $browseBtn = $('#btn-browse-rack-file');
        var $progressContainer = $('#upload-rack-progress');
        var $fileName = $('#upload-rack-file-name');
        var $fileSize = $('#upload-rack-file-size');
        var $progressBar = $('#upload-rack-progress-fill');

        if ($dropZone.length === 0) return;

        $browseBtn.off('click').on('click', function (e) {
            e.stopPropagation();
            $fileInput.trigger('click');
        });

        $dropZone.off('click').on('click', function (e) {
            if (e.target !== $browseBtn[0] && !$.contains($browseBtn[0], e.target)) {
                $fileInput.trigger('click');
            }
        });

        $dropZone.off('dragover dragenter').on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropZone.css('background', '#e8f8f2');
        });

        $dropZone.off('dragleave drop').on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropZone.css('background', '#f8fbf9');
        });

        $dropZone.off('drop').on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dropZone.css('background', '#f8fbf9');
            var files = e.originalEvent.dataTransfer.files;
            if (files && files.length > 0) {
                handleRackFile(files[0]);
            }
        });

        $fileInput.off('change').on('change', function () {
            if (this.files && this.files.length > 0) {
                handleRackFile(this.files[0]);
            }
        });

        function handleRackFile(file) {
            var ext = file.name.split('.').pop().toLowerCase();
            if (['xlsx', 'xls', 'csv'].indexOf(ext) === -1) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv', 'error');
                }
                return;
            }

            $progressContainer.show();
            $fileName.text(file.name);
            $fileSize.text((file.size / 1024).toFixed(1) + ' KB');
            $progressBar.css('width', '35%');

            var reader = new FileReader();
            reader.onload = function (e) {
                $progressBar.css('width', '70%');
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { type: 'array', cellDates: true });
                    
                    if (workbook.SheetNames.length > 1 && typeof Swal !== 'undefined') {
                        var sheetOpts = {};
                        workbook.SheetNames.forEach(function (s) { sheetOpts[s] = s; });
                        Swal.fire({
                            title: 'Pilih Sheet Excel (' + workbook.SheetNames.length + ' Sheet)',
                            text: 'Pilih sheet yang memuat Data Utilisasi Rack:',
                            input: 'select',
                            inputOptions: sheetOpts,
                            inputValue: workbook.SheetNames[0],
                            showCancelButton: true,
                            confirmButtonText: 'Proses Sheet Ini',
                            cancelButtonText: 'Batal'
                        }).then(function (res) {
                            if (res.isConfirmed && res.value) {
                                processRackSheet(workbook.Sheets[res.value]);
                            } else {
                                $progressContainer.hide();
                                $fileInput.val('');
                            }
                        });
                    } else {
                        processRackSheet(workbook.Sheets[workbook.SheetNames[0]]);
                    }
                } catch (err) {
                    console.error(err);
                    $progressContainer.hide();
                    $fileInput.val('');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'Gagal membaca file Excel: ' + err.message, 'error');
                    }
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function processRackSheet(sheet) {
            if (!sheet) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Sheet tidak valid atau kosong.', 'error');
                $progressContainer.hide();
                $fileInput.val('');
                return;
            }

            var sheetAOA = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
            if (!sheetAOA || sheetAOA.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Sheet yang dipilih kosong.', 'warning');
                $progressContainer.hide();
                $fileInput.val('');
                return;
            }

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
                        rowObj[headers[cIdx]] = (curRow[cIdx] !== undefined && curRow[cIdx] !== null) ? String(curRow[cIdx]).trim() : '';
                    }
                    mappedRows.push(rowObj);
                }
            } else {
                // No header row -> row 0 is first data row!
                var defaultHeaders = ['BARCODE', 'NAME', 'LABEL', 'ACTIVE', 'CATEGORY'];
                for (var rIdx = 0; rIdx < sheetAOA.length; rIdx++) {
                    var curRow = sheetAOA[rIdx];
                    if (!curRow || curRow.length === 0) continue;
                    var hasVal = curRow.some(function (v) { return String(v || '').trim() !== ''; });
                    if (!hasVal) continue;
                    var rowObj = {};
                    for (var cIdx = 0; cIdx < curRow.length; cIdx++) {
                        var h = (cIdx < defaultHeaders.length) ? defaultHeaders[cIdx] : ('col_' + cIdx);
                        rowObj[h] = (curRow[cIdx] !== undefined && curRow[cIdx] !== null) ? String(curRow[cIdx]).trim() : '';
                    }
                    mappedRows.push(rowObj);
                }
            }

            if (mappedRows.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Tidak ditemukan baris data pada sheet.', 'warning');
                $progressContainer.hide();
                $fileInput.val('');
                return;
            }

            $progressBar.css('width', '90%');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Data Is Processing Please Wait',
                    html: 'Menyimpan <b>' + mappedRows.length.toLocaleString('id-ID') + '</b> data rak ke database...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () { Swal.showLoading(); }
                });
            }

            var csrfToken = (window.WMS_CSRF_TOKEN) || ($('meta[name="csrf-token"]').attr('content')) || '';

            fetch('api/save_rack_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    action: 'batch',
                    csrf_token: csrfToken,
                    data: mappedRows
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                $progressBar.css('width', '100%');
                $progressContainer.hide();
                $fileInput.val('');
                $('#uploadExcelModalRack').modal('hide');

                if (res.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Upload Berhasil!',
                            text: res.message || ('Berhasil menyimpan ' + mappedRows.length + ' data rak.'),
                            confirmButtonColor: '#1cc88a'
                        });
                    }
                    // Reload rack table immediately
                    if (rackTable) {
                        rackTable.ajax.reload(null, false);
                    } else {
                        initRackTable();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal Menyimpan', res.message || 'Terjadi kesalahan pada server.', 'error');
                    }
                }
            })
            .catch(function (err) {
                console.error(err);
                $progressContainer.hide();
                $fileInput.val('');
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Terjadi kesalahan koneksi saat mengirim data.', 'error');
                }
            });
        }
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

    // Initialize drag & drop for all master data modals
    setupExcelDropZone('inbound-upload-drop-zone', 'excel-file-inbound-input');
    setupExcelDropZone('outbound-upload-drop-zone', 'excel-file-outbound-input');
    setupExcelDropZone('forwarder-upload-drop-zone', 'excel-file-forwarder-input');
    setupExcelDropZone('kpi-upload-drop-zone', 'excel-file-kpi-input');

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

    // Handle Inbound Excel File Upload
    $('#excel-file-inbound-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        if (file.size > 200 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 200MB.', 'error');
            $(this).val('');
            return;
        }

        var monthVal = $('#uploadInboundMonthSelect').val();
        var batchVal = $('#uploadInboundBatchSelect').val();
        var yearVal = $('#uploadInboundYearSelect').val();

        if (!monthVal || !batchVal || !yearVal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.', 'warning');
            } else {
                alert('Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.');
            }
            $(this).val('');
            return;
        }

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                promptSelectSheet(workbook, 'Pilih Sheet Inbound', workbook.SheetNames[0], function (chosenSheet) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Is Processing Please Wait',
                            html: 'Memproses sheet <b>' + chosenSheet + '</b> untuk periode <b>' + monthVal + ' ' + yearVal + '-Batch' + batchVal + '</b>...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    }

                    var worksheet = workbook.Sheets[chosenSheet];
                    if (!worksheet) {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Sheet "' + chosenSheet + '" tidak ditemukan.', 'error');
                        return;
                    }
                    var jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

                    if (jsonRows.length === 0) {
                        if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Sheet "' + chosenSheet + '" kosong.', 'warning');
                        return;
                    }

                    // Send to backend API with month and year parameters
                    fetch('api/save_inbound_master.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'batch',
                            month: monthVal,
                            year: yearVal,
                            batch: batchVal,
                            data: jsonRows
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Sukses', res.message || 'Import Data Inbound Berhasil!', 'success');
                            }
                            $('#uploadExcelModalInbound').modal('hide');
                            if (inboundTable) {
                                inboundTable.ajax.reload();
                                refreshInboundFilters();
                            } else {
                                location.reload();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message || 'Gagal import data.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan server saat menyimpan data.', 'error');
                    });
                }, function () {
                    $('#excel-file-inbound-input').val('');
                });
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Format file Excel tidak valid.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
        // Reset input value
        $(this).val('');
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

    // Outbound Excel File Upload Handler with intelligent 23-column mapping
    $('#excel-file-outbound-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        if (file.size > 200 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 200MB.', 'error');
            $(this).val('');
            return;
        }

        var outMonthVal = $('#uploadOutboundMonthSelect').val();
        var outBatchVal = $('#uploadOutboundBatchSelect').val();
        var outYearVal = $('#uploadOutboundYearSelect').val();

        if (!outMonthVal || !outBatchVal || !outYearVal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.', 'warning');
            } else {
                alert('Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.');
            }
            $(this).val('');
            return;
        }

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                promptSelectSheet(workbook, 'Pilih Sheet Outbound', workbook.SheetNames[0], function (chosenSheet) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Is Processing Please Wait',
                            html: 'Memproses sheet <b>' + chosenSheet + '</b> untuk periode <b>' + outMonthVal + ' ' + outYearVal + '-Batch' + outBatchVal + '</b>...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    }

                    var worksheet = workbook.Sheets[chosenSheet];
                    if (!worksheet) {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Sheet "' + chosenSheet + '" tidak ditemukan.', 'error');
                        return;
                    }
                    var rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

                    if (rawJson.length === 0) {
                        if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Sheet "' + chosenSheet + '" kosong.', 'warning');
                        return;
                    }

                // 23-Field Alias Definitions for intelligent fuzzy matching
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

                // Map raw Excel rows to normalized 23-column format
                var mappedRows = rawJson.map(function (rawRow) {
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

                // Send to backend API
                fetch('api/save_outbound_master.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'batch',
                        month: outMonthVal,
                        year: outYearVal,
                        batch: outBatchVal,
                        data: mappedRows
                    })
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Sukses', res.message || 'Import Data Outbound Berhasil!', 'success');
                            }
                            $('#uploadExcelModalOutbound').modal('hide');
                            if (outboundTable) {
                                outboundTable.ajax.reload();
                                refreshOutboundFilters();
                            } else {
                                location.reload();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message || 'Gagal import data.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan server saat menyimpan data.', 'error');
                    });
                }, function () {
                    $('#excel-file-outbound-input').val('');
                });
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Format file Excel tidak valid.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
        $(this).val('');
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

    // PR Forwarder Excel File Upload Handler (Hierarchical multi-row header & positional column parsing)
    $('#excel-file-forwarder-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        if (file.size > 200 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 200MB.', 'error');
            $(this).val('');
            return;
        }

        var fwdMonthVal = $('#uploadForwarderMonthSelect').val();
        var fwdBatchVal = $('#uploadForwarderBatchSelect').val();
        var fwdYearVal = $('#uploadForwarderYearSelect').val();

        if (!fwdMonthVal || !fwdBatchVal || !fwdYearVal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.', 'warning');
            } else {
                alert('Silakan pilih Bulan, Batch, dan Tahun Periode terlebih dahulu sebelum mengupload file.');
            }
            $(this).val('');
            return;
        }

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                promptSelectSheet(workbook, 'Pilih Sheet PR Forwarder', workbook.SheetNames[0], function (chosenSheet) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Is Processing Please Wait',
                            html: 'Memproses sheet <b>' + chosenSheet + '</b> untuk periode <b>' + fwdMonthVal + ' ' + fwdYearVal + '-Batch' + fwdBatchVal + '</b>...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    }

                    var worksheet = workbook.Sheets[chosenSheet];
                    if (!worksheet) {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Sheet "' + chosenSheet + '" tidak ditemukan.', 'error');
                        return;
                    }

                    // Parse 2D Array of rows
                    var sheetAOA = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                    if (!sheetAOA || sheetAOA.length === 0) {
                        if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Sheet "' + chosenSheet + '" kosong.', 'warning');
                        return;
                    }

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
                    if (totalCells === 0) return true; // empty row
                    return (matchCount / totalCells) >= 0.35;
                }

                // Determine data starting row index (skip header rows)
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

                    // Skip repeated sub-headers
                    if (isHeaderRow(rowArr)) continue;

                    var rowObj = {};
                    for (var colIdx = 0; colIdx < FORWARDER_COLUMNS.length; colIdx++) {
                        var colKey = FORWARDER_COLUMNS[colIdx];
                        var cellVal = (rowArr[colIdx] !== undefined && rowArr[colIdx] !== null) ? String(rowArr[colIdx]).trim() : '';
                        rowObj[colKey] = cellVal;
                    }
                    mappedRows.push(rowObj);
                }

                if (mappedRows.length === 0) {
                    if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Tidak ditemukan baris data yang valid pada file Excel.', 'warning');
                    return;
                }

                // Send to backend API
                fetch('api/save_outbound_forwarder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'batch',
                        month: fwdMonthVal,
                        year: fwdYearVal,
                        batch: fwdBatchVal,
                        data: mappedRows
                    })
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Sukses', res.message || 'Import Data PR Forwarder Berhasil!', 'success');
                            }
                            $('#uploadExcelModalForwarder').modal('hide');
                            if (forwarderTable) {
                                forwarderTable.ajax.reload();
                                refreshForwarderFilters();
                            } else {
                                location.reload();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message || 'Gagal import data.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan server saat menyimpan data.', 'error');
                    });
                }, function () {
                    $('#excel-file-forwarder-input').val('');
                });
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Format file Excel tidak valid.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
        $(this).val('');
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

    // KPI Excel File Upload Handler
    $('#excel-file-kpi-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        if (file.size > 200 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') Swal.fire('File Terlalu Besar', 'Ukuran file maksimum adalah 200MB.', 'error');
            $(this).val('');
            return;
        }

        var yearVal = $('#uploadKpiYearSelect').val();

        if (!yearVal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Tahun Periode terlebih dahulu sebelum mengupload file.', 'warning');
            } else {
                alert('Silakan pilih Tahun Periode terlebih dahulu sebelum mengupload file.');
            }
            $(this).val('');
            return;
        }

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (ev) {
            try {
                var data = new Uint8Array(ev.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                promptSelectSheet(workbook, 'Pilih Sheet KPI Master', workbook.SheetNames[0], function (chosenSheet) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Data Is Processing Please Wait',
                            html: 'Memproses sheet <b>' + chosenSheet + '</b> untuk KPI tahun <b>' + yearVal + '</b>...',
                            allowOutsideClick: false,
                            didOpen: function () { Swal.showLoading(); }
                        });
                    }

                    var worksheet = workbook.Sheets[chosenSheet];
                    if (!worksheet) {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Sheet "' + chosenSheet + '" tidak ditemukan.', 'error');
                        return;
                    }
                    var jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

                    if (jsonRows.length === 0) {
                        if (typeof Swal !== 'undefined') Swal.fire('Warning', 'Sheet "' + chosenSheet + '" kosong.', 'warning');
                        return;
                    }

                    // Send to backend API
                    fetch('api/save_kpi_master.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'batch',
                            year: yearVal,
                            data: jsonRows
                        })
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res.status === 'success') {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire('Sukses', res.message || 'Import Data KPI Berhasil!', 'success');
                                }
                                $('#uploadExcelModalKpi').modal('hide');
                                if (kpiMasterTable) {
                                    kpiMasterTable.ajax.reload();
                                } else {
                                    location.reload();
                                }
                            } else {
                                if (typeof Swal !== 'undefined') Swal.fire('Error', res.message || 'Gagal import data.', 'error');
                            }
                        })
                        .catch(function (err) {
                            console.error(err);
                            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan server saat menyimpan data.', 'error');
                        });
                }, function () {
                    $('#excel-file-kpi-input').val('');
                });
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Format file Excel tidak valid.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
        $(this).val('');
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

});
