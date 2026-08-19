/**
 * Excel Upload → Dynamic Dashboard Charts
 * =========================================
 * Parses uploaded Excel files using SheetJS and renders
 * Chart.js charts with auto-detected chart types.
 * 
 * Security: No innerHTML with user data. All rendering via
 * textContent, createElement, or Chart.js API.
 * File validation: extension allow-list + size limit.
 */

(function () {
    'use strict';

    // ── Constants ──────────────────────────────────────────
    var MAX_FILE_SIZE = 100 * 1024 * 1024; // 100 MB
    var ALLOWED_EXTENSIONS = ['.xlsx', '.xls', '.csv'];
    var PREVIEW_ROWS = 5;

    // Premium color palettes for charts
    var COLOR_PALETTES = [
        { bg: 'rgba(78, 115, 223, 0.85)', border: '#4e73df', hover: '#2e59d9' },
        { bg: 'rgba(28, 200, 138, 0.85)', border: '#1cc88a', hover: '#17a673' },
        { bg: 'rgba(246, 194, 62, 0.85)', border: '#f6c23e', hover: '#dda20a' },
        { bg: 'rgba(231, 74, 59, 0.85)', border: '#e74a3b', hover: '#be2617' },
        { bg: 'rgba(54, 185, 204, 0.85)', border: '#36b9cc', hover: '#2c9faf' },
        { bg: 'rgba(133, 135, 150, 0.85)', border: '#858796', hover: '#6c6e7e' },
        { bg: 'rgba(102, 16, 242, 0.85)', border: '#6610f2', hover: '#510bc4' },
        { bg: 'rgba(232, 62, 140, 0.85)', border: '#e83e8c', hover: '#d91a72' },
        { bg: 'rgba(253, 126, 20, 0.85)', border: '#fd7e14', hover: '#e96b02' },
        { bg: 'rgba(32, 201, 151, 0.85)', border: '#20c997', hover: '#199d76' }
    ];

    // Pie/doughnut uses more vivid colors
    var PIE_COLORS = [
        '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc',
        '#6610f2', '#e83e8c', '#fd7e14', '#20c997', '#858796',
        '#5a5c69', '#2e59d9', '#17a673', '#dda20a', '#be2617'
    ];

    // ── State ──────────────────────────────────────────────
    var parsedWorkbook = null;
    var currentSheetData = null;
    var currentHeaders = null;
    var selectedChartType = 'auto';
    var dynamicCharts = [];
    var chartIdCounter = 0;

    // ── DOM References ─────────────────────────────────────
    var dropZone, fileInput, progressContainer, progressFill;
    var fileNameEl, fileSizeEl, controlsPanel, sheetSelect;
    var previewSection, previewTableHead, previewTableBody, previewMore;
    var generateBtn, dynamicContainer, toastEl;

    // ── Initialization ─────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        initDOMReferences();
        bindEvents();
    });

    function initDOMReferences() {
        dropZone = document.getElementById('upload-drop-zone');
        fileInput = document.getElementById('excel-file-input');
        progressContainer = document.getElementById('upload-progress');
        progressFill = document.getElementById('upload-progress-fill');
        fileNameEl = document.getElementById('upload-file-name');
        fileSizeEl = document.getElementById('upload-file-size');
        controlsPanel = document.getElementById('upload-controls');
        sheetSelect = document.getElementById('sheet-select');
        previewSection = document.getElementById('data-preview-section');
        previewTableHead = document.getElementById('preview-table-head');
        previewTableBody = document.getElementById('preview-table-body');
        previewMore = document.getElementById('preview-more');
        generateBtn = document.getElementById('btn-generate-charts');
        dynamicContainer = document.getElementById('dynamic-charts-container');
        toastEl = document.getElementById('upload-toast');
    }

    function bindEvents() {
        if (!dropZone || !fileInput) return;

        // Drag & drop
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('drag-over');
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // Click to browse
        dropZone.addEventListener('click', function (e) {
            if (e.target.tagName !== 'BUTTON') {
                fileInput.click();
            }
        });

        var browseBtn = document.getElementById('btn-browse-file');
        if (browseBtn) {
            browseBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fileInput.click();
            });
        }

        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                handleFile(fileInput.files[0]);
            }
        });

        // Sheet selection change
        if (sheetSelect) {
            sheetSelect.addEventListener('change', function () {
                loadSheet(sheetSelect.value);
            });
        }

        // Chart type buttons
        var chartTypeBtns = document.querySelectorAll('.chart-type-btn');
        for (var i = 0; i < chartTypeBtns.length; i++) {
            chartTypeBtns[i].addEventListener('click', function () {
                for (var j = 0; j < chartTypeBtns.length; j++) {
                    chartTypeBtns[j].classList.remove('active');
                }
                this.classList.add('active');
                selectedChartType = this.getAttribute('data-type');
            });
        }

        // Generate button
        if (generateBtn) {
            generateBtn.addEventListener('click', function () {
                generateCharts();
            });
        }

        // Reset modal state when hidden
        $('#uploadExcelModal').on('hidden.bs.modal', function () {
            resetUpload();
        });
    }

    // ── Helper functions for CI search and period extraction ──
    function getValCI(row, key) {
        if (!row) return null;
        var lowerKey = key.toLowerCase();
        for (var k in row) {
            if (row.hasOwnProperty(k) && k.toLowerCase() === lowerKey) {
                return row[k];
            }
        }
        return null;
    }

    function formatPeriode(rawDate, year, batch) {
        if (!rawDate) return 'Unknown Period';

        var str = String(rawDate).toUpperCase();
        var currentYear = year || new Date().getFullYear();
        var batchNum = batch || '1';

        var months = {
            'JAN': 'January',
            'FEB': 'February',
            'MAR': 'March',
            'APR': 'April',
            'MEI': 'May',
            'JUN': 'June',
            'JUL': 'July',
            'AGU': 'August',
            'SEP': 'September',
            'OKT': 'October',
            'NOV': 'November',
            'DES': 'December'
        };

        for (var key in months) {
            if (str.indexOf(key) !== -1) {
                return months[key] + ' ' + currentYear + '-Batch' + batchNum;
            }
        }

        return 'Unknown Period';
    }

    // ── Modern Upload Steps UI Helpers ──
    function createUploadStepsUI() {
        if (!progressContainer) return;

        progressContainer.replaceChildren();

        var fileInfo = document.createElement('div');
        fileInfo.className = 'upload-file-info mb-3';

        var fnSpan = document.createElement('span');
        fnSpan.id = 'upload-file-name';
        fnSpan.className = 'file-name';
        fileInfo.appendChild(fnSpan);

        var fsSpan = document.createElement('span');
        fsSpan.id = 'upload-file-size';
        fsSpan.className = 'file-size';
        fileInfo.appendChild(fsSpan);

        progressContainer.appendChild(fileInfo);

        var stepsList = document.createElement('div');
        stepsList.className = 'upload-steps-list';

        var steps = [
            { id: 'step-read', label: 'Upload File...', icon: 'fa-file' },
            { id: 'step-parse', label: 'Memindai Sheet...', icon: 'fa-table' },
            { id: 'step-upload', label: 'Menyimpan ke Database...', icon: 'fa-database', hasProgress: true },
            { id: 'step-finalize', label: 'Finalisasi & Update Data...', icon: 'fa-sync-alt' }
        ];

        steps.forEach(function (step) {
            var item = document.createElement('div');
            item.className = 'upload-step-item';
            item.id = step.id;

            var iconContainer = document.createElement('div');
            iconContainer.className = 'step-icon-container';
            var icon = document.createElement('i');
            icon.className = 'fas ' + step.icon;
            iconContainer.appendChild(icon);
            item.appendChild(iconContainer);

            var labelContainer = document.createElement('div');
            labelContainer.className = 'step-label-container';
            labelContainer.style.flexGrow = '1';

            var label = document.createElement('span');
            label.className = 'step-label';
            label.textContent = step.label;
            labelContainer.appendChild(label);

            if (step.hasProgress) {
                var progressWrapper = document.createElement('div');
                progressWrapper.className = 'batch-progress-wrapper';
                progressWrapper.id = 'batch-progress-container';
                progressWrapper.style.display = 'none';

                var progressDetails = document.createElement('div');
                progressDetails.className = 'batch-progress-details';

                var progressText = document.createElement('span');
                progressText.id = 'batch-progress-text';
                progressText.textContent = '0 / 0 rows';
                progressDetails.appendChild(progressText);

                var progressPercent = document.createElement('span');
                progressPercent.id = 'batch-progress-percent';
                progressPercent.textContent = '0%';
                progressDetails.appendChild(progressPercent);

                progressWrapper.appendChild(progressDetails);

                var progressBg = document.createElement('div');
                progressBg.className = 'batch-progress-bar-bg';
                var progressFill = document.createElement('div');
                progressFill.className = 'batch-progress-bar-fill';
                progressFill.id = 'batch-progress-bar-fill';
                progressBg.appendChild(progressFill);
                progressWrapper.appendChild(progressBg);

                labelContainer.appendChild(progressWrapper);
            }

            item.appendChild(labelContainer);
            stepsList.appendChild(item);
        });

        progressContainer.appendChild(stepsList);

        fileNameEl = document.getElementById('upload-file-name');
        fileSizeEl = document.getElementById('upload-file-size');
    }

    function setStepState(stepId, state) {
        var stepItem = document.getElementById(stepId);
        if (!stepItem) return;

        var iconContainer = stepItem.querySelector('.step-icon-container');
        if (!iconContainer) return;

        stepItem.classList.remove('active', 'completed');
        iconContainer.replaceChildren();

        var defaultIcons = {
            'step-read': 'fa-file',
            'step-parse': 'fa-table',
            'step-upload': 'fa-database',
            'step-finalize': 'fa-sync-alt'
        };

        if (state === 'active') {
            stepItem.classList.add('active');
            var spinner = document.createElement('div');
            spinner.className = 'step-spinner';
            iconContainer.appendChild(spinner);
        } else if (state === 'completed') {
            stepItem.classList.add('completed');
            var check = document.createElement('i');
            check.className = 'fas fa-check';
            iconContainer.appendChild(check);
        } else {
            var icon = document.createElement('i');
            icon.className = 'fas ' + (defaultIcons[stepId] || 'fa-info-circle');
            iconContainer.appendChild(icon);
        }
    }

    function toggleModalInteractivity(allowInteraction) {
        var modal = $('#uploadExcelModal');
        var closeBtns = document.querySelectorAll('#uploadExcelModal .close');
        var browseBtn = document.getElementById('btn-browse-file');
        var generateBtn = document.getElementById('btn-generate-charts');
        var dataTypeSelect = document.getElementById('upload-data-type');

        if (allowInteraction) {
            // Enable closing modal
            var modalData = modal.data('bs.modal');
            if (modalData && modalData._config) {
                modalData._config.backdrop = true;
                modalData._config.keyboard = true;
            }

            // Show close buttons
            closeBtns.forEach(function (btn) {
                btn.style.display = '';
            });

            // Enable inputs & buttons
            if (browseBtn) browseBtn.removeAttribute('disabled');
            if (generateBtn) generateBtn.removeAttribute('disabled');
            if (dataTypeSelect) dataTypeSelect.removeAttribute('disabled');
        } else {
            // Disable closing modal
            var modalData = modal.data('bs.modal');
            if (modalData && modalData._config) {
                modalData._config.backdrop = 'static';
                modalData._config.keyboard = false;
            }

            // Hide close buttons
            closeBtns.forEach(function (btn) {
                btn.style.display = 'none';
            });

            // Disable inputs & buttons
            if (browseBtn) browseBtn.setAttribute('disabled', 'true');
            if (generateBtn) generateBtn.setAttribute('disabled', 'true');
            if (dataTypeSelect) dataTypeSelect.setAttribute('disabled', 'true');
        }
    }

    // ── File Handling ──────────────────────────────────────
    function handleFile(file) {
        var fileName = file.name || '';
        var ext = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
        if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
            showToast('Invalid file type. Please upload .xlsx, .xls, or .csv files.', 'error');
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            showToast('File too large. Maximum size is 100MB.', 'error');
            return;
        }

        createUploadStepsUI();
        showProgress(fileName, file.size);
        setStepState('step-read', 'active');

        var reader = new FileReader();
        reader.onload = function (e) {
            setStepState('step-read', 'completed');
            setStepState('step-parse', 'active');

            setTimeout(function () {
                try {
                    var data = new Uint8Array(e.target.result);
                    parsedWorkbook = XLSX.read(data, { type: 'array', cellFormula: false, cellDates: true });
                    setStepState('step-parse', 'completed');
                    populateSheetSelector();
                    loadSheet(parsedWorkbook.SheetNames[0]);
                    setModalExpanded(true);
                    showToast('File loaded successfully! ' + parsedWorkbook.SheetNames.length + ' sheet(s) found.', 'success');
                } catch (err) {
                    showToast('Failed to parse file. Please check the format.', 'error');
                    resetUpload();
                }
            }, 100);
        };
        reader.onerror = function () {
            showToast('Failed to read file.', 'error');
            resetUpload();
        };
        reader.readAsArrayBuffer(file);
    }

    function showProgress(name, size) {
        if (!progressContainer) return;
        progressContainer.classList.add('active');

        if (fileNameEl) fileNameEl.textContent = name;
        if (fileSizeEl) fileSizeEl.textContent = formatFileSize(size);
    }

    function resetUpload() {
        toggleModalInteractivity(true);
        if (progressContainer) progressContainer.classList.remove('active');
        if (controlsPanel) controlsPanel.classList.remove('active');
        if (previewSection) previewSection.classList.remove('active');
        parsedWorkbook = null;
        currentSheetData = null;
        currentHeaders = null;
        if (fileInput) fileInput.value = '';
        setModalExpanded(false);
    }

    function setModalExpanded(isExpanded) {
        var dialog = document.getElementById('uploadExcelModalDialog');
        var leftCol = document.getElementById('uploadModalLeftCol');
        var rightCol = document.getElementById('uploadModalRightCol');
        if (dialog && leftCol && rightCol) {
            if (isExpanded) {
                dialog.classList.add('modal-expanded');
                leftCol.classList.remove('col-lg-12');
                leftCol.classList.add('col-lg-6');
                rightCol.style.display = 'block';
            } else {
                dialog.classList.remove('modal-expanded');
                leftCol.classList.remove('col-lg-6');
                leftCol.classList.add('col-lg-12');
                rightCol.style.display = 'none';
            }
        }
    }

    // ── Sheet Loading ─────────────────────────────────────
    function populateSheetSelector() {
        if (!sheetSelect || !parsedWorkbook) return;

        // Clear existing options using replaceChildren
        sheetSelect.replaceChildren();

        for (var i = 0; i < parsedWorkbook.SheetNames.length; i++) {
            var option = document.createElement('option');
            option.value = parsedWorkbook.SheetNames[i];
            option.textContent = parsedWorkbook.SheetNames[i];
            sheetSelect.appendChild(option);
        }
    }

    function loadSheet(sheetName) {
        if (!parsedWorkbook) return;

        var ws = parsedWorkbook.Sheets[sheetName];
        if (!ws) return;

        // Convert to JSON with headers
        var jsonData = XLSX.utils.sheet_to_json(ws, { defval: '' });
        if (jsonData.length === 0) {
            showToast('Selected sheet is empty.', 'info');
            if (previewSection) previewSection.classList.remove('active');
            if (controlsPanel) controlsPanel.classList.remove('active');
            return;
        }

        currentHeaders = Object.keys(jsonData[0]);
        currentSheetData = jsonData;

        // Show controls and preview
        if (controlsPanel) controlsPanel.classList.add('active');
        renderPreview();
        if (generateBtn) generateBtn.disabled = false;
    }

    // ── Data Preview ──────────────────────────────────────
    function renderPreview() {
        // Preview removed per user request
    }


    // ── Data Analysis ─────────────────────────────────────
    function analyzeColumns() {
        if (!currentHeaders || !currentSheetData) return null;

        var analysis = {
            textColumns: [],
            numericColumns: [],
            dateColumns: [],
            totalRows: currentSheetData.length
        };

        for (var i = 0; i < currentHeaders.length; i++) {
            var header = currentHeaders[i];
            var colType = detectColumnType(header);
            if (colType === 'date') {
                analysis.dateColumns.push(header);
            } else if (colType === 'numeric') {
                analysis.numericColumns.push(header);
            } else {
                analysis.textColumns.push(header);
            }
        }

        return analysis;
    }

    function detectColumnType(header) {
        if (!currentSheetData || currentSheetData.length === 0) return 'text';

        var dateCount = 0;
        var numericCount = 0;
        var textCount = 0;
        var sampleSize = Math.min(20, currentSheetData.length);

        for (var i = 0; i < sampleSize; i++) {
            var val = currentSheetData[i][header];

            if (val === '' || val == null) continue;

            if (val instanceof Date) {
                dateCount++;
            } else if (typeof val === 'number' || (typeof val === 'string' && !isNaN(parseFloat(val)) && isFinite(val.toString().replace(/,/g, '')))) {
                numericCount++;
            } else {
                textCount++;
            }
        }

        var total = dateCount + numericCount + textCount;
        if (total === 0) return 'text';

        if (dateCount / total > 0.5) return 'date';
        if (numericCount / total > 0.5) return 'numeric';
        return 'text';
    }

    function detectChartType(analysis) {
        if (selectedChartType !== 'auto') {
            return selectedChartType;
        }

        var labelCount = 0;
        if (analysis.textColumns.length > 0 && currentSheetData) {
            // Count unique labels
            var seen = {};
            for (var i = 0; i < currentSheetData.length; i++) {
                var val = currentSheetData[i][analysis.textColumns[0]];
                if (val && !seen[val]) {
                    seen[val] = true;
                    labelCount++;
                }
            }
        }

        // Date-based → line chart
        if (analysis.dateColumns.length > 0 && analysis.numericColumns.length > 0) {
            return 'line';
        }

        // 1 text + 1 numeric → pie/doughnut
        if (analysis.textColumns.length >= 1 && analysis.numericColumns.length === 1 && labelCount <= 8) {
            return 'doughnut';
        }

        // Many categories → horizontal bar
        if (labelCount > 6) {
            return 'horizontalBar';
        }

        // Default → bar
        return 'bar';
    }

    // ── Update Dashboard Data ──────────────────────────────────
    function generateCharts() {
        if (!currentSheetData || !currentHeaders) {
            showToast('No data loaded. Please upload a file first.', 'error');
            return;
        }

        var dataType = document.getElementById('upload-data-type').value;
        var apiEndpoint = dataType === 'rack' ? 'api/save_rack_data.php' : 'api/save_data.php';

        toggleModalInteractivity(false);

        var totalRowsForEst = currentSheetData.length;
        var estSeconds = Math.max(5, Math.ceil(totalRowsForEst / 5000) * 5);
        var estTimeText = estSeconds < 60 ? estSeconds + " seconds" : Math.ceil(estSeconds / 60) + " minutes";

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Data Is Processing Please Wait',
                html: 'Please do not refresh the page.<br><br><span style="font-size: 0.9em; color: #666;"><i class="fas fa-clock mr-1"></i> Estimasi Waktu: ~' + estTimeText + '</span>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        setStepState('step-upload', 'active');
        setStepState('step-finalize', 'pending');

        var batchProgressContainer = document.getElementById('batch-progress-container');
        var batchProgressText = document.getElementById('batch-progress-text');
        var batchProgressBarFill = document.getElementById('batch-progress-bar-fill');
        var batchProgressPercent = document.getElementById('batch-progress-percent');

        if (batchProgressContainer) {
            batchProgressContainer.style.display = 'block';
        }

        var localSheetData = currentSheetData;
        var totalRows = localSheetData.length;
        var uploadedCount = 0;
        var BATCH_SIZE = 5000;

        var initPayload = { action: 'init' };

        // Read period selectors from the upload form
        var uploadBulan = document.getElementById('upload-bulan-select');
        var uploadBatch = document.getElementById('upload-batch-select');
        var uploadTahun = document.getElementById('upload-tahun-select');
        var periodMonth = uploadBulan ? uploadBulan.value : '';
        var periodBatch = uploadBatch ? uploadBatch.value : '1';
        var periodYear = uploadTahun ? uploadTahun.value : new Date().getFullYear().toString();

        if (dataType === 'asset') {
            var pGroup;
            if (periodMonth && periodYear && periodBatch) {
                pGroup = periodMonth + ' ' + periodYear + '-Batch' + periodBatch;
            } else {
                var activeSheetName = sheetSelect ? sheetSelect.value : '';
                pGroup = formatPeriode(activeSheetName, periodYear, periodBatch);
            }
            initPayload.periods = [pGroup];
            initPayload.month = periodMonth;
            initPayload.year = periodYear;
            initPayload.batch = periodBatch;

            // Add the sheetName to each row so the backend knows what to use for periode
            for (var idx = 0; idx < totalRows; idx++) {
                localSheetData[idx]['periode'] = sheetSelect ? sheetSelect.value : '';
            }
        }

        fetch(apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(initPayload)
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status !== 'success') {
                    throw new Error(res.message || 'Initialization failed');
                }
                sendNextBatch(0);
            })
            .catch(function (err) {
                handleUploadError(err.message || 'Error occurred during initialization');
            });

        function sendNextBatch(startIndex) {
            if (startIndex >= totalRows) {
                setStepState('step-upload', 'completed');
                setStepState('step-finalize', 'active');
                finalizeUpload();
                return;
            }

            var endIndex = Math.min(startIndex + BATCH_SIZE, totalRows);
            var batchRows = localSheetData.slice(startIndex, endIndex);

            var appendPayload = {
                action: 'append',
                data: batchRows,
                month: periodMonth,
                year: periodYear,
                batch: periodBatch
            };

            fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(appendPayload)
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status !== 'success') {
                        throw new Error(res.message || 'Batch upload failed');
                    }

                    uploadedCount += batchRows.length;

                    var percent = (uploadedCount / totalRows) * 100;
                    if (batchProgressText) {
                        batchProgressText.textContent = number_format(uploadedCount, 0, '.', ',') + ' / ' + number_format(totalRows, 0, '.', ',') + ' rows';
                    }
                    if (batchProgressBarFill) {
                        batchProgressBarFill.style.width = percent + '%';
                    }
                    if (batchProgressPercent) {
                        batchProgressPercent.textContent = Math.round(percent) + '%';
                    }

                    sendNextBatch(endIndex);
                })
                .catch(function (err) {
                    handleUploadError(err.message || 'Error uploading database batch');
                });
        }

        function finalizeUpload() {
            var finalizePayload = { action: 'finalize' };
            fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(finalizePayload)
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status !== 'success') {
                        throw new Error(res.message || 'Finalization failed');
                    }

                    setStepState('step-finalize', 'completed');
                    showToast('Data saved successfully!', 'success');

                    if (dataType === 'rack') {
                        if (window.FormulaController && window.currentDashboardData) {
                            try { window.FormulaController.updateDashboardCards(window.currentDashboardData, window.currentDashboardHeaders); } catch (e) { }
                        }
                    } else {
                        if (window.loadPeriods) {
                            var newPeriod = (res.periods && res.periods.length > 0) ? res.periods[0] : null;
                            window.loadPeriods(newPeriod);
                        } else if (window.FormulaController) {
                            try { window.FormulaController.updateDashboardCards(localSheetData, currentHeaders); } catch (e) { }
                        }
                    }

                    showToast('Dashboard updated successfully!', 'success');

                    setTimeout(function () {
                        toggleModalInteractivity(true);
                        $('#uploadExcelModal').modal('hide');
                        resetUpload();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Data Upload Success!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                })
                .catch(function (err) {
                    handleUploadError(err.message || 'Error during finalization');
                });
        }

        function handleUploadError(msg) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Upload Failed',
                    text: msg,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                showToast(msg, 'error');
            }
            toggleModalInteractivity(true);

            setStepState('step-upload', 'pending');
            setStepState('step-finalize', 'pending');
            if (batchProgressContainer) {
                batchProgressContainer.style.display = 'none';
            }
        }
    }



    // ── Helpers ────────────────────────────────────────────
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function formatDate(date) {
        if (!(date instanceof Date) || isNaN(date)) return String(date);
        var d = date.getDate();
        var m = date.getMonth() + 1;
        var y = date.getFullYear();
        return (d < 10 ? '0' + d : d) + '/' + (m < 10 ? '0' + m : m) + '/' + y;
    }



    // ── Toast Notification ────────────────────────────────
    function showToast(message, type) {
        if (!toastEl) {
            // Create toast element dynamically if missing
            toastEl = document.createElement('div');
            toastEl.id = 'upload-toast';
            toastEl.className = 'upload-toast';
            document.body.appendChild(toastEl);
        }

        // Remove existing classes
        toastEl.classList.remove('show', 'success', 'error', 'info');
        toastEl.replaceChildren();

        var icon = document.createElement('i');
        switch (type) {
            case 'success': icon.className = 'fas fa-check-circle'; break;
            case 'error': icon.className = 'fas fa-exclamation-circle'; break;
            default: icon.className = 'fas fa-info-circle'; break;
        }
        toastEl.appendChild(icon);

        var text = document.createTextNode(' ' + message);
        toastEl.appendChild(text);

        toastEl.classList.add(type || 'info');

        // Show
        setTimeout(function () {
            toastEl.classList.add('show');
        }, 50);

        // Auto-hide after 4 seconds
        setTimeout(function () {
            toastEl.classList.remove('show');
        }, 4000);
    }

})();
