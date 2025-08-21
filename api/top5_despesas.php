<?php
require_once '../conexao.php'; // Conexão $conn

$loja_id = $_GET['loja_id'] ?? 1;

$sql = "SELECT categoria, descricao, valor, data_transacao
        FROM transacoes_financeiras
        WHERE loja_id = ? AND tipo = 'saida'
        ORDER BY valor DESC, data_transacao DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$result = $stmt->get_result();

$despesas = [];
while($row = $result->fetch_assoc()){
    $despesas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($despesas);
