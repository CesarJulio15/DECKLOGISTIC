<?php
header('Content-Type: application/json; charset=utf-8');
include '../conexao.php';
session_start();

// 1️⃣ Define loja_id da sessão ou GET
$loja_id = intval($_SESSION['loja_id'] ?? ($_GET['loja_id'] ?? 0));
if (!$loja_id) {
    echo json_encode(['series' => [], 'total' => 0]);
    exit;
}

// Salva na sessão para próximas chamadas
$_SESSION['loja_id'] = $loja_id;

// 2️⃣ Define últimos 6 meses
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $meses[$mes] = ['entrada' => 0, 'saida' => 0];
}

// 3️⃣ Busca movimentações dos produtos da loja nos últimos 6 meses
$inicio = date('Y-m-01', strtotime('-5 month'));
$hoje = date('Y-m-t');

$sql = "
    SELECT tipo, quantidade, DATE_FORMAT(data_movimentacao, '%Y-%m') AS mes
    FROM movimentacoes_estoque
    WHERE produto_id IN (SELECT id FROM produtos WHERE loja_id = ? AND deletado_em IS NULL)
      AND data_movimentacao BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $loja_id, $inicio, $hoje);
$stmt->execute();
$res = $stmt->get_result();

// 4️⃣ Soma entradas e saídas por mês
while ($row = $res->fetch_assoc()) {
    $mes = $row['mes'];
    if (!isset($meses[$mes])) continue;

    $tipo = strtolower(trim($row['tipo']));
    $qtd = intval($row['quantidade']);

    if (in_array($tipo, ['entrada', 'compra', 'reabastecimento'])) {
        $meses[$mes]['entrada'] += $qtd;
    } elseif (in_array($tipo, ['saida', 'venda', 'retirada'])) {
        $meses[$mes]['saida'] += $qtd;
    }
}
$stmt->close();

// 5️⃣ Busca estoque atual total da loja
$stmt = $conn->prepare("
    SELECT SUM(quantidade_estoque) AS total 
    FROM produtos 
    WHERE loja_id = ? AND deletado_em IS NULL
");
$stmt->bind_param("i", $loja_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$estoque_atual = intval($row['total'] ?? 0);

// 6️⃣ Calcula estoque mês a mês (reverso)
$series = [];
$meses_chaves = array_keys($meses);
$acumulado = $estoque_atual;

for ($i = count($meses_chaves) - 1; $i >= 0; $i--) {
    $mes = $meses_chaves[$i];
    $entrada = $meses[$mes]['entrada'];
    $saida = $meses[$mes]['saida'];

    // Se não houve movimentação no mês, estoque = 0
    $mes_estoque = ($entrada === 0 && $saida === 0) ? 0 : $acumulado;

    $series[$i] = [
        'mes' => date('M/Y', strtotime($mes . '-01')),
        'entrada' => $entrada,
        'saida' => $saida,
        'estoque' => $mes_estoque
    ];

    $acumulado = $acumulado - $entrada + $saida;
}


// 7️⃣ Ordena cronologicamente
$series = array_reverse($series);

// 8️⃣ Retorna JSON final
echo json_encode([
    'series' => $series,
    'total'  => $estoque_atual
], JSON_PRETTY_PRINT);
?>
