<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexao.php';

$lojaId = $_SESSION['loja_id'] ?? 0;
if ($lojaId <= 0) {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}

// --------- período ----------
$periodo = $_GET['periodo'] ?? 'mes'; // 'mes' | '30d' | 'ano'
$agruparPor = 'dia';
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
    $agruparPor = 'dia';
}

$iniStr = $inicio->format('Y-m-d');
$fimStr = $fim->format('Y-m-d');

// --------- Lucro bruto por período a partir de vendas ----------
if ($agruparPor === 'mes') {
    $sqlV = "
        SELECT DATE_FORMAT(v.data_venda, '%Y-%m') AS periodo,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(v.data_venda, '%Y-%m')
        ORDER BY periodo ASC
    ";
} else {
    $sqlV = "
        SELECT DATE(v.data_venda) AS periodo,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE(v.data_venda)
        ORDER BY periodo ASC
    ";
}

$stmtV = $conn->prepare($sqlV);
$stmtV->bind_param('iss', $lojaId, $iniStr, $fimStr);
$stmtV->execute();
$resV = $stmtV->get_result();

$lucroBrutoMap = [];
$total = 0.0;

while ($row = $resV->fetch_assoc()) {
    $lucroBrutoMap[$row['periodo']] = (float)($row['lucro_bruto'] ?? 0);
    $total += (float)($row['lucro_bruto'] ?? 0);
}
$stmtV->close();

// --------- preencher faltas ----------
$series = [];
if ($agruparPor === 'mes') {
    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limit  = new DateTime($fim->format('Y-m-01'));
    $limit->modify('last day of this month');
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m');
        $valor = $lucroBrutoMap[$key] ?? 0.0;
        $series[] = ["data" => $key, "valor" => $valor];
        $cursor->modify('first day of next month');
    }
} else {
    $cursor = new DateTime($iniStr);
    $limit  = new DateTime($fimStr);
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m-d');
        $valor = $lucroBrutoMap[$key] ?? 0.0;
        $series[] = ["data" => $key, "valor" => $valor];
        $cursor->modify('+1 day');
    }
}

echo json_encode([
    "total" => $total,
    "series" => $series,
    "periodo" => $periodo,
    "agrupamento" => $agruparPor
]);
?>
