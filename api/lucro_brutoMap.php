<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php'; // Conexão PDO

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}

$lojaId = (int) $_SESSION['id'];

// Consulta lucro bruto por mês do ano atual
$stmt = $pdo->prepare("
    SELECT 
        MONTH(v.data_venda) AS mes,
        SUM(iv.preco_unitario * iv.quantidade - iv.custo_unitario * iv.quantidade) AS lucro_bruto
    FROM vendas v
    JOIN itens_venda iv ON iv.venda_id = v.id
    WHERE v.loja_id = ? AND YEAR(v.data_venda) = YEAR(CURDATE())
    GROUP BY MONTH(v.data_venda)
    ORDER BY MONTH(v.data_venda)
");
$stmt->execute([$lojaId]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preencher meses faltantes com 0
$meses = [];
$total = 0;
for ($i = 1; $i <= 12; $i++) {
    $encontrado = false;
    foreach ($result as $row) {
        if ((int)$row['mes'] === $i) {
            $valor = (float)$row['lucro_bruto'];
            $meses[] = ['mes' => $i, 'lucro_bruto' => $valor];
            $total += $valor;
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado) {
        $meses[] = ['mes' => $i, 'lucro_bruto' => 0];
    }
}

echo json_encode([
    "total" => $total,
    "series" => $meses,
    "lojaID" => $lojaId
]);
