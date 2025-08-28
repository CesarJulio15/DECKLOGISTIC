<?php
require_once '../conexao.php';

// Total atual de estoque
$res = mysqli_query($conn, "SELECT SUM(quantidade_estoque) AS total FROM produtos");
$total = 0;
if ($row = mysqli_fetch_assoc($res)) {
    $total = (int)$row['total'];
}

// Histórico diário (últimos 30 dias) baseado em movimentações de estoque
$series = [];
$sqlSeries = "
    SELECT 
        DATE(data_movimentacao) AS dia,
        SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE -quantidade END) AS movimento
    FROM movimentacoes_estoque
    WHERE data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY dia
    ORDER BY dia ASC
";
$resSeries = mysqli_query($conn, $sqlSeries);

$estoqueAcumulado = 0;
$temp = [];
while ($row = mysqli_fetch_assoc($resSeries)) {
    $estoqueAcumulado += (int)$row['movimento'];
    $temp[] = [
        'data' => $row['dia'],
        'valor' => $estoqueAcumulado
    ];
}

// Retorno
header('Content-Type: application/json');
echo json_encode([
    'total' => $total,
    'series' => $temp
]);
