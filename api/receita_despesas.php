<?php
// api/receita_despesas.php
require_once '../conexao.php';

$loja_id = $_GET['loja_id'] ?? 1; // default 1
$periodo = $_GET['periodo'] ?? 'mes'; // pode ser 'mes' ou 'ano'

$data_inicio = date('Y-m-01'); // início do mês atual
$data_fim = date('Y-m-t'); // fim do mês atual

// Receita: soma de vendas por dia
$queryReceita = "SELECT DATE(data_venda) as dia, SUM(valor_total) as receita
                 FROM vendas
                 WHERE loja_id = ? AND data_venda BETWEEN ? AND ?
                 GROUP BY DATE(data_venda)";
$stmt = $conn->prepare($queryReceita);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$receita = [];
while ($row = $result->fetch_assoc()) {
    $receita[$row['dia']] = (float)$row['receita'];
}

// Despesas: soma de transações de saída por dia
$queryDespesa = "SELECT data_transacao as dia, SUM(valor) as despesa
                 FROM transacoes_financeiras
                 WHERE loja_id = ? AND tipo='saida' AND data_transacao BETWEEN ? AND ?
                 GROUP BY data_transacao";
$stmt = $conn->prepare($queryDespesa);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$despesa = [];
while ($row = $result->fetch_assoc()) {
    $despesa[$row['dia']] = (float)$row['despesa'];
}

echo json_encode([
    'receita' => $receita,
    'despesa' => $despesa
]);
?>
