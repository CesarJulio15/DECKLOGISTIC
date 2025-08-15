<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
   <link rel="stylesheet" href="../../assets/redefinirSenha.css">
</head>
<body>

  <div class="sidebar">
<link rel="stylesheet" href="../../assets/sidebar.css">

  <div class="logo-area">
    <img src="../../img/logoDecklogistic.webp" alt="Logo">
  </div>

  <nav class="nav-section">
    <div class="nav-menus">
      <ul class="nav-list top-section">
        <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
        <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
      </ul>

      <hr>

      <ul class="nav-list middle-section">
    <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
        <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
        <li><a href="/Pages/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
        <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
      </ul>
    </div>

    <div class="bottom-links">
      <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
      <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
    </div>
  </nav>
</div>

  <div class="content">
    <div class="reset-card">
      <h1>Redefinição de senha</h1>
      <p>Insira sua nova senha abaixo para redefinir o acesso à sua conta.</p>
      <form action="/processa_redefinicao.php" method="POST">
        <div class="form-group">
          <label for="novaSenha">Nova Senha</label>
          <input type="password" id="novaSenha" name="novaSenha" required>
        </div>
        <div class="form-group">
          <label for="confirmaSenha">Confirmar Nova Senha</label>
          <input type="password" id="confirmaSenha" name="confirmaSenha" required>
        </div>
        <button type="submit" class="btn-submit">Redefinir</button>
      </form>
    </div>
  </div>



</body>
</html>