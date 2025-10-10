<?php
session_start();
include __DIR__ . "/../../../conexao.php";

$filtro = $_GET['filtro'] ?? 'dia';

// Função de agrupamento
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

// Título do filtro (exibido no card)
$tituloFiltro = match($filtro) {
    'bimestre' => 'Bimestre',
    'trimestre' => 'Trimestre',
    'semestre' => 'Semestre',
    'mes' => 'Mês',
    'ano' => 'Ano',
    default => 'Dia'
};

$dataField = getDataField($filtro);
$lojaId = $_SESSION['loja_id'] ?? 0;
$whereLoja = $lojaId ? " WHERE loja_id = " . intval($lojaId) : "";

// Consulta agrupada
$sql = "SELECT $dataField AS periodo,
               SUM(valor_total) AS receita,
               SUM(custo_total) AS custo
        FROM vendas
        $whereLoja
        GROUP BY $dataField
        ORDER BY periodo ASC";

$res = mysqli_query($conn, $sql);

$labels = [];
$dadosLucro = [];

while ($row = mysqli_fetch_assoc($res)) {
    $lucro = ($row['receita'] ?? 0) - ($row['custo'] ?? 0);
    $labels[] = $row['periodo'];
    $dadosLucro[] = round($lucro, 2);
}

// Total de lucro líquido
$totalLucro = array_sum($dadosLucro);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lucro Líquido - Decklogistic</title>
<link rel="icon" href="../../../img/logo2.svg" type="image/x-icon" />
<link rel="stylesheet" href="../../../assets/sidebar.css">
<link rel="stylesheet" href="../../../assets/lucroB.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>

<div class="content">
  <div class="sidebar">
    <div class="logo-area">
      <img src="../../../img/logo2.svg" alt="Logo">
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
    <h1>Lucro Líquido</h1>

    <div class="cards-container">
        <div class="card receita">
            <h2>Total (<?php echo $tituloFiltro; ?>)</h2>
            <p><?php echo number_format($totalLucro, 2, ',', '.'); ?></p>
        </div>
    </div>

    <form method="GET" class="filtros-container">
        <label for="filtro">Filtrar por:</label>
        <select name="filtro" id="filtro" onchange="this.form.submit()">
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
                    <th>Lucro Líquido</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($labels as $i => $periodo) {
                    $v = $dadosLucro[$i] ?? 0;
                    echo "<tr>
                            <td>{$periodo}</td>
                            <td>".number_format($v,2,',','.')."</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('toggleView');
    const grafico = document.getElementById('grafico');
    const tabela = document.getElementById('tabela-container');

    // --- Alterna entre gráfico e tabela ---
    btn.addEventListener('click', () => {
        const mostrandoTabela = tabela.style.display === 'block';
        tabela.style.display = mostrandoTabela ? 'none' : 'block';
        grafico.style.display = mostrandoTabela ? 'block' : 'none';
        btn.innerText = mostrandoTabela ? 'Ver Tabela' : 'Ver Gráfico';
    });

    // --- Gráfico ApexCharts ---
    const options = {
        chart: {
            type: 'line',
            height: 350,
            animations: { enabled: false }, // <== evita "flick"
            toolbar: { show: false }
        },
        series: [{
            name: 'Lucro Líquido',
            data: <?php echo json_encode($dadosLucro); ?>
        }],
        xaxis: { categories: <?php echo json_encode($labels); ?> },
        colors: ['#36A2EB'],
        stroke: { width: 2, curve: 'smooth' },
        markers: { size: 4 }
    };

    // Usa requestAnimationFrame pra garantir renderização estável
    requestAnimationFrame(() => {
        const chart = new ApexCharts(grafico, options);
        chart.render();
    });
});
</script>


</main>
</body>
</html>
