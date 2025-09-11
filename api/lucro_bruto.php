<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Ajuste este caminho se necessário
require_once __DIR__ . '/../conexao.php';

$lojaId = $_SESSION['loja_id'] ?? 0;
if ($lojaId == 0) {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}

// --------- período ----------
$periodo = $_GET['periodo'] ?? 'mes'; // 'mes' | '30d' | 'ano'
$agruparPor = 'dia'; // dia ou mes
$inicio = $fim = null;

$hoje = new DateTime('today');

if ($periodo === '30d') {
    $fim = clone $hoje;
    $inicio = (clone $hoje)->modify('-29 days');
    $agruparPor = 'dia';
} elseif ($periodo === 'ano') {
    $inicio = new DateTime(date('Y-01-01'));
    $fim    = new DateTime(date('Y-12-31'));
    $agruparPor = 'mes';
} else { // 'mes' padrão
    $inicio = new DateTime(date('Y-m-01'));
    $fim    = new DateTime(date('Y-m-t'));
   
}

// --------- consulta vendas: lucro bruto = valor_total - custo_total ----------
if ($agruparPor === 'mes') {
    $sql = "
        SELECT DATE_FORMAT(v.data_venda, '%Y-%m') AS periodo,
               SUM(iv.preco_unitario * iv.quantidade - iv.custo_unitario * iv.quantidade) AS lucro_bruto
        FROM vendas v
        JOIN itens_venda iv ON iv.venda_id = v.id
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(v.data_venda, '%Y-%m')
        ORDER BY periodo ASC
    ";
} else { // dia
    $sql = "
        SELECT DATE(v.data_venda) AS periodo,
               SUM(iv.preco_unitario * iv.quantidade - iv.custo_unitario * iv.quantidade) AS lucro_bruto
        FROM vendas v
        JOIN itens_venda iv ON iv.venda_id = v.id
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE(v.data_venda)
        ORDER BY periodo ASC
    ";
}

$stmt = $conn->prepare($sql);
$iniStr = $inicio->format('Y-m-d');
$fimStr = $fim->format('Y-m-d');
$stmt->bind_param('iss', $lojaId, $iniStr, $fimStr);
$stmt->execute();
$res = $stmt->get_result();

$map = [];
$total = 0.0;
while ($row = $res->fetch_assoc()) {
    $valor = (float) ($row['lucro_bruto'] ?? 0);
    $map[$row['periodo']] = $valor;
    $total += $valor;
}
$stmt->close();

// --------- preencher datas faltantes ----------
$series = [];
if ($agruparPor === 'mes') {
    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limit  = new DateTime($fim->format('Y-m-01'));
    $limit->modify('last day of this month');
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m');
        $series[] = ["data" => $key, "valor" => (float)($map[$key] ?? 0)];
        $cursor->modify('first day of next month');
    }
} else {
    $cursor = clone $inicio;
    while ($cursor <= $fim) {
        $key = $cursor->format('Y-m-d');
        $series[] = ["data" => $key, "valor" => (float)($map[$key] ?? 0)];
        $cursor->modify('+1 day');
    }
}

echo json_encode([
    "total" => $total,
    "series" => $series,
    "periodo" => $periodo,
    "agrupamento" => $agruparPor,
    "lojaID" => $lojaId
]);
