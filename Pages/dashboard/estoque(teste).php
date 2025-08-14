<link rel="stylesheet" href="../../assets/estoque.css">

<?php
require_once '../../conexao.php'; // aqui o $conn deve ser mysqli_connect(...)

$sqlReabastecidos = "SELECT lote, nome, data_reabastecimento FROM produtos ORDER BY data_reabastecimento DESC LIMIT 10";
$resReab = mysqli_query($conn, $sqlReabastecidos);

// Buscar dados para gráfico de quantidade em estoque
$sqlGraficoFaltaExcesso = "SELECT nome, quantidade_estoque FROM produtos";
$resGrafico1 = mysqli_query($conn, $sqlGraficoFaltaExcesso);

$labels1 = [];
$dados1  = [];
while ($row = mysqli_fetch_assoc($resGrafico1)) {
    $labels1[] = $row['nome'];
    $dados1[]  = (int) $row['quantidade_estoque'];
}

// Produtos parados há mais de 12 dias
$sqlGraficoParado = "
SELECT p.nome, DATEDIFF(CURDATE(), m.ultima_movimentacao) AS dias
FROM produtos p
LEFT JOIN (
    SELECT produto_id, MAX(data_movimentacao) AS ultima_movimentacao
    FROM movimentacoes_estoque
    GROUP BY produto_id
) m ON p.id = m.produto_id
WHERE DATEDIFF(CURDATE(), m.ultima_movimentacao) > 12
";
$resGrafico2 = mysqli_query($conn, $sqlGraficoParado);

$labels2 = [];
$dados2  = [];
while ($row = mysqli_fetch_assoc($resGrafico2)) {
    $labels2[] = $row['nome'];
    $dados2[]  = (int) $row['dias'];
}

// Dados de entrada/saída por produto
$sqlEntradaSaida = "
SELECT p.nome, m.data_movimentacao, SUM(m.quantidade) AS total
FROM produtos p
LEFT JOIN movimentacoes_estoque m ON p.id = m.produto_id
GROUP BY p.nome, m.data_movimentacao
ORDER BY m.data_movimentacao ASC
";
$resGrafico3 = mysqli_query($conn, $sqlEntradaSaida);

$labels3 = [];
$entrada = [];
$saida = [];

while ($row = mysqli_fetch_assoc($resGrafico3)) {
    $labels3[] = date('d/m/Y', strtotime($row['data_movimentacao']));
    if ($row['total'] >= 0) {
        $entrada[] = (int)$row['total'];
        $saida[] = 0;
    } else {
        $entrada[] = 0;
        $saida[] = abs((int)$row['total']);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1>Estoque</h1>

<div class="tables-container">
   <h2>Produtos reabastecidos recentemente</h2>
  <table>
    <thead>
      <tr><th>Lote</th><th>Nome</th><th>Data de Reabastecimento</th></tr>
    </thead>
    <tbody>
      <?php while($row = mysqli_fetch_assoc($resReab)): ?>
        <tr>
          <td><?= $row['lote'] ?></td>
          <td><?= $row['nome'] ?></td>
          <td><?= date('d/m/Y', strtotime($row['data_reabastecimento'])) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Gráficos -->
<div class="charts-container">
    <div>
        <h4>Produtos em falta/produtos em excesso</h4>
        <canvas id="graficoFaltaExcesso"></canvas>
    </div>
    <div>
        <h4>Produtos com estoque parado</h4>
        <canvas id="graficoEstoqueParado"></canvas>
    </div>
    <div>
        <h4>Entrada e saída de produtos</h4>
        <canvas id="graficoEntradaSaida"></canvas>
    </div>
</div>

<script>
// Gráfico 1
const grafico1 = new Chart(document.getElementById('graficoFaltaExcesso'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels1) ?>,
        datasets: [{
            label: 'Quantidade em estoque',
            data: <?= json_encode($dados1) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } }
    }
});

// Gráfico 2
const grafico2 = new Chart(document.getElementById('graficoEstoqueParado'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels2) ?>,
        datasets: [{
            label: 'Dias parado',
            data: <?= json_encode($dados2) ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)'
        }]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } }
    }
});

// Gráfico 3 - Entrada/Saída
const grafico3 = new Chart(document.getElementById('graficoEntradaSaida'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels3) ?>,
        datasets: [
            {
                label: 'Entrada',
                data: <?= json_encode($entrada) ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true
            },
            {
                label: 'Saída',
                data: <?= json_encode($saida) ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true
            }
        ]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } }
    }
});
</script>
</body>
</html>
