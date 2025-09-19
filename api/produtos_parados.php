<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../conexao.php';

// Pega o ID da loja corretamente para empresa ou funcionário
$lojaId = isset($_SESSION['tipo_login']) && $_SESSION['tipo_login'] === 'empresa'
    ? ($_SESSION['usuario_id'] ?? 0)
    : ($_SESSION['loja_id'] ?? 0);

if (!$lojaId) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT p.id, p.nome, p.quantidade_estoque, MAX(m.data_movimentacao) AS ultima_movimentacao
            FROM produtos p
            LEFT JOIN movimentacoes_estoque m ON m.produto_id = p.id
            WHERE p.loja_id = ?
              AND p.deletado_em IS NULL
            GROUP BY p.id
            HAVING ultima_movimentacao IS NULL OR ultima_movimentacao < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ultima_movimentacao ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lojaId);
    $stmt->execute();
    $result = $stmt->get_result();

    $produtos = [];
    while($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }

    echo json_encode($produtos);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
