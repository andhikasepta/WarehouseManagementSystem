// Chart.js initialization for Inbound Dashboard Charts
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

function formatRupiah(num) {
    if (isNaN(num)) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num));
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Distribusi Status Delivery (Circle Graph / Doughnut Chart)
    var ctxDistribusi = document.getElementById("distribusiStatusPoChart");
    if (ctxDistribusi) {
        window.distribusiStatusPoChart = new Chart(ctxDistribusi, {
            type: 'doughnut',
            data: {
                labels: [
                    "Total PO Ontime", 
                    "Total PO Terlambat", 
                    "PO Sudah GR"
                ],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#1cc88a', '#e74a3b', '#36b9cc'],
                    hoverBackgroundColor: ['#17a673', '#be2617', '#2c9faf'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: true,
                    caretPadding: 10,
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        fontSize: 11,
                        padding: 10
                    }
                },
                cutoutPercentage: 70,
            },
        });
    }

    // 2. QTY PO For Department (Bar Chart)
    var ctxNilaiPo = document.getElementById("nilaiPoBagianChart");
    if (ctxNilaiPo) {
        window.nilaiPoBagianChart = new Chart(ctxNilaiPo, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: "Sudah di GR",
                        backgroundColor: "#1cc88a",
                        hoverBackgroundColor: "#17a673",
                        borderColor: "#1cc88a",
                        data: [],
                        maxBarThickness: 40,
                    },
                    {
                        label: "Belum di GR",
                        backgroundColor: "#e74a3b",
                        hoverBackgroundColor: "#be2617",
                        borderColor: "#e74a3b",
                        data: [],
                        maxBarThickness: 40,
                    }
                ],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 15,
                        top: 15,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 6
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            maxTicksLimit: 5,
                            padding: 10,
                            precision: 0,
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID').format(value);
                            }
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        fontSize: 11,
                        padding: 10
                    }
                },
                tooltips: {
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: true,
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chartData) {
                            var dataset = chartData.datasets[tooltipItem.datasetIndex];
                            return dataset.label + ': ' + new Intl.NumberFormat('id-ID').format(tooltipItem.yLabel) + ' PO';
                        }
                    }
                },
            }
        });
    }

    // 3. Tren GR Bulanan (Line Graph)
    var ctxTrenGr = document.getElementById("trenGrBulananChart");
    if (ctxTrenGr) {
        window.trenGrBulananChart = new Chart(ctxTrenGr, {
            type: 'line',
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
                datasets: [
                    {
                        label: "GR Tepat Waktu",
                        lineTension: 0.3,
                        backgroundColor: "rgba(28, 200, 138, 0.05)",
                        borderColor: "#1cc88a",
                        pointRadius: 3,
                        pointBackgroundColor: "#1cc88a",
                        pointBorderColor: "#1cc88a",
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: "#17a673",
                        pointHoverBorderColor: "#17a673",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        label: "GR Terlambat",
                        lineTension: 0.3,
                        backgroundColor: "rgba(231, 74, 59, 0.05)",
                        borderColor: "#e74a3b",
                        pointRadius: 3,
                        pointBackgroundColor: "#e74a3b",
                        pointBorderColor: "#e74a3b",
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: "#be2617",
                        pointHoverBorderColor: "#be2617",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    }
                ],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 15,
                        top: 15,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 12
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            maxTicksLimit: 5,
                            padding: 10,
                            precision: 0
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        fontSize: 11,
                        padding: 10
                    }
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: true,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, chartData) {
                            var dataset = chartData.datasets[tooltipItem.datasetIndex];
                            return dataset.label + ': ' + tooltipItem.yLabel;
                        }
                    }
                }
            }
        });
    }

    window.updateInboundCharts = function(counts) {
        if (!counts) return;

        // Update Doughnut Chart
        if (window.distribusiStatusPoChart) {
            var ontime = counts.po_ontime_delivery || 0;
            var late = counts.po_terlambat_delivery || 0;
            var gr = counts.po_sudah_gr || 0;
            window.distribusiStatusPoChart.data.datasets[0].data = [ontime, late, gr];
            window.distribusiStatusPoChart.update();
        }

        // Update Department Bar Chart
        if (window.nilaiPoBagianChart && counts.dept_chart) {
            window.nilaiPoBagianChart.data.labels = counts.dept_chart.labels || [];
            window.nilaiPoBagianChart.data.datasets[0].data = counts.dept_chart.sudah_gr || [];
            window.nilaiPoBagianChart.data.datasets[1].data = counts.dept_chart.belum_gr || [];
            window.nilaiPoBagianChart.update();
        }

        // Update Monthly Trend Chart
        if (window.trenGrBulananChart && counts.trend_chart) {
            window.trenGrBulananChart.data.datasets[0].data = counts.trend_chart.ontime || array_fill(0, 12, 0);
            window.trenGrBulananChart.data.datasets[1].data = counts.trend_chart.terlambat || array_fill(0, 12, 0);
            window.trenGrBulananChart.update();
        }
    };
});
