<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$lojaId = $_SESSION['usuario_id'] ?? 0;
if (!$lojaId) {
    echo json_encode([]);
    exit;
}

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

$sql = "SELECT lote, nome, data_reabastecimento 
        FROM produtos 
        WHERE loja_id = ? AND deletado_em IS NULL
        $orderBy 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $lojaId);
$stmt->execute();
$res = $stmt->get_result();

$produtos = [];
while($row = $res->fetch_assoc()) {
    $row['data_reabastecimento'] = !empty($row['data_reabastecimento']) 
        ? date('d/m/Y', strtotime($row['data_reabastecimento'])) 
        : '-';
    $produtos[] = $row;
}

$stmt->close();
echo json_encode($produtos);
