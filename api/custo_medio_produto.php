<?php
session_start();
include __DIR__ . '/../conexao.php';

// Pega o id da loja logada na sessão
$lojaId = $_SESSION['loja_id'] ?? 0;
if(!$lojaId) {
    die(json_encode([]));
}

/**
 * Cálculo do custo médio:
 * - Pega todas as entradas (tipo = 'entrada') de cada produto da loja.
 * - Soma quantidade * custo_unitario do produto no momento da compra.
 * - Divide pelo total de quantidade.
 * - Se não houver entradas, custo_medio = 0
 */
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.nome AS produto,
        IFNULL(SUM(m.quantidade * p.custo_unitario) / NULLIF(SUM(m.quantidade),0), 0) AS custo_medio
    FROM produtos p
    LEFT JOIN movimentacoes_estoque m 
        ON m.produto_id = p.id AND m.tipo = 'entrada'
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
        'custo_medio' => round((float)$row['custo_medio'], 2)
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
