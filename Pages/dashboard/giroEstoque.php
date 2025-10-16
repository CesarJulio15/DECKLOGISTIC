<?php
// giroEstoque.php - versão que detecta relacionamento com loja automaticamente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

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
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    die("Acesso negado: usuário não autenticado. Faça login.");
}
$usuarioId = (int) $_SESSION['usuario_id'];

// busca loja_id do usuário (consulta segura)
$stmt = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
if ($stmt === false) {
    die("Erro ao preparar consulta de usuário: " . $conn->error);
}
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$resUser = $stmt->get_result();
if ($resUser === false) {
    die("Erro ao executar consulta de usuário: " . $conn->error);
}
$userRow = $resUser->fetch_assoc();
if (!$userRow || !isset($userRow['loja_id'])) {
    die("Loja do usuário não encontrada. Verifique se o usuário possui loja_id cadastrado.");
}
$lojaId = (int)$userRow['loja_id'];
$stmt->close();

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

// Saída mínima para o navegador
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Giro de Estoque - <?=htmlspecialchars($tituloFiltro ?? 'Geral')?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:20px}
    .card{display:inline-block;border:1px solid #ddd;padding:12px;margin:6px;border-radius:6px}
    table{border-collapse:collapse;width:100%;margin-top:1rem}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
    code{background:#f5f5f5;padding:4px;border-radius:4px;display:inline-block}
  </style>
</head>
<body>
  <h1>Giro de Estoque — <?=htmlspecialchars($tituloFiltro ?? 'Geral')?></h1>

  <div class="card"><strong>Total Entradas (no <?=htmlspecialchars($tituloFiltro ?? 'período')?> atual):</strong><br><?=number_format($entradas,0,',','.')?></div>
  <div class="card"><strong>Total Saídas (no <?=htmlspecialchars($tituloFiltro ?? 'período')?> atual):</strong><br><?=number_format($saidas,0,',','.')?></div>

  <h2>Dados agregados (períodos)</h2>
  <?php if (empty($labels)): ?>
    <p>Nenhum registro encontrado para o filtro selecionado.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Período</th><th>Entradas</th><th>Saídas</th></tr></thead>
      <tbody>
        <?php foreach ($labels as $i => $label): ?>
          <tr>
            <td><?=htmlspecialchars($label)?></td>
            <td><?=number_format($dadosEntradas[$i] ?? 0,0,',','.')?></td>
            <td><?=number_format($dadosSaidas[$i] ?? 0,0,',','.')?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($debug): ?>
    <h3>Debug</h3>
    <p>Estratégia: <?= $hasLojaCol ? 'filtrar por movimentacoes_estoque.loja_id' : 'filtrar via join produtos (movimentacoes_estoque.produto_id → produtos.id)' ?></p>
    <p>Query executada (agregada): <code><?=htmlspecialchars($sql)?></code></p>
    <p>SQL Totais Entradas: <code><?=htmlspecialchars($sqlEntradas)?></code></p>
    <p>SQL Totais Saídas: <code><?=htmlspecialchars($sqlSaidas)?></code></p>
  <?php endif; ?>
</body>
</html>
