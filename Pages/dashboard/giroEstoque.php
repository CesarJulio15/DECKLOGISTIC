<?php
// giroEstoque.php - versão que detecta relacionamento com loja automaticamente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../session_check.php';
// include seguro (ajuste o caminho se necessário)
$path = __DIR__ . '/../../conexao.php';
if (!file_exists($path)) {
    die("Erro: arquivo de conexão não encontrado em: $path");
}
include $path;

// valida conexão
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Erro: variável \$conn não definida ou não é uma conexão mysqli. Verifique seu arquivo conexao.php");
}

// exige usuário logado
if (!isset($_SESSION['loja_id']) || empty($_SESSION['loja_id'])) {
    die("Acesso negado: usuário não autenticado. Faça login.");
}
// Detecta tipo de login (empresa ou funcionário)
$tipoLogin = $_SESSION['tipo_login'] ?? 'funcionario';

if ($tipoLogin === 'empresa') {
    // Se for login da loja, o próprio ID da loja é o loja_id
    $lojaId = (int)$_SESSION['loja_id'];
} else {
    // Se for funcionário, buscamos o loja_id na tabela usuarios
    $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
    if (!$usuarioId) {
        die("Acesso negado: usuário não autenticado.");
    }

    $stmt = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
    if (!$stmt) {
        die("Erro ao preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row || empty($row['loja_id'])) {
        die("Loja do usuário não encontrada. Verifique se o funcionário está vinculado a uma loja.");
    }
    $lojaId = (int)$row['loja_id'];
    $stmt->close();
}

// Charset da página / conexão
header('Content-Type: text/html; charset=utf-8');
$conn->set_charset('utf8mb4');

// debug via GET (ex.: ?debug=1)
$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

// filtro via whitelist para evitar valores inválidos
$allowed = ['dia','mes','bimestre','trimestre','semestre','ano'];
$filtro = isset($_GET['filtro']) ? strtolower(trim($_GET['filtro'])) : 'dia';
if (!in_array($filtro, $allowed)) $filtro = 'dia';

// --- Detecta como filtrar por loja ---
// verifica se movimentacoes_estoque.loja_id existe
$schema = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
$checkLojaColQ = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = '{$schema}'
                    AND TABLE_NAME = 'movimentacoes_estoque'
                    AND COLUMN_NAME = 'loja_id'";
$hasLojaCol = false;
if ($result = $conn->query($checkLojaColQ)) {
    $hasLojaCol = ($result->fetch_row()[0] > 0);
    $result->free();
} else {
    // se por algum motivo não for possível acessar INFORMATION_SCHEMA, seguimos para checks alternativos
    if ($debug) echo "Aviso: não foi possível consultar INFORMATION_SCHEMA: " . htmlspecialchars($conn->error);
}

// se não tem loja_id, verifica se existe produto_id e tabela produtos com loja_id
$useJoinProdutos = false;
if (!$hasLojaCol) {
    // verifica se movimentacoes_estoque.produto_id existe
    $checkProdIdQ = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = '{$schema}'
                       AND TABLE_NAME = 'movimentacoes_estoque'
                       AND COLUMN_NAME = 'produto_id'";
    $hasProdutoId = false;
    if ($result = $conn->query($checkProdIdQ)) {
        $hasProdutoId = ($result->fetch_row()[0] > 0);
        $result->free();
    }

    // verifica se tabela produtos existe e tem coluna loja_id
    $checkProdutosTableQ = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA = '{$schema}'
                              AND TABLE_NAME = 'produtos'
                              AND COLUMN_NAME = 'loja_id'";
    $produtosHasLoja = false;
    if ($result = $conn->query($checkProdutosTableQ)) {
        $produtosHasLoja = ($result->fetch_row()[0] > 0);
        $result->free();
    }

    if ($hasProdutoId && $produtosHasLoja) {
        $useJoinProdutos = true;
    }
}

// se nenhuma estratégia disponível, pede DESCRIBE
if (!$hasLojaCol && !$useJoinProdutos) {
    die("Não foi possível filtrar por loja automaticamente. A tabela `movimentacoes_estoque` não possui coluna `loja_id` e/ou não é possível associá-la à tabela `produtos`. Por favor cole aqui o resultado de: <code>DESCRIBE movimentacoes_estoque;</code> ou confirme se existe <code>produtos.id</code> e <code>produtos.loja_id</code> para que eu adapte o SQL.");
}

// monta cláusulas conforme estratégia detectada
if ($hasLojaCol) {
    // usaremos alias m para movimentacoes_estoque e filtraremos por m.loja_id
    $fromClauseBase = "FROM movimentacoes_estoque AS m WHERE m.loja_id = {$lojaId} ";
    $whereTotaisPrefix = "m.";
    $groupFieldPrefix = "m.";
} else {
    // usaremos join com produtos p: movimentacoes_estoque AS m JOIN produtos p ON m.produto_id = p.id
    $fromClauseBase = "FROM movimentacoes_estoque AS m JOIN produtos AS p ON m.produto_id = p.id WHERE p.loja_id = {$lojaId} ";
    $whereTotaisPrefix = ""; // já usaremos p.loja_id em whereTotais abaixo
    $groupFieldPrefix = "m.";
}

// agora monta as queries agregadas com base no filtro, usando $fromClauseBase
switch ($filtro) {
    case 'bimestre':
        $sql = "SELECT CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/2)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/2)+1, 2, '0'))
                ORDER BY periodo ASC";
        $tituloFiltro = "Bimestre";
        break;
    case 'trimestre':
        $sql = "SELECT CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/3)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/3)+1, 2, '0'))
                ORDER BY periodo ASC";
        $tituloFiltro = "Trimestre";
        break;
    case 'semestre':
        $sql = "SELECT CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/6)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY CONCAT(YEAR({$groupFieldPrefix}data_movimentacao), '-', LPAD(FLOOR((MONTH({$groupFieldPrefix}data_movimentacao)-1)/6)+1, 2, '0'))
                ORDER BY periodo ASC";
        $tituloFiltro = "Semestre";
        break;
    case 'mes':
        $sql = "SELECT DATE_FORMAT({$groupFieldPrefix}data_movimentacao, '%Y-%m') AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY DATE_FORMAT({$groupFieldPrefix}data_movimentacao, '%Y-%m')
                ORDER BY periodo ASC";
        $tituloFiltro = "Mês";
        break;
    case 'ano':
        $sql = "SELECT YEAR({$groupFieldPrefix}data_movimentacao) AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY YEAR({$groupFieldPrefix}data_movimentacao)
                ORDER BY periodo ASC";
        $tituloFiltro = "Ano";
        break;
    default: // dia
        $sql = "SELECT DATE({$groupFieldPrefix}data_movimentacao) AS periodo,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'entrada' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS entradas,
                       SUM(CASE WHEN TRIM(LOWER({$groupFieldPrefix}tipo)) = 'saida' THEN COALESCE({$groupFieldPrefix}quantidade,0) ELSE 0 END) AS saidas
                {$fromClauseBase}
                GROUP BY DATE({$groupFieldPrefix}data_movimentacao)
                ORDER BY periodo ASC";
        $tituloFiltro = "Dia";
        break;
}

// Executa query com verificação de erro
$res = mysqli_query($conn, $sql);
if ($res === false) {
    die("Erro na consulta principal: " . mysqli_error($conn) . "<br>SQL: " . htmlspecialchars($sql));
}

$labels = [];
$dadosEntradas = [];
$dadosSaidas = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['periodo'];
    $dadosEntradas[] = (int)($row['entradas'] ?? 0);
    $dadosSaidas[] = (int)($row['saidas'] ?? 0);
}



