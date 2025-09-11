<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../conexao.php';

$lojaId = $_SESSION['usuario_id'] ?? 0;

try {
    $sql = "
        SELECT data_movimentacao,
            SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entrada,
            SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saida
        FROM movimentacoes_estoque me
        JOIN produtos p ON me.produto_id = p.id
        WHERE p.loja_id = ?
        GROUP BY data_movimentacao
        ORDER BY data_movimentacao ASC
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
            "saida" => (int)$row['saida'],
            "id" => $lojaId
        ];
    }

    echo json_encode($dados);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
