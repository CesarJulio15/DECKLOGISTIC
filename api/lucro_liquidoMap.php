<?php
// arquivo: api/lucro_liquido_array.php
require_once '../config.php';

$loja_id = $_GET['loja_id'] ?? 1;

// Query que retorna o lucro líquido por mês
$stmt = $pdo->prepare("
    SELECT 
        MONTH(data_venda) AS mes, 
        SUM(valor_total - custo_total) AS lucro_liquido
    FROM vendas
    WHERE loja_id = ? AND YEAR(data_venda) = YEAR(CURDATE())
    GROUP BY MONTH(data_venda)
    ORDER BY MONTH(data_venda)
");
$stmt->execute([$loja_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preencher meses faltantes com 0
$meses = [];
for ($i = 1; $i <= 12; $i++) {
    $mesEncontrado = false;
    foreach ($result as $row) {
        if ((int)$row['mes'] === $i) {
            $meses[] = [
                'mes' => $i,
                'lucro_liquido' => (float)$row['lucro_liquido']
            ];
            $mesEncontrado = true;
            break;
        }
    }
    if (!$mesEncontrado) {
        $meses[] = [
            'mes' => $i,
            'lucro_liquido' => 0
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($meses);
