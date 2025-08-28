<?php
session_start();
$lojaId = $_SESSION['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="../../assets/estoque.css?v=2">
  <link rel="stylesheet" href="../../assets/sidebar.css">
</head>
<body>

<div class="content">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo-area">
      <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>

    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span>Financeiro</a></li>
          <li class="active"><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span>Estoque</a></li>
        </ul>

        <hr>

        <ul class="nav-list middle-section">
          <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span>Visão Geral</a></li>
          <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span>Operações</a></li>
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span>Produtos</a></li>
          <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span>Tags</a></li>
        </ul>
      </div>

      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span>Conta</a>
        <a href="../auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span>Dicas</a>
      </div>
    </nav>
  </aside>

  <div class="dashboard">

    <!-- Total Estoque -->
    <div class="card">
      <h3>Total Estoque</h3>
      <div class="value" id="estoque">...</div>
      <div id="chartMiniEstoque"></div>
      <button id="btnVerMais">Ver mais</button>
    </div>

    <!-- Produtos em Falta -->
    <div class="card">
      <h3>Produtos em Falta</h3>
      <div id="tabelaProdutosFalta" style="margin-top:10px;"></div>
    </div>

    <!-- Estoque Parado -->
    <div class="card">
      <h3>Estoque Parado</h3>
      <div id="chartEstoqueParado" style="height:300px; margin-top:10px;"></div>
    </div>

    <!-- Entrada x Saída -->
    <div class="card">
      <h3>Entrada x Saída de Produtos</h3>
      <div id="chartEntradaSaida" style="height:300px; margin-top:10px;"></div>
    </div>

  </div>

  <!-- Produtos Reabastecidos Recentemente -->
  <div class="card reabastecidos">
    <h3>Produtos Reabastecidos Recentemente</h3>

    <!-- Filtro -->
    <div style="margin-left:26px; margin-bottom:10px;">
      <input type="text" id="filtroReabastecidos" placeholder="Pesquisar por produto..." style="padding:6px 10px; font-size:14px; border-radius:10px;">
      <button id="btnFiltrar" style="padding:6px 12px; font-size:14px; margin-left:5px; cursor:pointer;">Filtrar</button>
    </div>

    <div id="tabelaReabastecidos"></div>
  </div>
</div>

<script>
const lojaId = <?= $lojaId ?>;

document.getElementById("btnVerMais").addEventListener("click", () => {
  // Redireciona para produtos.php
  window.location.href = "../dashboard/tabelas/produtos.php";
});
// ===================
// Funções de dados
// ===================
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    document.querySelector("#estoque").textContent = parseFloat(data.total || 0).toFixed(2);
  } catch(e) { console.error(e); document.querySelector("#estoque").textContent = "Erro"; }
}

