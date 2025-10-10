<?php
header('Content-Type: application/json; charset=utf-8');
include '../conexao.php';

$loja_id = intval($_GET['loja_id'] ?? 0);
if (!$loja_id) {
    echo json_encode(['series' => []]);
    exit;
}

// 1. Define últimos 6 meses
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $meses[$mes] = ['entrada' => 0, 'saida' => 0];
}

// 2. Busca movimentações dos produtos da loja nos últimos 6 meses
$inicio = date('Y-m-01', strtotime('-5 month'));
$hoje = date('Y-m-t');
$sql = "
    SELECT tipo, quantidade, DATE_FORMAT(data_movimentacao, '%Y-%m') AS mes
    FROM movimentacoes_estoque
    WHERE produto_id IN (SELECT id FROM produtos WHERE loja_id = $loja_id AND deletado_em IS NULL)
      AND data_movimentacao BETWEEN '$inicio' AND '$hoje'
";
$res = $conn->query($sql);

// 3. Soma entradas e saídas por mês
while ($row = $res->fetch_assoc()) {
    $mes = $row['mes'];
    if (!isset($meses[$mes])) continue;
    if ($row['tipo'] === 'entrada') {
        $meses[$mes]['entrada'] += intval($row['quantidade']);
    } else {
        $meses[$mes]['saida'] += intval($row['quantidade']);
    }
}

// 4. Busca o estoque inicial ANTES do primeiro mês
$stmt = $conn->prepare("SELECT SUM(quantidade_estoque) AS total FROM produtos WHERE loja_id = ? AND deletado_em IS NULL");
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$estoque_atual = intval($row['total'] ?? 0);

// 5. Calcula estoque mês a mês (do passado para o presente)
$series = [];
$meses_chaves = array_keys($meses);
$acumulado = $estoque_atual;

// Para cada mês, do mais recente para o mais antigo, desfaz as movimentações
for ($i = count($meses_chaves) - 1; $i >= 0; $i--) {
    $mes = $meses_chaves[$i];
    $entrada = $meses[$mes]['entrada'];
    $saida = $meses[$mes]['saida'];

    // Para o mês atual, mostra o estoque atual
    // Para meses anteriores, desfaz movimentações para mostrar o saldo correto
    $series[$i] = [
        'mes' => date('M/Y', strtotime($mes . '-01')),
        'entrada' => $entrada,
        'saida' => $saida,
        'estoque' => $acumulado
    ];

    // Para o mês anterior, desfaz as movimentações deste mês
    $acumulado = $acumulado - $entrada + $saida;
}

// Inverte para ordem cronológica
$series = array_reverse($series);

echo json_encode(['series' => $series, 'total' => $estoque_atual], JSON_PRETTY_PRINT);
?>
