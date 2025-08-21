<?php
session_start();
include __DIR__ . '/../conexao.php';

// Pega o id da loja logada na sessão
$lojaId = $_SESSION['id'] ?? 0;
if(!$lojaId) {
    die(json_encode([]));
}

/**
 * Cálculo do custo médio:
 * - Pega todas as movimentações de estoque do tipo 'entrada' para cada produto da loja.
 * - Soma (quantidade * preço unitário) / soma quantidade.
 * - Se não houver entradas, custo_medio = 0
 */
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.nome AS produto,
        IFNULL(SUM(m.quantidade * p.preco_unitario) / NULLIF(SUM(m.quantidade),0), 0) AS custo_medio
    FROM produtos p
    LEFT JOIN movimentacoes_estoque m ON m.produto_id = p.id AND m.tipo = 'entrada'
    WHERE p.loja_id = ?
    GROUP BY p.id
");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = [
        'produto' => $row['produto'],
        'custo_medio' => (float)$row['custo_medio']
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
