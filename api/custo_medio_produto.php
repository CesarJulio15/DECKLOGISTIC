<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../conexao.php';

// Aceita loja_id via GET ou SESSION
$lojaId = $_GET['loja_id'] ?? ($_SESSION['loja_id'] ?? 0);
if (!$lojaId) {
    echo json_encode([]);
    exit;
}

// Busca todos produtos da loja (inclui custo cadastrado)
$sqlProdutos = "SELECT id, nome, custo_unitario FROM produtos WHERE loja_id = ?";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->bind_param("i", $lojaId);
$stmtProdutos->execute();
$resProdutos = $stmtProdutos->get_result();

$data = [];
while ($produto = $resProdutos->fetch_assoc()) {
    $nome = $produto['nome'];
    $custo_medio = (float)$produto['custo_unitario'];
    $data[] = [
        'produto' => $nome,
        'custo_medio' => number_format($custo_medio, 2, '.', '') // força 2 casas decimais
    ];
}

$stmtProdutos->close();
$conn->close();

echo json_encode($data, JSON_PRETTY_PRINT);
