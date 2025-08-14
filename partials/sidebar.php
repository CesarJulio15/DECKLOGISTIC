<?php require_once '../config.php'; ?>

<aside class="sidebar">
<link rel="stylesheet" href="<?= ASSETS_PATH ?>/sidebar.css">

  <div class="logo-area">
    <img src="<?= IMG_PATH ?>/logoDecklogistic.webp" alt="Logo">
  </div>

  <nav class="nav-section">
    <div class="nav-menus">
      <ul class="nav-list top-section">
        <li><a href="/Pages/financeiro.php"><span><img src="<?= IMG_PATH ?>/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
        <li class="active"><a href="/Pages/estoque.php"><span><img src="<?= IMG_PATH ?>/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
      </ul>

      <hr>

      <ul class="nav-list middle-section">
        <li><a href="/Pages/visaoGeral.php"><span><img src="<?= IMG_PATH ?>/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
        <li><a href="/Pages/operacoes.php"><span><img src="<?= IMG_PATH ?>/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
        <li><a href="/Pages/produtos.php"><span><img src="<?= IMG_PATH ?> /icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
      </ul>
    </div>

    <div class="bottom-links">
      <a href="/Pages/conta.php"><span><img src="<?= IMG_PATH ?>/icon-config.svg" alt="Conta"></span> Conta</a>
      <a href="/Pages/dicas.php"><span><img src="<?= IMG_PATH ?>/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
    </div>
  </nav>
</aside>
