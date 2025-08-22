<?php
require_once '../conexao.php';

// Falta/Excesso
$res1 = mysqli_query($conn, "SELECT nome, quantidade_estoque FROM produtos");
$labels1 = []; $dados1 = [];
while($row = mysqli_fetch_assoc($res1)){
    $labels1[] = $row['nome'];
    $dados1[] = (int)$row['quantidade_estoque'];
}

// Estoque parado > 12 dias
$res2 = mysqli_query($conn, "
SELECT p.nome, DATEDIFF(CURDATE(), m.ultima_movimentacao) AS dias
FROM produtos p
LEFT JOIN (
    SELECT produto_id, MAX(data_movimentacao) AS ultima_movimentacao
    FROM movimentacoes_estoque
    GROUP BY produto_id
) m ON p.id = m.produto_id
WHERE DATEDIFF(CURDATE(), m.ultima_movimentacao) > 12
");
$labels2 = []; $dados2 = [];
while($row = mysqli_fetch_assoc($res2)){
    $labels2[] = $row['nome'];
    $dados2[] = (int)$row['dias'];
}

// Entrada/Saída
$res3 = mysqli_query($conn, "
SELECT p.nome, m.data_movimentacao, SUM(m.quantidade) AS total
FROM produtos p
LEFT JOIN movimentacoes_estoque m ON p.id = m.produto_id
GROUP BY p.nome, m.data_movimentacao
ORDER BY m.data_movimentacao ASC
");
$labels3 = []; $entrada = []; $saida = [];
while($row = mysqli_fetch_assoc($res3)){
    $data = $row['data_movimentacao'] ?? null;
    $labels3[] = ($data && strtotime($data)!==false) ? date('d/m/Y', strtotime($data)) : '-';
    if($row['total'] >= 0){
        $entrada[] = (int)$row['total'];
        $saida[] = 0;
    } else {
        $entrada[] = 0;
        $saida[] = abs((int)$row['total']);
    }
}

echo json_encode([
    'falta_excesso' => ['labels'=>$labels1,'dados'=>$dados1],
    'estoque_parado'=> ['labels'=>$labels2,'dados'=>$dados2],
    'entrada_saida' => ['labels'=>$labels3,'entrada'=>$entrada,'saida'=>$saida]
]);
?>
