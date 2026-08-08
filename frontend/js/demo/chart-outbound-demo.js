// Chart.js initialization for Outbound Dashboard Charts
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

document.addEventListener('DOMContentLoaded', function () {
    var bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // 1. Chart Bulanan — Jumlah MR (%) (Bar Chart)
    var ctxBulananMr = document.getElementById("chartBulananJumlahMr");
    if (ctxBulananMr) {
        window.chartBulananJumlahMr = new Chart(ctxBulananMr, {
            type: 'bar',
            data: {
                labels: bulanLabels,
                datasets: [{
                    label: "Jumlah MR (%)",
                    backgroundColor: "#4e73df",
                    hoverBackgroundColor: "#2e59d9",
                    borderColor: "#4e73df",
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 10, top: 10, bottom: 0 } },
                scales: {
                    xAxes: [{
                        gridLines: { display: false, drawBorder: false },
                        ticks: { fontSize: 11 },
                        maxBarThickness: 30
                    }],
                    yAxes: [{
                        ticks: { min: 0, max: 100, padding: 10, fontSize: 11, callback: function(value) { return value + '%'; } },
                        gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] }
                    }]
                },
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel + '%';
                        }
                    }
                }
            }
        });
    }

    // 2. Chart Bulanan — Jumlah PO (%) (Bar Chart)
    var ctxBulananPo = document.getElementById("chartBulananJumlahPo");
    if (ctxBulananPo) {
        window.chartBulananJumlahPo = new Chart(ctxBulananPo, {
            type: 'bar',
            data: {
                labels: bulanLabels,
                datasets: [{
                    label: "Jumlah PO (%)",
                    backgroundColor: "#36b9cc",
                    hoverBackgroundColor: "#2c9faf",
                    borderColor: "#36b9cc",
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 10, top: 10, bottom: 0 } },
                scales: {
                    xAxes: [{
                        gridLines: { display: false, drawBorder: false },
                        ticks: { fontSize: 11 },
                        maxBarThickness: 30
                    }],
                    yAxes: [{
                        ticks: { min: 0, max: 100, padding: 10, fontSize: 11, callback: function(value) { return value + '%'; } },
                        gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] }
                    }]
                },
                legend: { display: false },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel + '%';
                        }
                    }
                }
            }
        });
    }

    // 3. Chart Close MR (%) (Pie Chart)
    var ctxCloseMr = document.getElementById("chartCloseMr");
    if (ctxCloseMr) {
        window.chartCloseMr = new Chart(ctxCloseMr, {
            type: 'pie',
            data: {
                labels: ['Closed', 'Partially Closed', 'Shipped (Belum Closed)'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e'],
                    hoverBackgroundColor: ['#17a673', '#2c9faf', '#dfa827']
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'start',
                    labels: { boxWidth: 12, fontSize: 11, padding: 10 }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.labels[tooltipItem.index] || '';
                            var value = data.datasets[0].data[tooltipItem.index] || 0;
                            return label + ': ' + value;
                        }
                    }
                }
            }
        });
    }

    // 4. Cost Delivery per Moda (Value) (Horizontal Bar Chart)
    var ctxCostModa = document.getElementById("costDeliveryPerModaChart");
    if (ctxCostModa) {
        window.costDeliveryPerModaChart = new Chart(ctxCostModa, {
            type: 'horizontalBar',
            data: {
                labels: ['Udara', 'Laut', 'Darat', 'Udara PTP'],
                datasets: [{
                    label: "Cost Delivery (Value)",
                    backgroundColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e"],
                    hoverBackgroundColor: ["#2e59d9", "#17a673", "#2c9fae", "#dda20a"],
                    data: [0, 0, 0, 0]
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 10, top: 10, bottom: 0 } },
                scales: {
                    xAxes: [{
                        ticks: { min: 0, fontSize: 11 },
                        gridLines: { color: "rgb(234, 236, 244)", drawBorder: false }
                    }],
                    yAxes: [{
                        gridLines: { display: false, drawBorder: false },
                        ticks: { fontSize: 11 }
                    }]
                },
                legend: { display: false }
            }
        });
    }

    // 5. Chart Tender / Direct Selection (%) (Pie Chart)
    var ctxTender = document.getElementById("chartTenderDirectSelection");
    if (ctxTender) {
        window.chartTenderDirectSelection = new Chart(ctxTender, {
            type: 'pie',
            data: {
                labels: ['Tender', 'Direct Selection'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#4e73df', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#dfa827']
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'bottom',
                    align: 'start',
                    labels: { boxWidth: 12, fontSize: 11, padding: 10 }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.labels[tooltipItem.index] || '';
                            var value = data.datasets[0].data[tooltipItem.index] || 0;
                            return label + ': ' + value + '%';
                        }
                    }
                }
            }
        });
    }

    // Legacy initializations for safety if elements exist
    var ctxDistribusi = document.getElementById("distribusiStatusMrChart");
    if (ctxDistribusi) {
        window.distribusiStatusMrChart = new Chart(ctxDistribusi, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#4e73df', '#36b9cc', '#f6c23e', '#1cc88a', '#858796'] }] },
            options: { maintainAspectRatio: false, legend: { display: true, position: 'bottom' } }
        });
    }
});
