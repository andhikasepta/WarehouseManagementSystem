/**
 * Formula Controller for Dashboard Summary Cards
 * ==============================================
 * Handles computing mathematical formulas (Sum, Average, Count) 
 * from Excel data to dynamically update the dashboard cards.
 */

(function (window) {
    'use strict';

    var FormulaController = {};

    // Helper: format numbers like "12.450"
    function formatNumber(num) {
        if (isNaN(num)) return '0';
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    // Helper: format currency like "Rp. 15.200.000"
    function formatCurrency(num) {
        if (isNaN(num)) return 'Rp 0';
        return 'Rp ' + formatNumber(num);
    }

    // Mathematical operations
    FormulaController.computeSum = function (data, columnName) {
        if (!data || !columnName) return 0;
        var sum = 0;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (raw === undefined || raw === null || raw === '') continue;

            if (typeof raw === 'number') {
                sum += raw;
                continue;
            }

            var str = String(raw).trim().replace(/[RrpP\s]/g, ''); // Remove Rp and spaces
            
            var lastDot = str.lastIndexOf('.');
            var lastComma = str.lastIndexOf(',');

            if (lastComma > lastDot && lastDot !== -1) {
                // Comma is after dot: dot is thousands, comma is decimal (Indo)
                str = str.replace(/\./g, '').replace(/,/g, '.');
            } else if (lastDot > lastComma && lastComma !== -1) {
                // Dot is after comma: comma is thousands, dot is decimal (English)
                str = str.replace(/,/g, '');
            } else if (lastDot !== -1 && lastComma === -1) {
                // Only dots. If multiple dots or exactly 3 digits after last dot, assume thousands separator
                if (str.split('.').length > 2 || str.split('.')[1].length === 3) {
                    str = str.replace(/\./g, '');
                }
            } else if (lastComma !== -1 && lastDot === -1) {
                // Only commas. If multiple commas or exactly 3 digits after last comma, assume thousands separator
                if (str.split(',').length > 2 || str.split(',')[1].length === 3) {
                    str = str.replace(/,/g, '');
                } else {
                    // Otherwise assume it's a decimal (Indo)
                    str = str.replace(/,/g, '.');
                }
            }

            var val = parseFloat(str);
            if (!isNaN(val)) {
                sum += val;
            }
        }
        return sum;
    };

    FormulaController.computeCount = function (data, columnName) {
        if (!data) return 0;
        if (!columnName) return data.length;
        var count = 0;
        for (var i = 0; i < data.length; i++) {
            if (data[i][columnName] !== undefined && data[i][columnName] !== '') {
                count++;
            }
        }
        return count;
    };

    FormulaController.computeAverage = function (data, columnName) {
        if (!data || data.length === 0 || !columnName) return 0;
        var sum = FormulaController.computeSum(data, columnName);
        return sum / data.length;
    };

    // Auto-detect columns based on keywords (prioritizes keywords in order)
    function findColumnByKeyword(headers, keywords) {
        // Iterate over keywords first to ensure priority
        for (var j = 0; j < keywords.length; j++) {
            for (var i = 0; i < headers.length; i++) {
                var h = String(headers[i]).toLowerCase().trim();
                if (h.includes(keywords[j].toLowerCase())) {
                    return headers[i];
                }
            }
        }
        return null;
    }

    // Helper to find exact column or fallback
    FormulaController.findBestColumn = function(headers, exactNames, keywords) {
        for (var i = 0; i < headers.length; i++) {
            var h = String(headers[i]).trim();
            for (var j = 0; j < exactNames.length; j++) {
                if (h === exactNames[j]) return headers[i];
            }
        }
        return findColumnByKeyword(headers, keywords);
    };

    FormulaController.getFieldValue = function(item, keys) {
        if (!item) return '-';
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            if (item[k] !== undefined && item[k] !== null && item[k] !== '') {
                return String(item[k]);
            }
        }
        var itemKeys = Object.keys(item);
        for (var i = 0; i < keys.length; i++) {
            var targetLower = keys[i].toLowerCase().trim();
            for (var j = 0; j < itemKeys.length; j++) {
                if (itemKeys[j].toLowerCase().trim() === targetLower) {
                    var val = item[itemKeys[j]];
                    if (val !== undefined && val !== null && val !== '') {
                        return String(val);
                    }
                }
            }
        }
        return '-';
    };

    function escapeHtml(str) {
        if (typeof str !== 'string') return str;
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    FormulaController.openDetailModal = function(chartTitle, label, records) {
        var modalEl = document.getElementById('chartDetailModal');
        if (!modalEl) return;

        var titleEl = document.getElementById('chartDetailTitle');
        var subtitleEl = document.getElementById('chartDetailSubtitle');
        var countEl = document.getElementById('chartDetailRecordCount');

        if (titleEl) titleEl.textContent = chartTitle;

        var isCategoryMode = (chartTitle.indexOf('Perangkat') !== -1 || chartTitle.indexOf('IN') !== -1 || chartTitle.indexOf('OUT') !== -1);
        var totalQty = records ? records.length : 0;
        var totalNbv = 0;

        var dataRows = [];
        if (records && records.length > 0) {
            records.forEach(function(item, idx) {
                var specCode = FormulaController.getFieldValue(item, ['spec_code', 'SPEC_CODE', 'spec code', 'spek']);
                var regNo = FormulaController.getFieldValue(item, ['reg_no', 'REG_NO', 'reg no', 'register']);
                var specName = FormulaController.getFieldValue(item, ['spec_name', 'SPEC_NAME', 'spec name', 'nama', 'perangkat', 'item']);
                var categoryVal = FormulaController.getFieldValue(item, ['category', 'CATEGORY', 'kategori', 'range', 'status', 'STATUS']);
                var rawNbv = FormulaController.getFieldValue(item, ['nbv', 'NBV', 'value', 'harga', 'price']);

                var numNbv = parseFloat(String(rawNbv).replace(/[^0-9.-]+/g, '')) || 0;
                totalNbv += numNbv;
                var formattedNbv = formatCurrency(numNbv);

                dataRows.push({
                    no: idx + 1,
                    spec_code: specCode,
                    reg_no: regNo,
                    spec_name: specName,
                    category: categoryVal || '-',
                    nbv_num: numNbv,
                    nbv_formatted: formattedNbv
                });
            });
        }

        if (subtitleEl) {
            var subtitleHtml = '';
            if (label) {
                subtitleHtml += '<span class="badge badge-primary mr-2 mb-1 shadow-sm" style="font-size: 0.72rem; font-weight: 600; padding: 3px 8px;"><i class="fas fa-filter mr-1"></i>Kategori / Group: ' + escapeHtml(label) + '</span>';
            }
            subtitleHtml += '<span class="badge badge-info mr-2 mb-1 shadow-sm" style="font-size: 0.72rem; font-weight: 600; padding: 3px 8px;"><i class="fas fa-boxes mr-1"></i>Total Qty: ' + formatNumber(totalQty) + ' Unit</span>';
            if (!isCategoryMode) {
                subtitleHtml += '<span class="badge badge-success mr-2 mb-1 shadow-sm" style="font-size: 0.72rem; font-weight: 600; padding: 3px 8px;"><i class="fas fa-coins mr-1"></i>Total NBV: ' + formatCurrency(totalNbv) + '</span>';
            }
            subtitleEl.innerHTML = subtitleHtml;
        }

        if (countEl) countEl.textContent = formatNumber(totalQty);

        var col5Title = isCategoryMode ? "CATEGORY" : "NBV";

        if (typeof $ !== 'undefined') {
            if ($.fn.DataTable) {
                if ($.fn.DataTable.isDataTable('#chartDetailTable')) {
                    $('#chartDetailTable').DataTable().clear().destroy();
                }

                $('#chartDetailTable').DataTable({
                    data: dataRows,
                    columns: [
                        { data: "no", title: "NO", className: "text-center", width: "50px" },
                        { 
                            data: "spec_code",
                            title: "SPEC CODE", 
                            className: "text-left",
                            render: function(data) {
                                return escapeHtml(data);
                            }
                        },
                        { 
                            data: "reg_no", 
                            title: "REG NO", 
                            className: "text-left",
                            render: function(data) {
                                return escapeHtml(data);
                            }
                        },
                        { 
                            data: "spec_name", 
                            title: "SPEC NAME", 
                            className: "text-left",
                            render: function(data) {
                                return escapeHtml(data);
                            }
                        },
                        { 
                            data: isCategoryMode ? "category" : "nbv_num", 
                            title: col5Title, 
                            className: isCategoryMode ? "text-left" : "text-right",
                            render: function(data, type, row) {
                                if (isCategoryMode) {
                                    return escapeHtml(data);
                                }
                                if (type === 'display' || type === 'filter') {
                                    return escapeHtml(row.nbv_formatted);
                                }
                                return data;
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        lengthMenu: "Tampilkan _MENU_ entries",
                        search: "Cari:",
                        zeroRecords: "Tidak ada data yang ditemukan",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entries",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 entries",
                        infoFiltered: "(disaring dari _MAX_ total entries)",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Berikutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
            }

            $('#chartDetailModal').modal('show');
        }
    };

    FormulaController.getClickedChartIndex = function(chartInstance, e) {
        if (!chartInstance || !chartInstance.canvas || !chartInstance.scales) return -1;

        var rect = chartInstance.canvas.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        var isHorizontalBar = (chartInstance.config && chartInstance.config.type === 'horizontalBar');
        var chartArea = chartInstance.chartArea;

        // Restrict modal triggering exclusively to label text area (outside bar graphics)
        var isClickOnLabelArea = false;
        if (chartArea) {
            if (isHorizontalBar) {
                // Horizontal bar chart (Asset Organization): Y-axis labels are on left (x <= chartArea.left + 5)
                isClickOnLabelArea = (x <= chartArea.left + 5);
            } else {
                // Vertical bar chart (Storage / Aging): X-axis labels are on bottom (y >= chartArea.bottom - 5)
                isClickOnLabelArea = (y >= chartArea.bottom - 5);
            }
        } else {
            isClickOnLabelArea = true;
        }

        // Ignore clicks on colored bar graphics
        if (!isClickOnLabelArea) {
            return -1;
        }

        // 2. Locate the Category Scale (Y-axis for horizontalBar, X-axis for vertical bar)
        var categoryScale = null;
        for (var scaleId in chartInstance.scales) {
            var s = chartInstance.scales[scaleId];
            if (!s) continue;
            if (isHorizontalBar && !s.isHorizontal) {
                categoryScale = s;
                break;
            } else if (!isHorizontalBar && s.isHorizontal) {
                categoryScale = s;
                break;
            }
        }

        // Fallback: Pick scale matching label count
        if (!categoryScale && chartInstance.data && chartInstance.data.labels) {
            for (var sId in chartInstance.scales) {
                var sc = chartInstance.scales[sId];
                if (sc && sc.ticks && sc.ticks.length === chartInstance.data.labels.length) {
                    categoryScale = sc;
                    break;
                }
            }
        }

        if (categoryScale) {
            var numLabels = (chartInstance.data && chartInstance.data.labels) ? chartInstance.data.labels.length : (categoryScale.ticks ? categoryScale.ticks.length : 0);
            if (numLabels === 0) return -1;

            // Method A: Native Chart.js getValueForPixel
            if (typeof categoryScale.getValueForPixel === 'function') {
                var rawVal = isHorizontalBar ? categoryScale.getValueForPixel(y) : categoryScale.getValueForPixel(x);
                var valIdx = Math.round(rawVal);
                if (typeof valIdx === 'number' && !isNaN(valIdx) && valIdx >= 0 && valIdx < numLabels) {
                    return valIdx;
                }
            }

            // Method B: Pixel range linear interpolation
            var startPixel = isHorizontalBar ? categoryScale.top : categoryScale.left;
            var endPixel = isHorizontalBar ? categoryScale.bottom : categoryScale.right;
            var clickPixel = isHorizontalBar ? y : x;

            if (typeof startPixel === 'number' && typeof endPixel === 'number' && endPixel > startPixel) {
                var step = (endPixel - startPixel) / numLabels;
                var interpolatedIdx = Math.floor((clickPixel - startPixel) / step);
                if (interpolatedIdx >= 0 && interpolatedIdx < numLabels) {
                    return interpolatedIdx;
                }
            }

            // Method C: Nearest tick pixel comparison
            var closestIdx = -1;
            var minDiff = Infinity;
            for (var i = 0; i < numLabels; i++) {
                var px = -1;
                if (typeof categoryScale.getPixelForTick === 'function') {
                    px = categoryScale.getPixelForTick(i);
                } else if (typeof categoryScale.getPixelForValue === 'function') {
                    px = categoryScale.getPixelForValue(i);
                }
                if (typeof px === 'number' && !isNaN(px) && px >= 0) {
                    var diff = Math.abs(clickPixel - px);
                    if (diff < minDiff) {
                        minDiff = diff;
                        closestIdx = i;
                    }
                }
            }

            if (closestIdx >= 0 && closestIdx < numLabels) {
                return closestIdx;
            }
        }

        return -1;
    };

    FormulaController.makeChartClickable = function(chartInstance, defaultTitle) {
        if (!chartInstance || !chartInstance.canvas) return;
        var canvas = chartInstance.canvas;
        
        if (canvas._hasClickListener) return;
        canvas._hasClickListener = true;

        canvas.style.cursor = 'pointer';

        canvas.addEventListener('mousemove', function(e) {
            var index = FormulaController.getClickedChartIndex(chartInstance, e);
            canvas.style.cursor = (index !== -1) ? 'pointer' : 'default';
        });

        canvas.addEventListener('click', function(e) {
            var index = FormulaController.getClickedChartIndex(chartInstance, e);
            if (index !== -1 && chartInstance.data && chartInstance.data.labels && index < chartInstance.data.labels.length) {
                var label = chartInstance.data.labels[index];
                var title = chartInstance._chartTitle || defaultTitle || 'Detail Data';
                var records = (chartInstance._recordsPerIndex && chartInstance._recordsPerIndex[index]) ? chartInstance._recordsPerIndex[index] : [];

                FormulaController.openDetailModal(title, label, records);
            }
        });
    };

    // Main entry point to update cards
    FormulaController.updateDashboardCards = function (sheetData, headers) {
        if (!sheetData || sheetData.length === 0) {
            console.log("Formula Controller: Clearing dashboard...");
            // Reset cards to 0
            var cardAsset = document.getElementById('card-total-asset');
            if (cardAsset) cardAsset.textContent = '0';
            var cardNbv = document.getElementById('card-total-nbv');
            if (cardNbv) cardNbv.textContent = 'Rp 0';
            var cardUtilText = document.getElementById('card-utilisasi-space-text');
            if (cardUtilText) cardUtilText.textContent = '0%';
            var cardUtilBar = document.getElementById('card-utilisasi-space-bar');
            if (cardUtilBar) { cardUtilBar.style.width = '0%'; cardUtilBar.className = 'progress-bar bg-danger'; }
            var cardFreeText = document.getElementById('card-free-space-text');
            if (cardFreeText) cardFreeText.textContent = '0%';
            var cardFreeBar = document.getElementById('card-free-space-bar');
            if (cardFreeBar) { cardFreeBar.style.width = '0%'; cardFreeBar.className = 'progress-bar bg-danger'; }
            var cardUpdate = document.getElementById('card-last-update');
            if (cardUpdate) cardUpdate.textContent = '-';
            
            // Clear charts
            if (window.myBarChart && window.myBarChart.data) { window.myBarChart.data.labels = []; window.myBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.myBarChart.update(); }
            if (window.myHorizontalBarChart && window.myHorizontalBarChart.data) { window.myHorizontalBarChart.data.labels = []; window.myHorizontalBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.myHorizontalBarChart.update(); }
            if (window.agingBarChart && window.agingBarChart.data) { window.agingBarChart.data.labels = []; window.agingBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.agingBarChart.update(); }
            if (window.perangkatInChart && window.perangkatInChart.data) { window.perangkatInChart.data.labels = []; window.perangkatInChart.data.datasets.forEach(function(d) { d.data = []; }); window.perangkatInChart.update(); }
            if (window.perangkatOutChart && window.perangkatOutChart.data) { window.perangkatOutChart.data.labels = []; window.perangkatOutChart.data.datasets.forEach(function(d) { d.data = []; }); window.perangkatOutChart.update(); }
            
            // Clear table
            var tbody = document.getElementById('table-utilisasi-area-body');
            if (tbody) tbody.replaceChildren();
            var dotContainer = document.getElementById('rack-status-dots');
            if (dotContainer) dotContainer.replaceChildren();

            // Clear Inbound Summary
            if (window.updateInboundFlowPieChart) {
                window.updateInboundFlowPieChart([0, 0, 0]);
            }
            var flowTotalGr = document.getElementById('flow-total-gr');
            if (flowTotalGr) flowTotalGr.textContent = '0 GR';
            var flowDoneCount = document.getElementById('flow-done-count');
            if (flowDoneCount) flowDoneCount.textContent = '0 Unit';

            // Clear Storage Summary
            if (window.updateInventorySummaryPieChart) {
                window.updateInventorySummaryPieChart([0, 0, 0, 0]);
            }

            return;
        }

        console.log("Formula Controller: Updating cards from data...", headers);

        // 1. TOTAL ASSET
        // Counts rows where "Nama Perangkat" is present
        var assetCol = findColumnByKeyword(headers, ['spec_name', 'spec name', 'nama perangkat', 'perangkat', 'nama', 'asset']);
        var totalAsset = assetCol ? FormulaController.computeCount(sheetData, assetCol) : FormulaController.computeCount(sheetData);
        var cardAsset = document.getElementById('card-total-asset');
        if (cardAsset) cardAsset.textContent = formatNumber(totalAsset);

        // 2. TOTAL NBV
        // Tries to sum an "NBV" column first, then "Value", "Harga", or "Price".
        var nbvCol = findColumnByKeyword(headers, ['nbv', 'value', 'harga', 'price', 'total']);
        var totalNbv = nbvCol ? FormulaController.computeSum(sheetData, nbvCol) : 0;
        var cardNbv = document.getElementById('card-total-nbv');
        if (cardNbv) cardNbv.textContent = formatCurrency(totalNbv);

        // Update Dashboard Overview Storage Summary elements if present
        var invTotalEl = document.getElementById('inv-total-perangkat');
        if (invTotalEl) invTotalEl.textContent = formatNumber(totalAsset) + ' Unit';

        var invNbvEl = document.getElementById('inv-total-nbv');
        if (invNbvEl) invNbvEl.textContent = formatCurrency(totalNbv);

        var rangeColDash = FormulaController.findBestColumn(headers, ['range', 'RANGE', 'aging_range', 'AGING_RANGE'], ['range', 'aging', 'usia', 'umur']);
        var catColDash = FormulaController.findBestColumn(headers, ['category', 'CATEGORY', 'kategori', 'KATEGORI'], ['category', 'kategori', 'status']);

        var cLess3m = 0;
        var c3to12m = 0;
        var cMore12m = 0;
        var cReUse = 0;

        if (sheetData && sheetData.length > 0) {
            for (var sd = 0; sd < sheetData.length; sd++) {
                var row = sheetData[sd];
                var rVal = rangeColDash ? String(row[rangeColDash] || '').trim().toLowerCase() : '';
                var cVal = catColDash ? String(row[catColDash] || '').trim().toLowerCase() : '';

                // Categorize Aging (>2 checked first to avoid "2 tahun" substring collision)
                if (rVal.indexOf('>2') !== -1 || rVal.indexOf('> 2') !== -1 || rVal.indexOf('2 - 3') !== -1 || rVal.indexOf('2-3') !== -1 || rVal.indexOf('> 2 tahun') !== -1 || rVal.indexOf('>2 tahun') !== -1) {
                    cMore12m++;
                } else if (rVal.indexOf('<1') !== -1 || rVal.indexOf('< 1') !== -1 || rVal.indexOf('< 3') !== -1 || rVal.indexOf('<3') !== -1 || rVal.indexOf('< 1 tahun') !== -1 || rVal.indexOf('<1 tahun') !== -1 || rVal.indexOf('<') !== -1) {
                    cLess3m++;
                } else if (rVal.indexOf('>1') !== -1 || rVal.indexOf('> 1') !== -1 || rVal.indexOf('1-2') !== -1 || rVal.indexOf('1 - 2') !== -1 || rVal.indexOf('3-12') !== -1 || rVal.indexOf('3 - 12') !== -1 || rVal.indexOf('1 tahun') !== -1) {
                    c3to12m++;
                } else if (rVal.indexOf('>') !== -1) {
                    cMore12m++;
                }

                if (cVal.indexOf('re-use') !== -1 || cVal.indexOf('reuse') !== -1 || cVal.indexOf('need to utilize') !== -1 || cVal.indexOf('slow moving') !== -1) {
                    cReUse++;
                }
            }
        }

        var elLess3m = document.getElementById('inv-aging-less-3m');
        if (elLess3m) elLess3m.textContent = formatNumber(cLess3m) + ' Unit';

        var el3to12m = document.getElementById('inv-aging-3-12m');
        if (el3to12m) el3to12m.textContent = formatNumber(c3to12m) + ' Unit';

        var elMore12m = document.getElementById('inv-aging-more-12m');
        if (elMore12m) elMore12m.textContent = formatNumber(cMore12m) + ' Unit';

        var elReUse = document.getElementById('inv-re-useg');
        if (elReUse) elReUse.textContent = formatNumber(cReUse) + ' Unit';

        if (window.updateInventorySummaryPieChart) {
            window.updateInventorySummaryPieChart(totalAsset, totalNbv);
        }

        // 3 & 4. UTILISASI SPACE & FREE SPACE
        // These cards are populated by the UTILISASI AREA / RACK table section (section 9) below.
        // Helper for Utilisasi progress bar colors
        function getUtilisasiClass(percent) {
            if (percent <= 50) return 'bg-success';
            if (percent <= 75) return 'bg-warning';
            return 'bg-danger';
        }

        // Helper for Free Space progress bar colors
        function getFreeSpaceClass(percent) {
            if (percent <= 24) return 'bg-danger';
            if (percent <= 49) return 'bg-warning';
            return 'bg-success';
        }

        // 5. LAST UPDATE
        // Set to today's date formatted
        var cardUpdate = document.getElementById('card-last-update');
        if (cardUpdate) {
            var date = new Date();
            var months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
            cardUpdate.textContent = months[date.getMonth()] + ' ' + date.getFullYear();
        }

        // 6. STORAGE BERDASARKAN AGING CHART (myBarChart)
        // Filters data by Category: 'Need To Utilize', 'Slow Moving', 'Re-use', 'New'
        // Groups filtered data by Aging Range ('range' column, e.g. <1 Tahun, 1-2 Tahun, 2-3 Tahun, >3 Tahun, etc.)
        if (window.myBarChart && window.myBarChart.data) {
            // Find Aging Range column (range, aging, etc.)
            var rangeCol = FormulaController.findBestColumn(headers, ['range', 'RANGE', 'aging_range', 'AGING_RANGE'], ['range', 'aging', 'usia', 'umur']);

            // Find Category column (category, kategori, etc.)
            var catColBar = FormulaController.findBestColumn(headers, ['category', 'CATEGORY', 'kategori', 'KATEGORI'], ['category', 'kategori', 'status']);

            // Find SPEC_CODE column for counting Qty
            var specCodeColBar = FormulaController.findBestColumn(headers, ['SPEC_CODE'], ['spec_code', 'spec code', 'spek']);

            // Find NBV column for summing
            var barNbvCol = FormulaController.findBestColumn(headers, ['NBV'], ['nbv', 'value', 'harga', 'price', 'total']);

            // Filter sheetData by Category of Need To Utilize, Slow Moving, Re-use, New
            var allowedCategories = ['need to utilize', 'slow moving', 're-use', 'reuse', 'new'];
            var filteredBarData = [];

            if (catColBar) {
                for (var i = 0; i < sheetData.length; i++) {
                    var cVal = String(sheetData[i][catColBar] || '').trim().toLowerCase();
                    if (allowedCategories.indexOf(cVal) !== -1) {
                        filteredBarData.push(sheetData[i]);
                    }
                }
            } else {
                filteredBarData = sheetData;
            }

            var groupByCol = rangeCol || catColBar;

            if (groupByCol) {
                // Group rows by unique Aging Range value
                var groups = {};
                for (var i = 0; i < filteredBarData.length; i++) {
                    var rangeKey = String(filteredBarData[i][groupByCol] || 'Unknown').trim();
                    if (!rangeKey) rangeKey = 'Unknown';
                    if (!groups[rangeKey]) {
                        groups[rangeKey] = { data: [] };
                    }
                    groups[rangeKey].data.push(filteredBarData[i]);
                }

                var labels = Object.keys(groups);
                var qtyData = [];
                var nbvData = [];
                window.myBarChart._recordsPerIndex = [];

                for (var j = 0; j < labels.length; j++) {
                    var groupData = groups[labels[j]].data;
                    window.myBarChart._recordsPerIndex.push(groupData);

                    var qty = specCodeColBar
                        ? FormulaController.computeCount(groupData, specCodeColBar)
                        : FormulaController.computeCount(groupData);

                    var nbv = barNbvCol
                        ? FormulaController.computeSum(groupData, barNbvCol)
                        : 0;

                    qtyData.push(qty);
                    nbvData.push(nbv);
                }

                window.myBarChart.data.labels = labels;
                window.myBarChart.data.datasets[0].data = qtyData;
                window.myBarChart.data.datasets[1].data = nbvData;
                window.myBarChart._chartTitle = "STORAGE - Berdasarkan Aging";
                window.myBarChart.update();
                FormulaController.makeChartClickable(window.myBarChart, "STORAGE - Berdasarkan Aging");
            } else {
                // Fallback if no range/category column is found: plot total
                window.myBarChart.data.labels = ["Total"];
                window.myBarChart.data.datasets[0].data = [totalAsset];
                window.myBarChart.data.datasets[1].data = [totalNbv];
                window.myBarChart._recordsPerIndex = [sheetData];
                window.myBarChart._chartTitle = "STORAGE - Berdasarkan Aging";
                window.myBarChart.update();
                FormulaController.makeChartClickable(window.myBarChart, "STORAGE - Berdasarkan Aging");
            }
        }

        // 7. BERDASARKAN ASSET ORGANIZATION (myHorizontalBarChart)
        // Uses ASSET_PLANNER_ORGANIZATION column for grouping.
        // Qty = count of SPEC_CODE per unique organization.
        // NBV = sum of NBV per unique organization.
        if (window.myHorizontalBarChart && window.myHorizontalBarChart.data) {
            // Try exact column name first, then fallback to keyword detection
            var orgCol = FormulaController.findBestColumn(headers, ['ASSET_PLANNER_ORGANIZATION'], ['asset_planner_organization', 'department', 'dept', 'organization', 'unit', 'pemilik', 'owner', 'divisi']);

            // Find SPEC_CODE column for counting Qty
            var specCodeCol = FormulaController.findBestColumn(headers, ['SPEC_CODE'], ['spec_code', 'spec code', 'spek']);

            // Find NBV column for summing
            var orgNbvCol = FormulaController.findBestColumn(headers, ['NBV'], ['nbv', 'value', 'harga', 'price', 'total']);

            if (orgCol) {
                // Group rows by unique ASSET_PLANNER_ORGANIZATION
                var orgGroups = {};
                for (var k = 0; k < sheetData.length; k++) {
                    var org = String(sheetData[k][orgCol] || 'Unknown').trim();
                    if (!orgGroups[org]) {
                        orgGroups[org] = { data: [] };
                    }
                    orgGroups[org].data.push(sheetData[k]);
                }

                // Calculate Qty & NBV for each organization and sort descending by Qty
                var orgList = [];
                var rawOrgKeys = Object.keys(orgGroups);

                for (var l = 0; l < rawOrgKeys.length; l++) {
                    var orgName = rawOrgKeys[l];
                    var oGroupData = orgGroups[orgName].data;

                    // Qty = count of rows that have a SPEC_CODE value in this org group
                    var oQty = specCodeCol
                        ? FormulaController.computeCount(oGroupData, specCodeCol)
                        : FormulaController.computeCount(oGroupData);

                    // NBV = sum of NBV column for this org group
                    var oNbv = orgNbvCol
                        ? FormulaController.computeSum(oGroupData, orgNbvCol)
                        : 0;

                    orgList.push({
                        label: orgName,
                        qty: oQty,
                        nbv: oNbv,
                        records: oGroupData
                    });
                }

                // Sort organizations from Highest Qty to Lowest Qty
                orgList.sort(function(a, b) {
                    if (b.qty !== a.qty) {
                        return b.qty - a.qty;
                    }
                    return b.nbv - a.nbv;
                });

                var orgLabels = [];
                var orgQtyData = [];
                var orgNbvData = [];
                window.myHorizontalBarChart._recordsPerIndex = [];

                for (var m = 0; m < orgList.length; m++) {
                    orgLabels.push(orgList[m].label);
                    orgQtyData.push(orgList[m].qty);
                    orgNbvData.push(orgList[m].nbv);
                    window.myHorizontalBarChart._recordsPerIndex.push(orgList[m].records);
                }

                window.myHorizontalBarChart.data.labels = orgLabels;
                window.myHorizontalBarChart.data.datasets[0].data = orgQtyData;
                window.myHorizontalBarChart.data.datasets[1].data = orgNbvData;

                // Dynamically resize chart container based on number of labels
                var chartContainer = document.getElementById('horizontalBarChartContainer');
                if (chartContainer) {
                    var minHeight = 320;
                    var perLabelHeight = 35;
                    var dynamicHeight = Math.max(minHeight, orgLabels.length * perLabelHeight);
                    chartContainer.style.height = dynamicHeight + 'px';
                }

                window.myHorizontalBarChart._chartTitle = "Berdasarkan Asset Organization";
                window.myHorizontalBarChart.update();
                FormulaController.makeChartClickable(window.myHorizontalBarChart, "Berdasarkan Asset Organization");
            } else {
                // Fallback if no org column is found
                window.myHorizontalBarChart.data.labels = ["Total"];
                window.myHorizontalBarChart.data.datasets[0].data = [totalAsset];
                window.myHorizontalBarChart.data.datasets[1].data = [totalNbv];
                window.myHorizontalBarChart._recordsPerIndex = [sheetData];
                window.myHorizontalBarChart._chartTitle = "Berdasarkan Asset Organization";
                window.myHorizontalBarChart.update();
                FormulaController.makeChartClickable(window.myHorizontalBarChart, "Berdasarkan Asset Organization");
            }
        }

        // 8. PERANGKAT IN & OUT CHARTS
        var countIn = 0;
        var countOut = 0;
        
        // Find the status column
        var statusCol = FormulaController.findBestColumn(headers, ['status', 'STATUS', 'Status'], ['status']);
        
        if (statusCol !== null) {
            for (var m = 0; m < sheetData.length; m++) {
                var st = String(sheetData[m][statusCol] || '').trim().toUpperCase();
                if (st === 'IN') {
                    countIn++;
                } else if (st === 'OUT') {
                    countOut++;
                }
            }
        }
        
        // The month the user chose
        var periodText = document.getElementById('selected-period-text') ? document.getElementById('selected-period-text').textContent : "Bulan X";
        if (!periodText || periodText === '-' || periodText === 'PILIH DATA' || periodText === 'PILIH PERIODE DATA' || periodText === 'Bulan X') {
            periodText = sheetData.length > 0 ? (sheetData[0]['periode_group'] || 'Unknown') : 'Unknown';
        }
        
        var pinTitle = document.getElementById('perangkat-in-title-period');
        if (pinTitle) {
            var match = periodText.match(/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/);
            var yr = match ? match[2] : periodText;
            pinTitle.textContent = "Tahun " + yr;
        }
        var poutTitle = document.getElementById('perangkat-out-title-period');
        if (poutTitle) {
            var match = periodText.match(/^(\w+)\s+(\d{4})(?:-Batch(\d+))?$/);
            var yr = match ? match[2] : periodText;
            poutTitle.textContent = "Tahun " + yr;
        }

        // 9. AGING PERANGKAT (agingBarChart)
        // Uses RANGE column for grouping by age.
        // Qty = count of SPEC_CODE per unique range.
        if (window.agingBarChart && window.agingBarChart.data) {
            // Try exact column name first, then fallback to keyword detection
            var rangeCol = headers.indexOf('RANGE') !== -1
                ? 'RANGE'
                : findColumnByKeyword(headers, ['range', 'aging', 'usia', 'umur']);

            // Find SPEC_CODE column for counting
            var specCodeColAging = headers.indexOf('SPEC_CODE') !== -1
                ? 'SPEC_CODE'
                : findColumnByKeyword(headers, ['spec_code', 'spec code', 'spek']);

            if (rangeCol) {
                // Group rows by unique RANGE
                var rangeGroups = {};
                for (var m = 0; m < sheetData.length; m++) {
                    var range = String(sheetData[m][rangeCol] || 'Unknown').trim();
                    if (!rangeGroups[range]) {
                        rangeGroups[range] = { data: [] };
                    }
                    rangeGroups[range].data.push(sheetData[m]);
                }

                var rangeLabels = Object.keys(rangeGroups);
                var rangeQtyData = [];
                window.agingBarChart._recordsPerIndex = [];

                for (var n = 0; n < rangeLabels.length; n++) {
                    var rGroupData = rangeGroups[rangeLabels[n]].data;
                    window.agingBarChart._recordsPerIndex.push(rGroupData);

                    // Qty = count of rows that have a SPEC_CODE value in this range group
                    var rQty = specCodeColAging
                        ? FormulaController.computeCount(rGroupData, specCodeColAging)
                        : FormulaController.computeCount(rGroupData);

                    rangeQtyData.push(rQty);
                }

                window.agingBarChart.data.labels = rangeLabels;
                window.agingBarChart.data.datasets[0].data = rangeQtyData;
                window.agingBarChart._chartTitle = "Aging Perangkat";
                window.agingBarChart.update();
                FormulaController.makeChartClickable(window.agingBarChart, "Aging Perangkat");
            }
        }

        // 9. UTILISASI AREA / RACK TABLE (Manual Input Data)
        var tbody = document.getElementById('table-utilisasi-area-body');
        if (tbody) {
            tbody.replaceChildren(); // clear first
            
            // Determine current period from the selected period text
            var currentPeriodEl = document.getElementById('selected-period-text');
            var currentPeriodStr = currentPeriodEl ? currentPeriodEl.textContent.trim() : '';
            
            // Parse "JUNE 2026" or "June 2026" into month and year
            var periodMonth = '';
            var periodYear = '';
            if (currentPeriodStr && currentPeriodStr !== 'PILIH DATA' && currentPeriodStr !== 'PILIH PERIODE DATA' && currentPeriodStr !== '-') {
                var periodParts = currentPeriodStr.split(' ');
                if (periodParts.length >= 2) {
                    // Capitalize first letter, lowercase rest for API
                    var rawMonth = periodParts[0];
                    periodMonth = rawMonth.charAt(0).toUpperCase() + rawMonth.slice(1).toLowerCase();
                    periodYear = periodParts[1];
                }
            }
            
            // Fetch manual utilisasi data for the selected period
            var utilisasiUrl = 'api/get_rack_utilisasi.php';
            if (periodMonth && periodYear) {
                utilisasiUrl += '?month=' + encodeURIComponent(periodMonth) + '&year=' + encodeURIComponent(periodYear);
            }
            
            fetch(utilisasiUrl)
                .then(function(response) { return response.json(); })
                .then(function(utilResult) {
                    var utilData = (utilResult.status === 'success' && utilResult.data) ? utilResult.data : [];
                    
                    // Group by rack_group for display
                    var rackGroups = {};
                    for (var i = 0; i < utilData.length; i++) {
                        var row = utilData[i];
                        var rackName = String(row.rack_group || row.label || 'Unknown').trim();
                        if (!rackGroups[rackName]) {
                            rackGroups[rackName] = { totalQty: 0, capacities: [], count: 0 };
                        }
                        rackGroups[rackName].totalQty += parseInt(row.qty) || 0;
                        rackGroups[rackName].capacities.push(parseFloat(row.capacity) || 0);
                        rackGroups[rackName].count++;
                    }
                    
                    var rackNames = Object.keys(rackGroups);
                    rackNames.sort();
                    
                    var greenCount = 0;
                    var yellowCount = 0;
                    var redCount = 0;
                    var rackCapacities = [];
                    
                    for (var q = 0; q < rackNames.length; q++) {
                        var rName = rackNames[q];
                        var group = rackGroups[rName];
                        var totalQty = group.totalQty;
                        
                        // Average capacity across all labels in this rack group
                        var avgCap = 0;
                        if (group.capacities.length > 0) {
                            var sumC = 0;
                            for (var c = 0; c < group.capacities.length; c++) {
                                sumC += group.capacities[c];
                            }
                            avgCap = Math.round(sumC / group.capacities.length);
                        }
                        if (avgCap > 100) avgCap = 100;
                        
                        rackCapacities.push(avgCap);
                        
                        var barColorClass;
                        if (avgCap <= 50) {
                            barColorClass = 'bg-success';
                            greenCount++;
                        } else if (avgCap <= 75) {
                            barColorClass = 'bg-warning';
                            yellowCount++;
                        } else {
                            barColorClass = 'bg-danger';
                            redCount++;
                        }
                        
                        var tr = document.createElement('tr');
                        tr.style.cursor = 'pointer';
                        tr.style.position = 'relative';
                        tr.setAttribute('title', rName + '  |  Qty: ' + totalQty + ' unit  |  Capacity: ' + avgCap + '%');
                        tr.setAttribute('data-toggle', 'tooltip');
                        tr.setAttribute('data-placement', 'top');
                        
                        // Rack/Area name
                        var tdName = document.createElement('td');
                        tdName.textContent = rName;
                        tdName.className = 'text-left';
                        tdName.style.fontSize = '0.85rem';
                        tdName.style.whiteSpace = 'nowrap';
                        
                        // Capacity column with progress bar
                        var tdCapacity = document.createElement('td');
                        var progressWrap = document.createElement('div');
                        progressWrap.className = 'd-flex align-items-center';
                        
                        var percentLabel = document.createElement('span');
                        percentLabel.className = 'mr-2 font-weight-bold';
                        percentLabel.style.minWidth = '38px';
                        percentLabel.style.fontSize = '0.8rem';
                        percentLabel.textContent = avgCap + '%';
                        
                        var progressOuter = document.createElement('div');
                        progressOuter.className = 'progress progress-sm flex-grow-1';
                        progressOuter.style.height = '10px';
                        progressOuter.style.borderRadius = '5px';
                        
                        var progressInner = document.createElement('div');
                        progressInner.className = 'progress-bar ' + barColorClass;
                        progressInner.setAttribute('role', 'progressbar');
                        progressInner.style.width = avgCap + '%';
                        progressInner.style.borderRadius = '5px';
                        progressInner.style.transition = 'width 0.6s ease';
                        
                        progressOuter.appendChild(progressInner);
                        progressWrap.appendChild(percentLabel);
                        progressWrap.appendChild(progressOuter);
                        tdCapacity.appendChild(progressWrap);
                        
                        tr.appendChild(tdName);
                        tr.appendChild(tdCapacity);
                        tbody.appendChild(tr);
                    }
                    
                    // Initialize Bootstrap tooltips on the rendered rows
                    if (typeof $ !== 'undefined') {
                        $('#table-utilisasi-area-body [data-toggle="tooltip"]').tooltip({ 
                            template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner bg-white text-dark border shadow-sm"></div></div>'
                        });
                    }
                    
                    // Update colored dot summary
                    var dotContainer = document.getElementById('rack-status-dots');
                    if (dotContainer) {
                        dotContainer.replaceChildren();
                        
                        function createDotBadge(colorClass, count) {
                            var badge = document.createElement('span');
                            badge.className = 'badge badge-pill mr-2 d-flex align-items-center';
                            badge.style.fontSize = '0.75rem';
                            badge.style.padding = '4px 10px';
                            
                            var dot = document.createElement('span');
                            dot.style.display = 'inline-block';
                            dot.style.width = '10px';
                            dot.style.height = '10px';
                            dot.style.borderRadius = '50%';
                            dot.style.marginRight = '5px';
                            
                            if (colorClass === 'success') {
                                dot.style.backgroundColor = '#1cc88a';
                                badge.style.backgroundColor = 'rgba(28, 200, 138, 0.15)';
                                badge.style.color = '#1cc88a';
                            } else if (colorClass === 'warning') {
                                dot.style.backgroundColor = '#f6c23e';
                                badge.style.backgroundColor = 'rgba(246, 194, 62, 0.15)';
                                badge.style.color = '#f6c23e';
                            } else {
                                dot.style.backgroundColor = '#e74a3b';
                                badge.style.backgroundColor = 'rgba(231, 74, 59, 0.15)';
                                badge.style.color = '#e74a3b';
                            }
                            
                            badge.appendChild(dot);
                            badge.appendChild(document.createTextNode(count));
                            return badge;
                        }
                        
                        dotContainer.appendChild(createDotBadge('success', greenCount));
                        dotContainer.appendChild(createDotBadge('warning', yellowCount));
                        dotContainer.appendChild(createDotBadge('danger', redCount));
                    }
                    
                    // Update UTILISASI SPACE & FREE SPACE cards
                    var avgCapacity = 0;
                    if (rackCapacities.length > 0) {
                        var sumCap = 0;
                        for (var t = 0; t < rackCapacities.length; t++) {
                            sumCap += rackCapacities[t];
                        }
                        avgCapacity = Math.round(sumCap / rackCapacities.length);
                    }
                    
                    var utilPercent = avgCapacity;
                    var freePercent = 100 - utilPercent;
                    
                    var cardUtilText = document.getElementById('card-utilisasi-space-text');
                    var cardUtilBar = document.getElementById('card-utilisasi-space-bar');
                    var cardFreeText = document.getElementById('card-free-space-text');
                    var cardFreeBar = document.getElementById('card-free-space-bar');
                    
                    if (cardUtilText) cardUtilText.textContent = utilPercent + '%';
                    if (cardUtilBar) {
                        cardUtilBar.style.width = utilPercent + '%';
                        cardUtilBar.setAttribute('aria-valuenow', utilPercent);
                        cardUtilBar.className = 'progress-bar ' + getUtilisasiClass(utilPercent);
                    }
                    
                    if (cardFreeText) cardFreeText.textContent = freePercent + '%';
                    if (cardFreeBar) {
                        cardFreeBar.style.width = freePercent + '%';
                        cardFreeBar.setAttribute('aria-valuenow', freePercent);
                        cardFreeBar.className = 'progress-bar ' + getFreeSpaceClass(freePercent);
                    }

                    // Update Dashboard Storage Utilization elements if present
                    var storageUtilRate = document.getElementById('storage-util-rate');
                    if (storageUtilRate) storageUtilRate.textContent = utilPercent + '%';

                    var storageUtilBar = document.getElementById('storage-util-bar');
                    if (storageUtilBar) {
                        storageUtilBar.style.width = utilPercent + '%';
                        if (utilPercent < 50) {
                            storageUtilBar.style.background = 'linear-gradient(90deg, #e74a3b 0%, #be2617 100%)';
                        } else if (utilPercent < 75) {
                            storageUtilBar.style.background = 'linear-gradient(90deg, #f6c23e 0%, #dfa827 100%)';
                        } else {
                            storageUtilBar.style.background = 'linear-gradient(90deg, #4e73df 0%, #224abe 100%)';
                        }
                    }

                    var storageTotalCap = document.getElementById('storage-total-capacity');
                    if (storageTotalCap) storageTotalCap.textContent = '100%';

                    var storageUsed = document.getElementById('storage-used');
                    if (storageUsed) storageUsed.textContent = utilPercent + '%';

                    var storageAvailable = document.getElementById('storage-available');
                    if (storageAvailable) storageAvailable.textContent = freePercent + '%';
                })
                .catch(function(err) { console.error('Error fetching utilisasi data:', err); });
        }
    };

    // Expose to window
    window.FormulaController = FormulaController;

})(window);
