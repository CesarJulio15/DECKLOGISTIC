<?php
include __DIR__ . '../../../../conexao.php';

$filtro = $_GET['filtro'] ?? 'dia';

// Função para gerar campo de data conforme filtro
function getDataField($filtro) {
    switch($filtro) {
        case 'bimestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/2)+1,2,'0'))";
        case 'trimestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/3)+1,2,'0'))";
        case 'semestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/6)+1,2,'0'))";
        case 'mes':
            return "DATE_FORMAT(data_venda, '%Y-%m')";
        case 'ano':
            return "YEAR(data_venda)";
        default:
            return "DATE(data_venda)";
    }
}

$dataField = getDataField($filtro);

// Consulta principal
$sql = "SELECT $dataField AS periodo,
               SUM(valor_total) AS receita,
               SUM(custo_total) AS custo
        FROM vendas
        GROUP BY $dataField
        ORDER BY periodo ASC";

$res = mysqli_query($conn, $sql);

$labels = [];
$dadosReceita = [];
$dadosCusto = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['periodo'];
    $dadosReceita[] = (float)$row['receita'];
    $dadosCusto[] = (float)$row['custo'];
}

// Totais para o filtro atual
$condicao = match($filtro) {
    'bimestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/2)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/2)+1,2,'0'))",
    'trimestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/3)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/3)+1,2,'0'))",
    'semestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/6)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/6)+1,2,'0'))",
    'mes' => "DATE_FORMAT(data_venda, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
    'ano' => "YEAR(data_venda) = YEAR(CURDATE())",
    default => "DATE(data_venda) = CURDATE()"
};

$tituloFiltro = match($filtro) {
    'bimestre' => 'Bimestre',
    'trimestre' => 'Trimestre',
    'semestre' => 'Semestre',
    'mes' => 'Mês',
    'ano' => 'Ano',
    default => 'Dia'
};

// Totais
$sqlReceita = "SELECT SUM(valor_total) AS total_receita FROM vendas WHERE $condicao";
$receita = mysqli_fetch_assoc(mysqli_query($conn, $sqlReceita))['total_receita'] ?? 0;

$sqlCusto = "SELECT SUM(custo_total) AS total_custo FROM vendas WHERE $condicao";
$custo = mysqli_fetch_assoc(mysqli_query($conn, $sqlCusto))['total_custo'] ?? 0;

// Lucro e variação
$lucro = $receita - $custo;
$percentual = 0;
$seta = "↑";
$classe = "positivo";
if ($receita > 0) {
    $percentual = ($lucro / $receita) * 100;
    if ($percentual < 0) {
        $seta = "↓";
        $classe = "negativo";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lucro Bruto - Decklogistic</title>
<link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
<link rel="stylesheet" href="../../../assets/sidebar.css">
<link rel="stylesheet" href="../../../assets/lucroB.css">
<link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>

<div class="content">
  <div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li class="active"><a href="../../../Pages/dashboard/financas.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../../../Pages/dashboard/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../../../Pages/dashboard/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../../../Pages/dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li><a href="../../dashboard/tabelas/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../../dashboard/tag.php"><span><img src="../../../img/tag.svg" alt="Tags"></span> Tags</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../../Pages/auth/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

<main class="dashboard">
    <h1>Lucro Bruto</h1>

    <div class="cards-container">
        <div class="card receita">
            <h2>Receita (<?php echo $tituloFiltro; ?>)</h2>
            <p><?php echo number_format($receita, 2, ',', '.'); ?></p>
        </div>
        <div class="card custo">
            <h2>Custo (<?php echo $tituloFiltro; ?>)</h2>
            <p><?php echo number_format($custo, 2, ',', '.'); ?></p>
        </div>
        <div class="card variacao <?php echo $classe; ?>">
            <h2>Lucro (<?php echo $tituloFiltro; ?>)</h2>
            <p><?php echo $seta . " " . number_format($percentual, 2, ',', '.'); ?>%</p>
        </div>
    </div>

    <form method="GET" class="filtros-container">
        <label for="filtro">Filtrar por:</label>
        <select name="filtro" id="filtro">
            <option value="dia" <?php if($filtro==='dia') echo 'selected'; ?>>Dia</option>
            <option value="mes" <?php if($filtro==='mes') echo 'selected'; ?>>Mês</option>
            <option value="bimestre" <?php if($filtro==='bimestre') echo 'selected'; ?>>Bimestre</option>
            <option value="trimestre" <?php if($filtro==='trimestre') echo 'selected'; ?>>Trimestre</option>
            <option value="semestre" <?php if($filtro==='semestre') echo 'selected'; ?>>Semestre</option>
            <option value="ano" <?php if($filtro==='ano') echo 'selected'; ?>>Ano</option>
        </select>
    </form>

    <div id="grafico"></div>
    
    <button id="toggleView" class="toggle-btn">Ver Tabela</button>
    <div id="tabela-container" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Receita</th>
                    <th>Custo</th>
                    <th>Lucro</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($labels as $i => $periodo) {
                    $r = $dadosReceita[$i] ?? 0;
                    $c = $dadosCusto[$i] ?? 0;
                    $l = $r - $c;
                    echo "<tr>
                            <td>{$periodo}</td>
                            <td>".number_format($r,2,',','.')."</td>
                            <td>".number_format($c,2,',','.')."</td>
                            <td>".number_format($l,2,',','.')."</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

<script>
document.getElementById('toggleView').addEventListener('click', () => {
    const grafico = document.getElementById('grafico');
    const tabela = document.getElementById('tabela-container');
    const btn = document.getElementById('toggleView');

    if (tabela.style.display === 'none') {
        grafico.style.display = 'none';
        tabela.style.display = 'block';
        btn.innerText = 'Ver Gráfico';
    } else {
        grafico.style.display = 'block';
        tabela.style.display = 'none';
        btn.innerText = 'Ver Tabela';
    }
});

const filtroSelect = document.getElementById('filtro');
filtroSelect.addEventListener('change', function() {
    localStorage.setItem('viewState', document.getElementById('tabela-container').style.display);
    this.form.submit();
});

document.addEventListener("DOMContentLoaded", () => {
    const tabela = document.getElementById('tabela-container');
    const grafico = document.getElementById('grafico');
    const btn = document.getElementById('toggleView');

    const estado = localStorage.getItem('viewState');
    if(estado === 'block') {
        tabela.style.display = 'block';
        grafico.style.display = 'none';
        btn.innerText = 'Ver Gráfico';
    } else {
        tabela.style.display = 'none';
        grafico.style.display = 'block';
        btn.innerText = 'Ver Tabela';
    }
});

// ApexCharts
var options = {
    chart: { type: 'line', height: 350 },
    series: [
        { name: 'Receita', data: <?php echo json_encode($dadosReceita); ?> },
        { name: 'Custo', data: <?php echo json_encode($dadosCusto); ?> }
    ],
    xaxis: { categories: <?php echo json_encode($labels); ?> }
};
var chart = new ApexCharts(document.querySelector("#grafico"), options);
chart.render();
</script>

</main>
</body>
</html>
