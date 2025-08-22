<?php
// api/meses_prejuizo.php
session_start(); 
require_once '../conexao.php';

$loja_id = $_GET['id']; // default 1

$query = "
SELECT 
    DATE_FORMAT(v.data_venda, '%Y-%m') AS mes,
    IFNULL(SUM(v.valor_total), 0) AS receita,
    IFNULL(SUM(tf.valor), 0) AS despesa
FROM (
    SELECT data_venda, valor_total
    FROM vendas
    WHERE loja_id = ?
) v
LEFT JOIN (
    SELECT data_transacao, valor
    FROM transacoes_financeiras
    WHERE loja_id = ? AND tipo='saida'
) tf
ON DATE_FORMAT(v.data_venda, '%Y-%m') = DATE_FORMAT(tf.data_transacao, '%Y-%m')
GROUP BY DATE_FORMAT(v.data_venda, '%Y-%m')
HAVING despesa > receita
ORDER BY mes ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $loja_id, $loja_id);
$stmt->execute();
$result = $stmt->get_result();

$meses_prejuizo = [];
while($row = $result->fetch_assoc()){
    $meses_prejuizo[] = [
        'mes' => $row['mes'],
        'receita' => (float)$row['receita'],
        'despesa' => (float)$row['despesa'],
        'prejuizo' => (float)$row['despesa'] - (float)$row['receita']
    ];
}

echo json_encode($meses_prejuizo);
