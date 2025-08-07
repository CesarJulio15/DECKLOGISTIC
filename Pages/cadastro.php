<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | DeckLogistic</title>
  <link rel="stylesheet" href="../assets/login.css">
  <link rel="icon" href="../img/logoDecklogistic.webp" type="image/x-icon" />
</head>
<body>
  <div class="container">
    <div class="left-side">
      <!-- Aqui você insere sua imagem no CSS -->
    </div>
    <div class="right-side">
      <div class="form-container">
        <img src="../img/logoDecklogistic.webp" alt="Logo" class="logo">
        <h1>Bem-Vindo!</h1>
        <form action="login2etapa.php" method="POST">
          <input type="text" name="empresa" placeholder="Nome da Empresa" required>
          <input type="email" name="email" placeholder="Endereço de e-mail" required>
          <input type="password" name="senha" placeholder="Insira sua Senha" required>

          <div class="login-link">
            Já é registrado? <a href="#">Login</a>
          </div>
          <button type="submit" class="btn">Continuar</button>
        </form>
        <div class="divider">Ou</div>
        <button class="google-btn">
          <img src="../img/google.webp" alt="Google"> Continuar com o Google
        </button>
      </div>
    </div>
  </div>
</body>
</html>
