<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | DeckLogistic</title>
  <link rel="stylesheet" href="../../assets/cadastrofuncionario.css">
  <link rel="icon" href="../img/logoDecklogistic.webp" type="image/x-icon" />
  <!-- Font Awesome -->
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
      padding-left: 35px; /* espaço pro ícone */
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left-side"></div>
    <div class="right-side">
      <div class="form-container">
        <img src="../../img/logoDecklogistic.webp" alt="Logo" class="logo">
        <h1>Cadastre um funcionário</h1>
        <form action="login2etapa.php" method="POST">
          
          <div class="input-container">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="empresa" placeholder="Nome funcionário" required>
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

          <button type="submit" class="btn">Prosseguir</button>
          <button type="submit" onclick="location.href='cadastro.php'" class="btn">Voltar</button>
        
        </form>
      </div>
    </div>
  </div>
</body>
