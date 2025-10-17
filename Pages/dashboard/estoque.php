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
          <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
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
        <select id="selectFiltro" style="padding:6px 10px; border-radius:8px; margin-left:5px;">
          <option value="data_recente">Mais recente</option>
          <option value="data_antiga">Mais antiga</option>
          <option value="categoria_asc">Categoria A-Z</option>
          <option value="categoria_desc">Categoria Z-A</option>
          <option value="nome_asc">Produto A-Z</option>
          <option value="nome_desc">Produto Z-A</option>
        </select>
        <button id="btnFiltrar" 
          style="padding:6px 12px; font-size:14px; margin-left:5px; cursor:pointer;
                 background: linear-gradient(135deg, rgba(255,153,0,0.9), rgba(255,200,0,0.85));
                 color:#fff; border:none; border-radius:8px;">Filtrar</button>
      </div>
      <div class="table-responsive" id="tabelaReabastecidos"></div>
    </div>
  </div>
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

<!-- =================== OVERLAYS DE AJUDA =================== -->

<!-- Blur atrás dos overlays -->
<div id="overlay-blur-estoque" class="full-screen-blur" style="display:none;"></div>

<!-- Overlay 1: Introdução à página de estoque -->
<div id="overlay-estoque-intro" style="display:none;">
  <div class="welcome-card">
    <h2>Estoque</h2>
    <p>Esta é a área de estoque da sua empresa. Aqui você gerencia produtos, monitora quantidades, analisa movimentações e identifica itens em falta ou parados.</p>
    <button id="closeOverlayEstoque1">Próximo</button>
  </div>
</div>

<!-- Overlay 2: Destaque para botões de histórico e giro -->
<div id="overlay-estoque-acoes" class="welcome-overlay" style="display:none;">
  <div class="welcome-card">
    <h2>Análises de Estoque</h2>
    <p>Use esses botões para visualizar o histórico dos últimos 6 meses e analisar o giro de estoque dos seus produtos.</p>
    <button id="closeOverlayEstoque2">Fechar</button>
  </div>
</div>

<!-- Botão de ajuda flutuante -->
<button id="help-btn-estoque">?</button>

<style>
/* Classe para botões ficarem acima do blur */
.fora-do-blur-estoque {
  position: relative !important;
  z-index: 10002 !important;
}

.fora-do-blur-estoque:hover {
  box-shadow: 0 0 0 3px #fff, 0 0 0 6px #ff6600 !important;
  transform: none !important;
}

/* Blur que cobre toda a tela */
#overlay-blur-estoque {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
}

/* Overlay introdução ao estoque */
#overlay-estoque-intro {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    justify-content: flex-end;
    align-items: flex-start;
    z-index: 10000;
    padding: 30px;
    padding-top: 700px;
    background: transparent;
}

/* Overlay de ações */
#overlay-estoque-acoes {
    display: none;
    position: absolute;
    z-index: 10001;
    justify-content: center;
    align-items: center;
}

/* Cards das overlays */
#overlay-estoque-intro .welcome-card,
#overlay-estoque-acoes .welcome-card {
  margin-top: 50px;
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    margin-bottom: 10px;
    z-index: 2;
    text-align: left;
}

#overlay-estoque-intro .welcome-card h2,
#overlay-estoque-acoes .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

#overlay-estoque-intro .welcome-card p,
#overlay-estoque-acoes .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}

#overlay-estoque-intro .welcome-card button,
#overlay-estoque-acoes .welcome-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

/* Botão de ajuda flutuante */
#help-btn-estoque {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ff6600;
    color: #fff;
    border: none;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

#help-btn-estoque:hover {
    box-shadow: 0 6px 24px rgba(255,102,0,0.4);
    transform: scale(1.1);
}
</style>

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
    const response = await fetch(`/DECKLOGISTIC/api/entrada_saida.php?loja_id=${lojaId}`);
    const json = await response.json();
    
    const data = json.data; // <-- acessa o array correto
    if (!Array.isArray(data) || !data.length) {
      container.innerHTML = "<p>Nenhuma movimentação registrada.</p>";
      return;
    }

    const entrada = data.map(d => ({ label: d.data, y: d.entrada }));
    const saida = data.map(d => ({ label: d.data, y: d.saida }));

    new CanvasJS.Chart(container, {
      animationEnabled: true,
      theme: "light2",
      title: { text: "Entrada x Saída" },
      axisY: { title: "Quantidade" },
      legend: { cursor: "pointer" },
      data: [
        { type: "line", name: "Entrada", showInLegend: true, dataPoints: entrada },
        { type: "line", name: "Saída", showInLegend: true, dataPoints: saida }
      ]
    }).render();

  } catch (e) {
    console.error(e);
    container.innerHTML = "<p>Erro ao carregar dados.</p>";
  }
}


