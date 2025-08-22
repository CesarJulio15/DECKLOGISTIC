<?php
// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexão com banco
require_once '../../conexao.php';

// ------------------------
// Total de estoque
// ------------------------
$sqlTotalEstoque = "SELECT SUM(quantidade_estoque) AS total FROM produtos";
$resTotal = mysqli_query($conn, $sqlTotalEstoque);
$totalEstoque = 0;

if ($row = mysqli_fetch_assoc($resTotal)) {
    $totalEstoque = (int)$row['total'];
}

// ------------------------
// Filtro backend
// ------------------------
$filtro = $_GET['filtro'] ?? '';
$orderBy = "ORDER BY data_reabastecimento DESC";

switch ($filtro) {
    case 'preco_asc':
        $orderBy = "ORDER BY preco_unitario ASC";
        break;
    case 'preco_desc':
        $orderBy = "ORDER BY preco_unitario DESC";
        break;
    case 'quantidade_asc':
        $orderBy = "ORDER BY quantidade_estoque ASC";
        break;
    case 'quantidade_desc':
        $orderBy = "ORDER BY quantidade_estoque DESC";
        break;
    case 'data_recente':
        $orderBy = "ORDER BY data_reabastecimento DESC";
        break;
    case 'data_antiga':
        $orderBy = "ORDER BY data_reabastecimento ASC";
        break;
}

// ------------------------
// Produtos reabastecidos recentemente
// ------------------------
$sqlReabastecidos = "SELECT lote, nome, data_reabastecimento FROM produtos $orderBy LIMIT 10";
$resReab = mysqli_query($conn, $sqlReabastecidos);

// ------------------------
// Gráfico: Quantidade em estoque
// ------------------------
$sqlGraficoFaltaExcesso = "SELECT nome, quantidade_estoque FROM produtos";
$resGrafico1 = mysqli_query($conn, $sqlGraficoFaltaExcesso);

$labels1 = [];
$dados1  = [];
while ($row = mysqli_fetch_assoc($resGrafico1)) {
    $labels1[] = $row['nome'];
    $dados1[]  = (int)$row['quantidade_estoque'];
}

// ------------------------
// Gráfico: Produtos parados há mais de 12 dias
// ------------------------
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
    $dados2[]  = (int)$row['dias'];
}

// ------------------------
// Gráfico: Entrada/Saída de produtos
// ------------------------
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
    $data = $row['data_movimentacao'] ?? null;
    $labels3[] = ($data && strtotime($data) !== false) ? date('d/m/Y', strtotime($data)) : '-';
    
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
  <link rel="stylesheet" href="../../assets/estoque.css">
  <link rel="stylesheet" href="../../assets/sidebar.css">
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
<div class="sidebar">
  <div class="logo-area">
    <img src="../../img/logoDecklogistic.webp" alt="Logo">
  </div>
  <nav class="nav-section">
    <div class="nav-menus">
      <ul class="nav-list top-section">
 <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<ul class="nav-list top-section">
  <li class="<?= $currentPage=='financas.php' ? 'active' : '' ?>">
    <a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a>
  </li>
  <li class="<?= $currentPage=='estoque.php' ? 'active' : '' ?>">
    <a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a>
  </li>
</ul>
      </ul>
      <hr>
      <ul class="nav-list middle-section">
        <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
        <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
        <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
       <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
      </ul>
    </div>
    <div class="bottom-links">
      <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
      <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
    </div>
  </nav>
</div>

<h1>Estoque</h1>
<div class="total-estoque">
    <h3>Itens:</h3>
    <span><?= $totalEstoque ?></span>
</div>

<a href="" class="ver-mais">ver mais</a>

<div class="tables-container">
   <h2>Produtos reabastecidos recentemente</h2>
   <div class="filtro-btn">
       <button id="btnFiltro">
           <img src="../../img/filtro.svg" alt="Filtro" class="icone-filtro">
       </button>
       <select id="selectFiltro" onchange="aplicarFiltro(this.value)">
           <option value="">Selecione...</option>
           <option value="preco_asc">Preço (menor primeiro)</option>
           <option value="preco_desc">Preço (maior primeiro)</option>
           <option value="quantidade_asc">Quantidade (menor primeiro)</option>
           <option value="quantidade_desc">Quantidade (maior primeiro)</option>
           <option value="data_recente">Data de reabastecimento (mais recente)</option>
           <option value="data_antiga">Data de reabastecimento (mais antiga)</option>
       </select>
   </div>

   <script>
   function aplicarFiltro(valor) {
       if (!valor) return;
       const url = new URL(window.location.href);
       url.searchParams.set('filtro', valor);
       window.location.href = url.toString();
   }
   </script>

<table>
  <thead>
    <tr>
      <th>Lote</th>
      <th>Nome</th>
      <th>Data de Reabastecimento</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = mysqli_fetch_assoc($resReab)): ?>
      <tr>
        <td><?= $row['lote'] ?></td>
        <td><?= $row['nome'] ?></td>
        <td>
          <?php 
            if (!empty($row['data_reabastecimento'])) {
                echo date('d/m/Y', strtotime($row['data_reabastecimento']));
            } else {
                echo '-';
            }
          ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</div>

<!-- Gráficos -->
<div class="charts-container">
    <div>
        <h4>Produtos em falta/produtos em excesso</h4>
        <div id="graficoFaltaExcesso"></div>
    </div>
    <div>
        <h4>Produtos com estoque parado</h4>
        <div id="graficoEstoqueParado"></div>
    </div>
    <div>
        <h4>Entrada e saída de produtos</h4>
         <a href="giroEstoque.php" class="visu">Visualizar comparativo</a>
        <div id="graficoEntradaSaida"></div>
       
    </div>
</div>

<script>
// Dados vindos do PHP
const labels1 = <?= json_encode($labels1) ?>;
const dados1  = <?= json_encode($dados1) ?>;
const labels2 = <?= json_encode($labels2) ?>;
const dados2  = <?= json_encode($dados2) ?>;
const labels3 = <?= json_encode($labels3) ?>;
const entrada = <?= json_encode($entrada) ?>;
const saida   = <?= json_encode($saida) ?>;

// Gráfico 1 - Falta/Excesso
new ApexCharts(document.querySelector("#graficoFaltaExcesso"), {
    chart: { type: 'bar', height: 350 },
    series: [{ name: 'Quantidade em estoque', data: dados1 }],
    xaxis: { categories: labels1 }
}).render();

// Gráfico 2 - Estoque parado
new ApexCharts(document.querySelector("#graficoEstoqueParado"), {
    chart: { type: 'bar', height: 350 },
    series: [{ name: 'Dias parado', data: dados2 }],
    xaxis: { categories: labels2 }
}).render();

// Gráfico 3 - Entrada/Saída
new ApexCharts(document.querySelector("#graficoEntradaSaida"), {
    chart: { type: 'line', height: 350 },
    series: [
        { name: 'Entrada', data: entrada },
        { name: 'Saída', data: saida }
    ],
    xaxis: { categories: labels3 }
}).render();
</script>
</body>
</html>



