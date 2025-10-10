<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['loja_id']) || !in_array($_SESSION['tipo_login'] ?? '', ['empresa', 'funcionario'])) {
    echo "Loja não autenticada";
    exit;
}

$lojaId = $_SESSION['loja_id'];
include $_SERVER['DOCUMENT_ROOT'] . '/DECKLOGISTIC/conexao.php';

// Buscar produtos ativos da loja
$sql = "SELECT preco_unitario, custo_unitario FROM produtos WHERE loja_id = ? AND deletado_em IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();

$total_margem = 0;
$qtd_produtos = 0;

while ($row = $result->fetch_assoc()) {
    $preco = (float)$row['preco_unitario'];
    $custo = (float)$row['custo_unitario'];

   // Para entender a margem de lucro relativa, queremos saber quanto do preço de venda é "lucro". Dividindo o lucro pelo preço de venda, estamos transformando esse valor em uma proporção do preço. O valor resultante nos diz qual a parte do preço de venda corresponde ao lucro.
    if ($preco > 0) {
        $margem = (($preco - $custo) / $preco) * 100;
        $total_margem += $margem;
        $qtd_produtos++;
    }
}



mysqli_close($conn);

if ($qtd_produtos > 0) {
    $margem_media = $total_margem / $qtd_produtos;
} else {
    $margem_media = 0;
}

// Exibe como valor único formatado
echo round($margem_media, 2) . "%";
?>
