<?php
header('Content-Type: text/plain');
include '../../../conexao.php';

$produto_id = intval($_POST['produto_id'] ?? 0);
$tag_id = intval($_POST['tag_id'] ?? 0);

if (!$produto_id || !$tag_id) {
    echo "Erro: Dados inválidos";
    exit;
}

// Remove tags anteriores (se quiser apenas uma por produto)
$stmt = $conn->prepare("DELETE FROM produto_tag WHERE produto_id = ?");
$stmt->bind_param("i", $produto_id);
$stmt->execute();
$stmt->close();

// Vincula nova tag
$stmt = $conn->prepare("INSERT INTO produto_tag (produto_id, tag_id) VALUES (?, ?)");
if (!$stmt) {
    echo "Erro: " . $conn->error;
    exit;
}
$stmt->bind_param("ii", $produto_id, $tag_id);
if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Erro: " . $stmt->error;
}
$stmt->close();
?>
