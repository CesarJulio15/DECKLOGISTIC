<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ----- Configurações seguras de cookie de sessão -----
// ATENÇÃO: execute ANTES de session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'); // true se HTTPS
session_set_cookie_params([
    'lifetime' => 0,          // cookie de sessão até fechar o navegador (ajuste se desejar)
    'path' => '/',
    'domain' => '',          // deixe vazio para o host atual, ou defina explicitamente
    'secure' => $secure,     // true em produção com HTTPS
    'httponly' => true,      // evita acesso via JS
    'samesite' => 'Lax'      // 'Lax' é um bom equilíbrio; troque para 'Strict' se desejar
]);

// Inicia sessão apenas se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../../conexao.php';

// Função utilitária simples para redirecionar com segurança
function redirect($url) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: $url");
    exit;
}

if (!empty($_POST['email']) && !empty($_POST['senha'])) {
    // Sanitiza email
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'];

    if (!$email) {
        $_SESSION['erro_login'] = "E-mail inválido.";
        redirect("loginLoja.php");
    }

    // Prepara a consulta (prepared statement já previne SQL injection)
    $stmt = $conn->prepare("
        SELECT id, nome, email, senha_hash 
        FROM lojas 
        WHERE email = ? 
        LIMIT 1
    ");
    if ($stmt === false) {
        // Em produção você pode logar o erro em arquivo em vez de exibir
        $_SESSION['erro_login'] = "Erro ao processar a requisição.";
        redirect("loginLoja.php");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verifica se encontrou a loja
    if ($result && $result->num_rows === 1) {
        $loja = $result->fetch_assoc();

        // Confere senha (usando password_verify é seguro)
        if (password_verify($senha, $loja['senha_hash'])) {
            // Regenera o id da sessão para evitar session fixation
            session_regenerate_id(true);

            // Define variáveis de sessão
            $_SESSION['usuario_id'] = (int)$loja['id'];
            $_SESSION['loja_id']    = (int)$loja['id'];
            $_SESSION['tipo_login'] = 'empresa';  
            $_SESSION['nome']       = $loja['nome'];
            $_SESSION['email']      = $loja['email'];

            // Cabeçalhos anti-cache antes do redirecionamento final
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Redireciona para página de direcionamento / dashboard
            redirect("../../../direcionamento.php");
        }
    }

    // Se chegou até aqui, erro de credencial
    $_SESSION['erro_login'] = "E-mail ou senha incorretos.";
    redirect("loginLoja.php");

} else {
    $_SESSION['erro_login'] = "Preencha todos os campos.";
    redirect("loginLoja.php");
}
