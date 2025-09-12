<?php
session_start();
include '../../../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega dados do formulário
    $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
    $loja_id    = isset($_POST['loja_id']) ? intval($_POST['loja_id']) : 0;
    $nome       = trim($_POST['nome'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $tipo_login = trim($_POST['tipo_login'] ?? '');

    if ($usuario_id > 0 && $loja_id > 0 && $nome !== '' && $email !== '') {
        // Atualiza no banco
        $sql = "
            UPDATE lojas
            SET nome = ?, email = ?, tipo_login = ?
            WHERE id = ? AND usuario_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $nome, $email, $tipo_login, $loja_id, $usuario_id);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Loja atualizada com sucesso!";
        } else {
            $_SESSION['msg'] = "Erro ao atualizar: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['msg'] = "Preencha todos os campos obrigatórios!";
    }

    // Redireciona para a página inicial
    header("Location: ../../../index.php");
    exit;
}
