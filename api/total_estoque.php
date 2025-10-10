<?php
header('Content-Type: application/json');
include '../conexao.php';

$loja_id = intval($_GET['loja_id'] ?? 0);
$series = [];

// Busca o saldo atual do estoque
$stmt = $conn->prepare("SELECT SUM(quantidade_estoque) as total FROM produtos WHERE loja_id = ? AND deletado_em IS NULL");
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$saldo_atual = intval($row['total'] ?? 0);

// Preenche os últimos 6 meses com o saldo atual (até que você tenha snapshots mensais)
for ($i = 5; $i >= 0; $i--) {
    $mes = date('M/Y', strtotime("-$i month"));
    $series[] = [
        'mes' => $mes,
        'total' => $saldo_atual
    ];
}

echo json_encode(['series' => $series, 'total' => $saldo_atual]);
?>
