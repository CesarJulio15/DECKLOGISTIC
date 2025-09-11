<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexao.php';

$usuarioId = (int)($_GET['usuario_id'] ?? 0);
if (!$usuarioId) {
    echo json_encode([]);
    exit;
}

// Pega todos os produtos que o usuário pode movimentar
$produtos = [];
$res = $conn->query("
    SELECT id, quantidade_inicial 
    FROM produtos 
");
while ($row = $res->fetch_assoc()) {
    $produtos[$row['id']] = (int)$row['quantidade_inicial'];
}

// Define os últimos 6 meses
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $meses[$mes] = ['entrada' => 0, 'saida' => 0, 'estoque' => 0];
}

// Pega todas as movimentações do usuário nos últimos 6 meses
$inicio = date('Y-m-01', strtotime('-5 month'));
$hoje = date('Y-m-t'); // último dia do mês atual

$sql = "
    SELECT produto_id, tipo, quantidade, DATE_FORMAT(data_movimentacao, '%Y-%m') AS mes
    FROM movimentacoes_estoque
    WHERE usuario_id = $usuarioId
      AND data_movimentacao BETWEEN '$inicio' AND '$hoje'
";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $mes = $row['mes'];
    if (!isset($meses[$mes])) continue;

    if ($row['tipo'] === 'entrada') {
        $meses[$mes]['entrada'] += (int)$row['quantidade'];
    } else {
        $meses[$mes]['saida'] += (int)$row['quantidade'];
    }
}

// Calcula estoque acumulado mês a mês
$acumulado = array_sum($produtos); // soma estoque inicial de todos produtos
foreach ($meses as $mes => &$dados) {
    $acumulado += $dados['entrada'] - $dados['saida'];
    $dados['estoque'] = $acumulado;
}
unset($dados);

echo json_encode($meses, JSON_PRETTY_PRINT);
