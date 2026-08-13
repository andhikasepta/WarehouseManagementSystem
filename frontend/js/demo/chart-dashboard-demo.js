// chart-dashboard-demo.js - Head Dashboard Overview Controller
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

document.addEventListener("DOMContentLoaded", function () {
  window.inventorySummaryRealValues = [0, 0, 0, 0];

  function getStorageDynamicStepNames() {
    var storageContainer = document.getElementById('storage-steps-container');
    var flowSteps = storageContainer ? storageContainer.querySelectorAll('.flow-step') : [];
    var names = [];
    flowSteps.forEach(function (stepEl, idx) {
      if (idx > 0) { // Skip Step 1 (Total Perangkat) as pie chart represents the 4 categories
        var titleEl = stepEl.querySelector('.text-gray-700');
        if (titleEl && titleEl.textContent.trim()) {
          names.push(titleEl.textContent.trim());
        }
      }
    });
    if (names.length === 4) return names;
    return ['< 1 Tahun', '1 - 2 Tahun', '> 2 Tahun', 'RE-Use'];
  }

  function syncStorageFlowLabels() {
    var stepNames = getStorageDynamicStepNames();

    // 1. Sync Chart.js doughnut chart labels
    if (window.dashInventorySummaryPieChart && window.dashInventorySummaryPieChart.data) {
      window.dashInventorySummaryPieChart.data.labels = stepNames;
    }

    // 2. Sync Right-side Legend item names (#inv-legend-name-1 .. #inv-legend-name-4)
    stepNames.forEach(function (name, idx) {
      var legendNameEl = document.getElementById('inv-legend-name-' + (idx + 1));
      if (legendNameEl) {
        legendNameEl.textContent = name;
      }
    });
  }

  // 1. Initialize Inventory Summary Pie/Doughnut Chart (Storage Tekno)
  var ctxInvPie = document.getElementById("dashInventorySummaryPieChart");
  if (ctxInvPie) {
    window.dashInventorySummaryPieChart = new Chart(ctxInvPie, {
      type: 'doughnut',
      data: {
        labels: getStorageDynamicStepNames(),
        datasets: [{
          data: [1, 1, 1, 1],
          backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
          hoverBackgroundColor: ['#17a673', '#2c9faf', '#dfa827', '#be2617'],
          hoverBorderColor: "rgba(234, 236, 244, 1)",
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        legend: {
          display: false
        },
        tooltips: {
          backgroundColor: "rgba(255,255,255,0.95)",
          bodyFontColor: "#4a5568",
          bodyFontSize: 9,
          titleFontSize: 9,
          borderColor: '#e2e8f0',
          borderWidth: 1,
          xPadding: 6,
          yPadding: 4,
          displayColors: true,
          boxWidth: 6,
          boxHeight: 6,
          caretPadding: 4,
          caretSize: 4,
          cornerRadius: 4,
          callbacks: {
            label: function (tooltipItem, data) {
              var realValues = window.inventorySummaryRealValues || [0, 0, 0, 0];
              var realVal = realValues[tooltipItem.index] !== undefined ? realValues[tooltipItem.index] : 0;
              var sum = realValues.reduce(function (a, b) { return a + b; }, 0);
              var pct = sum > 0 ? Math.round((realVal / sum) * 100) : 0;
              var stepName = data.labels[tooltipItem.index] || 'Category';
              return stepName + ': ' + realVal + ' (' + pct + '%)';
            }
          }
        }
      }
    });

    syncStorageFlowLabels();
  }

  // 1b. Initialize Storage HUB & Outlet Warehouse Pie/Doughnut Chart
  window.storageHubRealValues = [0, 0, 0, 0];

  function getStorageHubDynamicStepNames() {
    var storageContainer = document.getElementById('storage-steps-container-hub');
    var flowSteps = storageContainer ? storageContainer.querySelectorAll('.flow-step') : [];
    var names = [];
    flowSteps.forEach(function (stepEl, idx) {
      if (idx > 0) {
        var titleEl = stepEl.querySelector('.text-gray-700');
        if (titleEl && titleEl.textContent.trim()) {
          names.push(titleEl.textContent.trim());
        }
      }
    });
    if (names.length === 4) return names;
    return ['Aging <3 Bulan', 'Aging 3-12 Bulan', 'Aging >12 Bulan', 'RE-Use'];
  }

  function syncStorageHubFlowLabels() {
    var stepNames = getStorageHubDynamicStepNames();
    if (window.dashStorageHubPieChart && window.dashStorageHubPieChart.data) {
      window.dashStorageHubPieChart.data.labels = stepNames;
    }
    stepNames.forEach(function (name, idx) {
      var legendNameEl = document.getElementById('hub-inv-legend-name-' + (idx + 1));
      if (legendNameEl) {
        legendNameEl.textContent = name;
      }
    });
  }

  var ctxStorageHubPie = document.getElementById("dashStorageHubPieChart");
  if (ctxStorageHubPie) {
    window.dashStorageHubPieChart = new Chart(ctxStorageHubPie, {
      type: 'doughnut',
      data: {
        labels: getStorageHubDynamicStepNames(),
        datasets: [{
          data: [1, 1, 1, 1],
          backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
          hoverBackgroundColor: ['#17a673', '#2c9faf', '#dfa827', '#be2617'],
          hoverBorderColor: "rgba(234, 236, 244, 1)",
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        legend: {
          display: false
        },
        tooltips: {
          backgroundColor: "rgba(255,255,255,0.95)",
          bodyFontColor: "#4a5568",
          bodyFontSize: 9,
          titleFontSize: 9,
          borderColor: '#e2e8f0',
          borderWidth: 1,
          xPadding: 6,
          yPadding: 4,
          displayColors: true,
          boxWidth: 6,
          boxHeight: 6,
          caretPadding: 4,
          caretSize: 4,
          cornerRadius: 4,
          callbacks: {
            label: function (tooltipItem, data) {
              var realValues = window.storageHubRealValues || [0, 0, 0, 0];
              var realVal = realValues[tooltipItem.index] !== undefined ? realValues[tooltipItem.index] : 0;
              var sum = realValues.reduce(function (a, b) { return a + b; }, 0);
              var pct = sum > 0 ? Math.round((realVal / sum) * 100) : 0;
              var stepName = data.labels[tooltipItem.index] || 'Category';
              return stepName + ': ' + realVal + ' (' + pct + '%)';
            }
          }
        }
      }
    });

    syncStorageHubFlowLabels();
  }

  function getDynamicStepNames() {
    var inboundContainer = document.getElementById('inbound-steps-container');
    var flowSteps = inboundContainer ? inboundContainer.querySelectorAll('.flow-step') : [];
    var names = [];
    flowSteps.forEach(function (stepEl) {
      var titleEl = stepEl.querySelector('.text-gray-700');
      if (titleEl && titleEl.textContent.trim()) {
        names.push(titleEl.textContent.trim());
      }
    });
    if (names.length === 7) return names;
    return [
      'Total PO',
      'PO Proses Delivery',
      'PO Terlambat Delivery',
      'PO Sudah GR',
      'PO sudah Registrasi',
      'Total GR',
      'Total Registrasi'
    ];
  }

  window.inboundFlowRealValues = [0, 0, 0, 0, 0, 0, 0];

  function syncInboundFlowLabels() {
    var stepNames = getDynamicStepNames();
    
    // 1. Sync Chart.js labels
    if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.data) {
      window.dashInboundFlowPieChart.data.labels = stepNames;
    }

    // 2. Sync Right Side DOM Filter Legend Item labels
    stepNames.forEach(function (name, idx) {
      var legendItem = document.getElementById('inbound-flow-legend-' + idx);
      if (legendItem) {
        var spanEl = legendItem.querySelector('span');
        if (spanEl) {
          spanEl.textContent = name;
        }
      }
    });
  }

  // 1b. Initialize Inbound Flow Pie/Doughnut Chart (Placed under steps)
  var ctxInboundFlowPie = document.getElementById("dashInboundFlowPieChart");
  if (ctxInboundFlowPie) {
    window.dashInboundFlowPieChart = new Chart(ctxInboundFlowPie, {
      type: 'doughnut',
      data: {
        labels: getDynamicStepNames(),
        datasets: [{
          // Render equal dummy slices so all 7 colored segments show even when values are 0
          data: [1, 1, 1, 1, 1, 1, 1],
          backgroundColor: ['#4e73df', '#36b9cc', '#e74a3b', '#1cc88a', '#6f42c1', '#f6c23e', '#5a5c69'],
          hoverBackgroundColor: ['#2e59d9', '#2c9faf', '#be2617', '#17a673', '#5a32a3', '#dfa827', '#3a3b45'],
          hoverBorderColor: "rgba(234, 236, 244, 1)",
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        legend: {
          display: false
        },
        tooltips: {
          backgroundColor: "rgba(255,255,255,0.95)",
          bodyFontColor: "#4a5568",
          bodyFontSize: 9,
          titleFontSize: 9,
          borderColor: '#e2e8f0',
          borderWidth: 1,
          xPadding: 6,
          yPadding: 4,
          displayColors: true,
          boxWidth: 6,
          boxHeight: 6,
          caretPadding: 4,
          caretSize: 4,
          cornerRadius: 4,
          callbacks: {
            label: function (tooltipItem, data) {
              var realValues = window.inboundFlowRealValues || [0, 0, 0, 0, 0, 0, 0];
              var realVal = realValues[tooltipItem.index] !== undefined ? realValues[tooltipItem.index] : 0;
              var sum = realValues.reduce(function (a, b) { return a + b; }, 0);
              var pct = sum > 0 ? Math.round((realVal / sum) * 100) : 0;
              var labelName = (data.labels && data.labels[tooltipItem.index]) ? data.labels[tooltipItem.index] : 'Step';
              return labelName + ': ' + realVal + ' (' + pct + '%)';
            }
          }
        }
      }
    });

    syncInboundFlowLabels();
  }

  // Helper function to update Inventory Summary Pie Chart values (Storage Tekno)
  window.updateInventorySummaryPieChart = function (newValues) {
    if (!newValues || !Array.isArray(newValues)) return;
    window.inventorySummaryRealValues = newValues;
    var sum = newValues.reduce(function (a, b) { return a + b; }, 0);
    var totalEl = document.getElementById('inventory-pie-total-val');
    if (totalEl) totalEl.textContent = sum;
    if (window.dashInventorySummaryPieChart && window.dashInventorySummaryPieChart.data) {
      syncStorageFlowLabels();
      if (sum === 0) {
        window.dashInventorySummaryPieChart.data.datasets[0].data = [1, 1, 1, 1];
      } else {
        window.dashInventorySummaryPieChart.data.datasets[0].data = newValues;
      }
      window.dashInventorySummaryPieChart.update();
    }
  };

  // Helper function to update Storage HUB & Outlet Warehouse Pie Chart values
  window.updateStorageHubPieChart = function (newValues) {
    if (!newValues || !Array.isArray(newValues)) return;
    window.storageHubRealValues = newValues;
    var sum = newValues.reduce(function (a, b) { return a + b; }, 0);
    var totalEl = document.getElementById('hub-inventory-pie-total-val');
    if (totalEl) totalEl.textContent = sum;
    if (window.dashStorageHubPieChart && window.dashStorageHubPieChart.data) {
      syncStorageHubFlowLabels();
      if (sum === 0) {
        window.dashStorageHubPieChart.data.datasets[0].data = [1, 1, 1, 1];
      } else {
        window.dashStorageHubPieChart.data.datasets[0].data = newValues;
      }
      window.dashStorageHubPieChart.update();
    }
  };

  // Helper function to update Inbound Flow Pie Chart values
  window.updateInboundFlowPieChart = function (newValues) {
    if (!newValues || !Array.isArray(newValues)) return;
    window.inboundFlowRealValues = newValues;
    var sum = newValues.reduce(function (a, b) { return a + b; }, 0);
    var totalEl = document.getElementById('inbound-pie-total-val');
    if (totalEl) totalEl.textContent = sum;
    if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.data) {
      syncInboundFlowLabels();
      if (sum === 0) {
        window.dashInboundFlowPieChart.data.datasets[0].data = [1, 1, 1, 1, 1, 1, 1];
      } else {
        window.dashInboundFlowPieChart.data.datasets[0].data = newValues;
      }
      window.dashInboundFlowPieChart.update();
    }
  };

  // Backward compatibility alias
  window.updateInboundFlowBarChart = window.updateInboundFlowPieChart;

  // Clickable Filter Handler for Inbound Flow Steps & Right-side Filter Labels
  window.toggleInboundFlowSegment = function (index) {
    if (window.dashInboundFlowPieChart) {
      var meta = window.dashInboundFlowPieChart.getDatasetMeta(0);
      if (meta && meta.data && meta.data[index]) {
        meta.data[index].hidden = !meta.data[index].hidden;
        window.dashInboundFlowPieChart.update();
        var legendItem = document.getElementById('inbound-flow-legend-' + index);
        if (legendItem) {
          if (meta.data[index].hidden) {
            legendItem.style.opacity = '0.35';
            legendItem.style.textDecoration = 'line-through';
          } else {
            legendItem.style.opacity = '1';
            legendItem.style.textDecoration = 'none';
          }
        }
      }
    }
  };

  // 2. Dynamic progress bar color coding (red 0-49%, yellow 50-75%, green 75-100%)
  function getProgressColor(percent) {
    if (percent < 50) return { bg: 'bg-danger', text: 'text-danger' };
    if (percent < 75) return { bg: 'bg-warning', text: 'text-warning' };
    return { bg: 'bg-success', text: 'text-success' };
  }

  function applyProgressColor(barId, textId) {
    var bar = document.getElementById(barId);
    var text = document.getElementById(textId);
    if (!bar || !text) return;
    var percent = parseFloat(text.textContent) || 0;
    var colors = getProgressColor(percent);
    bar.className = 'progress-bar ' + colors.bg;
    text.className = text.className.replace(/text-(primary|success|warning|danger|info)/g, '').trim();
    text.classList.add(colors.text);
  }

  // Apply to Inbound progress
  applyProgressColor('flow-progress-bar', 'flow-rate-text');
  // Apply to Outbound progress
  applyProgressColor('outbound-progress-bar', 'outbound-progress-percent');

  // 3. Receiving Trend Line Chart (Perangkat IN & Perangkat OUT per month from Storage Tekno)
  var ctxReceivingTrend = document.getElementById("dashReceivingTrendLineChart");
  if (ctxReceivingTrend) {
    window.dashReceivingTrendLineChart = new Chart(ctxReceivingTrend, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
          {
            label: 'Perangkat IN',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.08)',
            pointBackgroundColor: '#1cc88a',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            fill: false,
            lineTension: 0.3
          },
          {
            label: 'Perangkat OUT',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            borderColor: '#e74a3b',
            backgroundColor: 'rgba(231, 74, 59, 0.08)',
            pointBackgroundColor: '#e74a3b',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            fill: false,
            lineTension: 0.3
          }
        ]
      },
      options: {
        maintainAspectRatio: false,
        layout: {
          padding: { left: 4, right: 8, top: 4, bottom: 0 }
        },
        scales: {
          xAxes: [{
            gridLines: { display: false, drawBorder: false },
            ticks: { fontSize: 9, fontColor: '#858796', padding: 4 }
          }],
          yAxes: [{
            gridLines: { color: '#eaecf4', zeroLineColor: '#eaecf4', drawBorder: false, borderDash: [2] },
            ticks: { fontSize: 9, fontColor: '#858796', padding: 6, beginAtZero: true, suggestedMax: 10, maxTicksLimit: 5,
              callback: function(value) { return Number.isInteger(value) ? value : ''; }
            }
          }]
        },
        legend: {
          display: true,
          position: 'top',
          labels: {
            boxWidth: 10,
            fontSize: 9,
            fontColor: '#4a5568',
            padding: 8,
            usePointStyle: true
          }
        },
        tooltips: {
          backgroundColor: 'rgba(255,255,255,0.95)',
          bodyFontColor: '#4a5568',
          titleFontColor: '#2d3748',
          titleFontSize: 10,
          bodyFontSize: 10,
          borderColor: '#e2e8f0',
          borderWidth: 1,
          xPadding: 8,
          yPadding: 6,
          displayColors: true,
          boxWidth: 6,
          boxHeight: 6,
          caretPadding: 6,
          callbacks: {
            label: function(tooltipItem, data) {
              var dsLabel = data.datasets[tooltipItem.datasetIndex].label || 'Value';
              return dsLabel + ': ' + tooltipItem.yLabel + ' Unit';
            }
          }
        }
      }
    });
  }

  // Helper function to update Receiving Trend Line Chart (Storage Tekno)
  window.updateDashReceivingTrendChart = function (inData, outData, inDetails, outDetails, yearText) {
    if (window.dashReceivingTrendLineChart && window.dashReceivingTrendLineChart.data) {
      window.dashReceivingTrendLineChart.data.datasets[0].data = inData || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      window.dashReceivingTrendLineChart.data.datasets[1].data = outData || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      window.dashReceivingTrendLineChart._inDetails = inDetails || [];
      window.dashReceivingTrendLineChart._outDetails = outDetails || [];
      
      // Update _recordsPerIndex for modal detail click if needed
      var mLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      window.dashReceivingTrendLineChart._recordsPerIndex = mLabels.map(function(m, idx) {
        var inRecs = (inDetails && inDetails[idx]) ? inDetails[idx] : [];
        var outRecs = (outDetails && outDetails[idx]) ? outDetails[idx] : [];
        return inRecs.concat(outRecs);
      });

      window.dashReceivingTrendLineChart.update();

      if (window.FormulaController) {
        window.FormulaController.makeChartClickable(window.dashReceivingTrendLineChart, "Perangkat IN & OUT (Storage Tekno)");
      }
    }
    var badgeEl = document.getElementById('trends-title-period');
    if (badgeEl && yearText) {
      badgeEl.textContent = 'Storage Tekno (' + yearText + ')';
    }
  };

  // 4. KPI Best Practice status badge helper
  function getKpiStatus(percent) {
    if (percent <= 50) return { label: 'Very Poor', bg: '#dc3545', color: '#fff' };
    if (percent <= 60) return { label: 'Poor', bg: '#fd7e14', color: '#fff' };
    if (percent <= 70) return { label: 'Good', bg: '#ffc107', color: '#212529' };
    if (percent <= 85) return { label: 'Very Good', bg: '#20c997', color: '#fff' };
    return { label: 'Excellent', bg: '#1cc88a', color: '#fff' };
  }

  function applyKpiStatus(pctElementId, statusElementId) {
    var pctEl = document.getElementById(pctElementId);
    var statusEl = document.getElementById(statusElementId);
    if (!pctEl || !statusEl) return;
    var pct = parseFloat(pctEl.textContent) || 0;
    var status = getKpiStatus(pct);
    statusEl.textContent = status.label;
    statusEl.style.backgroundColor = status.bg;
    statusEl.style.color = status.color;
    // Color the percentage text to match
    pctEl.style.color = status.bg;
  }

  // Apply KPI statuses
  applyKpiStatus('kpi-stock-opname-pct', 'kpi-stock-opname-status');
  applyKpiStatus('kpi-putaway-pct', 'kpi-putaway-status');
  applyKpiStatus('kpi-otd-pct', 'kpi-otd-status');
  applyKpiStatus('kpi-safety-pct', 'kpi-safety-status');

  // 5. Storage Utilization bar color (matches Inbound progress bar gradient style)
  function applyStorageUtilColor() {
    var rateEl = document.getElementById('storage-util-rate');
    var bar = document.getElementById('storage-util-bar');
    if (!rateEl || !bar) return;
    var pct = parseFloat(rateEl.textContent) || 0;
    bar.style.width = pct + '%';
    // Use the same dynamic color logic as other progress bars
    if (pct < 50) {
      bar.style.background = 'linear-gradient(90deg, #e74a3b 0%, #be2617 100%)';
    } else if (pct < 75) {
      bar.style.background = 'linear-gradient(90deg, #f6c23e 0%, #dfa827 100%)';
    } else {
      bar.style.background = 'linear-gradient(90deg, #4e73df 0%, #224abe 100%)';
    }
  }
  applyStorageUtilColor();

  // 6. Initialize KPI Monitoring Line Chart (12 Months Line Chart with Matriks Evaluasi 9 KPI Click Details)
  var ctxKpiLine = document.getElementById("dashKpiMonitoringLineChart");
  if (ctxKpiLine) {
    var kpiMonthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var targetMonthly = [95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0, 95.0];
    var initial12Months = [96.8, null, null, null, null, null, null, null, null, null, null, null];

    window.dashKpiMonitoringLineChart = new Chart(ctxKpiLine, {
      type: 'line',
      data: {
        labels: kpiMonthLabels,
        datasets: [
          {
            label: 'Target',
            data: targetMonthly,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.04)',
            borderDash: [6, 4],
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4.5,
            pointHoverRadius: 6.5,
            borderWidth: 2.2,
            fill: false,
            lineTension: 0.2
          },
          {
            label: 'Realisasi',
            data: initial12Months.slice(),
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.08)',
            pointBackgroundColor: '#1cc88a',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5.5,
            pointHoverRadius: 7.5,
            borderWidth: 3,
            fill: false,
            lineTension: 0.2
          }
        ]
      },
      options: {
        maintainAspectRatio: false,
        layout: {
          padding: { left: 4, right: 12, top: 10, bottom: 0 }
        },
        scales: {
          xAxes: [{
            gridLines: { display: true, color: '#f8f9fc', zeroLineColor: '#eaecf4' },
            ticks: {
              fontSize: 11,
              fontColor: '#4a5568',
              fontStyle: 'bold',
              padding: 8
            }
          }],
          yAxes: [{
            gridLines: { color: '#eaecf4', zeroLineColor: '#eaecf4', drawBorder: false, borderDash: [2] },
            ticks: {
              fontSize: 10,
              fontColor: '#858796',
              padding: 6,
              beginAtZero: true,
              suggestedMax: 105,
              maxTicksLimit: 6,
              callback: function (value) {
                return value + '%';
              }
            }
          }]
        },
        legend: {
          display: true,
          position: 'bottom',
          onClick: function(e, legendItem) {
            var index = legendItem.datasetIndex;
            var ci = this.chart;
            var meta = ci.getDatasetMeta(index);
            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
            ci.update();
          },
          labels: {
            boxWidth: 14,
            fontSize: 11.5,
            fontColor: '#2d3748',
            fontStyle: 'bold',
            padding: 16,
            usePointStyle: true
          }
        },
        tooltips: {
          backgroundColor: 'rgba(255,255,255,0.96)',
          bodyFontColor: '#4a5568',
          titleFontColor: '#2d3748',
          titleFontSize: 11.5,
          bodyFontSize: 11,
          borderColor: '#e2e8f0',
          borderWidth: 1,
          xPadding: 12,
          yPadding: 10,
          displayColors: true,
          boxWidth: 8,
          boxHeight: 8,
          callbacks: {
            label: function (tooltipItem, data) {
              var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
              var val = tooltipItem.yLabel;
              if (val === null || val === undefined || isNaN(val)) {
                return datasetLabel + ': - (Klik untuk detail Matriks)';
              }
              return datasetLabel + ': ' + val + '% (Klik untuk detail)';
            }
          }
        }
      }
    });

    // Make chart interactive on hover & click
    ctxKpiLine.style.cursor = 'pointer';
    ctxKpiLine.addEventListener('click', function(evt) {
      var chart = window.dashKpiMonitoringLineChart;
      if (!chart) return;

      var rect = ctxKpiLine.getBoundingClientRect();
      var clickY = evt.clientY - rect.top;

      // Prevent clicks on the bottom Target and Realisasi legend labels from triggering modal
      if (chart.legend && chart.legend.top !== undefined && clickY >= chart.legend.top - 5) {
        return;
      }
      if (chart.chartArea && clickY > chart.chartArea.bottom + 25) {
        return;
      }

      var activePoints = chart.getElementsAtEvent(evt);
      if (activePoints && activePoints.length > 0) {
        var clickedIdx = activePoints[0]._index;
        if (clickedIdx !== undefined && clickedIdx >= 0 && typeof window.openKpiMonthDetailModal === 'function') {
          window.openKpiMonthDetailModal(clickedIdx);
          return;
        }
      }
      
      // Fallback calculation by click X position on the chart area / month ticks
      if (window.FormulaController && typeof window.FormulaController.getClickedChartIndex === 'function') {
        var idx = window.FormulaController.getClickedChartIndex(chart, evt);
        if (idx >= 0 && typeof window.openKpiMonthDetailModal === 'function') {
          window.openKpiMonthDetailModal(idx);
        }
      }
    });

    window.updateDashKpiMonitoringChart = function (realisasiMonthlyData) {
      if (!window.dashKpiMonitoringLineChart) return;
      if (Array.isArray(realisasiMonthlyData)) {
        window.dashKpiMonitoringLineChart.data.datasets[1].data = realisasiMonthlyData;
      } else {
        window.dashKpiMonitoringLineChart.data.datasets[1].data = [null, null, null, null, null, null, null, null, null, null, null, null];
      }
      window.dashKpiMonitoringLineChart.update();
    };
  }

  syncStorageFlowLabels();
});
