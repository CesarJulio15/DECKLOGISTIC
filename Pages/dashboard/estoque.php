<?php
session_start();
include __DIR__ . '/../../conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado. Faça login primeiro.");
}

// Sempre pega o ID da loja
$lojaId = $_SESSION['tipo_login'] === 'empresa'
    ? $_SESSION['usuario_id']  // empresa logada
    : $_SESSION['loja_id'];    // funcionário logado
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
  <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
  <link rel="stylesheet" href="../../assets/estoque.css">
  <link rel="stylesheet" href="../../assets/sidebar.css">
</head>
<body>

<div class="content">

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
          <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
        </ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

  <div class="dashboard">

    <!-- Total Estoque -->
    <div class="card">
      <h3>Total Estoque</h3>
      <div class="value" id="estoque">...</div>
      <div id="chartMiniEstoque"></div>

      <!-- Botão Histórico -->
      <button id="btnHistorico" style="
        background: linear-gradient(135deg, rgba(255,153,0,0.9), rgba(255,200,0,0.9));
        color: #fff; padding: 10px 16px; font-size: 14px; border-radius: 8px;
        border: none; cursor: pointer; margin-top: 12px;
        transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(255,170,0,0.4);
      ">Ver histórico 6 meses</button>

      <!-- Botão Giro -->
      <button id="btnGiro" class="btn-modern" onclick="window.location.href='giroEstoque.php'">
        Ver Giro de Estoque
      </button>
    </div>

    <!-- Modal Histórico -->
    <div id="modalHistorico" style="
        display:none; position:fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.5); justify-content:center; align-items:center;
    ">
      <div style="
          background:#fff; padding:20px; border-radius:8px;
          width:90%; max-width:600px; position:relative;
      ">
        <button id="btnFecharModal" style="
            position:absolute; top:10px; right:10px;
            font-size:16px; cursor:pointer;
        ">✖</button>
        <h3>Histórico Estoque - Últimos 6 Meses</h3>
        <div id="chartHistorico" style="height:300px;"></div>
      </div>
    </div>

    <!-- Produtos em Falta -->
    <div class="card">
      <h3>Produtos em Falta</h3>
      <div id="tabelaProdutosFalta" style="margin-top:10px;"></div>
    </div>

    <!-- Estoque Parado -->
    <div class="card">
      <div id="chartEstoqueParado" style="height:300px; margin-top:10px;"></div>
    </div>

    <!-- Entrada x Saída -->
    <div class="card">
      <div id="chartEntradaSaida" style="height:300px; margin-top:10px;"></div>
    </div>

    <!-- Produtos Reabastecidos -->
    <div class="card reabastecidos">
      <h3>Produtos Reabastecidos Recentemente</h3>
      <div style="margin-bottom:10px;">
        <input type="text" id="filtroReabastecidos" placeholder="Pesquisar por produto..." 
          style="padding:6px 10px; font-size:14px; border-radius:10px; width:200px;
                 background: rgba(30,30,30,0.85); color: #fff; border: 1px solid #fff;">
        <button id="btnFiltrar" 
          style="padding:6px 12px; font-size:14px; margin-left:5px; cursor:pointer;
                 background: linear-gradient(135deg, rgba(255,153,0,0.9), rgba(255,200,0,0.85));
                 color:#fff; border:none; border-radius:8px;">Filtrar</button>
      </div>
      <div class="table-responsive" id="tabelaReabastecidos"></div>
    </div>
  </div>
</div>

<!-- =================== JAVASCRIPT =================== -->
<script>
const lojaId = <?= $lojaId ?>;

// ---------- Total Estoque ----------
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    document.querySelector("#estoque").textContent = parseFloat(data.total || 0).toFixed(2);
  } catch (e) { console.error(e); }
}

