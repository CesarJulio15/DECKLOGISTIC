<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../conexao.php';

$lojaId = $_SESSION['id'] ?? 0; // usar a mesma chave de sessão da loja

if (!$lojaId) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT me.data_movimentacao,
            SUM(CASE WHEN me.tipo = 'entrada' THEN me.quantidade ELSE 0 END) AS entrada,
            SUM(CASE WHEN me.tipo = 'saida' THEN me.quantidade ELSE 0 END) AS saida
        FROM movimentacoes_estoque me
        JOIN produtos p ON me.produto_id = p.id
        WHERE p.loja_id = ?
          AND p.deletado_em IS NULL
        GROUP BY me.data_movimentacao
        ORDER BY me.data_movimentacao ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lojaId);
    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while($row = $result->fetch_assoc()) {
        $dados[] = [
            "data_movimentacao" => $row['data_movimentacao'],
            "entrada" => (int)$row['entrada'],
            "saida" => (int)$row['saida']
        ];
    }

    echo json_encode($dados);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
