<?php
require_once '../config.php';

$loja_id = $_GET['loja_id'] ?? 1;
$periodo = $_GET['periodo'] ?? 'mes';

$wherePeriodo = '';
switch($periodo) {
    case 'mes':
        $wherePeriodo = "YEAR(data_venda) = YEAR(CURDATE()) AND MONTH(data_venda) = MONTH(CURDATE())";
        break;
    case 'semestre':
        $semestre = ceil(date('n') / 6);
        $wherePeriodo = "YEAR(data_venda) = YEAR(CURDATE()) AND CEIL(MONTH(data_venda)/6) = $semestre";
        break;
    case 'ano':
        $wherePeriodo = "YEAR(data_venda) = YEAR(CURDATE())";
        break;
}

$stmt = $pdo->prepare("
    SELECT SUM(valor_total) AS receita, SUM(custo_total) AS custo
    FROM vendas
    WHERE loja_id = ? AND $wherePeriodo
");
$stmt->execute([$loja_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$margem = 0;
if ($data['receita'] > 0) {
    $margem = (($data['receita'] - $data['custo']) / $data['receita']) * 100;
}

header('Content-Type: application/json');
echo json_encode(['margem_lucro_percent' => round($margem, 2)]);
