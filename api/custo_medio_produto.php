<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../conexao.php';

// Pega o ID da loja da sessão
$lojaId = $_SESSION['loja_id'] ?? 0;
if (!$lojaId) {
    echo json_encode([]);
    exit;
}

// Cálculo do custo médio real: soma todas entradas (tipo='entrada') do produto, usando o custo_unitario de cada movimentação
$sql = "
    SELECT 
        p.id,
        p.nome AS produto,
        SUM(m.quantidade) AS total_entrada,
        SUM(m.quantidade * m.custo_unitario) AS custo_total
    FROM produtos p
    LEFT JOIN movimentacoes_estoque m 
        ON m.produto_id = p.id AND m.tipo = 'entrada'
    WHERE p.loja_id = ?
    GROUP BY p.id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $total_entrada = (float)$row['total_entrada'];
    $custo_total = (float)$row['custo_total'];
    // Se não houver entradas, mostra o custo cadastrado do produto
    if ($total_entrada > 0) {
        $custo_medio = $custo_total / $total_entrada;
    } else {
        // Busca custo cadastrado
        $custo_medio = 0;
        $stmtCusto = $conn->prepare("SELECT custo_unitario FROM produtos WHERE id=?");
        $stmtCusto->bind_param("i", $row['id']);
        $stmtCusto->execute();
        $resCusto = $stmtCusto->get_result()->fetch_assoc();
        if ($resCusto) $custo_medio = (float)$resCusto['custo_unitario'];
        $stmtCusto->close();
    }
    $data[] = [
        'produto' => $row['produto'],
        'custo_medio' => round($custo_medio, 2)
    ];
}

$stmt->close();
$conn->close();

echo json_encode($data, JSON_PRETTY_PRINT);
