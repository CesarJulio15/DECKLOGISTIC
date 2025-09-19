<?php
// total_estoque.php (corrigido para usar sessão)
session_start();
require_once '../conexao.php';

mysqli_set_charset($conn, 'utf8mb4');

// Pega a loja logada
$loja_id = $_SESSION['tipo_login'] === 'empresa'
    ? $_SESSION['usuario_id']
    : $_SESSION['loja_id'] ?? 0; 
if (!$loja_id) {
    // Nenhuma loja logada, retorna zeros
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'total' => 0,
        'total_produtos' => 0,
        'series' => []
    ]);
    exit;
}

// Filtra sempre pela loja logada
$lojaFilter = " AND loja_id = $loja_id ";

/* 1) Total atual de estoque */
$res = mysqli_query($conn, "SELECT COALESCE(SUM(quantidade_estoque),0) AS total FROM produtos WHERE deletado_em IS NULL $lojaFilter");
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro SQL total: ' . mysqli_error($conn)]);
    exit;
}
$row = mysqli_fetch_assoc($res);
$total = (int)$row['total'];

/* 2) Total de produtos distintos */
$resProd = mysqli_query($conn, "SELECT COUNT(*) AS total_produtos FROM produtos WHERE deletado_em IS NULL $lojaFilter");
if (!$resProd) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro SQL total_produtos: ' . mysqli_error($conn)]);
    exit;
}
$row = mysqli_fetch_assoc($resProd);
$total_produtos = (int)$row['total_produtos'];

/* 3) Série: últimos 6 meses */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}
$start_date = date('Y-m-01', strtotime('-5 months'));

$sql = "
    SELECT DATE_FORMAT(data_reabastecimento, '%Y-%m') AS mes,
           COALESCE(SUM(quantidade_estoque),0) AS total_mes
    FROM produtos
    WHERE deletado_em IS NULL
      AND data_reabastecimento >= '$start_date'
      $lojaFilter
    GROUP BY mes
    ORDER BY mes ASC
";

$resSeries = mysqli_query($conn, $sql);
if (!$resSeries) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro SQL series: ' . mysqli_error($conn)]);
    exit;
}

$agg = [];
while ($r = mysqli_fetch_assoc($resSeries)) {
    $agg[$r['mes']] = (int)$r['total_mes'];
}

$series = [];
foreach ($months as $m) {
    $series[] = [
        'mes' => $m,
        'total' => $agg[$m] ?? 0
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total' => $total,
    'total_produtos' => $total_produtos,
    'series' => $series
]);
