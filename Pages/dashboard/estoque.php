<?php
session_start();
include __DIR__ . '/../../conexao.php';
require_once __DIR__ . '/../../session_check.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado. Faça login primeiro.");
}

// Sempre pega o ID da loja
$lojaId = $_SESSION['tipo_login'] === 'empresa'
    ? $_SESSION['loja_id']     // empresa logada
    : $_SESSION['loja_id'];    // funcionário logado
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
  <link rel="icon" href="/projetos/2025_dev/deckers/img/logoDecklogistic.webp" type="image/x-icon" />
  <link rel="stylesheet" href="/projetos/2025_dev/deckers/assets/estoque.css">
  <link rel="stylesheet" href="/projetos/2025_dev/deckers/assets/sidebar.css">
</head>

<noscript>
    <meta http-equiv="refresh" content="0; URL=/projetos/2025_dev/deckers/no-javascript.php">
</noscript>

<!-- BLOQUEIO MOBILE -->
<div id="mobile-lock">
  <div class="mobile-container">
    <img src="/projetos/2025_dev/deckers/img/logoDecklogistic.webp" alt="Logo" class="mobile-logo">
    <h1>Versão Desktop Necessária</h1>
    <p>Essa área do sistema foi projetada para telas grandes.  
    Acesse pelo seu computador para visualizar o painel financeiro completo.</p>
    <a href="/projetos/2025_dev/deckers/Pages/auth/config.php" class="mobile-btn">Acessar Configurações</a>
    <div class="mobile-footer">
      <p>© Decklogistic 2025 — Sistema Financeiro Empresarial</p>
    </div>
  </div>
</div>

<body>

<div class="content">

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo-area">
      <img src="/projetos/2025_dev/deckers/img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="/projetos/2025_dev/deckers/Pages/dashboard/financas.php"><span><img src="/projetos/2025_dev/deckers/img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
          <li class="active"><a href="/projetos/2025_dev/deckers/Pages/dashboard/estoque.php"><span><img src="/projetos/2025_dev/deckers/img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
        </ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="/projetos/2025_dev/deckers/Pages/dashboard/visaoGeral.php"><span><img src="/projetos/2025_dev/deckers/img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="/projetos/2025_dev/deckers/Pages/dashboard/tabelas/produtos.php"><span><img src="/projetos/2025_dev/deckers/img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="/projetos/2025_dev/deckers/Pages/dashboard/operacoes.php"><span><img src="/projetos/2025_dev/deckers/img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="/projetos/2025_dev/deckers/Pages/auth/config.php"><span><img src="/projetos/2025_dev/deckers/img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="/projetos/2025_dev/deckers/Pages/auth/dicas.php"><span><img src="/projetos/2025_dev/deckers/img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
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
      <button id="btnGiro" class="btn-modern" onclick="window.location.href='/projetos/2025_dev/deckers/Pages/dashboard/giroEstoque.php'">
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

<!-- =================== JAVASCRIPT =================== -->
<script>
const lojaId = <?= $lojaId ?>;