// monta condição de período para totais (sem WHERE de loja ainda)
switch ($filtro) {
    case 'bimestre':
        $condicaoPeriodo = "CONCAT(YEAR(" . ($groupFieldPrefix . "data_movimentacao") . "), '-', LPAD(FLOOR((MONTH(" . ($groupFieldPrefix . "data_movimentacao") . ")-1)/2)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/2)+1, 2, '0'))";
        break;
    case 'trimestre':
        $condicaoPeriodo = "CONCAT(YEAR(" . ($groupFieldPrefix . "data_movimentacao") . "), '-', LPAD(FLOOR((MONTH(" . ($groupFieldPrefix . "data_movimentacao") . ")-1)/3)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/3)+1, 2, '0'))";
        break;
    case 'semestre':
        $condicaoPeriodo = "CONCAT(YEAR(" . ($groupFieldPrefix . "data_movimentacao") . "), '-', LPAD(FLOOR((MONTH(" . ($groupFieldPrefix . "data_movimentacao") . ")-1)/6)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/6)+1, 2, '0'))";
        break;
    case 'mes':
        $condicaoPeriodo = "DATE_FORMAT(" . ($groupFieldPrefix . "data_movimentacao") . ", '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        break;
    case 'ano':
        $condicaoPeriodo = "YEAR(" . ($groupFieldPrefix . "data_movimentacao") . ") = YEAR(CURDATE())";
        break;
    default:
        $condicaoPeriodo = "DATE(" . ($groupFieldPrefix . "data_movimentacao") . ") = CURDATE()";
        break;
}

