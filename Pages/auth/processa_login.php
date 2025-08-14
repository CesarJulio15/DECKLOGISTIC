<?php
session_start();
include '../../conexao.php'; 

if (isset($_POST['email']) && isset($_POST['senha'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT id, loja_id, nome, senha_hash FROM usuarios WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $usuario = mysqli_fetch_assoc($result);

        // Verifica a senha corretamente
        if (password_verify($senha, $usuario['senha_hash'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_loja_id'] = $usuario['loja_id'];

            header("Location: ../../index.php");
            exit;
        } else {
            $_SESSION['erro_login'] = "Senha incorreta.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['erro_login'] = "E-mail não cadastrado.";
        header("Location: login.php");
        exit;
    }
} else {
    $_SESSION['erro_login'] = "Preencha e-mail e senha.";
    header("Location: login.php");
    exit;
}
?>
