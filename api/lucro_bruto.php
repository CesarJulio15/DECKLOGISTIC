<?php

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
    SELECT SUM(valor_total) AS lucro_bruto
    FROM vendas
    WHERE loja_id = ? AND $wherePeriodo
");
$stmt->execute([$loja_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($result);
