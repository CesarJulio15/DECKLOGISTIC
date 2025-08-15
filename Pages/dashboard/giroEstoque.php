<?php
include __DIR__ . '/../../conexao.php';

// Somar total de entradas
$sqlEntradas = "SELECT SUM(quantidade) AS total_entradas 
                FROM movimentacoes_estoque 
                WHERE tipo = 'entrada'";
$resEntradas = mysqli_query($conn, $sqlEntradas);
$entradas = (mysqli_fetch_assoc($resEntradas)['total_entradas']) ?? 0;

// Somar total de saídas
$sqlSaidas = "SELECT SUM(quantidade) AS total_saidas 
              FROM movimentacoes_estoque 
              WHERE tipo = 'saida'";
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../assets/estoque.css">
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
                <h2>Entradas</h2>
                <p><?php echo $entradas; ?></p>
            </div>
            <div class="card <?php echo $classe; ?>">
                <h2>Saídas</h2>
                <p><?php echo $saidas; ?></p>
            </div>
            <div class="card <?php echo $classe; ?>">
                <h2>Variação</h2>
                <p><?php echo $seta . " " . number_format($percentual, 2, ',', '.'); ?>%</p>
            </div>
        </div>

        <div id="grafico"></div>
    </div>
</main>

<script>
    var options = {
        chart: { type: 'line', height: 350 },
        series: [
            { name: 'Entradas', data: [<?php echo $entradas; ?>] },
            { name: 'Saídas', data: [<?php echo $saidas; ?>] }
        ],
        xaxis: { categories: ['Hoje'] }
    };
    var chart = new ApexCharts(document.querySelector("#grafico"), options);
    chart.render();
</script>

</body>
</html>
