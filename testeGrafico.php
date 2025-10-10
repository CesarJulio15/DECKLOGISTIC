<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard de Vendas</title>
  <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9fafb;
      margin: 0;
      padding: 0;
    }
    .dashboard {
      display: flex;
      gap: 20px;
      padding: 20px;
      justify-content: center;
    }
    #grafico {
      width: 700px;
      height: 400px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 10px;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <div id="grafico"></div>
  </div>

  <script>
    // Inicializa o gráfico
    var chart = echarts.init(document.getElementById('grafico'));

    // Configuração completa do gráfico
    var option = {
      title: {
        text: 'Vendas Mensais',
        subtext: 'Ano 2025',
        left: 'center'
      },
      tooltip: {
        trigger: 'axis'
      },
      legend: {
        data: ['Vendas', 'Lucro'],
        top: 'bottom'
      },
      toolbox: {
        feature: {
          saveAsImage: {}, // botão de download
          dataZoom: {}     // zoom interativo
        }
      },
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul']
      },
      yAxis: {
        type: 'value',
        name: 'R$ (mil)'
      },
      series: [
        {
          name: 'Vendas',
          type: 'line',
          smooth: true,
          data: [120, 132, 101, 134, 90, 230, 210],
          areaStyle: {
            color: 'rgba(80, 120, 255, 0.2)'
          },
          lineStyle: {
            color: '#4F46E5'
          }
        },
        {
          name: 'Lucro',
          type: 'bar',
          data: [50, 80, 60, 100, 70, 160, 120],
          itemStyle: {
            color: '#22C55E'
          }
        }
      ]
    };

    // Renderiza o gráfico
    chart.setOption(option);
  </script>
</body>
</html>
