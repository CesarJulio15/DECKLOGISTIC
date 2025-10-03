<?php
session_start();
include __DIR__ . '/../../../conexao.php';
include __DIR__ . '/../../../header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($senha, $usuario['senha_hash'])) {
            // Padronização de sessão
            $_SESSION['usuario_id'] = $usuario['id'];   // id do usuário
            $_SESSION['nome']       = $usuario['nome']; // nome do usuário
            $_SESSION['email']      = $usuario['email'];
            $_SESSION['loja_id']    = $usuario['loja_id']; // id da loja que ele pertence
            $_SESSION['tipo_login'] = 'funcionario';      // tipo de login

            header("Location: ../../dashboard/estoque.php");
            exit;
        }
    }

    $_SESSION['erro_login'] = "E-mail ou senha inválidos.";
    header("Location: login.php");
    exit;
}
?>
