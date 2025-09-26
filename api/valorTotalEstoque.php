<?php
// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifique a chave correta da sessão
if (!isset($_SESSION['loja_id']) || ($_SESSION['tipo_login'] ?? '') !== 'empresa') {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit; 
}

$lojaId = $_SESSION['loja_id'];

// Conectar ao banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/DECKLOGISTIC/conexao.php'; // Caminho absoluto para o arquivo de conexão

// Defina se quer calcular com o preço unitário ou custo unitário
$calcular_com_preco = true; // Defina como false se for calcular com custo unitário

// Consulta para obter todos os produtos no estoque da loja logada
$sql = "SELECT quantidade_estoque, preco_unitario, custo_unitario 
        FROM produtos 
        WHERE loja_id = ? AND deletado_em IS NULL";  // Filtro pela loja logada
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);  // Associar o ID da loja com a consulta
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}

// Variável para armazenar o valor total do estoque
$valor_total_estoque = 0;

// Iterar sobre os produtos e calcular o valor total do estoque
while ($row = $result->fetch_assoc()) {
    $quantidade = $row['quantidade_estoque'];
    $preco_unitario = $row['preco_unitario'];
    $custo_unitario = $row['custo_unitario'];

    // Calcular o valor total do estoque com base no preço ou custo unitário
    if ($calcular_com_preco) {
        $valor_total_estoque += $quantidade * $preco_unitario;
    } else {
        $valor_total_estoque += $quantidade * $custo_unitario;
    }
}

// Fechar a conexão com o banco de dados
mysqli_close($conn);

// Formatar o valor para exibição
$valor_formatado = "R$ " . number_format($valor_total_estoque, 2, ',', '.');

echo $valor_formatado;
?>
