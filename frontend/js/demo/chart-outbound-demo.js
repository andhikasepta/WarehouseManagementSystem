// Chart.js initialization for Outbound Dashboard Charts
if (typeof Chart !== 'undefined') {
    Chart.defaults.global.defaultFontFamily = 'Nunito, -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';
}

window._outboundMonthlyData = window._outboundMonthlyData || null;

window.initOrUpdateOutboundCharts = function (monthlyCharts) {
    if (typeof Chart === 'undefined') {
        setTimeout(function () {
            window.initOrUpdateOutboundCharts(monthlyCharts);
        }, 150);
        return;
    }

    var bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    if (monthlyCharts) {
        window._outboundMonthlyData = monthlyCharts;
    }

    var mrPercentages = (window._outboundMonthlyData && window._outboundMonthlyData.bulanan_mr && window._outboundMonthlyData.bulanan_mr.percentages)
        ? window._outboundMonthlyData.bulanan_mr.percentages
        : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    var poPercentages = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    var closePercentages = (window._outboundMonthlyData && window._outboundMonthlyData.close_mr && window._outboundMonthlyData.close_mr.percentages)
        ? window._outboundMonthlyData.close_mr.percentages
        : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    var modaPercentages = (window._outboundMonthlyData && window._outboundMonthlyData.moda_delivery && window._outboundMonthlyData.moda_delivery.percentages)
        ? window._outboundMonthlyData.moda_delivery.percentages
        : [0, 0, 0, 0];

    var modaLabels = (window._outboundMonthlyData && window._outboundMonthlyData.moda_delivery && window._outboundMonthlyData.moda_delivery.labels)
        ? window._outboundMonthlyData.moda_delivery.labels
        : ['Udara', 'Laut', 'Darat', 'Udara PTP'];

    // Common standard Y-Axis configuration: line chart with min 0% till 100%
    function getStandardPercentageYAxis() {
        return [{
            ticks: {
                min: 0,
                max: 100,
                stepSize: 20,
                padding: 10,
                fontSize: 11,
                callback: function (value) { return value + '%'; }
            },
            gridLines: {
                color: "rgb(234, 236, 244)",
                zeroLineColor: "rgb(234, 236, 244)",
                drawBorder: false,
                borderDash: [2],
                zeroLineBorderDash: [2]
            }
        }];
    }

    // 1. Chart Bulanan — Jumlah MR (%) (Line Chart 0% - 100%)
    var ctxBulananMr = document.getElementById("chartBulananJumlahMr");
    if (ctxBulananMr) {
        if (window.chartBulananJumlahMr && window.chartBulananJumlahMr.config && window.chartBulananJumlahMr.config.type === 'line') {
            window.chartBulananJumlahMr.data.labels = (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels;
            window.chartBulananJumlahMr.data.datasets[0].data = mrPercentages;
            window.chartBulananJumlahMr.update();
        } else {
            if (window.chartBulananJumlahMr && typeof window.chartBulananJumlahMr.destroy === 'function') {
                window.chartBulananJumlahMr.destroy();
            }
            window.chartBulananJumlahMr = new Chart(ctxBulananMr, {
                type: 'line',
                data: {
                    labels: (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels,
                    datasets: [{
                        label: "Jumlah MR (%)",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.08)",
                        borderColor: "#4e73df",
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointBackgroundColor: "#4e73df",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "#2e59d9",
                        pointHoverBorderColor: "#ffffff",
                        pointHitRadius: 10,
                        data: mrPercentages
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 15, top: 15, bottom: 0 } },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: { fontSize: 11 }
                        }],
                        yAxes: getStandardPercentageYAxis()
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleFontColor: '#6e707e',
                        titleMarginBottom: 10,
                        titleFontSize: 13,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var pct = tooltipItem.yLabel;
                                var idx = tooltipItem.index;
                                var lines = ['Persentase MR 2026: ' + pct + '%'];
                                if (window._outboundMonthlyData && window._outboundMonthlyData.bulanan_mr && window._outboundMonthlyData.bulanan_mr.counts) {
                                    var count = window._outboundMonthlyData.bulanan_mr.counts[idx];
                                    lines.push('Total NO MR: ' + Number(count || 0).toLocaleString('id-ID'));
                                }
                                return lines;
                            }
                        }
                    }
                }
            });
        }
    }

    // 2. Chart Bulanan — Jumlah PO (%) (Line Chart 0% - 100%)
    var ctxBulananPo = document.getElementById("chartBulananJumlahPo");
    if (ctxBulananPo) {
        if (window.chartBulananJumlahPo && window.chartBulananJumlahPo.config && window.chartBulananJumlahPo.config.type === 'line') {
            window.chartBulananJumlahPo.data.labels = (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels;
            window.chartBulananJumlahPo.data.datasets[0].data = poPercentages;
            window.chartBulananJumlahPo.update();
        } else {
            if (window.chartBulananJumlahPo && typeof window.chartBulananJumlahPo.destroy === 'function') {
                window.chartBulananJumlahPo.destroy();
            }
            window.chartBulananJumlahPo = new Chart(ctxBulananPo, {
                type: 'line',
                data: {
                    labels: (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels,
                    datasets: [{
                        label: "Jumlah PO (%)",
                        lineTension: 0.3,
                        backgroundColor: "rgba(54, 185, 204, 0.08)",
                        borderColor: "#36b9cc",
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointBackgroundColor: "#36b9cc",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "#2c9faf",
                        pointHoverBorderColor: "#ffffff",
                        pointHitRadius: 10,
                        data: poPercentages
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 15, top: 15, bottom: 0 } },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: { fontSize: 11 }
                        }],
                        yAxes: getStandardPercentageYAxis()
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleFontColor: '#6e707e',
                        titleMarginBottom: 10,
                        titleFontSize: 13,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return 'Persentase PO: ' + (tooltipItem.yLabel || 0) + '%';
                            }
                        }
                    }
                }
            });
        }
    }

    // 3. Chart Close MR (%) (Line Chart 0% - 100%)
    var ctxCloseMr = document.getElementById("chartCloseMr");
    if (ctxCloseMr) {
        if (window.chartCloseMr && window.chartCloseMr.config && window.chartCloseMr.config.type === 'line') {
            window.chartCloseMr.data.labels = (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels;
            window.chartCloseMr.data.datasets[0].data = closePercentages;
            window.chartCloseMr.update();
        } else {
            if (window.chartCloseMr && typeof window.chartCloseMr.destroy === 'function') {
                window.chartCloseMr.destroy();
            }
            window.chartCloseMr = new Chart(ctxCloseMr, {
                type: 'line',
                data: {
                    labels: (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels,
                    datasets: [{
                        label: "Close MR (%)",
                        lineTension: 0.3,
                        backgroundColor: "rgba(28, 200, 138, 0.08)",
                        borderColor: "#1cc88a",
                        borderWidth: 2.5,
                        pointRadius: 3.5,
                        pointBackgroundColor: "#1cc88a",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: "#17a673",
                        pointHoverBorderColor: "#ffffff",
                        pointHitRadius: 10,
                        data: closePercentages
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 15, top: 15, bottom: 0 } },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: { fontSize: 11 }
                        }],
                        yAxes: getStandardPercentageYAxis()
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleFontColor: '#6e707e',
                        titleMarginBottom: 10,
                        titleFontSize: 13,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var pct = tooltipItem.yLabel;
                                var idx = tooltipItem.index;
                                var lines = ['Persentase Close MR: ' + pct + '%'];
                                if (window._outboundMonthlyData && window._outboundMonthlyData.close_mr) {
                                    var cData = window._outboundMonthlyData.close_mr;
                                    if (cData.counts) {
                                        lines.push('Count MR Status CLOSED: ' + Number(cData.counts[idx] || 0).toLocaleString('id-ID'));
                                    }
                                    if (cData.distinct_closed && cData.distinct_closed[idx] > 0) {
                                        lines.push('NO MR CLOSED: ' + Number(cData.distinct_closed[idx]).toLocaleString('id-ID'));
                                    }
                                    if (cData.close_rates && cData.close_rates[idx] > 0) {
                                        lines.push('Close Rate: ' + cData.close_rates[idx] + '%');
                                    }
                                }
                                return lines;
                            }
                        }
                    }
                }
            });
        }
    }

    // 4. Delivery per Moda (Value / Qty Count Data)
    var ctxCostModa = document.getElementById("costDeliveryPerModaChart");
    if (ctxCostModa) {
        var modaCounts = (window._outboundMonthlyData && window._outboundMonthlyData.moda_delivery && window._outboundMonthlyData.moda_delivery.counts)
            ? window._outboundMonthlyData.moda_delivery.counts
            : [0, 0, 0, 0];

        if (window.costDeliveryPerModaChart && window.costDeliveryPerModaChart.config && window.costDeliveryPerModaChart.config.type === 'horizontalBar') {
            window.costDeliveryPerModaChart.data.labels = modaLabels;
            window.costDeliveryPerModaChart.data.datasets[0].data = modaCounts;
            window.costDeliveryPerModaChart.update();
        } else {
            if (window.costDeliveryPerModaChart && typeof window.costDeliveryPerModaChart.destroy === 'function') {
                window.costDeliveryPerModaChart.destroy();
            }
            window.costDeliveryPerModaChart = new Chart(ctxCostModa, {
                type: 'horizontalBar',
                data: {
                    labels: modaLabels,
                    datasets: [{
                        label: "Delivery per Moda (Qty)",
                        backgroundColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e"],
                        hoverBackgroundColor: ["#2e59d9", "#17a673", "#2c9faf", "#dda20a"],
                        borderColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e"],
                        borderWidth: 1,
                        data: modaCounts
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 5, right: 15, top: 10, bottom: 5 } },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2]
                            },
                            ticks: {
                                min: 0,
                                fontSize: 11,
                                callback: function (value) {
                                    return Number(value).toLocaleString('id-ID');
                                }
                            }
                        }],
                        yAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: { fontSize: 11 }
                        }]
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleFontColor: '#6e707e',
                        titleMarginBottom: 10,
                        titleFontSize: 13,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var qty = tooltipItem.xLabel || 0;
                                return 'Qty: ' + Number(qty).toLocaleString('id-ID') + ' Pengiriman';
                            }
                        }
                    }
                }
            });
        }
    }

    // 5. Chart Tender / Direct Selection (%) (Line Chart 0% - 100%)
    var ctxTender = document.getElementById("chartTenderDirectSelection");
    if (ctxTender) {
        if (window.chartTenderDirectSelection && window.chartTenderDirectSelection.config && window.chartTenderDirectSelection.config.type === 'line') {
            window.chartTenderDirectSelection.data.labels = (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels;
            window.chartTenderDirectSelection.update();
        } else {
            if (window.chartTenderDirectSelection && typeof window.chartTenderDirectSelection.destroy === 'function') {
                window.chartTenderDirectSelection.destroy();
            }
            window.chartTenderDirectSelection = new Chart(ctxTender, {
                type: 'line',
                data: {
                    labels: (window._outboundMonthlyData && window._outboundMonthlyData.labels) ? window._outboundMonthlyData.labels : bulanLabels,
                    datasets: [
                        {
                            label: "Tender (%)",
                            lineTension: 0.3,
                            backgroundColor: "rgba(78, 115, 223, 0.05)",
                            borderColor: "#4e73df",
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: "#4e73df",
                            pointBorderColor: "#ffffff",
                            pointBorderWidth: 1.5,
                            data: Array(12).fill(0)
                        },
                        {
                            label: "Direct Selection (%)",
                            lineTension: 0.3,
                            backgroundColor: "rgba(246, 194, 62, 0.05)",
                            borderColor: "#f6c23e",
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: "#f6c23e",
                            pointBorderColor: "#ffffff",
                            pointBorderWidth: 1.5,
                            data: Array(12).fill(0)
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: { padding: { left: 10, right: 15, top: 15, bottom: 0 } },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: { fontSize: 11 }
                        }],
                        yAxes: getStandardPercentageYAxis()
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        align: 'start',
                        labels: { boxWidth: 12, fontSize: 11, padding: 10 }
                    },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleFontColor: '#6e707e',
                        titleMarginBottom: 10,
                        titleFontSize: 13,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        caretPadding: 10,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                return (dataset.label || '') + ': ' + tooltipItem.yLabel + '%';
                            }
                        }
                    }
                }
            });
        }
    }
};

window.updateOutboundCharts = window.initOrUpdateOutboundCharts;
window.initOutboundCharts = function () {
    window.initOrUpdateOutboundCharts(window._outboundMonthlyData);
};

// Run automatically on ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        window.initOrUpdateOutboundCharts();
    });
} else {
    window.initOrUpdateOutboundCharts();
}
