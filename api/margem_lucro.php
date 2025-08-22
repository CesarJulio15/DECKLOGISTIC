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
} else { // 'mes'
    $inicio = new DateTime(date('Y-m-01'));
    $fim    = new DateTime(date('Y-m-t'));
    $agruparPor = 'dia';
}

$iniStr = $inicio->format('Y-m-d');
$fimStr = $fim->format('Y-m-d');

// --------- receita e lucro bruto por período ----------
if ($agruparPor === 'mes') {
    $sql = "
        SELECT DATE_FORMAT(v.data_venda, '%Y-%m') AS periodo,
               SUM(v.valor_total) AS receita,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(v.data_venda, '%Y-%m')
        ORDER BY periodo ASC
    ";
} else {
    $sql = "
        SELECT DATE(v.data_venda) AS periodo,
               SUM(v.valor_total) AS receita,
               SUM(v.valor_total - v.custo_total) AS lucro_bruto
        FROM vendas v
        WHERE v.loja_id = ?
          AND v.data_venda BETWEEN ? AND ?
        GROUP BY DATE(v.data_venda)
        ORDER BY periodo ASC
    ";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $lojaId, $iniStr, $fimStr);
$stmt->execute();
$res = $stmt->get_result();

$mapRec = [];
$mapLuc = [];
$sumRec = 0.0;
$sumLuc = 0.0;

while ($row = $res->fetch_assoc()) {
    $periodoKey = $row['periodo'];
    $receita = (float) ($row['receita'] ?? 0);
    $lucro   = (float) ($row['lucro_bruto'] ?? 0);
    $mapRec[$periodoKey] = $receita;
    $mapLuc[$periodoKey] = $lucro;
    $sumRec += $receita;
    $sumLuc += $lucro;
}
$stmt->close();

// --------- preencher faltas e calcular margem ----------
$series = [];
if ($agruparPor === 'mes') {
    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limit  = new DateTime($fim->format('Y-m-01'));
    $limit->modify('last day of this month');
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m');
        $r = $mapRec[$key] ?? 0.0;
        $l = $mapLuc[$key] ?? 0.0;
        $margem = ($r > 0) ? ($l / $r * 100.0) : 0.0;
        $series[] = ["data" => $key, "valor" => $margem];
        $cursor->modify('first day of next month');
    }
} else {
    $cursor = new DateTime($iniStr);
    $limit  = new DateTime($fimStr);
    while ($cursor <= $limit) {
        $key = $cursor->format('Y-m-d');
        $r = $mapRec[$key] ?? 0.0;
        $l = $mapLuc[$key] ?? 0.0;
        $margem = ($r > 0) ? ($l / $r * 100.0) : 0.0;
        $series[] = ["data" => $key, "valor" => $margem];
        $cursor->modify('+1 day');
    }
}

// --------- margem total do período ----------
$margem_total = ($sumRec > 0) ? ($sumLuc / $sumRec * 100.0) : 0.0;

echo json_encode([
    "total" => $margem_total,      // média ponderada do período
    "series" => $series,
    "periodo" => $periodo,
    "agrupamento" => $agruparPor
]);
