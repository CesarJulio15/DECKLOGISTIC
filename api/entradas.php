<?php
require_once __DIR__ . '/../conexao.php';

$loja_id = $_GET['loja_id'] ?? 1;
$periodo = $_GET['periodo'] ?? 'mes';

$data_inicio = date('Y-m-01');
$data_fim = date('Y-m-t');

// Entradas: vendas
$queryVendas = "SELECT DATE(data_venda) as dia, SUM(valor_total) as vendas
                FROM vendas
                WHERE loja_id = ? AND data_venda BETWEEN ? AND ?
                GROUP BY DATE(data_venda)";
$stmt = $conn->prepare($queryVendas);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$vendas = [];
while ($row = $result->fetch_assoc()) {
    $vendas[$row['dia']] = (float)$row['vendas'];
}

// Entradas: outros recebimentos
$queryOutros = "SELECT DATE(data_transacao) as dia, SUM(valor) as outros
                FROM transacoes_financeiras
                WHERE loja_id = ? AND tipo='entrada' AND categoria != 'venda'
                      AND data_transacao BETWEEN ? AND ?
                GROUP BY DATE(data_transacao)";
$stmt = $conn->prepare($queryOutros);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$outros = [];
while ($row = $result->fetch_assoc()) {
    $outros[$row['dia']] = (float)$row['outros'];
}

echo json_encode([
    'vendas' => $vendas,
    'outros' => $outros
]);