// ---------- Produtos em Falta ----------
async function loadProdutosFalta() {
  const tabela = document.querySelector("#tabelaProdutosFalta");
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data) || !data.length) {
      tabela.innerHTML = "<p>Nenhum produto em falta.</p>";
      return;
    }
    let html = `<table border="1" cellpadding="6" cellspacing="0" style="width:100%;">
      <thead><tr><th>Produto</th><th>Quantidade</th></tr></thead><tbody>`;
    data.forEach(p => {
      html += `<tr><td>${p.nome}</td><td>${p.quantidade_estoque}</td></tr>`;
    });
    html += "</tbody></table>";
    tabela.innerHTML = html;
  } catch (e) { tabela.innerHTML = "<p>Erro ao carregar dados</p>"; }
}

// ---------- Estoque Parado ----------
async function loadEstoqueProdutos() {
  const container = document.querySelector("#chartEstoqueParado");
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_parados.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data) || !data.length) {
      container.innerHTML = "<p>Nenhum produto parado.</p>";
      return;
    }
    new CanvasJS.Chart(container, {
      animationEnabled:true, theme:"light2", title:{text:"Estoque Parado"},
      axisY:{title:"Quantidade"}, data:[{type:"column", dataPoints:data.map(d=>({label:d.nome,y:+d.quantidade_estoque}))}]
    }).render();
  } catch (e) { console.error(e); }
}

// ---------- Entrada x Saída ----------
async function loadEntradaSaidaProdutos() {
  const container = document.querySelector("#chartEntradaSaida");
  try {
    const data = await fetch(`/DECKLOGISTIC/api/entrada_saida.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data) || !data.length) {
      container.innerHTML = "<p>Nenhuma movimentação registrada.</p>";
      return;
    }
    const entrada = data.map(d => ({ label:d.data_movimentacao, y:d.entrada }));
    const saida = data.map(d => ({ label:d.data_movimentacao, y:d.saida }));
    new CanvasJS.Chart(container, {
      animationEnabled:true, theme:"light2", title:{text:"Entrada x Saída"},
      axisY:{title:"Quantidade"},
      data:[
        {type:"line", name:"Entrada", showInLegend:true, dataPoints:entrada},
        {type:"line", name:"Saída", showInLegend:true, dataPoints:saida}
      ]
    }).render();
  } catch (e) { console.error(e); }
}

// ---------- Histórico Estoque ----------
async function loadHistoricoEstoque() {
  try {
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data.series)) return;
    const chart = new CanvasJS.Chart("chartHistorico", {
      animationEnabled:true, theme:"light2",
      title:{text:"Histórico de Estoque (últimos 6 meses)"},
      axisY:{title:"Quantidade Total"},
      data:[{type:"column", dataPoints:data.series.map(d=>({label:d.mes,y:+d.total}))}]
    });
    chart.render();
  } catch (e) { console.error(e); }
}

// ---------- Reabastecidos ----------
async function loadProdutosReabastecidos() {
  const tabela = document.querySelector("#tabelaReabastecidos");
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_reabastecidos.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data) || !data.length) {
      tabela.innerHTML = "<p>Nenhum produto reabastecido recentemente.</p>";
      return;
    }
    let html = `<table border="1" cellpadding="8" cellspacing="0" style="width:95%; margin-left:26px;">
      <thead><tr><th>Lote</th><th>Produto</th><th>Data</th></tr></thead><tbody>`;
    data.forEach(p => { html += `<tr><td>${p.lote}</td><td>${p.nome}</td><td>${p.data_reabastecimento}</td></tr>`; });
    html += "</tbody></table>";
    tabela.innerHTML = html;
  } catch (e) { tabela.innerHTML = "<p>Erro ao carregar dados</p>"; }
}

// ---------- Modal Histórico ----------
const btnHistorico = document.getElementById("btnHistorico");
const modal = document.getElementById("modalHistorico");
document.getElementById("btnFecharModal").onclick = () => modal.style.display = "none";
btnHistorico.onclick = async () => { modal.style.display = "flex"; await loadHistoricoEstoque(); };

// ---------- Inicialização ----------
document.addEventListener("DOMContentLoaded", () => {
  loadEstoqueTotal();
  loadProdutosFalta();
  loadEstoqueProdutos();
  loadEntradaSaidaProdutos();
  loadProdutosReabastecidos();
});
</script>

</body>
</html>
