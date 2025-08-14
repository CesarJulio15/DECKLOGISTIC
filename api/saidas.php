<?php
require_once '../conexao.php';

$loja_id = $_GET['loja_id'] ?? 1;
$periodo = $_GET['periodo'] ?? 'mes';

$data_inicio = date('Y-m-01');
$data_fim = date('Y-m-t');

// Custos fixos
$queryFixos = "SELECT DATE(data_transacao) as dia, SUM(valor) as fixos
               FROM transacoes_financeiras
               WHERE loja_id = ? AND tipo='despesa' AND categoria='fixo'
                     AND data_transacao BETWEEN ? AND ?
               GROUP BY DATE(data_transacao)";
$stmt = $conn->prepare($queryFixos);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$fixos = [];
while ($row = $result->fetch_assoc()) {
    $fixos[$row['dia']] = (float)$row['fixos'];
}

// Custos variáveis
$queryVariaveis = "SELECT DATE(data_transacao) as dia, SUM(valor) as variaveis
                   FROM transacoes_financeiras
                   WHERE loja_id = ? AND tipo='despesa' AND categoria='variavel'
                         AND data_transacao BETWEEN ? AND ?
                   GROUP BY DATE(data_transacao)";
$stmt = $conn->prepare($queryVariaveis);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$variaveis = [];
while ($row = $result->fetch_assoc()) {
    $variaveis[$row['dia']] = (float)$row['variaveis'];
}

// Custos imprevistos
$queryImprevistos = "SELECT DATE(data_transacao) as dia, SUM(valor) as imprevistos
                     FROM transacoes_financeiras
                     WHERE loja_id = ? AND tipo='despesa' AND categoria='imprevisto'
                           AND data_transacao BETWEEN ? AND ?
                     GROUP BY DATE(data_transacao)";
$stmt = $conn->prepare($queryImprevistos);
$stmt->bind_param("iss", $loja_id, $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$imprevistos = [];
while ($row = $result->fetch_assoc()) {
    $imprevistos[$row['dia']] = (float)$row['imprevistos'];
}

echo json_encode([
    'fixos' => $fixos,
    'variaveis' => $variaveis,
    'imprevistos' => $imprevistos
]);
