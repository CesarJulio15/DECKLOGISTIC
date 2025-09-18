<?php
session_start();
$lojaId = $_SESSION['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
  <link rel="stylesheet" href="../../assets/sidebar.css">
  <link rel="stylesheet" href="../../assets/estoque.css?v=2">
  
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
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span>Operações</a></li>
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span>Produtos</a></li>
          <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span>Tags</a></li>
        </ul>
      </div>

      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span>Conta</a>
        <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span>Dicas</a>
      </div>
    </nav>
  </aside>

  <div class="dashboard">

    <!-- Total Estoque -->
    <div class="card">
      <h3>Total Estoque</h3>
      <div class="value" id="estoque">...</div>
      <div id="chartMiniEstoque"></div>
      <!-- Botão estilizado -->
      <button id="btnHistorico" style="
          background: #4f46e5;
          color: #fff;
          padding: 10px 16px;
          font-size: 14px;
          border-radius: 8px;
          border: none;
          cursor: pointer;
          margin-top: 12px;
          transition: 0.2s;
      " onmouseover="this.style.background='#3730a3';" 
         onmouseout="this.style.background='#4f46e5';">
        Ver histórico 6 meses
      </button>
    </div>

<!-- Popup modal -->
<div id="modalHistorico" style="
    display:none;
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
">
  <div style="
      background:#fff;
      padding:20px;
      border-radius:8px;
      width:90%; max-width:600px;
      position:relative;
  ">
    <button id="btnFecharModal" style="
        position:absolute; top:10px; right:10px;
        font-size:16px; cursor:pointer;
    ">✖</button>
    <h3>Histórico Estoque - Últimos 6 Meses</h3>
    <div id="chartHistorico" style="height:300px;"></div>
  </div>
</div>

<script>
const lojaId = <?= $lojaId ?>;

// ===================
// Função para carregar gráfico histórico no modal
// ===================
async function loadHistoricoEstoque() {
  try {
    // Realiza a requisição para obter os dados do histórico de estoque
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());

    // Verifica se a resposta contém dados
    if (!Array.isArray(data.series) || data.series.length === 0) {
      document.querySelector("#chartHistorico").innerHTML = "<p>Nenhum dado disponível</p>";
      return;
    }

    // Prepara os dados para o gráfico
    const dataPoints = data.series.map(d => ({
      label: d.mes, 
      y: parseInt(d.total || 0)
    }));

    // Cria o gráfico de barras no modal com os dados
    new CanvasJS.Chart("chartHistorico", {
      animationEnabled: true,
      theme: "light2",
      title: {
        text: "Histórico de Estoque (últimos 6 meses)"
      },
      axisY: {
        title: "Quantidade Total de Produtos"
      },
      data: [{
        type: "column", 
        dataPoints: dataPoints
      }]
    }).render();
  } catch (e) {
    console.error(e);
    document.querySelector("#chartHistorico").innerHTML = "<p>Erro ao carregar gráfico</p>";
  }
}

// ===================
// Funções de dados principais
// ===================
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    const estoqueEl = document.querySelector("#estoque");
    if (estoqueEl) estoqueEl.textContent = parseFloat(data.total || 0).toFixed(2);
  } catch(e) { console.error(e); }
}

