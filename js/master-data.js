// js/master-data.js
$(document).ready(function () {

    var tablesLoaded = 0;
    var assetTableExist = $('#dataTableAsset').length > 0;
    var rackTableExist = $('#dataTableRack').length > 0;
    var inboundTableExist = $('#dataTableInbound').length > 0;

    var totalTables = 0;
    if (assetTableExist) totalTables++;
    if (rackTableExist) totalTables++;
    if (inboundTableExist) totalTables++;

    if (totalTables > 0 && typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Loading Data...',
            html: 'Please wait while the data is being processed.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    function checkAllTablesLoaded() {
        tablesLoaded++;
        if (tablesLoaded >= totalTables && typeof Swal !== 'undefined') {
            Swal.close();
        }
    }

    // 0. Initialize Inbound Master DataTable if element exists
    var inboundTable = null;
    var existingInboundPeriods = [];

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

    if (inboundTableExist) {
        inboundTable = $('#dataTableInbound').DataTable({
            deferRender: true,
            ajax: {
                url: 'api/get_inbound_master.php',
                dataSrc: function (json) {
                    if (json.status === 'success') {
                        // Populate filter options dynamically if returned
                        if (json.filters) {
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
                        return json.data || [];
                    } else {
                        return [];
                    }
                }
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
                        return data ? '<span class="badge badge-info px-2 py-1">' + data + '</span>' : '<span class="badge badge-secondary px-2 py-1">Unknown Period</span>';
                    }
                }
            ],
            initComplete: function () {
                checkAllTablesLoaded();
            }
        });

        // Populate Year dropdowns for Inbound Upload & Delete Modals
        var currentYear = new Date().getFullYear();
        ['uploadInboundYearSelect', 'deleteInboundYearSelect'].forEach(function (id) {
            var ySel = document.getElementById(id);
            if (ySel && ySel.options.length <= 1) {
                for (var y = 2024; y <= currentYear + 5; y++) {
                    var opt = document.createElement('option');
                    opt.value = String(y);
                    opt.textContent = String(y);
                    ySel.appendChild(opt);
                }
                ySel.value = String(currentYear);
            }
        });

        // Event listeners for Period Selection Status Check
        $('#uploadInboundMonthSelect, #uploadInboundYearSelect').on('change', checkInboundPeriodStatus);

        // Filter event listeners for Inbound Master
        $('#filter-inbound-periode').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            inboundTable.column(18).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filter-inbound-bagian').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            inboundTable.column(5).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filter-inbound-pic').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            inboundTable.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filter-inbound-kategori').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            inboundTable.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#btn-reset-filter-inbound').on('click', function () {
            $('#filter-inbound-periode').val('');
            $('#filter-inbound-bagian').val('');
            $('#filter-inbound-pic').val('');
            $('#filter-inbound-kategori').val('');
            if (inboundTable) {
                inboundTable.search('').columns().search('').draw();
            }
        });

        $('#btn-reset-filter-outbound').on('click', function () {
            $('#filter-tujuan-site-outbound').val('');
            $('#filter-pic-mr-outbound').val('');
            $('#filter-status-outbound').val('');
            if (typeof outboundTable !== 'undefined' && outboundTable) {
                outboundTable.search('').columns().search('').draw();
            } else if ($.fn.DataTable && $('#dataTableOutbound').length) {
                $('#dataTableOutbound').DataTable().search('').columns().search('').draw();
            }
        });
    }

    // 1. Initialize Asset DataTable if element exists
    var assetTable = null;
    if (assetTableExist) {
        assetTable = $('#dataTableAsset').DataTable({
            deferRender: true,
            ajax: 'api/get_master_assets.php',
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
            initComplete: function () {
                var api = this.api();
                var periodes = api.column(10).data().unique().toArray().sort(function (a, b) {
                    if (!a) return 1;
                    if (!b) return -1;
                    return new Date("01 " + a) - new Date("01 " + b);
                });
                var $periodeSelect = $('#filterAssetPeriode');
                $.each(periodes, function (i, d) {
                    if (d) $periodeSelect.append('<option value="' + d + '">' + d + '</option>');
                });

                var subLocations = api.column(8).data().unique().sort();
                var $subLocSelect = $('#filterAssetSubLocation');
                subLocations.each(function (d) {
                    if (d) $subLocSelect.append('<option value="' + d + '">' + d + '</option>');
                });

                $('#filterAssetPeriode, #filterAssetSubLocation').select2({ width: '100%' });

                var $searchBar = $('#dataTableAsset_filter');
                $searchBar.detach().appendTo('#assetSearchContainer');
                $searchBar.css({ 'text-align': 'right', 'width': '100%' });
                $searchBar.find('label').css({ 'margin-bottom': '0', 'display': 'inline-flex', 'align-items': 'center' });
                $searchBar.find('input').css('margin-left', '0.5em');

                checkAllTablesLoaded();
            }
        });

        $('#filterAssetPeriode').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            assetTable.column(10).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filterAssetSubLocation').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            assetTable.column(8).search(val ? '^' + val + '$' : '', true, false).draw();
        });
    }

    // 2. Initialize Rack DataTable if element exists
    var rackTable = null;
    if (rackTableExist) {
        rackTable = $('#dataTableRack').DataTable({
            deferRender: true,
            ajax: 'api/get_rack_data.php',
            columns: [
                { data: 'label' },
                { data: 'rack' },
                { data: 'category' }
            ],
            initComplete: function () {
                var api = this.api();

                var categories = api.column(2).data().unique().sort();
                var $categorySelect = $('#filterRackCategory');
                categories.each(function (d) {
                    if (d) $categorySelect.append('<option value="' + d + '">' + d + '</option>');
                });

                var racks = api.column(1).data().unique().sort();
                var $rackSelect = $('#filterRackName');
                racks.each(function (d) {
                    if (d) $rackSelect.append('<option value="' + d + '">' + d + '</option>');
                });

                $('#filterRackCategory, #filterRackName').select2({ width: '100%' });

                var $searchBar = $('#dataTableRack_filter');
                $searchBar.detach().appendTo('#rackSearchContainer');
                $searchBar.css({ 'text-align': 'right', 'width': '100%' });
                $searchBar.find('label').css({ 'margin-bottom': '0', 'display': 'inline-flex', 'align-items': 'center' });
                $searchBar.find('input').css('margin-left', '0.5em');

                checkAllTablesLoaded();
            }
        });

        $('#filterRackCategory').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            rackTable.column(2).search(val ? '^' + val + '$' : '', true, false).draw();
        });

        $('#filterRackName').on('change', function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            rackTable.column(1).search(val ? '^' + val + '$' : '', true, false).draw();
        });
    }

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
                title: 'Memuat Data...',
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
                        if (window.currentUserRole === 'head_warehouse_admin') {
                            inputQty.disabled = true;
                            inputQty.style.backgroundColor = '#eaecf4';
                        }
                        tdQty.appendChild(inputQty);
                        tr.appendChild(tdQty);

                        // Capacity (editable for admins, view-only for head_warehouse_admin)
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
                        if (window.currentUserRole === 'head_warehouse_admin') {
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
                    'Label': row.label || '',
                    'Rack Group': row.rack || '',
                    'Category': row.category || ''
                };
            });
            var ws = XLSX.utils.json_to_sheet(exportData);
            XLSX.utils.book_append_sheet(wb, ws, "Utilisasi Rack");
            XLSX.writeFile(wb, "Master_Data_Rack_" + dateStr + ".xlsx");

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

    $('#btn-template-rack').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [{
            'LABEL': 'RACK-A1',
            'RACK': 'Rack Group A',
            'CATEGORY': 'Server'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        XLSX.utils.book_append_sheet(wb, ws, "Rack Master");
        XLSX.writeFile(wb, "Template_Import_Data_Rack.xlsx");
    });

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

    // Handle Inbound Excel File Upload
    $('#excel-file-inbound-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        var monthVal = $('#uploadInboundMonthSelect').val();
        var yearVal = $('#uploadInboundYearSelect').val();

        if (!monthVal || !yearVal) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Peringatan', 'Silakan pilih Bulan dan Tahun Periode terlebih dahulu sebelum mengupload file.', 'warning');
            } else {
                alert('Silakan pilih Bulan dan Tahun Periode terlebih dahulu sebelum mengupload file.');
            }
            $(this).val('');
            return;
        }

        if (typeof XLSX === 'undefined') {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'SheetJS (XLSX) library tidak ditemukan.', 'error');
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Reading Excel File...',
                html: 'Memproses file Excel Inbound untuk periode <b>' + monthVal + ' ' + yearVal + '</b>...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var firstSheet = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheet];
                var jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

                if (jsonRows.length === 0) {
                    if (typeof Swal !== 'undefined') Swal.fire('Warning', 'File Excel kosong.', 'warning');
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
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Format file Excel tidak valid.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
        // Reset input value
        $(this).val('');
    });

    $('#btn-template-outbound').on('click', function () {
        if (typeof XLSX === 'undefined') return;
        var wb = XLSX.utils.book_new();
        var sampleData = [{
            'CUSTOMER CODE': 'CUST-2001',
            'CUSTOMER NAME': 'PT Logistics Global',
            'DESTINATION': 'Surakarta Branch',
            'CARRIER': 'JNE Express',
            'SERVICE TYPE': 'REGULAR',
            'STATUS': 'ACTIVE'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        XLSX.utils.book_append_sheet(wb, ws, "Master Outbound");
        XLSX.writeFile(wb, "Template_Import_Master_Data_Outbound.xlsx");
    });

});
