<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  
  <title>Estoque - Decklogistic</title>
  <style>
  body {
  margin-left: 300px; /* ou a largura da sua sidebar */
  margin-top: 30px;
  font-family: Arial, sans-serif;
  background: #fff;
  color: #111;
}
    h1 {
      margin-bottom: 5px;
    }
    
    .total-estoque {
      border: 1px solid #333;
      display: inline-block;
      padding: 6px 12px;
      margin-bottom: 15px;
    }
    a.ver-mais {
      display: block;
      margin-bottom: 25px;
      color: #06c;
      cursor: pointer;
      text-decoration: underline;
      font-size: 0.9em;
    }
    .tables-container {
      display: flex;
      gap: 30px;
      margin-bottom: 50px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      max-width: 600px;
      font-size: 0.85em;
    }
    th, td {
      border: 1px solid #999;
      padding: 8px;
      background-color: #f1f1f1;
      text-align: left;
    }
    th {
      background: #555;
      color: white;
      font-weight: bold;
    }
    .tables-wrapper {
      flex: 1;
      max-width: 600px;
      overflow-x: auto;
      max-height: 280px;
    }
    .filter-text {
      font-size: 0.8em;
      color: #333;
      float: right;
      margin-bottom: 6px;
      cursor: pointer;
      user-select: none;
    }
    .charts-container {
      display: flex;
      gap: 40px;
      justify-content: space-between;
      max-width: 1200px;
      margin-top: 30px;
    }
    .chart-box {
      flex: 1;
      max-width: 400px;
    }
    .chart-box h3 {
      font-size: 1em;
      margin-bottom: 5px;
    }
    .link-small {
      font-size: 0.75em;
      color: #06c;
      cursor: pointer;
      text-decoration: underline;
      margin-top: 5px;
      display: inline-block;
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
  <div class="sidebar">
    <?php include '../partials/sidebar.php'; ?>
  </div>
  <h1>Estoque</h1>
  <div class="total-estoque">Total em estoque: <!-- valor dinâmico aqui --></div>
  <a href="#" class="ver-mais">ver mais</a>

  <div class="tables-container">
    <div class="tables-wrapper">
      <div class="filter-text">Filtrar por ⏷</div>
      <table id="tabelaReabastecidos">
        <thead>
          <tr>
            <th>Lote do produto</th>
            <th>Nome do produto</th>
            <th>Data de reabastecimento</th>
          </tr>
        </thead>
        <tbody>
          <!-- linhas dinâmicas aqui -->
        </tbody>
      </table>
      <a href="#" class="link-small">ver mais</a>
    </div>

    <div class="tables-wrapper">
      <div class="filter-text">Filtrar por ⏷</div>
      <table id="tabelaVencidos">
        <thead>
          <tr>
            <th>Lote do produto</th>
            <th>Nome do produto</th>
            <th>Data de validade</th>
          </tr>
        </thead>
        <tbody>
          <!-- linhas dinâmicas aqui -->
        </tbody>
      </table>
      <a href="#" class="link-small">ver mais</a>
    </div>
  </div>

  <div class="charts-container">
    <div class="chart-box">
      <h3>Produtos em falta/produtos em excesso</h3>
      <canvas id="graficoFaltaExcesso"></canvas>
    </div>
    <div class="chart-box">
      <h3>Produtos com estoque parado <span style="font-weight:normal; font-size:0.8em;">&lt; 12 Dias &gt;</span></h3>
      <canvas id="graficoEstoqueParado"></canvas>
      <a href="#" class="link-small">Visualizar estoque morto</a>
    </div>
    <div class="chart-box">
      <h3>Entrada e saída de produtos</h3>
      <canvas id="graficoEntradaSaida"></canvas>
      <a href="#" class="link-small">Visualizar comparativo</a>
    </div>
  </div>

<script>
  // Apenas inicializar gráficos vazios

  const ctxFaltaExcesso = document.getElementById('graficoFaltaExcesso').getContext('2d');
  const graficoFaltaExcesso = new Chart(ctxFaltaExcesso, {
    type: 'bar',
    data: {
      labels: [],
      datasets: []
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true } }
    }
  });

  const ctxEstoqueParado = document.getElementById('graficoEstoqueParado').getContext('2d');
  const graficoEstoqueParado = new Chart(ctxEstoqueParado, {
    type: 'bar',
    data: {
      labels: [],
      datasets: []
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true } }
    }
  });

  const ctxEntradaSaida = document.getElementById('graficoEntradaSaida').getContext('2d');
  const graficoEntradaSaida = new Chart(ctxEntradaSaida, {
    type: 'line',
    data: {
      labels: [],
      datasets: []
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true } }
    }
  });

</script>

</body>
</html>
