<?php
// Inicia sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todos os dados da sessão
$_SESSION = [];
// Remove sessão do servidor
session_unset();
session_destroy();

// Apaga cookie de sessão no cliente (usa parâmetros atuais para garantir remoção)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Cabeçalhos anti-cache (assegura que logout não fique no cache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");

// Redireciona
header("Location: /DECKLOGISTIC/Pages/auth/lojas/cadastro.php");
exit;
?>
