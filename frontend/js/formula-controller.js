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

    // Excel =ISNUMBER() behavior
    FormulaController.isNumeric = function (raw) {
        if (raw === undefined || raw === null) return false;
        if (typeof raw === 'number') return !isNaN(raw);
        var str = String(raw).trim();
        if (!str || str === '-' || str.toLowerCase() === 'n/a' || str.toLowerCase() === 'null') return false;
        // Clean currency, spaces, and percentage signs
        var cleaned = str.replace(/[RrpP\s%]/g, '');
        if (!cleaned) return false;
        // If there are still alphabetic characters, it's text (e.g. "Quarterly", "Kabel"), not numeric
        if (/[a-zA-Z]/.test(cleaned)) return false;
        // Check standard decimal / integer or normalize Indonesian format
        var lastDot = cleaned.lastIndexOf('.');
        var lastComma = cleaned.lastIndexOf(',');
        if (lastComma > lastDot && lastDot !== -1) {
            cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
        } else if (lastDot > lastComma && lastComma !== -1) {
            cleaned = cleaned.replace(/,/g, '');
        } else if (lastDot !== -1 && lastComma === -1) {
            if (cleaned.split('.').length > 2 || cleaned.split('.')[1].length === 3) {
                cleaned = cleaned.replace(/\./g, '');
            }
        } else if (lastComma !== -1 && lastDot === -1) {
            if (cleaned.split(',').length > 2 || cleaned.split(',')[1].length === 3) {
                cleaned = cleaned.replace(/,/g, '');
            } else {
                cleaned = cleaned.replace(/,/g, '.');
            }
        }
        var val = parseFloat(cleaned);
        return !isNaN(val) && isFinite(val);
    };

    // Fast numeric parser with Indonesian and English currency/thousand-separator/percentage support
    FormulaController.parseNumeric = function (raw) {
        if (raw === undefined || raw === null || raw === '') return 0;
        if (typeof raw === 'number') return isNaN(raw) ? 0 : raw;
        var str = String(raw).trim();
        if (!str || str === '-' || str.toLowerCase() === 'n/a' || str.toLowerCase() === 'null') return 0;
        // Fast path for clean integers or standard decimals (e.g. from SQL)
        if (/^-?\d+(\.\d+)?$/.test(str)) {
            var n = parseFloat(str);
            return isNaN(n) ? 0 : n;
        }
        var isPercent = str.indexOf('%') !== -1;
        var cleaned = str.replace(/[RrpP\s%]/g, '');
        if (/[a-zA-Z]/.test(cleaned)) return 0; // Pure text cell

        var lastDot = cleaned.lastIndexOf('.');
        var lastComma = cleaned.lastIndexOf(',');
        if (lastComma > lastDot && lastDot !== -1) {
            cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
        } else if (lastDot > lastComma && lastComma !== -1) {
            cleaned = cleaned.replace(/,/g, '');
        } else if (lastDot !== -1 && lastComma === -1) {
            if (cleaned.split('.').length > 2 || cleaned.split('.')[1].length === 3) {
                cleaned = cleaned.replace(/\./g, '');
            }
        } else if (lastComma !== -1 && lastDot === -1) {
            if (cleaned.split(',').length > 2 || cleaned.split(',')[1].length === 3) {
                cleaned = cleaned.replace(/,/g, '');
            } else {
                cleaned = cleaned.replace(/,/g, '.');
            }
        }
        var val = parseFloat(cleaned);
        return isNaN(val) ? 0 : val;
    };

    // ── Excel Formula Implementations ─────────────────────────────

    // Excel =SUM(range): Sums numbers, ignoring text, dashes, and empty cells
    FormulaController.computeSum = function (data, columnName) {
        if (!data || !columnName) return 0;
        var sum = 0;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (FormulaController.isNumeric(raw)) {
                sum += FormulaController.parseNumeric(raw);
            }
        }
        return sum;
    };

    // Excel =COUNT(range): Counts ONLY cells containing numbers (ignores text, dashes, blanks)
    FormulaController.computeCount = function (data, columnName) {
        if (!data) return 0;
        if (!columnName) return data.length;
        var count = 0;
        for (var i = 0; i < data.length; i++) {
            if (FormulaController.isNumeric(data[i][columnName])) {
                count++;
            }
        }
        return count;
    };

    // Excel =COUNTA(range): Counts non-empty cells (includes numbers and text, excludes empty / '-')
    FormulaController.computeCountA = function (data, columnName) {
        if (!data) return 0;
        if (!columnName) return data.length;
        var count = 0;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (raw !== undefined && raw !== null) {
                var str = String(raw).trim();
                if (str !== '' && str !== '-' && str.toLowerCase() !== 'n/a' && str.toLowerCase() !== 'null') {
                    count++;
                }
            }
        }
        return count;
    };

    // Excel =AVERAGE(range): Sum of numeric cells divided by count of numeric cells
    // Completely ignores blank, text, '-', and 'N/A' cells. Does not divide by total rows.
    FormulaController.computeAverage = function (data, columnName) {
        if (!data || data.length === 0 || !columnName) return 0;
        var sum = 0;
        var count = 0;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (FormulaController.isNumeric(raw)) {
                sum += FormulaController.parseNumeric(raw);
                count++;
            }
        }
        return count > 0 ? (sum / count) : 0;
    };

    // Excel =MAX(range): Maximum numeric value in range, ignoring text and blanks
    FormulaController.computeMax = function (data, columnName) {
        if (!data || data.length === 0 || !columnName) return 0;
        var max = null;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (FormulaController.isNumeric(raw)) {
                var val = FormulaController.parseNumeric(raw);
                if (max === null || val > max) max = val;
            }
        }
        return max !== null ? max : 0;
    };

    // Excel =MIN(range): Minimum numeric value in range, ignoring text and blanks
    FormulaController.computeMin = function (data, columnName) {
        if (!data || data.length === 0 || !columnName) return 0;
        var min = null;
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (FormulaController.isNumeric(raw)) {
                var val = FormulaController.parseNumeric(raw);
                if (min === null || val < min) min = val;
            }
        }
        return min !== null ? min : 0;
    };

    // Excel =MEDIAN(range): Median value in range, ignoring text and blanks
    FormulaController.computeMedian = function (data, columnName) {
        if (!data || data.length === 0 || !columnName) return 0;
        var values = [];
        for (var i = 0; i < data.length; i++) {
            var raw = data[i][columnName];
            if (FormulaController.isNumeric(raw)) {
                values.push(FormulaController.parseNumeric(raw));
            }
        }
        if (values.length === 0) return 0;
        values.sort(function (a, b) { return a - b; });
        var half = Math.floor(values.length / 2);
        if (values.length % 2 !== 0) {
            return values[half];
        }
        return (values[half - 1] + values[half]) / 2.0;
    };

    // Excel =COUNTIF(range, criteria)
    FormulaController.computeCountIf = function (data, columnName, criteria) {
        if (!data || !columnName) return 0;
        var count = 0;
        var isFunc = typeof criteria === 'function';
        for (var i = 0; i < data.length; i++) {
            var val = data[i][columnName];
            if (isFunc) {
                if (criteria(val)) count++;
            } else if (String(val).trim().toLowerCase() === String(criteria).trim().toLowerCase()) {
                count++;
            }
        }
        return count;
    };

    // Excel =SUMIF(range, criteria, [sum_range])
    FormulaController.computeSumIf = function (data, criteriaCol, criteria, sumCol) {
        if (!data || !criteriaCol) return 0;
        var targetCol = sumCol || criteriaCol;
        var sum = 0;
        var isFunc = typeof criteria === 'function';
        for (var i = 0; i < data.length; i++) {
            var cVal = data[i][criteriaCol];
            var match = isFunc ? criteria(cVal) : (String(cVal).trim().toLowerCase() === String(criteria).trim().toLowerCase());
            if (match && FormulaController.isNumeric(data[i][targetCol])) {
                sum += FormulaController.parseNumeric(data[i][targetCol]);
            }
        }
        return sum;
    };

    // Excel =AVERAGEIF(range, criteria, [average_range])
    FormulaController.computeAverageIf = function (data, criteriaCol, criteria, avgCol) {
        if (!data || !criteriaCol) return 0;
        var targetCol = avgCol || criteriaCol;
        var sum = 0;
        var count = 0;
        var isFunc = typeof criteria === 'function';
        for (var i = 0; i < data.length; i++) {
            var cVal = data[i][criteriaCol];
            var match = isFunc ? criteria(cVal) : (String(cVal).trim().toLowerCase() === String(criteria).trim().toLowerCase());
            if (match && FormulaController.isNumeric(data[i][targetCol])) {
                sum += FormulaController.parseNumeric(data[i][targetCol]);
                count++;
            }
        }
        return count > 0 ? (sum / count) : 0;
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

    FormulaController.populateDetailTable = function(chartTitle, label, dataRows, isCategoryMode, totalQty, totalNbv) {
        var modalEl = document.getElementById('chartDetailModal');
        if (!modalEl) return;

        var titleEl = document.getElementById('chartDetailTitle');
        var subtitleEl = document.getElementById('chartDetailSubtitle');
        var countEl = document.getElementById('chartDetailRecordCount');

        if (titleEl) titleEl.textContent = chartTitle;

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

        if (typeof $ !== 'undefined' && $.fn.DataTable) {
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
                    search: "Search:",
                    searchPlaceholder: "Search...",
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
    };

    FormulaController.openDetailModal = function(chartTitle, label, records, chartType) {
        var modalEl = document.getElementById('chartDetailModal');
        if (!modalEl) return;

        var isCategoryMode = (chartTitle.indexOf('Perangkat') !== -1 || chartTitle.indexOf('IN') !== -1 || chartTitle.indexOf('OUT') !== -1);

        // Check if lazy-load is requested (summary mode)
        if (records && !Array.isArray(records) && records._lazy) {
            var lazyTotalQty = records.qty || 0;
            var lazyTotalNbv = records.nbv || 0;
            var detailType = records.type || chartType || (chartTitle.indexOf('Organization') !== -1 ? 'org' : (chartTitle.indexOf('Aging') !== -1 ? 'aging' : 'category'));

            FormulaController.populateDetailTable(chartTitle, label, [], isCategoryMode, lazyTotalQty, lazyTotalNbv);
            if (typeof $ !== 'undefined') {
                $('#chartDetailModal').modal('show');
            }

            var currentPeriodEl = document.getElementById('selected-period-text');
            var currentPeriodStr = currentPeriodEl ? currentPeriodEl.textContent.trim() : '';
            var fetchUrl = 'api/get_chart_detail.php?type=' + encodeURIComponent(detailType) + '&label=' + encodeURIComponent(label) + '&periode=' + encodeURIComponent(currentPeriodStr);

            fetch(fetchUrl)
                .then(function(res) { return res.json(); })
                .then(function(resData) {
                    var items = (resData && resData.status === 'success' && resData.data) ? resData.data : [];
                    var dataRows = [];
                    items.forEach(function(item, idx) {
                        var specCode = FormulaController.getFieldValue(item, ['spec_code', 'SPEC_CODE', 'spec code', 'spek']);
                        var regNo = FormulaController.getFieldValue(item, ['reg_no', 'REG_NO', 'reg no', 'register']);
                        var specName = FormulaController.getFieldValue(item, ['spec_name', 'SPEC_NAME', 'spec name', 'nama', 'perangkat', 'item']);
                        var categoryVal = FormulaController.getFieldValue(item, ['category', 'CATEGORY', 'kategori', 'range', 'status', 'STATUS']);
                        var rawNbv = FormulaController.getFieldValue(item, ['nbv', 'NBV', 'value', 'harga', 'price']);

                        var numNbv = parseFloat(String(rawNbv).replace(/[^0-9.-]+/g, '')) || 0;

                        dataRows.push({
                            no: idx + 1,
                            spec_code: specCode,
                            reg_no: regNo,
                            spec_name: specName,
                            category: categoryVal || '-',
                            nbv_num: numNbv,
                            nbv_formatted: formatCurrency(numNbv)
                        });
                    });
                    FormulaController.populateDetailTable(chartTitle, label, dataRows, isCategoryMode, lazyTotalQty, lazyTotalNbv);
                })
                .catch(function(err) {
                    console.error('Failed to load chart detail rows:', err);
                });
            return;
        }

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

                dataRows.push({
                    no: idx + 1,
                    spec_code: specCode,
                    reg_no: regNo,
                    spec_name: specName,
                    category: categoryVal || '-',
                    nbv_num: numNbv,
                    nbv_formatted: formatCurrency(numNbv)
                });
            });
        }

        FormulaController.populateDetailTable(chartTitle, label, dataRows, isCategoryMode, totalQty, totalNbv);
        if (typeof $ !== 'undefined') {
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
        if (!chartInstance.$datalabels) {
            chartInstance.$datalabels = { _listened: true };
        }
        var canvas = chartInstance.canvas;
        
        if (canvas._hasClickListener) return;
        canvas._hasClickListener = true;

        canvas.style.cursor = 'pointer';

        canvas.addEventListener('mousemove', function(e) {
            try {
                if (!chartInstance.$datalabels) chartInstance.$datalabels = { _listened: true };
                var index = FormulaController.getClickedChartIndex(chartInstance, e);
                canvas.style.cursor = (index !== -1) ? 'pointer' : 'default';
            } catch (err) {
                // Ignore chart interaction errors
            }
        });

        canvas.addEventListener('click', function(e) {
            try {
                if (!chartInstance.$datalabels) chartInstance.$datalabels = { _listened: true };
                var index = FormulaController.getClickedChartIndex(chartInstance, e);
                if (index !== -1 && chartInstance.data && chartInstance.data.labels && index < chartInstance.data.labels.length) {
                    var label = chartInstance.data.labels[index];
                    var title = chartInstance._chartTitle || defaultTitle || 'Detail Data';
                    var records = (chartInstance._recordsPerIndex && chartInstance._recordsPerIndex[index]) ? chartInstance._recordsPerIndex[index] : [];
                    var chartType = chartInstance._chartType || null;

                    FormulaController.openDetailModal(title, label, records, chartType);
                }
            } catch (err) {
                console.warn('Error handling chart click:', err);
            }
        });
    };

    // Main entry point to update cards
    // allPeriodTotals: { total_asset: N, total_nbv: N } from get_dashboard_totals.php (optional)
    // summary: server-computed SQL aggregation object from get_data.php (optional, ultra fast)
    FormulaController.updateDashboardCards = function (sheetData, headers, allPeriodTotals, summary) {
        if (!summary && sheetData && sheetData.summary) {
            summary = sheetData.summary;
            sheetData = sheetData.data || [];
        }

        if ((!sheetData || sheetData.length === 0) && !summary) {
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
            
            // Clear charts
            try {
                if (window.myBarChart && window.myBarChart.data) { if (!window.myBarChart.$datalabels) window.myBarChart.$datalabels = { _listened: true }; window.myBarChart.data.labels = []; window.myBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.myBarChart.update(); }
            } catch (e) { }
            try {
                if (window.myHorizontalBarChart && window.myHorizontalBarChart.data) { if (!window.myHorizontalBarChart.$datalabels) window.myHorizontalBarChart.$datalabels = { _listened: true }; window.myHorizontalBarChart.data.labels = []; window.myHorizontalBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.myHorizontalBarChart.update(); }
            } catch (e) { }
            try {
                if (window.agingBarChart && window.agingBarChart.data) { if (!window.agingBarChart.$datalabels) window.agingBarChart.$datalabels = { _listened: true }; window.agingBarChart.data.labels = []; window.agingBarChart.data.datasets.forEach(function(d) { d.data = []; }); window.agingBarChart.update(); }
            } catch (e) { }
            try {
                if (window.perangkatInChart && window.perangkatInChart.data) { if (!window.perangkatInChart.$datalabels) window.perangkatInChart.$datalabels = { _listened: true }; window.perangkatInChart.data.labels = []; window.perangkatInChart.data.datasets.forEach(function(d) { d.data = []; }); window.perangkatInChart.update(); }
            } catch (e) { }
            try {
                if (window.perangkatOutChart && window.perangkatOutChart.data) { if (!window.perangkatOutChart.$datalabels) window.perangkatOutChart.$datalabels = { _listened: true }; window.perangkatOutChart.data.labels = []; window.perangkatOutChart.data.datasets.forEach(function(d) { d.data = []; }); window.perangkatOutChart.update(); }
            } catch (e) { }
            
            // Clear table
            var tbody = document.getElementById('table-utilisasi-area-body');
            if (tbody) tbody.replaceChildren();
            var dotContainer = document.getElementById('rack-status-dots');
            if (dotContainer) dotContainer.replaceChildren();

            // Clear Inbound Summary
            if (window.updateInboundFlowPieChart) {
                try { window.updateInboundFlowPieChart([0, 0, 0]); } catch (e) {}
            }
            var flowTotalGr = document.getElementById('flow-total-gr');
            if (flowTotalGr) flowTotalGr.textContent = '0 GR';
            var flowDoneCount = document.getElementById('flow-done-count');
            if (flowDoneCount) flowDoneCount.textContent = '0 Unit';

            // Clear Storage Summary
            if (window.updateInventorySummaryPieChart) {
                try { window.updateInventorySummaryPieChart(0, 0); } catch (e) {}
            }

            return;
        }

        // ── High-speed path using server SQL aggregate summary (per selected period) ──
        if (summary) {
            console.log("Formula Controller: Updating cards and charts from server SQL summary...");
            var totalAsset = (summary.total_asset !== undefined) ? summary.total_asset : (summary.total_qty || 0);
            var totalNbv = (summary.total_nbv !== undefined) ? summary.total_nbv : 0;

            var cardAsset = document.getElementById('card-total-asset');
            if (cardAsset) cardAsset.textContent = formatNumber(totalAsset);

            var cardNbv = document.getElementById('card-total-nbv');
            if (cardNbv) cardNbv.textContent = formatCurrency(totalNbv);

            var invTotalEl = document.getElementById('inv-total-perangkat');
            if (invTotalEl) invTotalEl.textContent = formatNumber(totalAsset) + ' Unit';

            var invNbvEl = document.getElementById('inv-total-nbv');
            if (invNbvEl) invNbvEl.textContent = formatCurrency(totalNbv);

            var elLess3m = document.getElementById('inv-aging-less-3m');
            if (elLess3m) elLess3m.textContent = formatNumber(summary.less_3m || 0) + ' Unit';

            var el3to12m = document.getElementById('inv-aging-3-12m');
            if (el3to12m) el3to12m.textContent = formatNumber(summary.m3_to_12 || 0) + ' Unit';

            var elMore12m = document.getElementById('inv-aging-more-12m');
            if (elMore12m) elMore12m.textContent = formatNumber(summary.more_12m || 0) + ' Unit';

            var elReUse = document.getElementById('inv-re-useg');
            if (elReUse) elReUse.textContent = formatNumber(summary.reuse || 0) + ' Unit';

            if (window.updateInventorySummaryPieChart) {
                window.updateInventorySummaryPieChart(totalAsset, totalNbv);
            }

            // 1. Storage Berdasarkan Aging (myBarChart)
            if (window.myBarChart && window.myBarChart.data && summary.aging_chart) {
                var agingLabels = summary.aging_chart.map(function(item) { return item.range; });
                var agingQtyData = summary.aging_chart.map(function(item) { return item.qty; });
                var agingNbvData = summary.aging_chart.map(function(item) { return item.nbv; });

                window.myBarChart.data.labels = agingLabels;
                window.myBarChart.data.datasets[0].data = agingQtyData;
                window.myBarChart.data.datasets[1].data = agingNbvData;
                window.myBarChart._recordsPerIndex = summary.aging_chart.map(function(item) {
                    return { _lazy: true, type: 'aging', label: item.range, qty: item.qty, nbv: item.nbv };
                });
                window.myBarChart._chartTitle = "STORAGE - Berdasarkan Aging";
                window.myBarChart._chartType = "aging";
                try {
                    if (!window.myBarChart.$datalabels) window.myBarChart.$datalabels = { _listened: true };
                    window.myBarChart.update();
                    FormulaController.makeChartClickable(window.myBarChart, "STORAGE - Berdasarkan Aging");
                } catch (e) { console.warn('myBarChart update error:', e); }
            }

            // 2. Berdasarkan Asset Organization (myHorizontalBarChart)
            if (window.myHorizontalBarChart && window.myHorizontalBarChart.data && summary.org_chart) {
                var orgLabels = summary.org_chart.map(function(item) {
                    var name = item.org || item.label;
                    if (!name || String(name).trim() === '') return 'Tanpa Organization';
                    return String(name).trim();
                });
                var orgQtyData = summary.org_chart.map(function(item) { return parseInt(item.qty, 10) || 0; });
                var orgNbvData = summary.org_chart.map(function(item) { return parseFloat(item.nbv) || 0; });

                window.myHorizontalBarChart.data.labels = orgLabels;
                window.myHorizontalBarChart.data.datasets[0].data = orgQtyData;
                window.myHorizontalBarChart.data.datasets[1].data = orgNbvData;
                window.myHorizontalBarChart._recordsPerIndex = summary.org_chart.map(function(item) {
                    var name = item.org || item.label;
                    if (!name || String(name).trim() === '') name = 'Tanpa Organization';
                    return { _lazy: true, type: 'org', label: name, qty: item.qty, nbv: item.nbv };
                });

                var chartContainer = document.getElementById('horizontalBarChartContainer');
                if (chartContainer) {
                    var minHeight = 320;
                    var perLabelHeight = 42;
                    var dynamicHeight = Math.max(minHeight, orgLabels.length * perLabelHeight);
                    chartContainer.style.height = dynamicHeight + 'px';
                }

                window.myHorizontalBarChart._chartTitle = "Berdasarkan Asset Organization";
                window.myHorizontalBarChart._chartType = "org";
                try {
                    if (!window.myHorizontalBarChart.$datalabels) window.myHorizontalBarChart.$datalabels = { _listened: true };
                    window.myHorizontalBarChart.update();
                    FormulaController.makeChartClickable(window.myHorizontalBarChart, "Berdasarkan Asset Organization");
                } catch (e) { console.warn('myHorizontalBarChart update error:', e); }
            }

            // 3. Aging Perangkat (agingBarChart)
            if (window.agingBarChart && window.agingBarChart.data && summary.aging_chart) {
                var agBarLabels = summary.aging_chart.map(function(item) {
                    var name = item.range || item.label;
                    if (!name || String(name).trim() === '') return 'Unassigned';
                    return String(name).trim();
                });
                var agBarQty = summary.aging_chart.map(function(item) { return parseInt(item.qty, 10) || 0; });

                window.agingBarChart.data.labels = agBarLabels;
                window.agingBarChart.data.datasets[0].data = agBarQty;
                window.agingBarChart._recordsPerIndex = summary.aging_chart.map(function(item) {
                    var name = item.range || item.label || 'Unassigned';
                    return { _lazy: true, type: 'aging', label: name, qty: item.qty, nbv: item.nbv };
                });
                window.agingBarChart._chartTitle = "Aging Perangkat";
                window.agingBarChart._chartType = "aging";
                try {
                    if (!window.agingBarChart.$datalabels) window.agingBarChart.$datalabels = { _listened: true };
                    window.agingBarChart.update();
                    FormulaController.makeChartClickable(window.agingBarChart, "Aging Perangkat");
                } catch (e) { console.warn('agingBarChart update error:', e); }
            }

            var periodText = document.getElementById('selected-period-text') ? document.getElementById('selected-period-text').textContent.trim() : "Bulan X";
            var mName = '';
            var yr = '';
            if (periodText) {
                var mMatch = periodText.match(/\b(Januari|January|Jan|Februari|February|Feb|Maret|March|Mar|April|Apr|Mei|May|Juni|June|Jun|Juli|July|Jul|Agustus|August|Agu|Aug|September|Sep|Oktober|October|Okt|Oct|November|Nov|Nop|Desember|December|Des|Dec)\b/i);
                var yMatch = periodText.match(/\b(20\d{2})\b/);
                if (mMatch) mName = mMatch[1];
                if (yMatch) yr = yMatch[1];
            }
            var pinTitle = document.getElementById('perangkat-in-title-period');
            if (pinTitle && yr) {
                pinTitle.textContent = "Tahun " + yr;
            }
            var poutTitle = document.getElementById('perangkat-out-title-period');
            if (poutTitle && yr) {
                poutTitle.textContent = "Tahun " + yr;
            }

            // 5. Rack utilisasi
            FormulaController.loadRackUtilisasi(mName, yr);
            return;
        }

        console.log("Formula Controller: Updating cards from raw data...", headers);

        // Detect relevant columns once
        var assetCol = findColumnByKeyword(headers, ['spec_name', 'spec name', 'nama perangkat', 'perangkat', 'nama', 'asset']);
        var nbvCol = FormulaController.findBestColumn(headers, ['NBV', 'nbv'], ['nbv', 'value', 'harga', 'price', 'total']);
        var rangeCol = FormulaController.findBestColumn(headers, ['range', 'RANGE', 'aging_range', 'AGING_RANGE'], ['range', 'aging', 'usia', 'umur']);
        var catCol = FormulaController.findBestColumn(headers, ['category', 'CATEGORY', 'kategori', 'KATEGORI'], ['category', 'kategori', 'status']);
        var orgCol = FormulaController.findBestColumn(headers, ['ASSET_PLANNER_ORGANIZATION', 'asset_planner_organization'], ['asset_planner_organization', 'department', 'dept', 'organization', 'unit', 'pemilik', 'owner', 'divisi']);
        var statusCol = FormulaController.findBestColumn(headers, ['status', 'STATUS', 'Status'], ['status']);

        var totalAssetCalculated = sheetData.length;
        var totalNbvCalculated = 0;
        var cLess3m = 0, c3to12m = 0, cMore12m = 0, cReUse = 0;
        var countIn = 0, countOut = 0;
        var agingGroups = {};
        var orgGroups = {};

        // ── Single high-speed pass through all rows ──
        for (var sd = 0; sd < sheetData.length; sd++) {
            var row = sheetData[sd];
            var numNbv = nbvCol ? FormulaController.parseNumeric(row[nbvCol]) : 0;
            totalNbvCalculated += numNbv;

            // 1. Aging Range Grouping
            var rKey = rangeCol ? String(row[rangeCol] || 'Unknown').trim() : 'Unknown';
            if (!rKey) rKey = 'Unknown';
            if (!agingGroups[rKey]) {
                agingGroups[rKey] = { qty: 0, nbv: 0, records: [] };
            }
            agingGroups[rKey].qty++;
            agingGroups[rKey].nbv += numNbv;
            if (agingGroups[rKey].records.length < 500) {
                agingGroups[rKey].records.push(row);
            }

            // 2. Organization Grouping
            var oKey = orgCol ? String(row[orgCol] || '').trim() : '';
            if (!oKey || oKey.toLowerCase() === 'unknown') oKey = 'Tanpa Organization';
            if (!orgGroups[oKey]) {
                orgGroups[oKey] = { qty: 0, nbv: 0, records: [] };
            }
            orgGroups[oKey].qty++;
            orgGroups[oKey].nbv += numNbv;
            if (orgGroups[oKey].records.length < 500) {
                orgGroups[oKey].records.push(row);
            }

            // 3. Aging categorization for summary cards
            var rVal = rKey.toLowerCase();
            if (rVal.indexOf('>2') !== -1 || rVal.indexOf('> 2') !== -1 || rVal.indexOf('2 - 3') !== -1 || rVal.indexOf('2-3') !== -1 || rVal.indexOf('> 2 tahun') !== -1 || rVal.indexOf('>2 tahun') !== -1) {
                cMore12m++;
            } else if (rVal.indexOf('<1') !== -1 || rVal.indexOf('< 1') !== -1 || rVal.indexOf('< 3') !== -1 || rVal.indexOf('<3') !== -1 || rVal.indexOf('< 1 tahun') !== -1 || rVal.indexOf('<1 tahun') !== -1 || rVal.indexOf('<') !== -1) {
                cLess3m++;
            } else if (rVal.indexOf('>1') !== -1 || rVal.indexOf('> 1') !== -1 || rVal.indexOf('1-2') !== -1 || rVal.indexOf('1 - 2') !== -1 || rVal.indexOf('3-12') !== -1 || rVal.indexOf('3 - 12') !== -1 || rVal.indexOf('1 tahun') !== -1) {
                c3to12m++;
            } else if (rVal.indexOf('>') !== -1) {
                cMore12m++;
            }

            // 4. Category categorization for summary cards
            var cVal = catCol ? String(row[catCol] || '').trim().toLowerCase() : '';
            if (cVal.indexOf('re-use') !== -1 || cVal.indexOf('reuse') !== -1 || cVal.indexOf('need to utilize') !== -1 || cVal.indexOf('slow moving') !== -1) {
                cReUse++;
            }

            // 5. IN / OUT status
            if (statusCol) {
                var st = String(row[statusCol] || '').trim().toUpperCase();
                if (st === 'IN') countIn++;
                else if (st === 'OUT') countOut++;
            }
        }

        // 1. TOTAL ASSET (all rows in chosen period)
        var totalAsset = (summary && summary.total_asset !== undefined) ? summary.total_asset : totalAssetCalculated;
        var cardAsset = document.getElementById('card-total-asset');
        if (cardAsset) cardAsset.textContent = formatNumber(totalAsset);

        // 2. TOTAL NBV (sum of nbv in chosen period)
        var totalNbv = (summary && summary.total_nbv !== undefined) ? summary.total_nbv : totalNbvCalculated;
        var cardNbv = document.getElementById('card-total-nbv');
        if (cardNbv) cardNbv.textContent = formatCurrency(totalNbv);

        // Update Dashboard Overview Storage Summary elements if present
        var invTotalEl = document.getElementById('inv-total-perangkat');
        if (invTotalEl) invTotalEl.textContent = formatNumber(totalAsset) + ' Unit';

        var invNbvEl = document.getElementById('inv-total-nbv');
        if (invNbvEl) invNbvEl.textContent = formatCurrency(totalNbv);

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

        // 6. STORAGE BERDASARKAN AGING CHART (myBarChart)
        if (window.myBarChart && window.myBarChart.data) {
            var agingLabels = Object.keys(agingGroups);
            if (agingLabels.length > 0) {
                var agingQtyData = [];
                var agingNbvData = [];
                window.myBarChart._recordsPerIndex = [];

                for (var j = 0; j < agingLabels.length; j++) {
                    var agKey = agingLabels[j];
                    agingQtyData.push(agingGroups[agKey].qty);
                    agingNbvData.push(agingGroups[agKey].nbv);
                    window.myBarChart._recordsPerIndex.push(agingGroups[agKey].records);
                }

                window.myBarChart.data.labels = agingLabels;
                window.myBarChart.data.datasets[0].data = agingQtyData;
                window.myBarChart.data.datasets[1].data = agingNbvData;
                window.myBarChart._chartTitle = "STORAGE - Berdasarkan Aging";
                try {
                    if (!window.myBarChart.$datalabels) window.myBarChart.$datalabels = { _listened: true };
                    window.myBarChart.update();
                    FormulaController.makeChartClickable(window.myBarChart, "STORAGE - Berdasarkan Aging");
                } catch (e) { console.warn('myBarChart update error:', e); }
            } else {
                window.myBarChart.data.labels = ["Total"];
                window.myBarChart.data.datasets[0].data = [totalAsset];
                window.myBarChart.data.datasets[1].data = [totalNbv];
                window.myBarChart._recordsPerIndex = [sheetData.slice(0, 500)];
                window.myBarChart._chartTitle = "STORAGE - Berdasarkan Aging";
                try {
                    if (!window.myBarChart.$datalabels) window.myBarChart.$datalabels = { _listened: true };
                    window.myBarChart.update();
                    FormulaController.makeChartClickable(window.myBarChart, "STORAGE - Berdasarkan Aging");
                } catch (e) { console.warn('myBarChart fallback update error:', e); }
            }
        }

        // 7. BERDASARKAN ASSET ORGANIZATION (myHorizontalBarChart)
        if (window.myHorizontalBarChart && window.myHorizontalBarChart.data) {
            var rawOrgKeys = Object.keys(orgGroups);
            if (rawOrgKeys.length > 0) {
                var orgList = [];
                for (var l = 0; l < rawOrgKeys.length; l++) {
                    var orgName = rawOrgKeys[l];
                    orgList.push({
                        label: orgName,
                        qty: orgGroups[orgName].qty,
                        nbv: orgGroups[orgName].nbv,
                        records: orgGroups[orgName].records
                    });
                }

                orgList.sort(function (a, b) {
                    if (b.qty !== a.qty) return b.qty - a.qty;
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

                var chartContainer = document.getElementById('horizontalBarChartContainer');
                if (chartContainer) {
                    var minHeight = 320;
                    var perLabelHeight = 35;
                    var dynamicHeight = Math.max(minHeight, orgLabels.length * perLabelHeight);
                    chartContainer.style.height = dynamicHeight + 'px';
                }

                window.myHorizontalBarChart._chartTitle = "Berdasarkan Asset Organization";
                try {
                    if (!window.myHorizontalBarChart.$datalabels) window.myHorizontalBarChart.$datalabels = { _listened: true };
                    window.myHorizontalBarChart.update();
                    FormulaController.makeChartClickable(window.myHorizontalBarChart, "Berdasarkan Asset Organization");
                } catch (e) { console.warn('myHorizontalBarChart update error:', e); }
            } else {
                window.myHorizontalBarChart.data.labels = ["Total"];
                window.myHorizontalBarChart.data.datasets[0].data = [totalAsset];
                window.myHorizontalBarChart.data.datasets[1].data = [totalNbv];
                window.myHorizontalBarChart._recordsPerIndex = [sheetData.slice(0, 500)];
                window.myHorizontalBarChart._chartTitle = "Berdasarkan Asset Organization";
                try {
                    if (!window.myHorizontalBarChart.$datalabels) window.myHorizontalBarChart.$datalabels = { _listened: true };
                    window.myHorizontalBarChart.update();
                    FormulaController.makeChartClickable(window.myHorizontalBarChart, "Berdasarkan Asset Organization");
                } catch (e) { console.warn('myHorizontalBarChart fallback update error:', e); }
            }
        }

        // 8. Header / Title Periods
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
        if (window.agingBarChart && window.agingBarChart.data) {
            var agBarLabels = Object.keys(agingGroups);
            var agBarQty = [];
            window.agingBarChart._recordsPerIndex = [];
            for (var ab = 0; ab < agBarLabels.length; ab++) {
                var abKey = agBarLabels[ab];
                agBarQty.push(agingGroups[abKey].qty);
                window.agingBarChart._recordsPerIndex.push(agingGroups[abKey].records);
            }
            window.agingBarChart.data.labels = agBarLabels;
            window.agingBarChart.data.datasets[0].data = agBarQty;
            window.agingBarChart._chartTitle = "Aging Perangkat";
            try {
                if (!window.agingBarChart.$datalabels) window.agingBarChart.$datalabels = { _listened: true };
                window.agingBarChart.update();
                FormulaController.makeChartClickable(window.agingBarChart, "Aging Perangkat");
            } catch (e) { console.warn('agingBarChart update error:', e); }
        }

        // 10. UTILISASI AREA / RACK TABLE & CARDS
        FormulaController.loadRackUtilisasi();
    };

    FormulaController.resetRackUtilisasi = function () {
        var tbody = document.getElementById('table-utilisasi-area-body');
        if (tbody) tbody.replaceChildren();
        var dotContainer = document.getElementById('rack-status-dots');
        if (dotContainer) dotContainer.replaceChildren();
        var cardUtilText = document.getElementById('card-utilisasi-space-text');
        var cardUtilBar = document.getElementById('card-utilisasi-space-bar');
        var cardFreeText = document.getElementById('card-free-space-text');
        var cardFreeBar = document.getElementById('card-free-space-bar');
        if (cardUtilText) cardUtilText.textContent = '0%';
        if (cardUtilBar) { cardUtilBar.style.width = '0%'; cardUtilBar.className = 'progress-bar bg-danger'; }
        if (cardFreeText) cardFreeText.textContent = '0%';
        if (cardFreeBar) { cardFreeBar.style.width = '0%'; cardFreeBar.className = 'progress-bar bg-danger'; }
    };

    FormulaController.loadRackUtilisasi = function (periodMonth, periodYear) {
        var tbody = document.getElementById('table-utilisasi-area-body');
        if (!tbody && !document.getElementById('card-utilisasi-space-text')) return;

        // Helper for Utilisasi progress bar colors: Green <= 50%, Warning <= 75%, Danger > 75%
        function getUtilClass(percent) {
            if (percent <= 50) return 'bg-success';
            if (percent <= 75) return 'bg-warning';
            return 'bg-danger';
        }

        // Helper for Free Space progress bar colors: Danger <= 24%, Warning <= 49%, Success >= 50%
        function getFreeClass(percent) {
            if (percent <= 24) return 'bg-danger';
            if (percent <= 49) return 'bg-warning';
            return 'bg-success';
        }

        // Determine current period if not passed
        if (!periodMonth || !periodYear) {
            // Priority 1: Check the active period displayed on the navbar/header
            var currentPeriodEl = document.getElementById('selected-period-text');
            var currentPeriodStr = currentPeriodEl ? currentPeriodEl.textContent.trim() : '';
            if (currentPeriodStr && currentPeriodStr !== 'PILIH DATA' && currentPeriodStr !== 'PILIH PERIODE DATA' && currentPeriodStr !== '-') {
                var mMatch = currentPeriodStr.match(/\b(Januari|January|Jan|Februari|February|Feb|Maret|March|Mar|April|Apr|Mei|May|Juni|June|Jun|Juli|July|Jul|Agustus|August|Agu|Aug|September|Sep|Oktober|October|Okt|Oct|November|Nov|Nop|Desember|December|Des|Dec)\b/i);
                var yMatch = currentPeriodStr.match(/\b(20\d{2})\b/);
                if (mMatch) {
                    periodMonth = mMatch[1];
                }
                if (yMatch) {
                    periodYear = yMatch[1];
                }
            }
            // Priority 2: Fallback to dropdown elements
            if (!periodMonth || !periodYear) {
                var mSel = document.getElementById('period-month-select');
                var ySel = document.getElementById('period-year-select');
                if (mSel && mSel.value && ySel && ySel.value) {
                    periodMonth = mSel.value;
                    periodYear = ySel.value;
                }
            }
        }

        // Normalize month name (English, Indonesian, 3-letter abbreviation)
        var monthNamesMap = {
            'JAN': 'January', 'JANUARI': 'January', 'JANUARY': 'January',
            'FEB': 'February', 'FEBRUARI': 'February', 'FEBRUARY': 'February',
            'MAR': 'March', 'MARET': 'March', 'MARCH': 'March',
            'APR': 'April', 'APRIL': 'April',
            'MAY': 'May', 'MEI': 'May',
            'JUN': 'June', 'JUNI': 'June', 'JUNE': 'June',
            'JUL': 'July', 'JULI': 'July', 'JULY': 'July',
            'AUG': 'August', 'AGU': 'August', 'AGUSTUS': 'August', 'AUGUST': 'August',
            'SEP': 'September', 'SEPTEMBER': 'September',
            'OCT': 'October', 'OKT': 'October', 'OKTOBER': 'October', 'OCTOBER': 'October',
            'NOV': 'November', 'NOP': 'November', 'NOVEMBER': 'November',
            'DEC': 'December', 'DES': 'December', 'DESEMBER': 'December', 'DECEMBER': 'December'
        };
        var upM = String(periodMonth || '').trim().toUpperCase();
        if (monthNamesMap[upM]) {
            periodMonth = monthNamesMap[upM];
        }

        if (!periodMonth || !periodYear) {
            FormulaController.resetRackUtilisasi();
            return;
        }

        var utilisasiUrl = 'api/get_rack_utilisasi.php?month=' + encodeURIComponent(periodMonth) + '&year=' + encodeURIComponent(periodYear);
        fetch(utilisasiUrl)
            .then(function(response) { return response.json(); })
            .then(function(utilResult) {
                var utilData = (utilResult.status === 'success' && utilResult.data) ? utilResult.data : [];
                if (tbody) tbody.replaceChildren();

                // 1. Calculate overall average capacity across measured storage rows (Excel behavior: 36,440 / 444 = 82%)
                var totalCapSum = 0;
                var storageCount = 0;
                for (var i = 0; i < utilData.length; i++) {
                    var cat = String(utilData[i].category || '').trim().toUpperCase();
                    if (cat !== 'QUARTERLY' && cat !== 'KABEL') {
                        storageCount++;
                    }
                    var cNum = parseFloat(utilData[i].capacity);
                    if (!isNaN(cNum)) {
                        totalCapSum += cNum;
                    }
                }
                var denom = storageCount > 0 ? storageCount : utilData.length;
                var avgNum = (utilResult.summary && typeof utilResult.summary.avg_utilization !== 'undefined')
                    ? parseFloat(utilResult.summary.avg_utilization)
                    : (denom > 0 ? (totalCapSum / denom) : 0);
                avgNum = Math.max(0, Math.min(100, avgNum));

                var roundedInt = Math.round(avgNum);
                var utilDisplay = (Math.abs(avgNum - roundedInt) < 0.15) ? roundedInt.toString() : avgNum.toFixed(1);
                var freeNum = Math.max(0, 100 - avgNum);
                var freeRoundedInt = Math.round(freeNum);
                var freeDisplay = (Math.abs(freeNum - freeRoundedInt) < 0.15) ? freeRoundedInt.toString() : freeNum.toFixed(1);
                var utilPercent = Math.round(avgNum);
                var freePercent = Math.round(freeNum);

                // Update UTILISASI SPACE & FREE SPACE cards
                var cardUtilText = document.getElementById('card-utilisasi-space-text');
                var cardUtilBar = document.getElementById('card-utilisasi-space-bar');
                var cardFreeText = document.getElementById('card-free-space-text');
                var cardFreeBar = document.getElementById('card-free-space-bar');

                if (cardUtilText) cardUtilText.textContent = utilDisplay + '%';
                if (cardUtilBar) {
                    cardUtilBar.style.width = avgNum + '%';
                    cardUtilBar.setAttribute('aria-valuenow', Math.round(avgNum));
                    cardUtilBar.className = 'progress-bar ' + getUtilClass(Math.round(avgNum));
                }

                if (cardFreeText) cardFreeText.textContent = freeDisplay + '%';
                if (cardFreeBar) {
                    cardFreeBar.style.width = freeNum + '%';
                    cardFreeBar.setAttribute('aria-valuenow', Math.round(freeNum));
                    cardFreeBar.className = 'progress-bar ' + getFreeClass(Math.round(freeNum));
                }

                // 2. Group by rack_group for table display
                var rackGroups = {};
                for (var j = 0; j < utilData.length; j++) {
                    var row = utilData[j];
                    var rackName = String(row.rack_group || row.name || row.label || 'Unknown').trim();
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

                for (var q = 0; q < rackNames.length; q++) {
                    var rName = rackNames[q];
                    var group = rackGroups[rName];
                    var totalQty = group.totalQty;

                    var avgCap = 0;
                    if (group.capacities.length > 0) {
                        var sumC = 0;
                        for (var c = 0; c < group.capacities.length; c++) {
                            sumC += group.capacities[c];
                        }
                        avgCap = Math.round(sumC / group.capacities.length);
                    }
                    if (avgCap > 100) avgCap = 100;

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

                    if (tbody) {
                        var tr = document.createElement('tr');
                        tr.style.cursor = 'pointer';
                        tr.style.position = 'relative';
                        tr.setAttribute('title', rName + '  |  Qty: ' + totalQty + ' unit  |  Capacity: ' + avgCap + '%');
                        tr.setAttribute('data-toggle', 'tooltip');
                        tr.setAttribute('data-placement', 'top');

                        (function(clickRackName) {
                            tr.addEventListener('click', function() {
                                FormulaController.openRackDetailModal(clickRackName, periodMonth, periodYear);
                            });
                        })(rName);

                        var tdName = document.createElement('td');
                        tdName.textContent = rName;
                        tdName.className = 'text-left';
                        tdName.style.fontSize = '0.85rem';
                        tdName.style.whiteSpace = 'nowrap';

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
                }

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
    };

    /**
     * Open rack detail modal — shows all individual labels for a given rack name
     * with their CAP percentage for the specified period.
     */
    FormulaController.openRackDetailModal = function(rackName, month, year) {
        var modalEl = document.getElementById('rackDetailModal');
        if (!modalEl) return;

        var titleEl = document.getElementById('rackDetailModalLabel');
        if (titleEl) {
            titleEl.innerHTML = '<i class="fas fa-warehouse mr-1"></i> ' + escapeHtml(rackName);
        }

        var subtitleEl = document.getElementById('rackDetailSubtitle');
        if (subtitleEl) {
            var periodLabel = (month && year) ? (month + ' ' + year) : 'Latest';
            subtitleEl.innerHTML = '<span class="badge badge-primary mr-2" style="font-size: 0.72rem; padding: 3px 8px;"><i class="fas fa-calendar-alt mr-1"></i>Period: ' + escapeHtml(periodLabel) + '</span>';
        }

        // Build API URL
        var url = 'api/get_rack_detail.php?name=' + encodeURIComponent(rackName);
        if (month) url += '&month=' + encodeURIComponent(month);
        if (year) url += '&year=' + encodeURIComponent(year);

        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(result) {
                var dataRows = [];
                if (result.status === 'success' && result.data && result.data.length > 0) {
                    result.data.forEach(function(item, idx) {
                        dataRows.push({
                            no: idx + 1,
                            barcode: item.barcode || '-',
                            label: item.label || '-',
                            active: item.active || '-',
                            category: item.category || '-',
                            capacity: parseFloat(item.capacity) || 0
                        });
                    });
                }

                // Update subtitle with count
                if (subtitleEl) {
                    var periodLabel = (month && year) ? (month + ' ' + year) : 'Latest';
                    subtitleEl.innerHTML = '<span class="badge badge-primary mr-2" style="font-size: 0.72rem; padding: 3px 8px;"><i class="fas fa-calendar-alt mr-1"></i>Period: ' + escapeHtml(periodLabel) + '</span>' +
                        '<span class="badge badge-info mr-2" style="font-size: 0.72rem; padding: 3px 8px;"><i class="fas fa-layer-group mr-1"></i>Total Labels: ' + formatNumber(dataRows.length) + '</span>';
                }

                if (typeof $ !== 'undefined' && $.fn.DataTable) {
                    if ($.fn.DataTable.isDataTable('#rackDetailTable')) {
                        $('#rackDetailTable').DataTable().clear().destroy();
                    }

                    $('#rackDetailTable').DataTable({
                        data: dataRows,
                        columns: [
                            { data: 'no', title: 'NO', className: 'text-center', width: '50px' },
                            { data: 'barcode', title: 'BARCODE', className: 'text-left',
                              render: function(d) { return escapeHtml(d); } },
                            { data: 'label', title: 'LABEL', className: 'text-left',
                              render: function(d) { return escapeHtml(d); } },
                            { data: 'active', title: 'ACTIVE', className: 'text-center',
                              render: function(d) { return escapeHtml(d); } },
                            { data: 'category', title: 'CATEGORY', className: 'text-left',
                              render: function(d) { return escapeHtml(d); } },
                            { data: 'capacity', title: 'CAP (%)', className: 'text-center',
                              render: function(d, type) {
                                  if (type === 'display') {
                                      var pct = parseFloat(d) || 0;
                                      var colorClass = pct <= 50 ? 'bg-success' : (pct <= 75 ? 'bg-warning' : 'bg-danger');
                                      return '<div class="d-flex align-items-center"><span class="mr-2 font-weight-bold" style="min-width:35px;">' + pct + '%</span>' +
                                             '<div class="progress progress-sm flex-grow-1" style="height:8px;border-radius:4px;"><div class="progress-bar ' + colorClass + '" style="width:' + pct + '%;border-radius:4px;"></div></div></div>';
                                  }
                                  return d;
                              }
                            }
                        ],
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        language: {
                            lengthMenu: 'Tampilkan _MENU_ entries',
                            search: 'Search:',
                            searchPlaceholder: 'Search...',
                            zeroRecords: 'Tidak ada data yang ditemukan',
                            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entries',
                            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 entries',
                            infoFiltered: '(disaring dari _MAX_ total entries)',
                            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                        }
                    });

                    $('#rackDetailModal').modal('show');
                }
            })
            .catch(function(err) {
                console.error('Error fetching rack detail:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Failed to load rack detail data.', 'error');
                }
            });
    };

    // Expose to window
    window.FormulaController = FormulaController;

})(window);
