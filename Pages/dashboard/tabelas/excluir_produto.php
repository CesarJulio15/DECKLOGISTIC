<?php
include '../../../conexao.php';

$produto_id = intval($_POST['produto_id'] ?? 0);

if ($produto_id) {
    // Deleta dependências primeiro
    $conn->query("DELETE FROM itens_venda WHERE produto_id = $produto_id");
    $conn->query("DELETE FROM produto_tag WHERE produto_id = $produto_id");
    $conn->query("DELETE FROM movimentacoes_estoque WHERE produto_id = $produto_id");
    
    // Deleta o produto
    if ($conn->query("DELETE FROM produtos WHERE id = $produto_id")) {
        echo "ok";
    } else {
        echo "erro";
    }
} else {
    echo "erro";
}
?>