async function loadProdutosFalta() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php`).then(r => r.json());
    const tabelaContainer = document.querySelector("#tabelaProdutosFalta");
    if (!Array.isArray(data) || data.length === 0) { tabelaContainer.innerHTML = "<p>Nenhum produto em falta.</p>"; return; }
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
    const data = await fetch('/DECKLOGISTIC/api/produtos_parados.php').then(r => r.json());
    const container = document.querySelector("#chartEstoqueParado");
    if(!Array.isArray(data)) { container.innerHTML = "<p style='color:red;'>Erro ao carregar dados</p>"; return; }
    if(data.length === 0) {
      container.innerHTML = `<div style="display:flex;justify-content:center;align-items:center;height:100%;font-size:18px;color:#6b7280;font-weight:500;background:#f3f4f6;border-radius:8px;padding:20px;text-align:center;">Nenhum produto parado nos últimos 30 dias</div>`;
      return;
    }
    const dataPoints = data.map(d => ({ label: d.nome, y: parseInt(d.quantidade_estoque) }));
    new CanvasJS.Chart(container, { animationEnabled:true, theme:"light2", title:{text:"Estoque Parado"}, axisY:{title:"Quantidade"}, data:[{type:"column", dataPoints}]}).render();
  } catch(e) { console.error(e); document.querySelector("#chartEstoqueParado").innerHTML = "<p style='color:red;'>Erro ao carregar gráfico</p>"; }
}

async function loadEntradaSaidaProdutos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/entrada_saida.php').then(r => r.json());
    const container = document.querySelector("#chartEntradaSaida");
    if (!Array.isArray(data) || data.length === 0) { 
      container.innerHTML = `<div style="display:flex;justify-content:center;align-items:center;height:100%;font-size:16px;color:#6b7280;font-weight:500;background:#f3f4f6;border-radius:8px;padding:20px;text-align:center;">Nenhuma movimentação registrada</div>`; 
      return; 
    }
    const entrada = data.map(d => ({ label: d.data_movimentacao, y: d.entrada }));
    const saida   = data.map(d => ({ label: d.data_movimentacao, y: d.saida }));
    new CanvasJS.Chart(container, { animationEnabled:true, theme:"light2", title:{text:"Entrada x Saída"}, axisY:{title:"Quantidade"}, data:[{type:"line", name:"Entrada", showInLegend:true, dataPoints:entrada},{type:"line", name:"Saída", showInLegend:true, dataPoints:saida}]}).render();
  } catch(e) { console.error(e); document.querySelector("#chartEntradaSaida").innerHTML = "<p>Erro ao carregar gráfico</p>"; }
}

// ===================
// Carregar tudo no DOMContentLoaded
// ===================
document.addEventListener("DOMContentLoaded", () => {
  loadEstoqueTotal();
  loadProdutosFalta();
  loadEstoqueProdutos();
  loadEntradaSaidaProdutos();
});

// ===================
// Modal histórico
// ===================
const btnHistorico = document.getElementById("btnHistorico");
const modalHistorico = document.getElementById("modalHistorico");
const btnFecharModal = document.getElementById("btnFecharModal");

btnHistorico.addEventListener("click", async () => {
  modalHistorico.style.display = "flex";
  await loadHistoricoEstoque();  // Carrega o gráfico ao abrir o modal
});

btnFecharModal.addEventListener("click", () => { modalHistorico.style.display = "none"; });
</script>


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

    <!-- Produtos Reabastecidos Recentemente -->
<!-- Produtos Reabastecidos Recentemente -->
<div class="card reabastecidos">
  <h3>Produtos Reabastecidos Recentemente</h3>

  <!-- Filtro -->
  <div style="margin-bottom:10px;">
    <input type="text" id="filtroReabastecidos" placeholder="Pesquisar por produto..." style="padding:6px 10px; font-size:14px; border-radius:10px; width:200px;">
    <button id="btnFiltrar" style="padding:6px 12px; font-size:14px; margin-left:5px; cursor:pointer;">Filtrar</button>
  </div>

  <div class="table-responsive" id="tabelaReabastecidos"></div>
</div>

  </div>
</div>

<script>
const lojaId = <?= $lojaId ?>;

// ===================
// Funções de dados
// ===================
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    const estoqueEl = document.querySelector("#estoque");
    if (estoqueEl) estoqueEl.textContent = parseFloat(data.total || 0).toFixed(2);
  } catch (e) { console.error(e); }
}

async function loadProdutosFalta() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php`).then(r => r.json());
    const tabelaContainer = document.querySelector("#tabelaProdutosFalta");
    if (!Array.isArray(data) || data.length === 0) { tabelaContainer.innerHTML = "<p>Nenhum produto em falta.</p>"; return; }
    let tabela = `<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px;">
      <thead style="background:#f3f4f6; text-align:left;"><tr><th>Produto</th><th>Quantidade</th></tr></thead><tbody>`;
    data.forEach(p => { tabela += `<tr style="background:${p.quantidade_estoque <= 0 ? '#ddd' : '#fff'};">
      <td>${p.nome}</td><td>${p.quantidade_estoque}</td></tr>`; });
    tabela += `</tbody></table>`;
    tabelaContainer.innerHTML = tabela;
  } catch (e) { console.error(e); document.querySelector("#tabelaProdutosFalta").innerHTML = "<p>Erro ao carregar dados</p>"; }
}

