<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$lojaId = $_SESSION['usuario_id'] ?? 0;

if (!$lojaId) {
    echo json_encode([]);
    exit;
}

// Puxar produtos em falta (quantidade_estoque <= 0)
$sql = "SELECT nome, quantidade_estoque 
        FROM produtos 
        WHERE loja_id = ? AND quantidade_estoque <= 0
        ORDER BY nome ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while ($row = $res->fetch_assoc()) {
    $produtos[] = [
        'nome' => $row['nome'],
        'quantidade_estoque' => (int)$row['quantidade_estoque']
    ];
}

$stmt->close();
echo json_encode($produtos);
