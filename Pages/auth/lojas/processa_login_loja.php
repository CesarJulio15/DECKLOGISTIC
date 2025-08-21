<?php
session_start();
include '../../../conexao.php';

if (!empty($_POST['email']) && !empty($_POST['senha'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Consulta apenas os campos necessários
    $stmt = $conn->prepare("
        SELECT id, nome, email, senha_hash 
        FROM lojas 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $loja = $result->fetch_assoc();

        // Verifica a senha
        if (password_verify($senha, $loja['senha_hash'])) {
            session_regenerate_id(true); // segurança extra

            // Guarda dados essenciais da sessão
            $_SESSION['id']    = $loja['id'];
            $_SESSION['nome']  = $loja['nome'];
            $_SESSION['email'] = $loja['email'];

            // Redireciona para o index
            header("Location: ../../../index.php");
            exit;
        } else {
            $_SESSION['erro_login'] = "Senha incorreta.";
            header("Location: loginLoja.php");
            exit;
        }
    } else {
        $_SESSION['erro_login'] = "E-mail não cadastrado.";
        header("Location: loginLoja.php");
        exit;
    }
} else {
    $_SESSION['erro_login'] = "Preencha e-mail e senha.";
    header("Location: loginLoja.php");
    exit;
}
?>
