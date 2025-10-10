<?php
session_start();
include __DIR__ . '/../../../conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_login']) || $_SESSION['tipo_login'] !== 'empresa') {
    die("Acesso negado. Apenas empresas podem cadastrar funcionários.");
}


// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

if ($nome && $email && $senha && $senha2) {
    if ($senha !== $senha2) {
        $msg = "As senhas não coincidem.";
    } else {
        // 🔎 Verifica se já existe um usuário com esse e-mail
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Este e-mail já está em uso.";
        } else {
            // Se não existir, cadastra normalmente
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $loja_id = $_SESSION['usuario_id'];

            $stmt = $conn->prepare("INSERT INTO usuarios (loja_id, nome, email, senha_hash) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $loja_id, $nome, $email, $senha_hash);

            if ($stmt->execute()) {
                $msg = "Funcionário cadastrado com sucesso!";
            } else {
                $msg = "Erro ao cadastrar: " . $stmt->error;
            }
        }
    }
} else {
    $msg = "Preencha todos os campos.";
}

}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Funcionário | DeckLogistic</title>
  <link rel="stylesheet" href="../../../assets/cadastrofuncionario.css">
  <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    .input-container {
      position: relative;
      width: 100%;
    }
    .input-container i {
      position: absolute;
      top: 50%;
        left: 10px;
      transform: translateY(-50%);
      color: #777;
    }
    .input-container input {
      padding-left: 35px;
    }
    .msg {
      margin: 10px 0;
      font-weight: bold;
      color: green    
      
      
      ;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left-side"></div>
    <div class="right-side">
      <div class="form-container">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo" class="logo">
        <h1>Cadastre um funcionário</h1>

        <?php if (!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>

        <form method="POST">
          <div class="input-container">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="nome" placeholder="Nome do funcionário" required>
          </div>

          <div class="input-container">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" name="email" placeholder="Endereço de e-mail" required>
          </div>

          <div class="input-container">
            <i class="fa-solid fa-key"></i>
            <input type="password" name="senha" placeholder="Insira uma Senha" required>
          </div>

          <div class="input-container">
            <i class="fa-solid fa-key"></i>
            <input type="password" name="senha2" placeholder="Repita a senha" required>
          </div>

          <button type="submit" class="btn">Cadastrar Funcionário</button>
          <button type="button" onclick="location.href='../config.php'" class="btn">Voltar</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
