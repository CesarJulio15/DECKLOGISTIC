<?php
session_start();

// ID da loja logada na sessão
$lojaId = $_SESSION['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Finanças - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="../../assets/estoque.css">
</head>
<body>

<div class="content">
  <div class="sidebar">
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

   <!-- Dashboard Cards -->
   <div class="dashboard">
    <div class="card">
    <h3>Total Estoque</h3>
    <div id="estoque" class="value">...</div>
    <div id="chartEstoque" style="height:60px; margin-top:10px;"></div>
    </div>

    <div class="card">
      <h3>Produtos em Falta</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Produtos com Estoque Parado</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Entrada e Saída de Produtos</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {
  // API: Total de estoque
  fetch('../api/total_estoque.php')
    .then(res => res.json())
    .then(data => {
      document.getElementById('estoque').textContent = data.total;
    })
    .catch(err => console.error('Erro ao carregar total de estoque:', err));

  // Aqui você faria o mesmo para outras APIs, por exemplo:
  // fetch('api/produtos_falta.php') => atualizar gráfico de produtos em falta
  // fetch('api/produtos_parado.php') => atualizar gráfico de produtos parados
  // fetch('api/entrada_saida.php') => atualizar gráfico de entrada/saída
});
</script>

</body>
</html>



