<?php
require_once '../config.php';

$loja_id = $_GET['loja_id'] ?? 1;
$periodo = $_GET['periodo'] ?? 'mes';

// Definir período
$wherePeriodo = '';
switch($periodo) {
    case 'mes':
        $wherePeriodo = "YEAR(v.data_venda) = YEAR(CURDATE()) AND MONTH(v.data_venda) = MONTH(CURDATE())";
        break;
    case 'semestre':
        $semestre = ceil(date('n') / 6);
        $wherePeriodo = "YEAR(v.data_venda) = YEAR(CURDATE()) AND CEIL(MONTH(v.data_venda)/6) = $semestre";
        break;
    case 'ano':
        $wherePeriodo = "YEAR(v.data_venda) = YEAR(CURDATE())";
        break;
}

// Query de lucro bruto real
$stmt = $pdo->prepare("
    SELECT SUM((iv.preco_unitario - iv.custo_unitario) * iv.quantidade) AS lucro_bruto
    FROM vendas v
    INNER JOIN itens_venda iv ON iv.venda_id = v.id
    WHERE v.loja_id = ? AND $wherePeriodo
");
$stmt->execute([$loja_id]);
$result = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode($result);
