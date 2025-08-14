<?php
require_once 'config.php';
session_start(); // inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/lojas/cadastro.php'); // redireciona para a página de login
    exit; // importante, para parar a execução
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="<?= ASSETS_PATH ?>/style.css">
</head>
<body>

  <?php require_once PARTIALS_PATH . '/sidebar.php'; ?>

  <main class="content">
    <h1>Bem-vindo ao Dashboard</h1>
    <p>Conteúdo principal aqui.</p>
  </main>

</body>
</html>
