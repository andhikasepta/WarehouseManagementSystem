// js/master-data.js
$(document).ready(function () {

    var tablesLoaded = 0;
    var assetTableExist = $('#dataTableAsset').length > 0;
    var rackTableExist = $('#dataTableRack').length > 0;

    var totalTables = 0;
    if (assetTableExist) totalTables++;
    if (rackTableExist) totalTables++;

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

                        // Qty (editable)
                        var tdQty = document.createElement('td');
                        var inputQty = document.createElement('input');
                        inputQty.type = 'number';
                        inputQty.className = 'form-control form-control-sm utilisasi-qty-input';
                        inputQty.min = '0';
                        inputQty.value = parseInt(row.qty) || 0;
                        inputQty.style.textAlign = 'center';
                        inputQty.setAttribute('data-label', row.label || '');
                        tdQty.appendChild(inputQty);
                        tr.appendChild(tdQty);

                        // Capacity (editable)
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
        var sampleData = [{
            'VENDOR CODE': 'VND-1001',
            'VENDOR NAME': 'PT Supplier Utama',
            'ITEM CODE': 'ITEM-INB-001',
            'ITEM NAME': 'Sparepart Server Card',
            'CATEGORY': 'Electronics',
            'UOM': 'PCS',
            'STATUS': 'ACTIVE'
        }];
        var ws = XLSX.utils.json_to_sheet(sampleData);
        XLSX.utils.book_append_sheet(wb, ws, "Master Inbound");
        XLSX.writeFile(wb, "Template_Import_Master_Data_Inbound.xlsx");
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
