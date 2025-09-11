<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$usuarioId = (int)($_GET['usuario_id'] ?? 0);
if (!$usuarioId) { 
    echo json_encode([]); 
    exit; 
}

$res = mysqli_query($conn, "
SELECT 
    p.id,
    p.nome,
    p.quantidade_estoque
FROM produtos p
JOIN movimentacoes_estoque m ON p.id = m.produto_id
WHERE m.usuario_id = 19
ORDER BY m.data_movimentacao DESC
LIMIT 1;

");

$dados = [];
while($row = mysqli_fetch_assoc($res)) {
    $dados[] = [
        'mes' => $row['mes'],
        'entrada' => (int)$row['entrada'],
        'saida' => (int)$row['saida']
    ];
}

$dados = array_reverse($dados);
echo json_encode($dados);
