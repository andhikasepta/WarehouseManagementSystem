/**
 * WMS Period Controller
 * Manages global active period across all WMS pages (Dashboard, Outbound, Inbound, Storage Management, Storage Hub, KPI).
 * - Defaults to current period based on today (month, batch, year).
 * - Persists selected period across page navigation via localStorage.
 * - Auto-selects and loads data across pages seamlessly.
 */
(function (window) {
    'use strict';

    var ALL_MONTHS = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    var STORAGE_KEY = 'wms_active_period';
    var DETAILS_KEY = 'wms_active_period_details';
    var KPI_KEY = 'wms_kpi_year';

    var PeriodManager = {
        ALL_MONTHS: ALL_MONTHS,
        _pageHandler: null,
        _listenersBound: false,

        /**
         * Determine default period based on today's date.
         * Day 1-15 -> Batch 1, Day 16+ -> Batch 2.
         * Cross-checks availablePeriods from API so existing data is matched first.
         */
        getDefaultPeriodForToday: function (availablePeriods, availableYears) {
            var today = new Date();
            var curMonth = ALL_MONTHS[today.getMonth()];
            var curYear = today.getFullYear().toString();

            /* Batch concept commented out for future use:
            var curDay = today.getDate();
            var curBatch = curDay <= 15 ? '1' : '2';
            var altBatch = curBatch === '1' ? '2' : '1';
            var prefPeriod = curMonth + ' ' + curYear + '-Batch' + curBatch;
            var altPeriod = curMonth + ' ' + curYear + '-Batch' + altBatch;
            */
            var monthPrefix = curMonth + ' ' + curYear;

            if (Array.isArray(availablePeriods) && availablePeriods.length > 0) {
                // 1. Any period matching current month & year
                var matched = availablePeriods.find(function (p) {
                    return p && p.indexOf(monthPrefix) === 0;
                });
                if (matched) {
                    return monthPrefix;
                }
                // 2. Return first available period's Month Year if today's month not in data
                if (availablePeriods[0]) {
                    var parsed0 = this.parsePeriod(availablePeriods[0]);
                    if (parsed0 && parsed0.month && parsed0.year) {
                        return parsed0.month + ' ' + parsed0.year;
                    }
                    return availablePeriods[0];
                }
            }
            return monthPrefix;
        },

        /**
         * Get current active period for standard batch-level modules (Dashboard, Outbound, Inbound, Warehouse, Storage Hub).
         * Rejects pure 4-digit years so KPI year selection never crashes standard batch modules.
         */
        getActivePeriod: function (availablePeriods, availableYears) {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved && saved.trim() !== '' && saved !== 'PILIH PERIODE DATA') {
                var trimmed = saved.trim();
                var parsed = this.parsePeriod(trimmed);
                // Ensure it is a valid period with a Month
                if (parsed && parsed.month && parsed.month !== '') {
                    // Always normalize to Month Year to cover all batches
                    var normalized = parsed.month + ' ' + parsed.year;
                    if (normalized !== trimmed) {
                        this.setActivePeriod(normalized);
                    }
                    return normalized;
                }
                // If saved was only a 4-digit year, check if availablePeriods has a period matching that year
                if (parsed && parsed.year && Array.isArray(availablePeriods) && availablePeriods.length > 0) {
                    var yearMatch = availablePeriods.find(function (p) {
                        return p && p.indexOf(parsed.year) !== -1;
                    });
                    if (yearMatch) {
                        var ymParsed = this.parsePeriod(yearMatch);
                        var ymNormalized = ymParsed && ymParsed.month ? (ymParsed.month + ' ' + ymParsed.year) : yearMatch;
                        this.setActivePeriod(ymNormalized);
                        return ymNormalized;
                    }
                }
            }

            var def = this.getDefaultPeriodForToday(availablePeriods, availableYears);
            if (def) {
                this.setActivePeriod(def);
                return def;
            }
            return null;
        },

        /**
         * Get current active year for KPI Monitoring (Year-only).
         */
        getKpiYear: function (availableYears) {
            var saved = localStorage.getItem(KPI_KEY);
            if (saved && /^\d{4}$/.test(saved.trim())) {
                return saved.trim();
            }
            var curBatch = localStorage.getItem(STORAGE_KEY);
            if (curBatch) {
                var m = curBatch.match(/(\d{4})/);
                if (m) {
                    this.setKpiYear(m[1]);
                    return m[1];
                }
            }
            var curYear = (new Date()).getFullYear().toString();
            this.setKpiYear(curYear);
            return curYear;
        },

        /**
         * Save active KPI year to localStorage without corrupting wms_active_period.
         */
        setKpiYear: function (year) {
            if (!year) return;
            var str = String(year).trim();
            var m = str.match(/(\d{4})/);
            var yr = m ? m[1] : str;
            if (/^\d{4}$/.test(yr)) {
                localStorage.setItem(KPI_KEY, yr);
            }
        },

        /**
         * Save active period to localStorage (and sessionStorage for backward compatibility).
         */
        setActivePeriod: function (period, details) {
            if (!period || period === 'PILIH PERIODE DATA') {
                this.clearActivePeriod();
                return;
            }

            // If someone mistakenly passes just a 4-digit year, route it to setKpiYear
            if (/^\d{4}$/.test(String(period).trim())) {
                this.setKpiYear(period);
                return;
            }

            localStorage.setItem(STORAGE_KEY, period);
            sessionStorage.setItem('wms_dashboard_period', period);

            if (details) {
                localStorage.setItem(DETAILS_KEY, JSON.stringify(details));
                if (details.year) {
                    this.setKpiYear(details.year);
                }
            } else {
                var parsed = this.parsePeriod(period);
                if (parsed) {
                    localStorage.setItem(DETAILS_KEY, JSON.stringify(parsed));
                    if (parsed.year) {
                        this.setKpiYear(parsed.year);
                    }
                }
            }
        },

        /**
         * Clear active period from storage.
         */
        clearActivePeriod: function () {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(DETAILS_KEY);
            sessionStorage.removeItem('wms_dashboard_period');
        },

        /**
         * Retrieve detailed breakdown { month, batch, year, site }
         */
        getActivePeriodDetails: function () {
            try {
                var raw = localStorage.getItem(DETAILS_KEY);
                if (raw) return JSON.parse(raw);
            } catch (e) {}

            var cur = localStorage.getItem(STORAGE_KEY);
            if (cur) return this.parsePeriod(cur);
            return null;
        },

        /**
         * Parse string like "October 2026-Batch2", "October 2026", or "2026"
         */
        parsePeriod: function (period) {
            if (!period) return null;
            var str = String(period).trim();
            if (str === '' || str === 'PILIH PERIODE DATA') return null;

            var match = str.match(/^([A-Za-z]+)\s+(\d{4})(?:-Batch(\d+))?$/i);
            if (match) {
                return {
                    month: match[1],
                    year: match[2],
                    batch: match[3] || ''
                };
            }
            var parts = str.split(' ');
            if (parts.length >= 2 && !/^\d{4}$/.test(parts[0])) {
                var yClean = (parts[1] || '').replace(/-Batch\d+/i, '');
                var bMatch = (parts[1] || '').match(/-Batch(\d+)/i);
                return {
                    month: parts[0],
                    year: yClean,
                    batch: bMatch ? bMatch[1] : ''
                };
            }
            if (/^\d{4}$/.test(str)) {
                return {
                    month: '',
                    year: str,
                    batch: ''
                };
            }
            return null;
        },

        /**
         * Check whether the active page is Year-Only (KPI Monitoring) vs Standard Batch
         */
        isYearOnlyMode: function () {
            if (window.WMS_SPA && window.WMS_SPA.currentPage) {
                return window.WMS_SPA.currentPage === 'kpi_monitoring';
            }
            var mg = document.getElementById('month-select-group');
            return !!(mg && (mg.style.display === 'none' || (window.jQuery && $(mg).is(':hidden'))));
        },

        /**
         * Pre-select dropdowns and navbar text with given period and optional site.
         */
        applyPeriodToNavbar: function (period, site, forceYearOnly) {
            var pText = document.getElementById('selected-period-text');
            var mSel = document.getElementById('period-month-select');
            var bSel = document.getElementById('period-batch-select');
            var ySel = document.getElementById('period-year-select');
            var sSel = document.getElementById('period-site-select');
            var btnLoad = document.getElementById('btn-load-period');
            var isYearOnly = (forceYearOnly === true) || (forceYearOnly !== false && this.isYearOnlyMode());

            if (!period) {
                if (pText) pText.textContent = isYearOnly ? "PILIH TAHUN" : "PILIH PERIODE DATA";
                if (mSel) mSel.value = '';
                if (bSel) bSel.value = '';
                if (ySel) ySel.value = '';
                if (sSel) sSel.value = '';
                if (btnLoad) btnLoad.disabled = true;
                return;
            }

            var parsed = this.parsePeriod(period);

            if (isYearOnly) {
                var yr = (parsed && parsed.year) ? parsed.year : period.toString().replace(/\D/g, '');
                if (ySel && yr) ySel.value = yr;
                if (pText && yr) pText.textContent = yr;
                this.updateLoadButtonState();
                return;
            }

            if (parsed) {
                if (parsed.month && mSel) mSel.value = parsed.month;
                if (bSel) bSel.value = parsed.batch || '';
                if (parsed.year && ySel) ySel.value = parsed.year;
                if (sSel && site) sSel.value = site;
            }

            if (pText) {
                if (parsed && parsed.month) {
                    // Clean month and year display covering all batches (no '(ALL BATCH)' text or badge)
                    pText.textContent = (parsed.month + ' ' + parsed.year).toUpperCase();
                } else if (parsed && parsed.year) {
                    pText.textContent = parsed.year;
                }
            }

            this.updateLoadButtonState();
        },

        /**
         * Enable/disable btn-load-period based on dropdown validity
         */
        updateLoadButtonState: function () {
            var btnLoad = document.getElementById('btn-load-period');
            if (!btnLoad) return;

            var mSel = document.getElementById('period-month-select');
            var ySel = document.getElementById('period-year-select');
            var monthGroup = document.getElementById('month-select-group');

            var isYearOnly = this.isYearOnlyMode();
            if (isYearOnly) {
                btnLoad.disabled = !(ySel && ySel.value);
            } else {
                btnLoad.disabled = !(mSel && mSel.value && ySel && ySel.value);
            }
        },

        /**
         * Helper to populate standard HTML select
         */
        populateSelect: function (selectId, items, placeholder) {
            var sel = document.getElementById(selectId);
            if (!sel) return;
            sel.replaceChildren();
            var defOpt = document.createElement('option');
            defOpt.value = '';
            defOpt.textContent = placeholder;
            sel.appendChild(defOpt);
            (items || []).forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item;
                opt.textContent = String(item).toUpperCase();
                sel.appendChild(opt);
            });
        },

        /**
         * Ensure current year is in the availableYears array
         */
        ensureCurrentYear: function (availableYears) {
            var curYear = (new Date()).getFullYear().toString();
            var years = (availableYears || []).map(function (y) { return String(y); });
            if (years.indexOf(curYear) === -1) {
                years.push(curYear);
                years.sort();
            }
            return years;
        },

        /**
         * Register active page handler callbacks: { onLoad: function(period, site){}, onReset: function(){} }
         */
        registerPageHandler: function (handler) {
            this._pageHandler = handler;
            this.initNavbarListeners();
        },

        /**
         * Bind global navbar period controls once
         */
        initNavbarListeners: function () {
            if (this._listenersBound) return;
            this._listenersBound = true;

            var self = this;
            var mSel = document.getElementById('period-month-select');
            var bSel = document.getElementById('period-batch-select');
            var ySel = document.getElementById('period-year-select');
            var sSel = document.getElementById('period-site-select');
            var btnLoad = document.getElementById('btn-load-period');
            var btnReset = document.getElementById('btn-reset-period');
            var periodMenu = document.getElementById('period-dropdown-menu');

            var onSelectChange = function () {
                self.updateLoadButtonState();
            };

            if (mSel) mSel.addEventListener('change', onSelectChange);
            if (bSel) bSel.addEventListener('change', onSelectChange);
            if (ySel) ySel.addEventListener('change', onSelectChange);
            if (sSel) sSel.addEventListener('change', onSelectChange);

            if (periodMenu) {
                periodMenu.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }

            if (btnLoad) {
                btnLoad.addEventListener('click', function (e) {
                    e.preventDefault();
                    var isYearOnly = self.isYearOnlyMode();

                    var m = document.getElementById('period-month-select');
                    var b = document.getElementById('period-batch-select');
                    var y = document.getElementById('period-year-select');
                    var s = document.getElementById('period-site-select');

                    var site = s ? s.value : '';

                    if (isYearOnly) {
                        var yr = y ? y.value : '';
                        if (yr) {
                            self.setKpiYear(yr);
                            var pText = document.getElementById('selected-period-text');
                            if (pText) pText.textContent = yr;

                            // Also adjust year in active period if it exists, preserving month
                            var curPeriod = localStorage.getItem(STORAGE_KEY);
                            var parsedCur = self.parsePeriod(curPeriod);
                            if (parsedCur && parsedCur.month) {
                                /* Batch concept commented out for future use:
                                var newBatch = parsedCur.month + ' ' + yr + (parsedCur.batch ? '-Batch' + parsedCur.batch : '');
                                */
                                var newBatch = parsedCur.month + ' ' + yr;
                                self.setActivePeriod(newBatch);
                            }

                            if (window.jQuery && typeof $('#periodDropdown').dropdown === 'function') {
                                $('#periodDropdown').dropdown('toggle');
                            }
                            if (self._pageHandler && typeof self._pageHandler.onLoad === 'function') {
                                self._pageHandler.onLoad(yr, site);
                            }
                        }
                    } else {
                        var mVal = m ? m.value : '';
                        var yVal = y ? y.value : '';
                        /* Batch concept commented out for future use:
                        var bVal = b ? b.value : '';
                        var period = bVal
                            ? (mVal + ' ' + yVal + '-Batch' + bVal)
                            : (mVal + ' ' + yVal);
                        */
                        if (mVal && yVal) {
                            var period = mVal + ' ' + yVal;

                            self.setActivePeriod(period, {
                                month: mVal,
                                batch: '', // Batch concept commented out for future use
                                year: yVal,
                                site: site
                            });
                            self.setKpiYear(yVal);
                            self.applyPeriodToNavbar(period, site, false);

                            if (window.jQuery && typeof $('#periodDropdown').dropdown === 'function') {
                                $('#periodDropdown').dropdown('toggle');
                            }
                            if (self._pageHandler && typeof self._pageHandler.onLoad === 'function') {
                                self._pageHandler.onLoad(period, site);
                            }
                        }
                    }
                });
            }

            if (btnReset) {
                btnReset.addEventListener('click', function (e) {
                    e.preventDefault();
                    self.clearActivePeriod();
                    self.applyPeriodToNavbar(null);

                    if (self._pageHandler && typeof self._pageHandler.onReset === 'function') {
                        self._pageHandler.onReset();
                    }
                });
            }
        }
    };

    var DataCache = {
        _mem: {},
        _TTL: 30 * 60 * 1000, // 30 minutes TTL
        get: function (key) {
            if (!key) return null;
            if (this._mem[key]) {
                var item = this._mem[key];
                if (Date.now() - item.time < this._TTL) {
                    return item.val;
                }
                delete this._mem[key];
            }
            try {
                var stored = sessionStorage.getItem('wmscache_' + key);
                if (stored) {
                    var parsed = JSON.parse(stored);
                    if (Date.now() - parsed.time < this._TTL) {
                        this._mem[key] = parsed;
                        return parsed.val;
                    }
                    sessionStorage.removeItem('wmscache_' + key);
                }
            } catch (e) {}
            return null;
        },
        set: function (key, val) {
            if (!key || val === undefined || val === null) return;
            var item = { val: val, time: Date.now() };
            this._mem[key] = item;
            try {
                var str = JSON.stringify(item);
                if (str.length < 2000000) {
                    sessionStorage.setItem('wmscache_' + key, str);
                }
            } catch (e) {}
        },
        has: function (key) {
            return this.get(key) !== null;
        },
        clear: function (prefix) {
            var self = this;
            if (prefix) {
                Object.keys(this._mem).forEach(function (k) {
                    if (k.indexOf(prefix) === 0) delete self._mem[k];
                });
                try {
                    for (var i = sessionStorage.length - 1; i >= 0; i--) {
                        var k = sessionStorage.key(i);
                        if (k && k.indexOf('wmscache_' + prefix) === 0) {
                            sessionStorage.removeItem(k);
                        }
                    }
                } catch (e) {}
            } else {
                this._mem = {};
                try {
                    for (var j = sessionStorage.length - 1; j >= 0; j--) {
                        var sk = sessionStorage.key(j);
                        if (sk && sk.indexOf('wmscache_') === 0) {
                            sessionStorage.removeItem(sk);
                        }
                    }
                } catch (e) {}
            }
        }
    };

    window.WMSPeriodManager = PeriodManager;
    window.WMSDataCache = DataCache;

    document.addEventListener('DOMContentLoaded', function () {
        PeriodManager.initNavbarListeners();
    });
})(window);
