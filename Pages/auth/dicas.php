<?php
include __DIR__ . '../../../session_check.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dicas - DeckLogistic</title>
  <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
  <link rel="stylesheet" href="../../assets/sidebar.css">
  <link rel="stylesheet" href="../../assets/dicas.css">

  
</head>
<body>
<div class="content">
  <!-- Sidebar -->
<div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../dashboard/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a class="active" href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>
  <div class="container">
    <h1> Dicas do Sistema</h1>

    <div class="card">
      <h2> Boas práticas de senha</h2>
      <ul>
        <li>Use pelo menos 8 caracteres.</li>
        <li>Misture letras maiúsculas, minúsculas, números e símbolos.</li>
        <li>Evite senhas fáceis como "123456".</li>
      </ul>
    </div>

    <div class="card">
      <h2> Organização do Estoque</h2>
      <ul>
        <li>Mantenha os produtos bem categorizados.</li>
        <li>Atualize o estoque diariamente.</li>
        <li>Revise produtos próximos da validade.</li>
      </ul>
    </div>

    <div class="card">
      <h2> Atalhos úteis</h2>
      <ul>
        <li><b>Ctrl + F</b>: Buscar produtos.</li>
        <li><b>Ctrl + R</b>: Atualizar relatórios.</li>
      </ul>
    </div>

    <div class="card">
      <h2>🛡 Segurança</h2>
      <ul>
        <li>Não compartilhe sua senha.</li>
        <li>Sempre saia ao terminar o uso.</li>
        <li>Evite logar em dispositivos públicos.</li>
      </ul>
    </div>
  </div>
</body>
</html>
