<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DECKLOGISTIC</title>
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#000000ff">
  <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('Service Worker registrado', reg))
        .catch(err => console.error('Erro ao registrar SW', err));
    }
  </script>
</head>
<body>
  <script>
    // Redirecionamento conforme sessão
    <?php if (isset($_SESSION['usuario_id'])): ?>
        window.location.href = 'Pages/dashboard/tabelas/produtos.php';
    <?php else: ?>
        window.location.href = 'Pages/auth/lojas/cadastro.php';
    <?php endif; ?>
  </script>
  <p>Redirecionando...</p>
</body>
</html>
