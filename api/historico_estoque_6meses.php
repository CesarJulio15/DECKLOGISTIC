<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$lojaId = (int)($_GET['loja_id'] ?? 0);
if (!$lojaId) { echo json_encode([]); exit; }

$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(m.data_movimentacao, '%Y-%m') AS mes,
           SUM(CASE WHEN m.tipo='entrada' THEN m.quantidade ELSE 0 END) AS entrada,
           SUM(CASE WHEN m.tipo='saida' THEN m.quantidade ELSE 0 END) AS saida
    FROM movimentacoes_estoque m
    INNER JOIN produtos p ON p.id = m.produto_id
    WHERE p.loja_id = $lojaId
    GROUP BY mes
    ORDER BY mes DESC
    LIMIT 6
");

$dados = [];
while($row = mysqli_fetch_assoc($res)) {
    $dados[] = [
        'mes' => $row['mes'],
        'entrada' => (int)$row['entrada'],
        'saida' => (int)$row['saida']
    ];
}

// Ordem cronológica
$dados = array_reverse($dados);

echo json_encode($dados);
