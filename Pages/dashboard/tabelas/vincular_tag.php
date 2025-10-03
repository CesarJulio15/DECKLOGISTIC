<?php
include '../../../conexao.php';
include '../../../header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $tag_id = intval($_POST['tag_id'] ?? 0);

    if ($produto_id && $tag_id) {
        // Remove qualquer tag anterior para esse produto (uma tag por produto)
        $conn->query("DELETE FROM produto_tag WHERE produto_id = $produto_id");

        // Insere a nova tag
        $stmt = $conn->prepare("INSERT INTO produto_tag (produto_id, tag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $tag_id);

        if ($stmt->execute()) {
            echo "ok";
        } else {
            echo "erro: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "dados inválidos";
    }
} else {
    echo "método inválido";
}
?>
