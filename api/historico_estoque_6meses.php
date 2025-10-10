<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$lojaId = (int)($_GET['loja_id'] ?? 0);
if (!$lojaId) {
    echo json_encode(['series' => [], 'total' => 0]);
    exit;
}

// 1. Pega estoque inicial cadastrado dos produtos
$res = $conn->query("SELECT SUM(quantidade_inicial) AS inicial FROM produtos WHERE loja_id = $lojaId AND deletado_em IS NULL");
$row = $res ? $res->fetch_assoc() : null;
$estoque_inicial = (int)($row['inicial'] ?? 0);

// 2. Define últimos 6 meses
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $meses[$mes] = ['entrada' => 0, 'saida' => 0];
}

// 3. Busca todas movimentações da loja nos últimos 6 meses
$inicio = date('Y-m-01', strtotime('-5 month'));
$hoje = date('Y-m-t');

$sql = "
    SELECT tipo, quantidade, DATE_FORMAT(data_movimentacao, '%Y-%m') AS mes
    FROM movimentacoes_estoque
    WHERE produto_id IN (SELECT id FROM produtos WHERE loja_id = $lojaId AND deletado_em IS NULL)
      AND data_movimentacao BETWEEN '$inicio' AND '$hoje'
";
$res = $conn->query($sql);

// 4. Soma entradas e saídas por mês
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $mes = $row['mes'];
        if (!isset($meses[$mes])) continue;
        if ($row['tipo'] === 'entrada') {
            $meses[$mes]['entrada'] += (int)$row['quantidade'];
        } elseif ($row['tipo'] === 'saida') {
            $meses[$mes]['saida'] += (int)$row['quantidade'];
        }
    }
}

// 5. Calcula estoque acumulado mês a mês corretamente
$series = [];
$acumulado = $estoque_inicial;
foreach ($meses as $mes => $dados) {
    $acumulado += $dados['entrada'] - $dados['saida'];
    $series[] = [
        'mes' => date('M/Y', strtotime($mes . '-01')),
        'entrada' => $dados['entrada'],
        'saida' => $dados['saida'],
        'estoque' => $acumulado
    ];
}

// 6. Estoque total atual (somatório quantidade_estoque)
$res = $conn->query("SELECT SUM(quantidade_estoque) AS total FROM produtos WHERE loja_id = $lojaId AND deletado_em IS NULL");
$row = $res ? $res->fetch_assoc() : null;
$total_atual = (int)($row['total'] ?? 0);

// 7. Retorna JSON
echo json_encode(['series' => $series, 'total' => $total_atual], JSON_PRETTY_PRINT);
?>
