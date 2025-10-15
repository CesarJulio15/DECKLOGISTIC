<?php
session_start();
include '../../../conexao.php';

$produto_id = intval($_POST['produto_id'] ?? 0);
$tag_id = intval($_POST['tag_id'] ?? 0);

if (!$produto_id || !$tag_id) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Verifica se já existe vínculo
$stmt = $conn->prepare("SELECT 1 FROM produto_tag WHERE produto_id=? AND tag_id=?");
$stmt->bind_param("ii", $produto_id, $tag_id);
$stmt->execute();
$existe = $stmt->get_result()->fetch_row();
$stmt->close();

if ($existe) {
    // Se já existe, desvincula (remove)
    $stmt = $conn->prepare("DELETE FROM produto_tag WHERE produto_id=? AND tag_id=?");
    $stmt->bind_param("ii", $produto_id, $tag_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => 'removido']);
} else {
    // Se não existe, vincula (adiciona)
    $stmt = $conn->prepare("INSERT INTO produto_tag (produto_id, tag_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $produto_id, $tag_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => 'adicionado']);
}
