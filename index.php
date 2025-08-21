<?php
require_once 'config.php';
session_start(); // inicia a sessão

if (!isset($_SESSION['id'])) {
    header('Location: pages/auth/lojas/cadastro.php'); 
    exit; 
}

if (isset($_SESSION['id'])) {
    header('Location: pages/dashboard/financas.php'); 
    exit; 
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
