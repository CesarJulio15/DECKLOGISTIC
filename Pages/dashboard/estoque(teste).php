<?php include '../partials/sidebar.php'; ?>
<?php include '../conexao.php'; ?>

<?php
// Buscar produtos reabastecidos
$sqlReabastecidos = "SELECT lote, nome, data_reabastecimento FROM produtos ORDER BY data_reabastecimento DESC LIMIT 10";
$resReab = mysqli_query($conn, $sqlReabastecidos);


// Buscar dados para gráficos
$sqlGraficoFaltaExcesso = "SELECT nome, quantidade_estoque FROM produtos";
$resGrafico1 = mysqli_query($conn, $sqlGraficoFaltaExcesso);

$labels1 = [];
$dados1  = [];
while ($row = mysqli_fetch_assoc($resGrafico1)) {
    $labels1[] = $row['nome'];
    $dados1[]  = (int) $row['quantidade_estoque'];
}

// Produtos parados há mais de 12 dias
$sqlGraficoParado = "SELECT nome, DATEDIFF(CURDATE(), ultima_movimentacao) AS dias FROM produtos WHERE DATEDIFF(CURDATE(), ultima_movimentacao) > 12";
$resGrafico2 = mysqli_query($conn, $sqlGraficoParado);

$labels2 = [];
$dados2  = [];
while ($row = mysqli_fetch_assoc($resGrafico2)) {
    $labels2[] = $row['nome'];
    $dados2[]  = (int) $row['dias'];
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
  <div class="sidebar">
    <?php include '../../partials/sidebar.php'; ?>
  </div>
  <h1>Estoque</h1>
  <div class="total-estoque">Total em estoque: <!-- valor dinâmico aqui --></div>
  <a href="#" class="ver-mais">ver mais</a>

<div class="tables-container">
  <!-- Tabela Reabastecidos -->
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


<!-- Gráficos -->
<canvas id="graficoFaltaExcesso"></canvas>
<canvas id="graficoEstoqueParado"></canvas>

<script>
const grafico1 = new Chart(document.getElementById('graficoFaltaExcesso'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels1) ?>,
        datasets: [{
            label: 'Quantidade em estoque',
            data: <?= json_encode($dados1) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    }
});

const grafico2 = new Chart(document.getElementById('graficoEstoqueParado'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels2) ?>,
        datasets: [{
            label: 'Dias parado',
            data: <?= json_encode($dados2) ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)'
        }]
    }
});
</script>
</body>
</html>