// adiciona filtro por loja aos totais conforme a estratégia
if ($hasLojaCol) {
    $whereTotais = $condicaoPeriodo . " AND m.loja_id = " . $lojaId;
} else {
    // usamos p.loja_id
    $whereTotais = $condicaoPeriodo . " AND p.loja_id = " . $lojaId;
}

// Totais com normalização de 'tipo' e fallback para 0
if ($hasLojaCol) {
    $sqlEntradas = "SELECT SUM(COALESCE(m.quantidade,0)) AS total_entradas FROM movimentacoes_estoque AS m WHERE TRIM(LOWER(m.tipo))='entrada' AND {$whereTotais}";
    $sqlSaidas   = "SELECT SUM(COALESCE(m.quantidade,0)) AS total_saidas   FROM movimentacoes_estoque AS m WHERE TRIM(LOWER(m.tipo))='saida'   AND {$whereTotais}";
} else {
    // join com produtos para totais
    $sqlEntradas = "SELECT SUM(COALESCE(m.quantidade,0)) AS total_entradas FROM movimentacoes_estoque AS m JOIN produtos p ON m.produto_id = p.id WHERE TRIM(LOWER(m.tipo))='entrada' AND {$whereTotais}";
    $sqlSaidas   = "SELECT SUM(COALESCE(m.quantidade,0)) AS total_saidas   FROM movimentacoes_estoque AS m JOIN produtos p ON m.produto_id = p.id WHERE TRIM(LOWER(m.tipo))='saida'   AND {$whereTotais}";
}

$resE = mysqli_query($conn, $sqlEntradas);
if ($resE === false) {
    die("Erro no SQL de entradas: " . mysqli_error($conn) . "<br>SQL: " . htmlspecialchars($sqlEntradas));
}
$entradasRow = mysqli_fetch_assoc($resE);
$entradas = (int)($entradasRow['total_entradas'] ?? 0);

$resS = mysqli_query($conn, $sqlSaidas);
if ($resS === false) {
    die("Erro no SQL de saídas: " . mysqli_error($conn) . "<br>SQL: " . htmlspecialchars($sqlSaidas));
}
$saidasRow = mysqli_fetch_assoc($resS);
$saidas = (int)($saidasRow['total_saidas'] ?? 0);

// ✅ Cálculo da variação percentual entre entradas e saídas
$variacao = $entradas - $saidas;
$percentual = 0;
$seta = "↑";
$classe = "positivo";

if ($entradas > 0) {
    $percentual = ($variacao / $entradas) * 100;
    if ($percentual < 0) {
        $seta = "↓";
        $classe = "negativo";
    }
}


// Saída mínima para o navegador
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giro de Estoque - Decklogistic</title>
    <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <link rel="stylesheet" href="../../assets/lucroB.css">
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
</head>
<noscript>
    <meta http-equiv="refresh" content="0; URL=../../no-javascript.php">
