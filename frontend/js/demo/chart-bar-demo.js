Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';
Chart.defaults.global.defaultFontSize = 11;

function number_format(number, decimals, dec_point, thousands_sep) {
  // *     example: number_format(1234.56, 2, ',', ' ');
  // *     return: '1 234,56'
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
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
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

// Bar Chart — starts empty, populated by Excel upload
var ctx = document.getElementById("myBarChart");
var myBarChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [],
    datasets: [{
      label: "Qty (Unit)",
      yAxisID: "y-axis-qty",
      backgroundColor: "#4e73df",
      hoverBackgroundColor: "#2e59d9",
      borderColor: "#4e73df",
      data: [],
    }, {
      label: "NBV (Rp)",
      yAxisID: "y-axis-nbv",
      backgroundColor: "#1cc88a",
      hoverBackgroundColor: "#17a673",
      borderColor: "#1cc88a",
      data: [],
    }],
  },
  options: {
    maintainAspectRatio: false,
    layout: {
      padding: {
        left: 5,
        right: 15,
        top: 20,
        bottom: 0
      }
    },
    scales: {
      xAxes: [{
        time: {
          unit: 'month'
        },
        gridLines: {
          display: false,
          drawBorder: false
        },
        ticks: {
          maxTicksLimit: 6,
          fontSize: 10.5
        },
        barPercentage: 1.0,
        categoryPercentage: 0.5,
        maxBarThickness: 18,
      }],
      yAxes: [{
        id: "y-axis-qty",
        position: "left",
        afterDataLimits: function(scale) {
          if (scale.max > 0) scale.max = scale.max * 1.18;
        },
        ticks: {
          min: 0,
          maxTicksLimit: 5,
          padding: 6,
          fontSize: 9,
          callback: function (value, index, values) {
            return '' + number_format(value);
          }
        },
        gridLines: {
          color: "rgb(234, 236, 244)",
          zeroLineColor: "rgb(234, 236, 244)",
          drawBorder: false,
          borderDash: [2],
          zeroLineBorderDash: [2]
        }
      }, {
        id: "y-axis-nbv",
        position: "right",
        afterDataLimits: function(scale) {
          if (scale.max > 0) scale.max = scale.max * 1.18;
        },
        ticks: {
          min: 0,
          maxTicksLimit: 5,
          padding: 6,
          fontSize: 9,
          callback: function (value, index, values) {
            if (value >= 1000000) {
                return 'Rp ' + number_format(value / 1000000) + 'M';
            }
            return 'Rp ' + number_format(value);
          }
        },
        gridLines: {
          drawOnChartArea: false
        }
      }],

    },
    legend: {
      display: true,
      labels: {
        usePointStyle: true,
        fontSize: 10,
        boxWidth: 10
      }
    },
    tooltips: {
      mode: 'index',
      intersect: false,
      titleMarginBottom: 4,
      titleFontColor: '#6e707e',
      titleFontSize: 11,
      bodyFontSize: 10,
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 8,
      yPadding: 6,
      displayColors: false,
      caretPadding: 6,
      callbacks: {
        label: function (tooltipItem, chart) {
          var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
          var val = number_format(tooltipItem.yLabel);
          if (datasetLabel === "Qty (Unit)") {
            return datasetLabel + ' : ' + val + ' Unit';
          } else if (datasetLabel === "NBV (Rp)") {
            return datasetLabel + ' : Rp ' + val;
          }
          return datasetLabel + ' : ' + val;
        }
      }
    },
    plugins: {
      datalabels: {
        color: '#5a5c69',
        anchor: 'end',
        align: 'top',
        font: {
            size: 10,
            weight: 'bold'
        },
        formatter: function(value, context) {
          if (value === 0 || value == null) return '';
          var datasetLabel = context.dataset.label || '';
          if (datasetLabel.includes('NBV')) {
            if (value >= 1000000000) return number_format(value / 1000000000, 1) + 'B';
            if (value >= 1000000) return number_format(value / 1000000, 1) + 'M';
            return number_format(value);
          }
          return number_format(value);
        }
      }
    }
  }
});

// Perangkat In Chart — starts empty
var ctxIn = document.getElementById("perangkatInChart");
if (ctxIn) {
  window.perangkatInChart = new Chart(ctxIn, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [{
        label: "Perangkat In",
        backgroundColor: "#1cc88a",
        hoverBackgroundColor: "#17a673",
        borderColor: "#1cc88a",
        data: [],
      }],
    },
    options: {
      maintainAspectRatio: false,
      layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
      scales: {
        xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 12, autoSkip: false }, maxBarThickness: 25 }],
        yAxes: [{ afterDataLimits: function(scale) { if (scale.max > 0) scale.max = scale.max * 1.18; }, ticks: { min: 0, maxTicksLimit: 5, padding: 10 }, gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } }],
      },
      legend: { display: false },
      tooltips: {
        titleMarginBottom: 4, titleFontColor: '#6e707e', titleFontSize: 11, bodyFontSize: 10, backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", borderColor: '#dddfeb', borderWidth: 1, xPadding: 8, yPadding: 6, displayColors: false, caretPadding: 6
      },
      plugins: {
        datalabels: {
          color: '#5a5c69',
          anchor: 'end',
          align: 'top',
          font: {
            size: 11,
            weight: 'bold'
          },
          formatter: function(value, context) {
            if (!value) return '';
            return number_format(value);
          }
        }
      }
    }
  });
}

// Perangkat Out Chart — starts empty
var ctxOut = document.getElementById("perangkatOutChart");
if (ctxOut) {
  window.perangkatOutChart = new Chart(ctxOut, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [{
        label: "Perangkat Out",
        backgroundColor: "#e74a3b",
        hoverBackgroundColor: "#be2617",
        borderColor: "#e74a3b",
        data: [],
      }],
    },
    options: {
      maintainAspectRatio: false,
      layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
      scales: {
        xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 12, autoSkip: false }, maxBarThickness: 25 }],
        yAxes: [{ afterDataLimits: function(scale) { if (scale.max > 0) scale.max = scale.max * 1.18; }, ticks: { min: 0, maxTicksLimit: 5, padding: 10 }, gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } }],
      },
      legend: { display: false },
      tooltips: {
        titleMarginBottom: 4, titleFontColor: '#6e707e', titleFontSize: 11, bodyFontSize: 10, backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", borderColor: '#dddfeb', borderWidth: 1, xPadding: 8, yPadding: 6, displayColors: false, caretPadding: 6
      },
      plugins: {
        datalabels: {
          color: '#5a5c69',
          anchor: 'end',
          align: 'top',
          font: {
            size: 11,
            weight: 'bold'
          },
          formatter: function(value, context) {
            if (!value) return '';
            return number_format(value);
          }
        }
      }
    }
  });
}
