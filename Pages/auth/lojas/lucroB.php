<?php
    session_start();
    include __DIR__ . "/../../../conexao.php";
include __DIR__ . '/../../../session_check.php';

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

    // ID da loja logada
    $lojaId = $_SESSION['loja_id'] ?? 0;
    $whereLoja = $lojaId ? " WHERE loja_id = " . intval($lojaId) : "";

    // Consulta receita e custo agrupado
    $sql = "SELECT $dataField AS periodo,
                SUM(valor_total) AS receita,
                SUM(custo_total) AS custo
            FROM vendas
            $whereLoja
            GROUP BY $dataField
            ORDER BY periodo ASC";

    $res = mysqli_query($conn, $sql);

    $labels = [];
    $dadosReceita = [];
    $dadosCusto = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = $row['periodo'];
        $dadosReceita[] = (float)($row['receita'] ?? 0);
        $dadosCusto[] = (float)($row['custo'] ?? 0);
    }

    // Condição para totais
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

    // Receita e custo totais para o filtro atual
    $sqlReceita = "SELECT SUM(valor_total) AS total_receita 
                FROM vendas 
                WHERE $condicao " . ($lojaId ? "AND loja_id = " . intval($lojaId) : "");

    $rowReceita = mysqli_fetch_assoc(mysqli_query($conn, $sqlReceita));
    $receita = $rowReceita['total_receita'] ?? 0;

    $sqlCusto = "SELECT SUM(custo_total) AS total_custo 
                FROM vendas 
                WHERE $condicao " . ($lojaId ? "AND loja_id = " . intval($lojaId) : "");

    $rowCusto = mysqli_fetch_assoc(mysqli_query($conn, $sqlCusto));
    $custo = $rowCusto['total_custo'] ?? 0;

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
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    </head>
    <noscript>
    <meta http-equiv="refresh" content="0; URL=../../../no-javascript.php">
</noscript>
    <body>

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
    
    <div class="content">
    <div class="sidebar">
        <link rel="stylesheet" href="../../../assets/sidebar.css">
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
        <button id="btnVoltar" class="voltar-btn">← Voltar</button>

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
    document.addEventListener("DOMContentLoaded", () => {
        const grafico = document.getElementById('grafico');
        const tabela = document.getElementById('tabela-container');
        const btn = document.getElementById('toggleView');
        const filtroSelect = document.getElementById('filtro');

        // Garantir que o container do gráfico tem dimensões
        grafico.style.width = "100%";
        grafico.style.height = "400px"; // você pode ajustar
        grafico.style.minHeight = "400px";

        // Restaurar estado toggle
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

        // Toggle botão
        btn.addEventListener('click', () => {
            if(tabela.style.display === 'none') {
                grafico.style.display = 'none';
                tabela.style.display = 'block';
                btn.innerText = 'Ver Gráfico';
            } else {
                grafico.style.display = 'block';
                tabela.style.display = 'none';
                btn.innerText = 'Ver Tabela';
                initChart(); // inicializa gráfico ao tornar visível
            }
            localStorage.setItem('viewState', tabela.style.display);
        });

        // Submit do filtro
        filtroSelect.addEventListener('change', function() {
            localStorage.setItem('viewState', tabela.style.display);
            this.form.submit();
        });

        // Função de inicialização do gráfico
function initChart() {
    if (!grafico.echartsInstance) {
        const myChart = echarts.init(grafico);

        // Calcular lucro bruto por ponto
        const dadosLucro = <?php echo json_encode(array_map(function($r, $c){ return $r - $c; }, $dadosReceita, $dadosCusto)); ?>;

        const option = {
            title: {
                text: 'Receita x Custo x Lucro Bruto',
                left: 'center',
                textStyle: { fontSize: 18, fontWeight: 'bold', color: '#e0e0e0' }
            },
            tooltip: { trigger: 'axis', backgroundColor: 'rgba(0,0,0,0.7)', textStyle: { color: '#fff' } },
            legend: {
                bottom: 0,
                textStyle: { color: '#e0e0e0' },
                data: ['Receita', 'Custo', 'Lucro Bruto']
            },
            grid: { left: '3%', right: '4%', bottom: '12%', containLabel: true },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: <?php echo json_encode($labels); ?>,
                axisLine: { lineStyle: { color: '#aaa' } },
                axisLabel: { color: '#e0e0e0' }
            },
            yAxis: {
                type: 'value',
                axisLine: { show: false },
                splitLine: { lineStyle: { color: '#333' } },
                axisLabel: { color: '#e0e0e0' }
            },
            series: [
                {
                    name: 'Receita',
                    type: 'line',
                    smooth: true,
                    data: <?php echo json_encode($dadosReceita); ?>,
                    lineStyle: { color: '#4caf50', width: 3 },
                    areaStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'rgba(76,175,80,0.4)'},{offset:1,color:'rgba(76,175,80,0)'}]) },
                    symbol: 'circle', symbolSize: 6, itemStyle: { color: '#aaffadff' }
                },
                {
                    name: 'Custo',
                    type: 'line',
                    smooth: true,
                    data: <?php echo json_encode($dadosCusto); ?>,
                    lineStyle: { color: '#f44336', width: 3 },
                    areaStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'rgba(244,67,54,0.3)'},{offset:1,color:'rgba(244,67,54,0)'}]) },
                    symbol: 'square', symbolSize: 6, itemStyle: { color: '#f79c96ff' }
                },
                {
                    name: 'Lucro Bruto',
                    type: 'line',
                    smooth: true,
                    data: dadosLucro,
                    lineStyle: { color: '#2196f3', width: 3 },
                    areaStyle: { color: 'rgba(33,150,243,0.3)' },
                    symbol: 'diamond', symbolSize: 6, itemStyle: { color: '#a5d4faff' }
                }
            ]
        };

        myChart.setOption(option);
        grafico.echartsInstance = myChart;
        window.addEventListener('resize', () => myChart.resize());
    } else {
        grafico.echartsInstance.resize();
    }
}


        // Inicializar gráfico se visível
        if(grafico.style.display !== 'none') {
            initChart();
        }
    });

    const btnVoltar = document.getElementById('btnVoltar');
    btnVoltar.addEventListener('click', () => {
    history.back(); 
});

    </script>

    </main>
    </body>
    </html>
