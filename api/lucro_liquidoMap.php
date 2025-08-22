<?php
// arquivo: api/lucro_liquido_array.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../conexao.php';

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}
$lojaId = (int) $_SESSION['id'];

// Período
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
} else {
    $inicio = new DateTime(date('Y-m-01'));
    $fim    = new DateTime(date('Y-m-t'));
    $agruparPor = 'dia';
}

$iniStr = $inicio->format('Y-m-d');
$fimStr = $fim->format('Y-m-d');

// Lucro bruto por vendas
if ($agruparPor === 'mes') {
    $sqlV = "
        SELECT DATE_FORMAT(data_venda, '%Y-%m') AS periodo,
               SUM(valor_total - custo_total) AS lucro_bruto
        FROM vendas
        WHERE loja_id = ? AND data_venda BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(data_venda, '%Y-%m')
    ";
} else {
    $sqlV = "
        SELECT DATE(data_venda) AS periodo,
               SUM(valor_total - custo_total) AS lucro_bruto
        FROM vendas
        WHERE loja_id = ? AND data_venda BETWEEN ? AND ?
        GROUP BY DATE(data_venda)
    ";
}
$stmtV = $conn->prepare($sqlV);
$stmtV->bind_param('iss', $lojaId, $iniStr, $fimStr);
$stmtV->execute();
$resV = $stmtV->get_result();
$lucroBrutoMap = [];
while ($row = $resV->fetch_assoc()) {
    $lucroBrutoMap[$row['periodo']] = (float)($row['lucro_bruto'] ?? 0);
}
$stmtV->close();

// Saldo financeiro
if ($agruparPor === 'mes') {
    $sqlF = "
        SELECT DATE_FORMAT(data_transacao, '%Y-%m') AS periodo,
               SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) - 
               SUM(CASE WHEN tipo='saida' THEN valor ELSE 0 END) AS saldo_fin
        FROM transacoes_financeiras
        WHERE loja_id = ? AND data_transacao BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(data_transacao, '%Y-%m')
    ";
} else {
    $sqlF = "
        SELECT DATE(data_transacao) AS periodo,
               SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) - 
               SUM(CASE WHEN tipo='saida' THEN valor ELSE 0 END) AS saldo_fin
        FROM transacoes_financeiras
        WHERE loja_id = ? AND data_transacao BETWEEN ? AND ?
        GROUP BY DATE(data_transacao)
    ";
}
$stmtF = $conn->prepare($sqlF);
$stmtF->bind_param('iss', $lojaId, $iniStr, $fimStr);
$stmtF->execute();
$resF = $stmtF->get_result();
$saldoFinMap = [];
while ($row = $resF->fetch_assoc()) {
    $saldoFinMap[$row['periodo']] = (float)($row['saldo_fin'] ?? 0);
}
$stmtF->close();

// Combinar e preencher datas faltantes
$series = [];
$total = 0.0;

if ($agruparPor === 'mes') {
    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limit  = new DateTime($fim->format('Y-m-01'));
    $limit->modify('last day of this month');
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m');
        $valor = ($lucroBrutoMap[$key] ?? 0) + ($saldoFinMap[$key] ?? 0);
        $series[] = ["data" => $key, "valor" => $valor];
        $total += $valor;
        $cursor->modify('first day of next month');
    }
} else {
    $cursor = clone $inicio;
    while ($cursor <= $fim) {
        $key = $cursor->format('Y-m-d');
        $valor = ($lucroBrutoMap[$key] ?? 0) + ($saldoFinMap[$key] ?? 0);
        $series[] = ["data" => $key, "valor" => $valor];
        $total += $valor;
        $cursor->modify('+1 day');
    }
}

echo json_encode([
    "total" => $total,
    "series" => $series,
    "periodo" => $periodo,
    "agrupamento" => $agruparPor
]);