</noscript>
<body>
<div class="content">
  <aside class="sidebar">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>

    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span>Financeiro</a></li>
          <li><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span>Estoque</a></li>
        </ul>

        <hr>

        <ul class="nav-list middle-section">
          <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span>Visão Geral</a></li>
          <li><a  class="active" href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span>Produtos</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Histórico"></span>Histórico</a></li>
        </ul>
      </div>

      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span>Conta</a>
        <a href="../auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span>Dicas</a>
      </div>
    </nav>
  </aside>

    <main class="dashboard">
        <h1>Giro de Estoque</h1>

        <div class="cards-container">
            <div class="card receita">
                <h2>Entradas (<?php echo $tituloFiltro; ?>)</h2>
                <p><?php echo number_format($entradas, 0, ',', '.'); ?></p>
            </div>
            <div class="card custo">
                <h2>Saídas (<?php echo $tituloFiltro; ?>)</h2>
                <p><?php echo number_format($saidas, 0, ',', '.'); ?></p>
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
        <button id="btnVoltar" class="voltar-btn">← Voltar</button>

        <div id="tabela-container" style="display:none;">
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Entradas</th>
                        <th>Saídas</th>
                        <th>Variação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($labels as $i => $periodo) {
                        $e = $dadosEntradas[$i] ?? 0;
                        $s = $dadosSaidas[$i] ?? 0;
                        $v = $e - $s;
                        echo "<tr>
                                <td>".htmlspecialchars($periodo)."</td>
                                <td>".number_format($e,0,',','.')."</td>
                                <td>".number_format($s,0,',','.')."</td>
                                <td>".number_format($v,0,',','.')."</td>
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

            grafico.style.width = "100%";
            grafico.style.height = "400px";
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

            btn.addEventListener('click', () => {
                if(tabela.style.display === 'none') {
                    grafico.style.display = 'none';
                    tabela.style.display = 'block';
                    btn.innerText = 'Ver Gráfico';
                } else {
                    grafico.style.display = 'block';
                    tabela.style.display = 'none';
                    btn.innerText = 'Ver Tabela';
                    initChart();
                }
                localStorage.setItem('viewState', tabela.style.display);
            });

            filtroSelect.addEventListener('change', function() {
                localStorage.setItem('viewState', tabela.style.display);
                this.form.submit();
            });

            function initChart() {
                if (!grafico.echartsInstance) {
                    const myChart = echarts.init(grafico);

                    // Calcular variação por ponto
                    const dadosVaria = <?php echo json_encode(array_map(function($e, $s){ return $e - $s; }, $dadosEntradas, $dadosSaidas)); ?>;

                    const option = {
                        title: {
                            text: 'Entradas x Saídas x Variação',
                            left: 'center',
                            textStyle: { fontSize: 18, fontWeight: 'bold', color: '#e0e0e0' }
                        },
                        tooltip: { trigger: 'axis', backgroundColor: 'rgba(0,0,0,0.7)', textStyle: { color: '#fff' } },
                        legend: {
                            bottom: 0,
                            textStyle: { color: '#e0e0e0' },
                            data: ['Entradas', 'Saídas', 'Variação']
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
                                name: 'Entradas',
                                type: 'line',
                                smooth: true,
                                data: <?php echo json_encode($dadosEntradas); ?>,
                                lineStyle: { color: '#4caf50', width: 3 },
                                areaStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'rgba(76,175,80,0.4)'},{offset:1,color:'rgba(76,175,80,0)'}]) },
                                symbol: 'circle', symbolSize: 6, itemStyle: { color: '#aaffadff' }
                            },
                            {
                                name: 'Saídas',
                                type: 'line',
                                smooth: true,
                                data: <?php echo json_encode($dadosSaidas); ?>,
                                lineStyle: { color: '#f44336', width: 3 },
                                areaStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[{offset:0,color:'rgba(244,67,54,0.3)'},{offset:1,color:'rgba(244,67,54,0)'}]) },
                                symbol: 'square', symbolSize: 6, itemStyle: { color: '#f79c96ff' }
                            },
                            {
                                name: 'Variação',
                                type: 'line',
                                smooth: true,
                                data: dadosVaria,
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
</div>
</body>
</html>
