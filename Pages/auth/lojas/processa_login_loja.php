<?php
session_start();
include '../../../conexao.php';
include __DIR__ . '/../../../header.php';


if (!empty($_POST['email']) && !empty($_POST['senha'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Prepara a consulta
    $stmt = $conn->prepare("
        SELECT id, nome, email, senha_hash 
        FROM lojas 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verifica se encontrou a loja
    if ($result && $result->num_rows === 1) {
        $loja = $result->fetch_assoc();

        // Confere senha
        if (password_verify($senha, $loja['senha_hash'])) {
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $loja['id'];
            $_SESSION['loja_id']    = $loja['id'];
            $_SESSION['tipo_login'] = 'empresa';  
            $_SESSION['nome']       = $loja['nome'];
            $_SESSION['email']      = $loja['email'];
            
            // Apenas loja por enquanto
            // $_SESSION['tipo_login'] = 'empresa'; 

            header("Location: ../../../direcionamento.php");
            exit;
        }
    }

    // Se chegou até aqui, deu erro (senha ou email inválido)
    $_SESSION['erro_login'] = "E-mail ou senha incorretos.";
    header("Location: loginLoja.php");
    exit;

} else {
    $_SESSION['erro_login'] = "Preencha todos os campos.";
    header("Location: loginLoja.php");
    exit;
}
