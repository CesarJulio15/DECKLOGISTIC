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
  <title>Cadastro | DeckLogistic</title>
  <link rel="stylesheet" href="../../../assets/cadastro.css">
  <link rel="stylesheet" href="../../../assets/style1.css">
  <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />

<link rel="manifest" href="/decklogistic/manifest.json">
<meta name="theme-color" content="#0d6efd">


</head>
<body>
  <div class="container">
    <div class="left-side"></div>
    <div class="right-side">
      <div class="form-container">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo" class="logo">
        <h1 class="titulo">Bem-Vindo!</h1>
        
        <?php if (!empty($erro)): ?>
          <p style="color:red;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form action="" method="POST">
          <div class="form-group">
            <label for="nome">Nome da Empresa</label>
            <input type="text" id="nome" name="nome" maxlength="100" placeholder="Digite o nome da empresa" required
                   pattern="[A-Za-zÀ-ú0-9\s\-\.]+" title="Apenas letras, números, espaços, hífen e ponto">
          </div>

          <div class="form-group">
            <label for="email">Endereço de e-mail</label>
            <input type="email" id="email" name="email" maxlength="100" placeholder="Digite seu e-mail" required
                   title="Digite um e-mail válido">
          </div>

          <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" minlength="6" maxlength="50" placeholder="Insira sua senha" required
                   title="Mínimo 6 caracteres">
          </div>

          <div class="form-group">
            <label for="senha2">Repita a senha</label>
            <input type="password" id="senha2" name="senha2" minlength="6" maxlength="50" placeholder="Repita sua senha" required
                   title="Mínimo 6 caracteres">
          </div>

          <div class="login-link">
            Já é uma loja registrada? <a href="loginLoja.php">Login</a>
          </div>
          <div class="login-link">
            Já é um funcionário registrado? <a href="../funcionarios/login.php">Login</a>
          </div>

          <button type="submit" class="btn">Continuar</button>
          <button type="button" onclick="location.href='../../../index.php'" class="btn">Voltar</button>
        </form>
      </div>
    </div>
  </div>

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/decklogistic/sw.js');
}

</script>


</body>
</html>
