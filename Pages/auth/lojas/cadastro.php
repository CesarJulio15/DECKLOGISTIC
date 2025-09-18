<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação mínima
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if ($nome && $email && $senha && $senha === $senha2) {
        // Armazena os dados na sessão
        $_SESSION['cadastro'] = [
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT) // hash da senha
        ];
        // Redireciona para a página completa
        header('Location: cadastroEmpresaCompleto.php');
        exit;
    } else {
        $erro = "Preencha todos os campos corretamente.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | DeckLogistic</title>
  <link rel="stylesheet" href="../../../assets/cadastro.css">
  <link rel="icon" href="../img/logoDecklogistic.webp" type="image/x-icon" />
</head>
<body>
  <div class="container">
    <div class="left-side">
      
    </div>
    <div class="right-side">
      <div class="form-container">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo" class="logo">
        <h1>Bem-Vindo!</h1>
          <form action="" method="POST">
            <input type="text" name="nome" placeholder="Nome da Empresa" required>
              <input type="email" name="email" placeholder="Endereço de e-mail" required>
              <input type="password" name="senha" placeholder="Insira sua Senha" required>
              <input type="password" name="senha2" placeholder="Repita sua senha" required>
          <div class="login-link">
            Já é registrado? <a href="../funcionarios/login.php">Login</a>
          </div>
          <div class="login-link">
            Já é uma loja registrada? <a href="loginLoja.php">Login</a>
          </div>
          <button type="submit" class="btn">Continuar</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
