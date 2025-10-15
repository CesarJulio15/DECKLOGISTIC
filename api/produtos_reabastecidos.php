<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

// Usa loja_id do GET
$lojaId = $_GET['loja_id'] ?? 0;
if (!$lojaId) {
    echo json_encode([]);
    exit;
}

// Filtro de pesquisa por nome
$pesquisa = trim($_GET['pesquisa'] ?? '');
$wherePesquisa = '';
$params = [$lojaId];
$types = 'i';

if ($pesquisa !== '') {
    $wherePesquisa = " AND p.nome LIKE ?";
    $params[] = "%$pesquisa%";
    $types .= 's';
}

// Filtro de ordenação
$filtro = $_GET['filtro'] ?? '';
$orderBy = "ORDER BY m.data_movimentacao DESC";
switch ($filtro) {
    case 'categoria_asc': $orderBy = "ORDER BY categoria ASC"; break;
    case 'categoria_desc': $orderBy = "ORDER BY categoria DESC"; break;
    case 'nome_asc': $orderBy = "ORDER BY p.nome ASC"; break;
    case 'nome_desc': $orderBy = "ORDER BY p.nome DESC"; break;
    case 'data_recente': $orderBy = "ORDER BY m.data_movimentacao DESC"; break;
    case 'data_antiga': $orderBy = "ORDER BY m.data_movimentacao ASC"; break;
}

// Busca entradas recentes (reabastecimento)
$sql = "
    SELECT 
        p.nome, 
        COALESCE(t.nome, 'nenhuma') AS categoria,
        m.data_movimentacao
    FROM produtos p
    LEFT JOIN produto_tag pt ON pt.produto_id = p.id
    LEFT JOIN tags t ON pt.tag_id = t.id AND t.deletado_em IS NULL
    INNER JOIN movimentacoes_estoque m ON m.produto_id = p.id AND m.tipo = 'entrada'
    WHERE p.loja_id = ? AND p.deletado_em IS NULL
    $wherePesquisa
    $orderBy
    LIMIT 20
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while($row = $res->fetch_assoc()) {
    $row['data_reabastecimento'] = !empty($row['data_movimentacao']) 
        ? date('d/m/Y', strtotime($row['data_movimentacao'])) 
        : '-';
    $produtos[] = [
        'nome' => $row['nome'],
        'categoria' => $row['categoria'],
        'data_reabastecimento' => $row['data_reabastecimento']
    ];
}

$stmt->close();
echo json_encode($produtos);