// ---------- Total Estoque ----------
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`/projetos/2025_dev/deckers/api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    document.querySelector("#estoque").textContent = parseFloat(data.total || 0).toFixed();
  } catch (e) { console.error(e); }
}

// ---------- Produtos em Falta ----------
async function loadProdutosFalta() {
  const tabela = document.querySelector("#tabelaProdutosFalta");
  try {
    const data = await fetch(`/projetos/2025_dev/deckers/api/produtos_falta.php?loja_id=${lojaId}`).then(r => r.json());
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
    const data = await fetch(`/projetos/2025_dev/deckers/api/produtos_parados.php?loja_id=${lojaId}`).then(r => r.json());
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
    const response = await fetch(`/projetos/2025_dev/deckers/api/entrada_saida.php?loja_id=${lojaId}`);
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
    const data = await fetch(`/projetos/2025_dev/deckers/api/historico_estoque_6meses.php?loja_id=${lojaId}`).then(r => r.json());
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
    const url = `/projetos/2025_dev/deckers/api/produtos_reabastecidos.php?loja_id=${lojaId}&pesquisa=${encodeURIComponent(pesquisa)}&filtro=${filtro}`;
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

// ---------- Inicialização ----------
document.addEventListener("DOMContentLoaded", () => {
  loadEstoqueTotal();
  loadProdutosFalta();
  loadEstoqueProdutos();
  loadEntradaSaidaProdutos();
  loadProdutosReabastecidos();
});
</script>
<style>
    /* --- BLOQUEIO MOBILE AJUSTADO --- */
#mobile-lock {
  display: none;
}

@media (max-width: 1000px) {
  body > *:not(#mobile-lock) {
    display: none !important;
  }

  #mobile-lock {
    display: flex !important;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    height: 100vh;
    width: 100vw;
    background: radial-gradient(circle at center, #0f0f0f 0%, #000 100%);
    color: #fff;
    text-align: center;
    padding: 30px;
    animation: fadeIn 0.6s ease-out forwards;
  }

  .mobile-container {
    width: 80%;
    max-width: 750px;
    min-height: 50vh; 
    background: rgba(30, 30, 30, 0.85);
    padding: 40px 24px;
    border-radius: 18px;
    backdrop-filter: blur(12px);
    box-shadow: 0 0 25px rgba(255, 102, 0, 0.25);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin-top: 5vh;   /* mais espaço acima */
    margin-bottom: 5vh;/* mais espaço abaixo */
    animation: float 3s ease-in-out infinite;
  }

  .mobile-logo {
    width: 160px;
    margin-bottom: 20px;
    filter: drop-shadow(0 0 10px #ff6600);
  }

  .mobile-container h1 {
    font-size: 2.8rem;
    margin-bottom: 14px;
    font-weight: 700;
    letter-spacing: 0.6px;
  }

  .mobile-container p {
    font-size: 2rem;
    color: #ccc;
    line-height: 1.5;
    margin-bottom: 28px;
  }

  .mobile-btn {
    display: inline-block;
    background: linear-gradient(90deg, #ff6600, #ff8533);
    color: #fff;
    text-decoration: none;
    padding: 18px 26px;
    border-radius: 10px;
    font-size: 1.4rem;
    transition: 0.3s ease;
  }

  .mobile-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(255, 102, 0, 0.3);
  }

  .mobile-footer {
    margin-top: 26px;
    font-size: 0.4rem;
    color: #777;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
  }

  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
  }
}
</style>
<!-- Blur atrás das overlays -->
<div id="overlay-blur" class="full-screen-blur" style="display:none;"></div>

<!-- Overlay 1: canto inferior direito -->
<div id="overlay-estoque" style="display:none;">
  <div class="welcome-card">
    <h2>Estoque</h2>
    <p>Essa é a área de estoque da sua empresa, aqui você vai gerir e analisar detalhadamente o controle de produtos e movimentações.</p>
    <button id="closeOverlay1">Próximo</button>
  </div>
</div>

<!-- Overlay 2: próxima aos botões de histórico e giro -->
<div id="overlay-botoes" class="welcome-overlay" style="display:none;">
  <div class="welcome-card">
    <h2>Análises Detalhadas</h2>
    <p>Aqui você pode visualizar o histórico de estoque dos últimos 6 meses e analisar o giro de estoque dos seus produtos.</p>
    <button id="closeOverlay2">Concluir</button>
  </div>
</div>

<style>
/* Classe para botões ficarem acima do blur */
.fora-do-blur {
  position: relative;
  z-index: 10002 !important;
}

.fora-do-blur:hover {
  box-shadow: none !important;
  transform: none !important;
  background: inherit !important;
  color: inherit !important;
  border: none !important;
  cursor: pointer !important;
}

/* Blur que cobre toda a tela */
#overlay-blur {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  z-index: 9999;
}

/* Overlay de estoque */
#overlay-estoque {
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

/* Overlay botões */
#overlay-botoes {
  display: none;
  position: absolute;
  z-index: 10001;
  justify-content: center;
  align-items: center;
}

/* Cards das overlays */
#overlay-estoque .welcome-card,
#overlay-botoes .welcome-card {
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

#overlay-estoque .welcome-card h2,
#overlay-botoes .welcome-card h2 {
  font-size: 1.1rem;
  margin-bottom: 8px;
}

#overlay-estoque .welcome-card p,
#overlay-botoes .welcome-card p {
  font-size: 15px;
  margin-bottom: 18px;
}

#overlay-estoque .welcome-card button,
#overlay-botoes .welcome-card button {
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
#help-btn {
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
}
</style>

<button id="help-btn">?</button>

<script>
const helpBtn = document.getElementById('help-btn');
const overlay1 = document.getElementById('overlay-estoque');
const overlay2 = document.getElementById('overlay-botoes');
const blur = document.getElementById('overlay-blur');
const btnClose1 = document.getElementById('closeOverlay1');
const btnClose2 = document.getElementById('closeOverlay2');

helpBtn.addEventListener('click', () => {
  overlay1.style.display = 'flex';
  blur.style.display = 'block';
});

btnClose1.addEventListener('click', () => {
  overlay1.style.display = 'none';
  
  // Mantém blur ativo
  blur.style.display = 'block';

  // Posiciona overlay2 próxima ao botão histórico
  const btnHistorico = document.getElementById('btnHistorico');
  const rect = btnHistorico.getBoundingClientRect();
  
  overlay2.style.position = 'absolute';
  overlay2.style.top = `${rect.bottom + window.scrollY + 10}px`;
  overlay2.style.left = `${rect.left + window.scrollX}px`;
  overlay2.style.display = 'flex';
  overlay2.style.zIndex = '10001';

  // Adiciona classe para os botões ficarem acima do blur
  document.getElementById('btnHistorico').classList.add('fora-do-blur');
  document.getElementById('btnGiro').classList.add('fora-do-blur');
});

btnClose2.addEventListener('click', () => {
  overlay2.style.display = 'none';
  blur.style.display = 'none';

  // Remove classe dos botões
  document.getElementById('btnHistorico').classList.remove('fora-do-blur');
  document.getElementById('btnGiro').classList.remove('fora-do-blur');
});
</script>

</body>
</html>

