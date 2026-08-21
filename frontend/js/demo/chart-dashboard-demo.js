// chart-dashboard-demo.js - Head Dashboard Overview Controller
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

document.addEventListener("DOMContentLoaded", function () {
  window.inventorySummaryRealValues = [0, 0, 0, 0];

  function getStorageDynamicStepNames() {
    return ['Total Perangkat', 'Total NBV'];
  }

  function syncStorageFlowLabels() {
    var stepNames = ['Total Perangkat', 'Total NBV'];

    // 1. Sync Chart.js doughnut chart labels
    if (window.dashInventorySummaryPieChart && window.dashInventorySummaryPieChart.data) {
      window.dashInventorySummaryPieChart.data.labels = stepNames;
    }

    // 2. Sync Legend item names (#inv-legend-name-1 and #inv-legend-name-2)
    var legend1 = document.getElementById('inv-legend-name-1');
    if (legend1) legend1.textContent = 'Total Perangkat';
    var legend2 = document.getElementById('inv-legend-name-2');
    if (legend2) legend2.textContent = 'Total NBV';
  }

  // 1. Initialize Inventory Summary Pie/Doughnut Chart (Storage Tekno)
  var ctxInvPie = document.getElementById("dashInventorySummaryPieChart");
  if (ctxInvPie) {
    window.dashInventorySummaryPieChart = new Chart(ctxInvPie, {
      type: 'doughnut',
      data: {
        labels: ['Total Perangkat', 'Total NBV'],
        datasets: [{
          data: [1, 1],
          backgroundColor: ['#4e73df', '#1cc88a'],
          hoverBackgroundColor: ['#2e59d9', '#17a673'],
          hoverBorderColor: "#ffffff",
          hoverBorderWidth: 3,
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        layout: {
          padding: {
            top: 6,
            bottom: 6,
            left: 6,
            right: 6
          }
        },
        legend: {
          display: false
        },
        tooltips: {
          position: 'outsideArc',
          backgroundColor: "rgba(15, 23, 42, 0.94)",
          bodyFontColor: "#ffffff",
          bodyFontSize: 9,
          titleFontSize: 9,
          borderColor: '#334155',
          borderWidth: 1,
          xPadding: 7,
          yPadding: 5,
          displayColors: true,
          boxWidth: 7,
          boxHeight: 7,
          caretPadding: 6,
          caretSize: 5,
          cornerRadius: 6,
          callbacks: {
            label: function (tooltipItem, data) {
              var totalQty = window.inventoryTotalAssetVal || 0;
              var totalNbv = window.inventoryTotalNbvVal || 0;
              if (tooltipItem.index === 0) {
                return 'Total Perangkat: ' + totalQty.toLocaleString('id-ID') + ' Unit';
              } else {
                return 'Total NBV: Rp ' + totalNbv.toLocaleString('id-ID');
              }
            }
          }
        }
      }
    });

    // Link hover on outside legend items to chart slices
    [1, 2].forEach(function (idx) {
      var legendItem = document.getElementById('storage-legend-item-' + idx);
      if (legendItem) {
        legendItem.addEventListener('mouseenter', function () {
          if (window.dashInventorySummaryPieChart && window.dashInventorySummaryPieChart.getDatasetMeta(0).data[idx - 1]) {
            window.dashInventorySummaryPieChart.tooltip._active = [window.dashInventorySummaryPieChart.getDatasetMeta(0).data[idx - 1]];
            window.dashInventorySummaryPieChart.tooltip.update(true);
            window.dashInventorySummaryPieChart.draw();
          }
        });
        legendItem.addEventListener('mouseleave', function () {
          if (window.dashInventorySummaryPieChart) {
            window.dashInventorySummaryPieChart.tooltip._active = [];
            window.dashInventorySummaryPieChart.tooltip.update(true);
            window.dashInventorySummaryPieChart.draw();
          }
        });
      }
    });
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

  function getInboundDynamicStepNames() {
    return ['Ontime', 'Terlambat'];
  }

  window.inboundFlowRealValues = [0, 0];

  function syncInboundFlowLabels() {
    // 1. Sync Chart.js labels
    if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.data) {
      window.dashInboundFlowPieChart.data.labels = ['Ontime', 'Terlambat'];
    }
  }

  // Custom Tooltip Positioner: places tooltip outside the doughnut ring so center text is never blocked
  if (Chart.Tooltip && Chart.Tooltip.positioners && !Chart.Tooltip.positioners.outsideArc) {
    Chart.Tooltip.positioners.outsideArc = function (elements, eventPosition) {
      if (!elements.length) return false;
      var el = elements[0];
      var vm = el._view;
      if (!vm) return eventPosition;
      var angle = (vm.startAngle + vm.endAngle) / 2;
      var radius = (vm.outerRadius || 50) + 12;
      return {
        x: vm.x + Math.cos(angle) * radius,
        y: vm.y + Math.sin(angle) * radius
      };
    };
  }

  // 1b. Initialize Inbound Flow Pie/Doughnut Chart (Placed under steps)
  var ctxInboundFlowPie = document.getElementById("dashInboundFlowPieChart");
  if (ctxInboundFlowPie) {
    window.dashInboundFlowPieChart = new Chart(ctxInboundFlowPie, {
      type: 'doughnut',
      data: {
        labels: ['Ontime', 'Terlambat'],
        datasets: [{
          // Render equal dummy slices so 2 colored segments show even when values are 0
          data: [1, 1],
          backgroundColor: ['#1cc88a', '#e74a3b'],
          hoverBackgroundColor: ['#17a673', '#be2617'],
          hoverBorderColor: "#ffffff",
          hoverBorderWidth: 3,
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutoutPercentage: 68,
        layout: {
          padding: {
            top: 6,
            bottom: 6,
            left: 6,
            right: 6
          }
        },
        legend: {
          display: false
        },
        tooltips: {
          position: 'outsideArc',
          backgroundColor: "rgba(15, 23, 42, 0.94)",
          bodyFontColor: "#ffffff",
          bodyFontSize: 10,
          titleFontSize: 10,
          borderColor: '#334155',
          borderWidth: 1,
          xPadding: 8,
          yPadding: 6,
          displayColors: true,
          boxWidth: 8,
          boxHeight: 8,
          caretPadding: 6,
          caretSize: 5,
          cornerRadius: 6,
          callbacks: {
            label: function (tooltipItem, data) {
              var ontimeVal = window.inboundOntimeVal || 0;
              var terlambatVal = window.inboundTerlambatVal || 0;
              var totalPo = (window.inboundTotalPoVal !== undefined && window.inboundTotalPoVal > 0) ? window.inboundTotalPoVal : (ontimeVal + terlambatVal);
              var val = tooltipItem.index === 0 ? ontimeVal : terlambatVal;
              var pct = totalPo > 0 ? Math.round((val / totalPo) * 100) : 0;
              var labelName = (data.labels && data.labels[tooltipItem.index]) ? data.labels[tooltipItem.index] : 'Status';
              return labelName + ': ' + val + ' PO (' + pct + '%)';
            }
          }
        }
      }
    });

    syncInboundFlowLabels();

    // Link hover on outside legend items to chart slices
    var legendOntime = document.getElementById('inbound-flow-legend-1');
    if (legendOntime) {
      legendOntime.addEventListener('mouseenter', function () {
        if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.getDatasetMeta(0).data[0]) {
          window.dashInboundFlowPieChart.tooltip._active = [window.dashInboundFlowPieChart.getDatasetMeta(0).data[0]];
          window.dashInboundFlowPieChart.tooltip.update(true);
          window.dashInboundFlowPieChart.draw();
        }
      });
      legendOntime.addEventListener('mouseleave', function () {
        if (window.dashInboundFlowPieChart) {
          window.dashInboundFlowPieChart.tooltip._active = [];
          window.dashInboundFlowPieChart.tooltip.update(true);
          window.dashInboundFlowPieChart.draw();
        }
      });
    }

    var legendTerlambat = document.getElementById('inbound-flow-legend-2');
    if (legendTerlambat) {
      legendTerlambat.addEventListener('mouseenter', function () {
        if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.getDatasetMeta(0).data[1]) {
          window.dashInboundFlowPieChart.tooltip._active = [window.dashInboundFlowPieChart.getDatasetMeta(0).data[1]];
          window.dashInboundFlowPieChart.tooltip.update(true);
          window.dashInboundFlowPieChart.draw();
        }
      });
      legendTerlambat.addEventListener('mouseleave', function () {
        if (window.dashInboundFlowPieChart) {
          window.dashInboundFlowPieChart.tooltip._active = [];
          window.dashInboundFlowPieChart.tooltip.update(true);
          window.dashInboundFlowPieChart.draw();
        }
      });
    }
  }

  // Helper function to update Inventory Summary Pie Chart values (Storage Tekno)
  window.updateInventorySummaryPieChart = function (totalQty, totalNbv) {
    if (Array.isArray(totalQty)) {
      totalQty = totalQty.reduce(function (a, b) { return a + b; }, 0);
    }
    if (typeof totalQty === 'number') window.inventoryTotalAssetVal = totalQty;
    if (typeof totalNbv === 'number') window.inventoryTotalNbvVal = totalNbv;

    var qtyVal = (window.inventoryTotalAssetVal || 0);
    var nbvVal = (window.inventoryTotalNbvVal || 0);

    var totalEl = document.getElementById('inventory-pie-total-val');
    if (totalEl) totalEl.textContent = qtyVal.toLocaleString('id-ID') + ' Unit';

    var leg1 = document.getElementById('inv-legend-1');
    if (leg1) leg1.textContent = qtyVal.toLocaleString('id-ID') + ' Unit';

    var leg2 = document.getElementById('inv-legend-2');
    if (leg2) leg2.textContent = 'Rp ' + nbvVal.toLocaleString('id-ID');

    if (window.dashInventorySummaryPieChart && window.dashInventorySummaryPieChart.data) {
      window.dashInventorySummaryPieChart.data.labels = ['Total Perangkat', 'Total NBV'];
      window.dashInventorySummaryPieChart.data.datasets[0].data = [1, 1];
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
    var ontime = 0;
    var terlambat = 0;
    var totalPo = 0;

    if (Array.isArray(newValues)) {
      if (newValues.length === 2) {
        ontime = parseInt(newValues[0]) || 0;
        terlambat = parseInt(newValues[1]) || 0;
        totalPo = ontime + terlambat;
      } else if (newValues.length >= 3) {
        totalPo = parseInt(newValues[0]) || 0;
        ontime = parseInt(newValues[1]) || 0;
        terlambat = parseInt(newValues[2]) || 0;
        if (totalPo === 0 && (ontime > 0 || terlambat > 0)) {
          totalPo = ontime + terlambat;
        }
      }
    }

    window.inboundOntimeVal = ontime;
    window.inboundTerlambatVal = terlambat;
    window.inboundTotalPoVal = totalPo;
    window.inboundFlowRealValues = [ontime, terlambat];

    var ontimePct = totalPo > 0 ? Math.round((ontime / totalPo) * 100) : 0;
    var terlambatPct = totalPo > 0 ? Math.round((terlambat / totalPo) * 100) : 0;

    // Update center elements
    var totalEl = document.getElementById('inbound-pie-total-val');
    if (totalEl) totalEl.textContent = totalPo + ' PO';

    var pctEl = document.getElementById('inbound-pie-pct-val');
    if (pctEl) pctEl.textContent = '100%';

    // Update top flow steps if elements exist
    var flowPoEl = document.getElementById('flow-po-count');
    if (flowPoEl && newValues && newValues.length > 0) flowPoEl.textContent = totalPo + ' PO';
    var flowOntimeEl = document.getElementById('flow-po-proses-delivery');
    if (flowOntimeEl && newValues && newValues.length > 1) flowOntimeEl.textContent = ontime + ' PO';
    var flowTerlambatEl = document.getElementById('flow-po-terlambat-delivery');
    if (flowTerlambatEl && newValues && newValues.length > 2) flowTerlambatEl.textContent = terlambat + ' PO';

    // Update legend badges
    var totalBadge = document.getElementById('legend-po-total-badge');
    if (totalBadge) totalBadge.textContent = totalPo + ' (100%)';

    var ontimeBadge = document.getElementById('legend-po-ontime-badge');
    if (ontimeBadge) ontimeBadge.textContent = ontime + ' (' + ontimePct + '%)';

    var terlambatBadge = document.getElementById('legend-po-terlambat-badge');
    if (terlambatBadge) terlambatBadge.textContent = terlambat + ' (' + terlambatPct + '%)';

    if (window.dashInboundFlowPieChart && window.dashInboundFlowPieChart.data) {
      if (ontime === 0 && terlambat === 0) {
        window.dashInboundFlowPieChart.data.datasets[0].data = [1, 1];
      } else {
        window.dashInboundFlowPieChart.data.datasets[0].data = [ontime, terlambat];
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
        var legendItem = document.getElementById('inbound-flow-legend-' + (index + 1));
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

  syncStorageFlowLabels();
});

