<?php
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para a página de login (ajuste o caminho se necessário)
header("Location: /lojas/cadastro.php");
exit;
?>
