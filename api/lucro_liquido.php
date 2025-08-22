<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Ajuste este caminho se necessário
require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}
$lojaId = (int) $_SESSION['id'];

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

// --------- (A) Lucro bruto por período a partir de vendas ----------
if ($agruparPor === 'mes') {
    $sqlV = "
        SELECT DATE_FORMAT(v.data_venda, '%Y-%m') AS periodo,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(v.data_venda, '%Y-%m')
    ";
} else {
    $sqlV = "
        SELECT DATE(v.data_venda) AS periodo,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE(v.data_venda)
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

// --------- (B) Saldo financeiro (entradas - saídas) por período ----------
if ($agruparPor === 'mes') {
    $sqlF = "
        SELECT DATE_FORMAT(t.data_transacao, '%Y-%m') AS periodo,
               SUM(CASE WHEN t.tipo = 'entrada' THEN t.valor ELSE 0 END) -
               SUM(CASE WHEN t.tipo = 'saida'   THEN t.valor ELSE 0 END) AS saldo_fin
        FROM transacoes_financeiras t
        WHERE t.loja_id = ?
          AND t.data_transacao BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.data_transacao, '%Y-%m')
    ";
} else {
    $sqlF = "
        SELECT DATE(t.data_transacao) AS periodo,
               SUM(CASE WHEN t.tipo = 'entrada' THEN t.valor ELSE 0 END) -
               SUM(CASE WHEN t.tipo = 'saida'   THEN t.valor ELSE 0 END) AS saldo_fin
        FROM transacoes_financeiras t
        WHERE t.loja_id = ?
          AND t.data_transacao BETWEEN ? AND ?
        GROUP BY DATE(t.data_transacao)
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

// --------- combinar, preencher faltas e somar ----------
$series = [];
$total = 0.0;

if ($agruparPor === 'mes') {
    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limit  = new DateTime($fim->format('Y-m-01'));
    $limit->modify('last day of this month');
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m');
        $lucroBruto = $lucroBrutoMap[$key] ?? 0.0;
        $saldoFin   = $saldoFinMap[$key] ?? 0.0;
        $valor = $lucroBruto + $saldoFin;
        $series[] = ["data" => $key, "valor" => $valor];
        $total += $valor;
        $cursor->modify('first day of next month');
    }
} else {
    $cursor = new DateTime($iniStr);
    $limit  = new DateTime($fimStr);
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m-d');
        $lucroBruto = $lucroBrutoMap[$key] ?? 0.0;
        $saldoFin   = $saldoFinMap[$key] ?? 0.0;
        $valor = $lucroBruto + $saldoFin;
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