async function loadProdutosFalta() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php`).then(r => r.json());
    const tabelaContainer = document.querySelector("#tabelaProdutosFalta");
    if(!Array.isArray(data) || data.length === 0) { tabelaContainer.innerHTML = "<p>Nenhum produto em falta.</p>"; return; }

    let tabela = `<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px;">
      <thead style="background:#f3f4f6; text-align:left;"><tr><th>Produto</th><th>Quantidade</th></tr></thead><tbody>`;
    data.forEach(p => { tabela += `<tr style="background:${p.quantidade_estoque <= 0 ? '#ddd' : '#fff'};">
      <td>${p.nome}</td><td>${p.quantidade_estoque}</td></tr>`; });
    tabela += `</tbody></table>`;
    tabelaContainer.innerHTML = tabela;
  } catch(e) { console.error(e); document.querySelector("#tabelaProdutosFalta").innerHTML = "<p>Erro ao carregar dados</p>"; }
}

async function loadEstoqueProdutos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/teste_estoque.php').then(r => r.json());
    if(!Array.isArray(data)) { document.querySelector("#chartEstoqueParado").innerHTML = "<p>Dados inválidos</p>"; return; }

    const produtos = data.map(d => d.produto);
    const quantidades = data.map(d => parseInt(d.qtd));

    new ApexCharts(document.querySelector("#chartEstoqueParado"), {
      chart: { type: 'bar', height: 300 },
      series: [{ name: 'Estoque Parado', data: quantidades }],
      xaxis: { categories: produtos },
      colors: ['#10b981'],
      dataLabels: { enabled: true }
    }).render();
  } catch(e) { console.error(e); document.querySelector("#chartEstoqueParado").innerHTML = "<p>Erro ao carregar gráfico</p>"; }
}

async function loadEntradaSaidaProdutos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/teste_entrada_saida.php').then(r => r.json());
    if(!Array.isArray(data) || data.length === 0) { document.querySelector("#chartEntradaSaida").innerHTML = "<p>Dados inválidos</p>"; return; }

    const labels = data.map(d => d.data_movimentacao);
    const entrada = data.map(d => parseInt(d.entrada || 0));
    const saida = data.map(d => parseInt(d.saida || 0));

    new ApexCharts(document.querySelector("#chartEntradaSaida"), {
      chart: { type: 'line', height: 300 },
      series: [{ name: 'Entrada', data: entrada }, { name: 'Saída', data: saida }],
      xaxis: { categories: labels },
      stroke: { curve: 'smooth' },
      colors: ['#10b981', '#ef4444']
    }).render();
  } catch(e) { console.error(e); document.querySelector("#chartEntradaSaida").innerHTML = "<p>Erro ao carregar gráfico</p>"; }
}

async function loadMiniEstoque() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    const total = parseFloat(data.total || 0);
    new ApexCharts(document.querySelector("#chartMiniEstoque"), {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth', width: 2 },
      fill: { opacity: 0.3 },
      series: [{ data: [total, total * 0.9, total * 1.1, total] }],
      colors: ['#10b981']
    }).render();
  } catch(e) { console.error("Erro mini gráfico:", e); }
}

// Produtos Reabastecidos Recentemente
async function loadProdutosReabastecidos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/produtos_reabastecidos.php').then(r => r.json());
    const tabelaContainer = document.querySelector("#tabelaReabastecidos");
    if(!Array.isArray(data) || data.length === 0) { tabelaContainer.innerHTML = "<p>Nenhum produto reabastecido recentemente.</p>"; return; }

    let tabela = `<table border="1" cellpadding="8" cellspacing="0" 
      style="width:95%; font-size:15px; margin-left:26px; margin-bottom:50px;">
      <thead style="background:#f3f4f6; text-align:left;">
      <tr><th>Lote</th><th>Produto</th><th>Data de Reabastecimento</th></tr></thead><tbody>`;

    data.forEach(p => { tabela += `<tr><td>${p.lote}</td><td>${p.nome}</td><td>${p.data_reabastecimento}</td></tr>`; });
    tabela += `</tbody></table>`;
    tabelaContainer.innerHTML = tabela;
  } catch(e) { console.error("Erro ao carregar reabastecidos:", e); tabelaContainer.innerHTML = "<p>Erro ao carregar dados</p>"; }
}

// ===================
// Botão de filtro
// ===================
const btnFiltrar = document.getElementById("btnFiltrar");
const filtroInput = document.getElementById("filtroReabastecidos");

btnFiltrar.addEventListener("click", () => {
  const filtro = filtroInput.value.toLowerCase();
  const tabela = document.querySelector("#tabelaReabastecidos table");
  if(!tabela) return;

  const linhas = tabela.querySelectorAll("tbody tr");
  linhas.forEach(linha => {
    const texto = linha.cells[1].textContent.toLowerCase(); // coluna Produto
    linha.style.display = texto.includes(filtro) ? "" : "none";
  });
});

// ===================
// Carregar todos os dados
// ===================
loadMiniEstoque();
loadEstoqueTotal();
loadProdutosFalta();
loadEstoqueProdutos();
loadEntradaSaidaProdutos();
loadProdutosReabastecidos();
</script>
</body>
</html>
