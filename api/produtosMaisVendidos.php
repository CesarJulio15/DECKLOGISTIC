<?php
// Conectar ao banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/DECKLOGISTIC/conexao.php';

// Definir o período de interesse (exemplo: vendas de 2025)
$data_inicio = '2025-01-01';
$data_fim = '2025-12-31';

// Consulta para pegar os produtos mais vendidos da tabela itens_venda
$sql = "SELECT p.nome AS produto, 
               SUM(iv.quantidade) AS total_vendido
        FROM itens_venda iv
        JOIN produtos p ON p.id = iv.produto_id
        WHERE iv.data_venda BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_vendido DESC";  // Ordena os produtos do mais vendido para o menos vendido

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);  // Bind das datas
$stmt->execute();
$result = $stmt->get_result();

// Exibir os produtos mais vendidos
if ($result->num_rows > 0) {
    echo '<table border="1" cellpadding="10" cellspacing="0">';
    echo '<tr><th>Produto</th><th>Total Vendido</th></tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['produto']) . '</td>';
        echo '<td>' . $row['total_vendido'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Nenhum produto vendido encontrado para o período especificado.</p>';
}

// Fechar a conexão
$stmt->close();
$conn->close();
?>
