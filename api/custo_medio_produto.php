<?php
require_once '../conexao.php';

$loja_id = $_GET['loja_id'] ?? 1;

$sql = "
    SELECT 
        p.id AS produto_id,
        p.nome AS produto_nome,
        SUM(iv.custo_unitario * iv.quantidade) / SUM(iv.quantidade) AS custo_medio
    FROM itens_venda iv
    JOIN produtos p ON iv.produto_id = p.id
    JOIN vendas v ON iv.venda_id = v.id
    WHERE v.loja_id = ?
    GROUP BY p.id, p.nome
    ORDER BY custo_medio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$result = $stmt->get_result();

$produtos = [];
while($row = $result->fetch_assoc()){
    $row['custo_medio'] = number_format($row['custo_medio'], 2, '.', '');
    $produtos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($produtos);
