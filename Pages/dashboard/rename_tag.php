<?php
include __DIR__ . '/../../conexao.php';
include __DIR__ . '/../../header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $novoNome = $_POST['nome'] ?? '';

    if ($id && $novoNome) {
        $stmt = $conn->prepare("UPDATE tags SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $novoNome, $id);
        if ($stmt->execute()) {
            echo json_encode(['ok' => true, 'msg' => 'Tag renomeada!']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Erro ao renomear.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Dados inválidos.']);
    }
}
?>
