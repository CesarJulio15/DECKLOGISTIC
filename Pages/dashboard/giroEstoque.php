<?php
include __DIR__ . '/../../conexao.php';

$filtro = $_GET['filtro'] ?? 'dia'; // padrão: dia

switch ($filtro) {
    case 'mes':
        $sql = "SELECT DATE_FORMAT(data_movimentacao, '%Y-%m') AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY DATE_FORMAT(data_movimentacao, '%Y-%m')
                ORDER BY periodo ASC";
        break;

    case 'ano':
        $sql = "SELECT YEAR(data_movimentacao) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY YEAR(data_movimentacao)
                ORDER BY periodo ASC";
        break;

    default: // dia
        $sql = "SELECT DATE(data_movimentacao) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY DATE(data_movimentacao)
                ORDER BY periodo ASC";
        break;
}

$res = mysqli_query($conn, $sql);

$labels = [];
$dadosEntradas = [];
$dadosSaidas = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['periodo'];
    $dadosEntradas[] = (int)$row['entradas'];
    $dadosSaidas[] = (int)$row['saidas'];
}


// Definir condição de filtro para os cards
switch ($filtro) {
    case 'mes':
        $condicao = "DATE_FORMAT(data_movimentacao, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $tituloFiltro = "Mês";
        break;

    case 'ano':
        $condicao = "YEAR(data_movimentacao) = YEAR(CURDATE())";
        $tituloFiltro = "Ano";
        break;

    default: // dia
        $condicao = "DATE(data_movimentacao) = CURDATE()";
        $tituloFiltro = "Dia";
        break;
}

// Somar total de entradas no período
$sqlEntradas = "SELECT SUM(quantidade) AS total_entradas 
                FROM movimentacoes_estoque 
                WHERE tipo = 'entrada' AND $condicao";
$resEntradas = mysqli_query($conn, $sqlEntradas);
$entradas = (mysqli_fetch_assoc($resEntradas)['total_entradas']) ?? 0;

// Somar total de saídas no período
$sqlSaidas = "SELECT SUM(quantidade) AS total_saidas 
              FROM movimentacoes_estoque 
              WHERE tipo = 'saida' AND $condicao";
$resSaidas = mysqli_query($conn, $sqlSaidas);
$saidas = (mysqli_fetch_assoc($resSaidas)['total_saidas']) ?? 0;

// Calcular variação percentual
$percentual = 0;
$seta = "↑";
$classe = "positivo";
if ($entradas > 0) {
    $percentual = (($entradas - $saidas) / $entradas) * 100;
    if ($percentual < 0) {
        $seta = "↓";
        $classe = "negativo";
    }
}


// Calcular variação percentual
$percentual = 0;
$seta = "↑";
$classe = "positivo";
if ($entradas > 0) {
    $percentual = (($entradas - $saidas) / $entradas) * 100;
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
    <link rel="stylesheet" href="../../assets/giroEstoque.css">
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Decklogistic</title>
</head>
<body>
    <style>
    /* Conteúdo principal */
.dashboard {
    margin-left: 0; /* remove o espaço da esquerda */
    padding: 20px;  /* padding interno para não colar na borda */
}

/* Container de cards */
.cards-container {
    display: flex;
    gap: 20px;
    margin-left: 0; /* garante que os cards fiquem alinhados à esquerda */
}

/* Cards individuais */
.card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    flex: 1;
}

/* Gráfico */
#grafico {
    margin-top: 30px;
    margin-left: 0; /* alinha à esquerda */
}

/* Se quiser ajustar h1 e outros títulos */
.dashboard h1 {
    margin-left: 0;
}
    </style>

<!-- Sidebar completa -->
<aside class="sidebar">
    <div class="logo-area">
        <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>

    <nav class="nav-section">
        <div class="nav-menus">
            <ul class="nav-list top-section">
             <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
             <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
            </ul>

            <hr>

            <ul class="nav-list middle-section">
                <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                <li><a href="/Pages/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
    <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
            </ul>
        </div>

        <div class="bottom-links">
            <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<!-- Conteúdo principal -->
<main class="dashboard">
    <div class="content">
        <h1>Controle de Estoque</h1>

        <div class="cards-container">
            <div class="card <?php echo $classe; ?>">
                <h2>Entradas (<?php echo $tituloFiltro; ?>)</h2>
<p><?php echo $entradas; ?></p>

<h2>Saídas (<?php echo $tituloFiltro; ?>)</h2>
<p><?php echo $saidas; ?></p>

<h2>Variação (<?php echo $tituloFiltro; ?>)</h2>
<p><?php echo $seta . " " . number_format($percentual, 2, ',', '.'); ?>%</p>

            </div>
        </div>

<form method="GET" style="margin-bottom:20px;">
    <label for="filtro">Filtrar por:</label>
    <select name="filtro" id="filtro" onchange="this.form.submit()">
        <option value="dia" <?php if(($_GET['filtro'] ?? '') === 'dia') echo 'selected'; ?>>Dia</option>
        <option value="mes" <?php if(($_GET['filtro'] ?? '') === 'mes') echo 'selected'; ?>>Mês</option>
        <option value="ano" <?php if(($_GET['filtro'] ?? '') === 'ano') echo 'selected'; ?>>Ano</option>
    </select>
</form>


        <div id="grafico"></div>
    </div>
</main>

<script>
    var options = {
        chart: { type: 'line', height: 350 },
series: [
    { name: 'Entradas', data: <?php echo json_encode($dadosEntradas); ?> },
    { name: 'Saídas', data: <?php echo json_encode($dadosSaidas); ?> }
],
xaxis: { categories: <?php echo json_encode($labels); ?> }
    };
    var chart = new ApexCharts(document.querySelector("#grafico"), options);
    chart.render();
</script>

</body>
</html>
