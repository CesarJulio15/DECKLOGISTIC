<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Funcionário | DeckLogistic</title>
  <link rel="stylesheet" href="../../../assets/login.css">
  <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
</head>
<body>
<div class="container">
  <div class="left-side">
  </div>
  <div class="right-side">
    <img src="../../../img/logoDecklogistic.webp" alt="Logo" class="logo">
    <div class="form-container">
      <h1>Olá Novamente!</h1>

      <!-- Mostra erro, se existir -->
      <?php
      if (isset($_SESSION['erro_login'])) {
          echo '<div class="erro-msg">' . $_SESSION['erro_login'] . '</div>';
          unset($_SESSION['erro_login']);
      }
      ?>

      <form action="processa_login.php" method="POST">
        <input type="email" name="email" placeholder="Endereço de e-mail" required>
        <input type="password" name="senha" placeholder="Insira sua Senha" required>

        <div class="login-link">
          Ainda não tem uma conta para funcionário?
          <a href="cadastroFuncionario.php">Cadastrar</a>
        </div>
        <button type="submit" class="btn">Continuar</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
