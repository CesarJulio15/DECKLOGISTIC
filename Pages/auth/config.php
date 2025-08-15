<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="stylesheet" href="../../assets/config.css">
</head>
<body>
<div class="pagina">
    
    <!-- Sidebar -->
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

    <!-- Conteúdo -->
    <div class="conteudo">
        <h1>Minha Conta</h1>

        <div class="caixa-conta">
            <div class="perfil">
                <div class="icone-usuario"><img src="../../img/icon-user.svg" alt=""></div>
                <h2>Empresa x</h2>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-name.svg" alt=""></span>
                <strong>Nome:</strong>
                <span>Empresa X</span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-email.svg" alt=""></span>
                <strong>Email:</strong>
                <span>empresaxloja@gmail.com</span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-senha.svg" alt=""></span>
                <strong>Senha:</strong>
                <span>********</span>
                <a href="#">Alterar Senha</a>
            </div>

            <button class="btn-sair">Sair da conta</button>

            <button class="btn-excluir">
               <a> Excluir Conta </a><img src="../../img/icon-lixo.svg" alt="">
            </button>
        </div>
    </div>
</div>
</body>
</html>