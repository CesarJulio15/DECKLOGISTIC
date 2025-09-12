<?php
// total_estoque.php (versão corrigida)
// Ajuste o caminho do require_once se necessário (../conexao.php ou ../../conexao.php)
require_once '../conexao.php';

// Debug temporário (remova em produção se quiser)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

mysqli_set_charset($conn, 'utf8mb4');

$loja_id = isset($_GET['loja_id']) ? intval($_GET['loja_id']) : 0;
$lojaFilter = $loja_id ? " AND loja_id = $loja_id " : "";

/* 1) Total atual de estoque (filtrado por loja se informado) */
$res = mysqli_query($conn, "SELECT COALESCE(SUM(quantidade_estoque),0) AS total FROM produtos WHERE deletado_em IS NULL $lojaFilter");
if (!$res) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Erro SQL total: ' . mysqli_error($conn)]);
    exit;
}
$row = mysqli_fetch_assoc($res);
$total = (int)$row['total'];

/* 2) Total de produtos distintos */
$resProd = mysqli_query($conn, "SELECT COUNT(*) AS total_produtos FROM produtos WHERE deletado_em IS NULL $lojaFilter");
if (!$resProd) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Erro SQL total_produtos: ' . mysqli_error($conn)]);
    exit;
}
$row = mysqli_fetch_assoc($resProd);
$total_produtos = (int)$row['total_produtos'];

/* 3) Série: últimos 6 meses (incluindo mês atual).
   - Gera array com os 6 meses (YYYY-MM) e preenche com soma por mês (ou 0 se não houver).
*/
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}
// data de corte: primeiro dia do mês há 5 meses (ex.: para 2025-09 -> '2025-04-01')
$start_date = date('Y-m-01', strtotime('-5 months'));

// Agrega em uma única query (produtos criados a partir do $start_date)
$sql = "
    SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes,
           COALESCE(SUM(quantidade_estoque),0) AS total_mes
    FROM produtos
    WHERE deletado_em IS NULL
      AND criado_em >= '$start_date'
      $lojaFilter
    GROUP BY mes
    ORDER BY mes ASC
";

$resSeries = mysqli_query($conn, $sql);
if (!$resSeries) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Erro SQL series: ' . mysqli_error($conn)]);
    exit;
}

// monta mapa mês => total encontrado
$agg = [];
while ($r = mysqli_fetch_assoc($resSeries)) {
    $agg[$r['mes']] = (int)$r['total_mes'];
}

// preenche os 6 meses com valor encontrado ou 0
$series = [];
foreach ($months as $m) {
    $series[] = [
        'mes' => $m,
        'total' => isset($agg[$m]) ? $agg[$m] : 0
    ];
}

// retorno final
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total' => $total,
    'total_produtos' => $total_produtos,
    'series' => $series
]);
