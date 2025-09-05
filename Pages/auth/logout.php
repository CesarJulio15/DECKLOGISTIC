<?php
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para a página de cadastro da loja
header("Location: /DECKLOGISTIC/Pages/auth/lojas/cadastro.php");
exit;
?>
