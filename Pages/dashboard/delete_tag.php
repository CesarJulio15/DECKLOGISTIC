<?php
header('Content-Type: application/json'); // FORÇA JSON
include __DIR__ . '/../../conexao.php';

$id = $_POST['id'] ?? 0;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM tags WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => $success]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'ID não fornecido']);
}