// ---------- Histórico Estoque ----------
async function loadHistoricoEstoque() {
  try {
    const data = await fetch(`../../api/historico_estoque_6meses.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data.series)) return;

    const chart = new CanvasJS.Chart("chartHistorico", {
      animationEnabled: true,
      theme: "light2",
      title: { text: "Histórico de Estoque (últimos 6 meses)" },
      axisY: { title: "Quantidade Total" },
      data: [
        {
          type: "column",
          dataPoints: data.series.map(d => ({ label: d.mes, y: +d.estoque }))
        }
      ]
    });

    chart.render();
  } catch (e) {
    console.error(e);
  }
}


// ---------- Reabastecidos ----------
async function loadProdutosReabastecidos() {
  const tabela = document.querySelector("#tabelaReabastecidos");
  const pesquisa = document.getElementById("filtroReabastecidos").value.trim();
  const filtro = document.getElementById("selectFiltro") ? document.getElementById("selectFiltro").value : "data_recente";
  try {
    const url = `/DECKLOGISTIC/api/produtos_reabastecidos.php?loja_id=${lojaId}&pesquisa=${encodeURIComponent(pesquisa)}&filtro=${filtro}`;
    const data = await fetch(url).then(r => r.json());
    if (!Array.isArray(data) || !data.length) {
      tabela.innerHTML = "<p>Nenhum produto reabastecido recentemente.</p>";
      return;
    }
    let html = `<table border="1" cellpadding="8" cellspacing="0" style="width:95%; margin-left:26px;">
      <thead><tr><th>Produto</th><th>Categoria</th><th>Data</th></tr></thead><tbody>`;
    data.forEach(p => { html += `<tr><td>${p.nome}</td><td>${p.categoria}</td><td>${p.data_reabastecimento}</td></tr>`; });
    html += "</tbody></table>";
    tabela.innerHTML = html;
  } catch (e) { tabela.innerHTML = "<p>Erro ao carregar dados</p>"; }
}

document.getElementById("btnFiltrar").onclick = loadProdutosReabastecidos;
if (document.getElementById("filtroReabastecidos")) {
  document.getElementById("filtroReabastecidos").addEventListener("keyup", function(e) {
    if (e.key === "Enter") loadProdutosReabastecidos();
  });
}
if (document.getElementById("selectFiltro")) {
  document.getElementById("selectFiltro").addEventListener("change", loadProdutosReabastecidos);
}

// ---------- Modal Histórico ----------
const btnHistorico = document.getElementById("btnHistorico");
const modal = document.getElementById("modalHistorico");
document.getElementById("btnFecharModal").onclick = () => modal.style.display = "none";
btnHistorico.onclick = async () => { modal.style.display = "flex"; await loadHistoricoEstoque(); };

// =================== SISTEMA DE OVERLAYS ===================
const helpBtnEstoque = document.getElementById('help-btn-estoque');
const overlayIntro = document.getElementById('overlay-estoque-intro');
const overlayAcoes = document.getElementById('overlay-estoque-acoes');
const blurEstoque = document.getElementById('overlay-blur-estoque');
const btnCloseIntro = document.getElementById('closeOverlayEstoque1');
const btnCloseAcoes = document.getElementById('closeOverlayEstoque2');

// Abre primeira overlay
helpBtnEstoque.addEventListener('click', () => {
  overlayIntro.style.display = 'flex';
  blurEstoque.style.display = 'block';
});

// Fecha primeira overlay e abre segunda
btnCloseIntro.addEventListener('click', () => {
  overlayIntro.style.display = 'none';
  
  // Mantém blur ativo
  blurEstoque.style.display = 'block';

  // Pega posição do botão "Ver histórico 6 meses"
  const btnHistorico = document.getElementById('btnHistorico');
  const rect = btnHistorico.getBoundingClientRect();
  
  // Posiciona overlay2 próximo aos botões
  overlayAcoes.style.position = 'absolute';
  overlayAcoes.style.top = `${rect.bottom + window.scrollY + 10}px`;
  overlayAcoes.style.left = `${rect.left + window.scrollX}px`;
  overlayAcoes.style.display = 'flex';
  overlayAcoes.style.zIndex = '10001';

  // Adiciona classe para botões ficarem acima do blur
  btnHistorico.classList.add('fora-do-blur-estoque');
  document.getElementById('btnGiro').classList.add('fora-do-blur-estoque');
});

// Fecha segunda overlay
btnCloseAcoes.addEventListener('click', () => {
  overlayAcoes.style.display = 'none';
  blurEstoque.style.display = 'none';

  // Remove classe dos botões
  document.getElementById('btnHistorico').classList.remove('fora-do-blur-estoque');
  document.getElementById('btnGiro').classList.remove('fora-do-blur-estoque');
});

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