async function loadEstoqueProdutos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/produtos_parados.php').then(r => r.json());
    const container = document.querySelector("#chartEstoqueParado");
    if (!Array.isArray(data)) { container.innerHTML = "<p style='color:red;'>Erro ao carregar dados</p>"; return; }
    if (data.length === 0) {
      container.innerHTML = `<div style="display:flex;justify-content:center;align-items:center;height:100%;font-size:18px;color:#6b7280;font-weight:500;background:#f3f4f6;border-radius:8px;padding:20px;text-align:center;">Nenhum produto parado nos últimos 30 dias</div>`;
      return;
    }
    const dataPoints = data.map(d => ({ label: d.nome, y: parseInt(d.quantidade_estoque) }));
    new CanvasJS.Chart(container, { animationEnabled: true, theme: "light2", title: { text: "Estoque Parado" }, axisY: { title: "Quantidade" }, data: [{ type: "column", dataPoints }] }).render();
  } catch (e) { console.error(e); document.querySelector("#chartEstoqueParado").innerHTML = "<p style='color:red;'>Erro ao carregar gráfico</p>"; }
}

async function loadEntradaSaidaProdutos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/entrada_saida.php').then(r => r.json());
    const container = document.querySelector("#chartEntradaSaida");
    if (!Array.isArray(data) || data.length === 0) {
      container.innerHTML = `<div style="display:flex;justify-content:center;align-items:center;height:100%;font-size:16px;color:#6b7280;font-weight:500;background:#f3f4f6;border-radius:8px;padding:20px;text-align:center;">Nenhuma movimentação registrada</div>`;
      return;
    }
    const entrada = data.map(d => ({ label: d.data_movimentacao, y: d.entrada }));
    const saida = data.map(d => ({ label: d.data_movimentacao, y: d.saida }));
    new CanvasJS.Chart(container, { animationEnabled: true, theme: "light2", title: { text: "Entrada x Saída" }, axisY: { title: "Quantidade" }, data: [{ type: "line", name: "Entrada", showInLegend: true, dataPoints: entrada }, { type: "line", name: "Saída", showInLegend: true, dataPoints: saida }] }).render();
  } catch (e) { console.error(e); document.querySelector("#chartEntradaSaida").innerHTML = "<p style='color:red;'>Erro ao carregar gráfico</p>"; }
}

// Função para carregar produtos reabastecidos
async function loadProdutosReabastecidos() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/produtos_reabastecidos.php').then(r => r.json());
    const tabelaContainer = document.querySelector("#tabelaReabastecidos");
    if (!Array.isArray(data) || data.length === 0) {
      tabelaContainer.innerHTML = "<p>Nenhum produto reabastecido recentemente.</p>";
      return;
    }

    let tabela = `<table border="1" cellpadding="8" cellspacing="0" style="width:95%; font-size:15px; margin-left:26px; margin-bottom:50px;">
      <thead style="background:#f3f4f6; text-align:left;">
      <tr><th>Lote</th><th>Produto</th><th>Data de Reabastecimento</th></tr></thead><tbody>`;

    data.forEach(p => {
      tabela += `<tr>
          <td>${p.lote}</td>
          <td>${p.nome}</td>
          <td>${p.data_reabastecimento}</td>
      </tr>`;
    });

    tabela += `</tbody></table>`;
    tabelaContainer.innerHTML = tabela;
  } catch (e) {
    console.error("Erro ao carregar reabastecidos:", e);
    document.querySelector("#tabelaReabastecidos").innerHTML = "<p>Erro ao carregar dados</p>";
  }
}

// Modal histórico
const btnHistorico = document.getElementById("btnHistorico");
const modalHistorico = document.getElementById("modalHistorico");
const btnFecharModal = document.getElementById("btnFecharModal");

btnHistorico.addEventListener("click", async () => {
  modalHistorico.style.display = "flex";
  try {
    const data = await fetch(`../../api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data) || data.series.length === 0) { 
        document.querySelector("#chartHistorico").innerHTML = "<p>Nenhum dado disponível</p>"; 
        return; 
    }
    const dataPoints = data.series.map(d => ({ label: d.mes, y: parseInt(d.total || 0) }));
    new CanvasJS.Chart("chartHistorico", { 
      animationEnabled: true, 
      theme: "light2", 
      title: { text: "Histórico de Estoque (últimos 6 meses)" }, 
      axisY: { title: "Quantidade Total de Produtos" }, 
      data: [{ type: "column", dataPoints }] 
    }).render();
  } catch (e) { 
    console.error(e); 
    document.querySelector("#chartHistorico").innerHTML = "<p>Erro ao carregar gráfico</p>"; 
  }
});

btnFecharModal.addEventListener("click", () => { 
  modalHistorico.style.display = "none"; 
});

// Chamar no carregamento
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
