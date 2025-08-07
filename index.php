<?php
require_once 'config.php';
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
