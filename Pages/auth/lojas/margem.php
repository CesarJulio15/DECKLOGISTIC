<?php
session_start();
include __DIR__ . "/../../../conexao.php";


$filtro = $_GET['filtro'] ?? 'dia';

// Função para definir agrupamento de data
function getDataField($filtro) {
    switch ($filtro) {
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

// Nome do filtro (para exibir no card)
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

// Consulta com cálculo de margem (%)
$sql = "SELECT 
            $dataField AS periodo,
            SUM(valor_total) AS receita,
            SUM(custo_total) AS custo
        FROM vendas
        $whereLoja
        GROUP BY $dataField
        ORDER BY periodo ASC";

$res = mysqli_query($conn, $sql);

$labels = [];
$margens = [];

while ($row = mysqli_fetch_assoc($res)) {
    $receita = $row['receita'] ?? 0;
    $custo = $row['custo'] ?? 0;
    $lucro = $receita - $custo;
    $margem = $receita > 0 ? ($lucro / $receita) * 100 : 0;
    $labels[] = $row['periodo'];
    $margens[] = round($margem, 2);
}

// Média da margem total
$mediaMargem = count($margens) > 0 ? array_sum($margens) / count($margens) : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Margem de Lucro - Decklogistic</title>
<link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
<link rel="stylesheet" href="../../../assets/sidebar.css">
<link rel="stylesheet" href="../../../assets/lucroB.css">
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
</head>

    <style>
        .voltar-btn {
        margin-top: 20px;
        padding: 10px 15px;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        }

        .voltar-btn:hover {
            background: #555;
        }
    </style>
    
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
          <li><a href="../../dashboard/tabelas/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../../../Pages/dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../../Pages/auth/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

<main class="dashboard">
    <h1>Margem de Lucro</h1>

    <div class="cards-container">
        <div class="card receita">
            <h2>Média (<?php echo $tituloFiltro; ?>)</h2>
            <p><?php echo number_format($mediaMargem, 2, ',', '.'); ?>%</p>
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
    <button id="btnVoltar" class="voltar-btn">← Voltar</button>
    <div id="tabela-container" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Margem de Lucro (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($labels as $i => $periodo) {
                    $v = $margens[$i] ?? 0;
                    echo "<tr>
                            <td>{$periodo}</td>
                            <td>".number_format($v,2,',','.')."%</td>
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

    // Toggle gráfico/tabela
    btn.addEventListener('click', () => {
        const mostrandoTabela = tabela.style.display === 'block';
        tabela.style.display = mostrandoTabela ? 'none' : 'block';
        grafico.style.display = mostrandoTabela ? 'block' : 'none';
        btn.innerText = mostrandoTabela ? 'Ver Tabela' : 'Ver Gráfico';

        if (!mostrandoTabela) initChart(); // Renderiza gráfico ao mostrar
    });

    function initChart() {
        if (!grafico.echartsInstance) {
            const myChart = echarts.init(grafico);

            const option = {
                title: {
                    text: 'Margem de Lucro (%)',
                    left: 'center',
                    textStyle: { fontSize: 18, fontWeight: 'bold', color: '#e0e0e0' }
                },
                tooltip: { trigger: 'axis', backgroundColor: 'rgba(0,0,0,0.7)', textStyle: { color: '#fff' } },
                xAxis: {
                    type: 'category',
                    data: <?php echo json_encode($labels); ?>,
                    axisLine: { lineStyle: { color: '#aaa' } },
                    axisLabel: { color: '#e0e0e0' }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { show: false },
                    splitLine: { lineStyle: { color: '#333' } },
                    axisLabel: { color: '#e0e0e0' },
                    min: 0
                },
                series: [{
                    name: 'Margem de Lucro (%)',
                    type: 'line',
                    data: <?php echo json_encode($margens); ?>,
                    smooth: true,
                    lineStyle: { color: '#36A2EB', width: 3 },
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'rgba(54,162,235,0.3)'},{offset:1,color:'rgba(54,162,235,0)'}])
                    },
                    symbol: 'circle',
                    symbolSize: 6
                }]
            };

            myChart.setOption(option);
            grafico.echartsInstance = myChart;
            window.addEventListener('resize', () => myChart.resize());
        } else {
            grafico.echartsInstance.resize();
        }
    }

    // Inicializar gráfico se estiver visível
    if(grafico.style.display !== 'none') initChart();
});

    const btnVoltar = document.getElementById('btnVoltar');
btnVoltar.addEventListener('click', () => {
    history.back(); // volta para a página anterior
});
</script>

</main>
</body>
</html>
