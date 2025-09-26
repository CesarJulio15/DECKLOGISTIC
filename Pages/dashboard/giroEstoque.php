<?php
include __DIR__ . '/../../conexao.php';

$filtro = $_GET['filtro'] ?? 'dia'; // padrão: dia

switch ($filtro) {
    case 'bimestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
    case 'trimestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
    case 'semestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
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

// Condição de filtro para os cards
switch ($filtro) {
    case 'bimestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/2)+1, 2, '0'))";
        $tituloFiltro = "Bimestre";
        break;
    case 'trimestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/3)+1, 2, '0'))";
        $tituloFiltro = "Trimestre";
        break;
    case 'semestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/6)+1, 2, '0'))";
        $tituloFiltro = "Semestre";
        break;
    case 'mes':
        $condicao = "DATE_FORMAT(data_movimentacao, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $tituloFiltro = "Mês";
        break;
    case 'ano':
        $condicao = "YEAR(data_movimentacao) = YEAR(CURDATE())";
        $tituloFiltro = "Ano";
        break;
    default:
        $condicao = "DATE(data_movimentacao) = CURDATE()";
        $tituloFiltro = "Dia";
        break;
}

// Totais
$sqlEntradas = "SELECT SUM(quantidade) AS total_entradas FROM movimentacoes_estoque WHERE tipo='entrada' AND $condicao";
$entradas = mysqli_fetch_assoc(mysqli_query($conn, $sqlEntradas))['total_entradas'] ?? 0;

$sqlSaidas = "SELECT SUM(quantidade) AS total_saidas FROM movimentacoes_estoque WHERE tipo='saida' AND $condicao";
$saidas = mysqli_fetch_assoc(mysqli_query($conn, $sqlSaidas))['total_saidas'] ?? 0;

// Variação
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Decklogistic</title>
<link rel="stylesheet" href="../../assets/sidebar.css">
<link rel="stylesheet" href="../../assets/giroEstoque.css">
<link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>

<aside class="sidebar">
    <div class="logo-area">
        <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
            <ul class="nav-list top-section">
                <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
            </ul>
            <hr>
            <ul class="nav-list middle-section">
                <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
            </ul>
        </div>
        <div class="bottom-links">
            <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<main class="dashboard">
    <div class="content">
        <h1>Controle de Estoque</h1>

        <div class="cards-container">
            <div class="card entradas">
                <h2>Entradas (<?php echo $tituloFiltro; ?>)</h2>
                <p><?php echo $entradas; ?></p>
            </div>
            <div class="card saidas">
                <h2>Saídas (<?php echo $tituloFiltro; ?>)</h2>
                <p><?php echo $saidas; ?></p>
            </div>
            <div class="card variacao <?php echo $classe; ?>">
                <h2>Variação (<?php echo $tituloFiltro; ?>)</h2>
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
                        <th>Entradas</th>
                        <th>Saídas</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($labels as $i => $periodo) {
                        $entrada = $dadosEntradas[$i] ?? 0;
                        $saida = $dadosSaidas[$i] ?? 0;
                        $saldo = $entrada - $saida;
                        echo "<tr>
                                <td>{$periodo}</td>
                                <td>{$entrada}</td>
                                <td>{$saida}</td>
                                <td>{$saldo}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

<script>
    // Alternar gráfico <-> tabela
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

    // Salvar estado ao mudar filtro
    const filtroSelect = document.getElementById('filtro');
    filtroSelect.addEventListener('change', function() {
        localStorage.setItem('viewState', document.getElementById('tabela-container').style.display);
        this.form.submit();
    });

    // Restaurar estado ao carregar
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
            { name: 'Entradas', data: <?php echo json_encode($dadosEntradas); ?> },
            { name: 'Saídas', data: <?php echo json_encode($dadosSaidas); ?> }
        ],
        xaxis: { categories: <?php echo json_encode($labels); ?> }
    };
    var chart = new ApexCharts(document.querySelector("#grafico"), options);
    chart.render();
</script>
    </div>
</main>
</body>
</html>
