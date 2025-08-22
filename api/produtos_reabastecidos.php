<?php
require_once '../conexao.php';

$filtro = $_GET['filtro'] ?? '';
$orderBy = "ORDER BY data_reabastecimento DESC";

switch ($filtro) {
    case 'preco_asc': $orderBy = "ORDER BY preco_unitario ASC"; break;
    case 'preco_desc': $orderBy = "ORDER BY preco_unitario DESC"; break;
    case 'quantidade_asc': $orderBy = "ORDER BY quantidade_estoque ASC"; break;
    case 'quantidade_desc': $orderBy = "ORDER BY quantidade_estoque DESC"; break;
    case 'data_recente': $orderBy = "ORDER BY data_reabastecimento DESC"; break;
    case 'data_antiga': $orderBy = "ORDER BY data_reabastecimento ASC"; break;
}

$res = mysqli_query($conn, "SELECT lote, nome, data_reabastecimento FROM produtos $orderBy LIMIT 10");
$produtos = [];
while($row = mysqli_fetch_assoc($res)) {
    $row['data_reabastecimento'] = !empty($row['data_reabastecimento']) ? date('d/m/Y', strtotime($row['data_reabastecimento'])) : '-';
    $produtos[] = $row;
}

echo json_encode($produtos);
?>
