<?php
session_start();
include __DIR__ . '/../../conexao.php';
// Verifique a chave correta da sessão
if (!isset($_SESSION['loja_id']) || ($_SESSION['tipo_login'] ?? '') !== 'empresa') {
    echo json_encode(["error" => "Loja não autenticada"]);
    exit; 
    
}



$lojaId = $_SESSION['loja_id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Visão Geral - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
  <link rel="stylesheet" href="../../assets/visaoGeral.css">
  <link rel="stylesheet" href="../../assets/sidebar .css">
</head>
<body>

<div class="content">
  <!-- Sidebar -->
<div class="sidebar">
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li class="active"><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
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
<style>
.btn-modern {
    display: inline-block;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(90deg, #1a1b1bff, #000000ff);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    margin-top: auto; /* empurra o botão para o bottom do card */
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>

  <!-- Dashboard Cards -->
  <div class="dashboard dashboard-custom">
    <div class="card card-valorEstoque">
      <h3>Valor do Estoque Atual</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/valorTotalEstoque.php'; ?>
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card card-custoEstoque">
      <h3>Custo do Estoque Atual</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/custoTotalEstoque.php'; ?>
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card card-margemLucro">
      <h3>Margem de Lucro Média</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/margemMediaProdutos.php'; ?>
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card card-produtosMaisVendidos" style="grid-column: span 2;">
      <h3>Produtos Mais Vendidos</h3>
      <div id="produtosMaisVendidos">
        <?php include '../../api/produtosMaisVendidos.php'; ?>
      </div>
    </div>
    <div class="card card-anomalias" id="cardAnomalias" style="grid-column: span 2.5; min-height: 300px; height: 100%; display: flex; flex-direction: column; justify-content: flex-start; align-items: stretch;">
      <h3 style="margin-bottom: 2px;">Anomalias de Vendas</h3>
      <div style="font-size:13px;color:#ff9900;margin-bottom:10px;">Detecta dias com vendas muito acima ou abaixo do normal</div>
      <div id="anomaliasVendas" style="flex:1;min-height:120px;max-height:320px;overflow-y:auto;position:relative;background:transparent;"></div>
      <button class="btn-modern" onclick="executarIA()" id="btnExecutarIA" style="align-self:flex-end;margin-top:12px;">Executar IA de Anomalias</button>
    </div>
    <div class="card card-reabastecimento" id="cardReabastecimento" style="grid-column: span 2.5; min-height: 300px; height: 100%; display: flex; flex-direction: column; justify-content: flex-start; align-items: stretch;">
      <h3 style="margin-bottom: 2px;">Sugestão de Reabastecimento</h3>
      <div style="font-size:13px;color:#ff9900;margin-bottom:10px;">Baseado em previsão de vendas e giro dos produtos</div>
      <div id="reabastecimentoLista" style="flex:1;min-height:120px;max-height:320px;overflow-y:auto;position:relative;background:transparent;"></div>
      <button class="btn-modern" onclick="executarReabastecimento()" id="btnExecutarReabastecimento" style="align-self:flex-end;margin-top:12px;">Executar IA de Reabastecimento</button>
    </div>
<style>
/* Card de reabastecimento ocupa 2 colunas no grid */
.card-reabastecimento {
  grid-column: span 2;
  min-height: 320px;
  background: #181818;
  border: 1.5px solid #ff9900;
  box-shadow: 0 4px 24px rgba(255,153,0,0.10);
  position: relative;
}
#reabastecimentoLista ul {
  background: #232526;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(255,153,0,0.07);
  border: 1px solid #2a2a2a;
  margin: 0;
  padding: 0;
}
#reabastecimentoLista li {
  border-bottom: 1px solid #2a2a2a;
  padding: 16px 22px 12px 22px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: transparent;
  border-radius: 10px;
  margin: 0;
  transition: background 0.2s;
}
#reabastecimentoLista li:last-child {
  border-bottom: none;
}
#reabastecimentoLista span {
  word-break: break-word;
}
.reabastecimento-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100px;
}
.reabastecimento-erro {
  color: #ff6b6b;
  text-align: center;
  margin: 20px 0;
}
.reabastecimento-vazio {
  color: #adb5bd;
  text-align: center;
  margin: 20px 0;
}
@media (max-width: 900px) {
  .card-reabastecimento { grid-column: span 1; }
}
</style>
<style>
/* Card de anomalias igual ao de reabastecimento */
.card-anomalias {
  grid-column: span 2;
  min-height: 320px;
  background: #181818;
  border: 1.5px solid #ff9900;
  box-shadow: 0 4px 24px rgba(255,153,0,0.10);
  position: relative;
}
#anomaliasVendas ul {
  background: #232526;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(255,153,0,0.07);
  border: 1px solid #2a2a2a;
  margin: 0;
  padding: 0;
}
#anomaliasVendas li {
  border-bottom: 1px solid #2a2a2a;
  padding: 16px 22px 12px 22px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: transparent;
  border-radius: 10px;
  margin: 0;
  transition: background 0.2s;
}
#anomaliasVendas li:last-child {
  border-bottom: none;
}
#anomaliasVendas span {
  word-break: break-word;
}
.anomalias-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100px;
}
.anomalias-erro {
  color: #ff6b6b;
  text-align: center;
  margin: 20px 0;
}
.anomalias-vazio {
  color: #adb5bd;
  text-align: center;
  margin: 20px 0;
}
@media (max-width: 900px) {
  .card-anomalias { grid-column: span 1; }
}
</style>
  <style>
  /* Estilo extra para lista de anomalias */
  #anomaliasLista ul {
    background: linear-gradient(90deg, #f7f8fa 60%, #e9ecef 100%);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(30,32,37,0.07);
    border: 1px solid #e0e0e0;
  }
  #anomaliasLista li {
    border-bottom: 1px solid #e0e0e0;
    padding: 12px 18px 10px 18px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    background: transparent;
    border-radius: 10px;
    margin: 4px 0;
  }
  #anomaliasLista li:last-child {
    border-bottom: none;
  }
  #anomaliasLista span {
    word-break: break-word;
  }
  </style>
    </div>
    <button class="btn-modern" onclick="executarIA()" id="btnExecutarIA">Executar IA</button>
  </div>

</div>

<!-- Botão de dica flutuante -->
<button id="dica-btn-flutuante" title="Dica rápida">
    <i class="fa-solid fa-lightbulb">?</i>
</button>

<!-- Overlay 1: Dica inicial -->
<div id="dica-overlay-1" style="display:none;">
    <div class="dica-blur-bg"></div>
    <div class="dica-card" id="dica-card-1">
        <h3>Dica rápida</h3>
        <p>Esta página mostra um resumo do seu estoque, vendas, anomalias e sugestões de reabastecimento.</p>
        <button id="dica-avancar-1">Avançar</button>
    </div>
</div>

<!-- Overlay 2: Dica sobre IA de Anomalias -->
<div id="dica-overlay-2" style="display:none;">
    <div class="dica-blur-bg"></div>
    <div class="dica-card" id="dica-card-2">
        <h3>IA de Anomalias</h3>
        <p>O botão <b>Executar IA de Anomalias</b> analisa suas vendas e destaca dias fora do padrão. Use para identificar oportunidades ou problemas rapidamente.</p>
        <button id="dica-fechar-2">Fechar</button>
    </div>
</div>

<style>
/* Botão flutuante */
#dica-btn-flutuante {
  position: absolute;
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
  z-index: 101;
  display: flex;
  align-items: center;
  justify-content: center;
}
#dica-btn-flutuante:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.28);
}

/* Overlay 1 */
#dica-overlay-1 {
    position: fixed;
    right: 90px;
    bottom: 90px;
    z-index: 1300;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    pointer-events: none;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
}
#dica-overlay-1 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-1 .dica-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 320px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    margin-bottom: 10px;
    z-index: 2;
}
#dica-overlay-1 .dica-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
}

/* Overlay 2 */
#dica-overlay-2 {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    z-index: 1400;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
}
#dica-overlay-2 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-2 .dica-card {
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
#dica-overlay-1 .dica-card h3 {
    font-size: 1.1rem;
    margin-bottom: 15px;
}
#dica-overlay-2 .dica-card h3 {
    font-size: 1.1rem;
    margin-bottom: 15px;
}
#dica-overlay-2 .dica-card p {
    font-size: 15px;
    margin-bottom: 18px;
}
#dica-overlay-2 .dica-card button {
    margin-top: 12px;
    background: #ff6600;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
}

/* Destaca botão IA de Anomalias */
#dica-overlay-2.active #btnExecutarIA {
    position: relative !important;
    z-index: 2500 !important;
    box-shadow: 0 0 0 5px #fff, 0 0 0 10px #ff6600;
    outline: 2px solid #ff6600;
    transition: box-shadow 0.2s;
    pointer-events: auto !important;
    filter: none !important;
}

/* Garante que outros elementos fiquem borrados */
#dica-overlay-2.active > *:not(.dica-card):not(.dica-blur-bg):not(#btnExecutarIA) {
    filter: blur(2px);
    pointer-events: none;
}
</style>

<script>
  const lojaId = <?= $lojaId ?>;


//produtos mais vendidos

async function loadProdutosMaisVendidos() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtosMaisVendidos.php?loja_id=${lojaId}`).then(r => r.json());

    const produtosDiv = document.getElementById("produtosMaisVendidos");
    produtosDiv.innerHTML = '';

    if (data.length === 0) {
      produtosDiv.innerHTML = '<p>Nenhum produto encontrado</p>';
      return;
    }

    const table = document.createElement('table');
    table.setAttribute('border', '1');
    table.setAttribute('cellpadding', '10');
    table.setAttribute('cellspacing', '0');

    const headerRow = document.createElement('tr');
    headerRow.innerHTML = '<th>Produto</th><th>Total Vendido</th>';
    table.appendChild(headerRow);

    data.forEach(d => {
      const row = document.createElement('tr');
      row.innerHTML = `<td>${d.produto}</td><td>${d.total_vendido}</td>`;
      table.appendChild(row);
    });

    produtosDiv.appendChild(table);
  } catch (err) {
    console.error("Erro ao carregar produtos mais vendidos:", err);
  }
}




// Anomalias de Vendas - formato igual ao card de reabastecimento
async function loadAnomalias() {
  const div = document.getElementById("anomaliasVendas");
  div.innerHTML = `<div class='anomalias-spinner'><svg width='40' height='40' viewBox='0 0 40 40' fill='none'><circle cx='20' cy='20' r='16' stroke='#ff9900' stroke-width='4' opacity='0.18'/><path d='M36 20a16 16 0 0 1-16 16' stroke='#ff9900' stroke-width='4'><animateTransform attributeName='transform' type='rotate' from='0 20 20' to='360 20 20' dur='1s' repeatCount='indefinite'/></path></svg></div>`;
  try {
    const data = await fetch(`/DECKLOGISTIC/api/anomalias.php?loja_id=${lojaId}`).then(r => r.json());
    if (!data || data.length === 0) {
      div.innerHTML = `<div class='anomalias-vazio'>Nenhuma anomalia detectada nos últimos dias.</div>`;
      return;
    }
    const ul = document.createElement('ul');
    data.forEach(a => {
      const li = document.createElement('li');
      li.innerHTML = `
        <span style='font-weight:600;color:#ff9900;font-size:15px;'>${formatarData(a.data_ocorrencia)}</span>
        <span style='color:#fff;font-size:13px;'>${explicacaoAnomalia(a.detalhe, a.score)}</span>
        <span style='color:#adb5bd;font-size:12px;'>Score: ${a.score.toFixed(2)}</span>
      `;
      ul.appendChild(li);
    });
    div.innerHTML = '';
    div.appendChild(ul);
  } catch (err) {
    div.innerHTML = `<div class='anomalias-erro'>Erro ao carregar anomalias</div>`;
    console.error("Erro ao carregar anomalias:", err);
  }
}

function formatarData(data) {
  // yyyy-mm-dd para dd/mm/yyyy
  if (!data) return '';
  const d = new Date(data);
  if (isNaN(d)) return data;
  return d.toLocaleDateString('pt-BR');
}

function explicacaoAnomalia(detalhe, score) {
  if (score > 0) return `Venda muito acima do esperado. (${detalhe})`;
  if (score < 0) return `Venda muito abaixo do esperado. (${detalhe})`;
  return detalhe;
}




// Popup estilizado para feedback da IA
function showIAPopup(msg, success=true) {
  let popup = document.getElementById('ia-popup');
  if (!popup) {
    popup = document.createElement('div');
    popup.id = 'ia-popup';
    popup.style.position = 'fixed';
    popup.style.top = '50%';
    popup.style.left = '50%';
    popup.style.transform = 'translate(-50%, -50%)';
    popup.style.background = success ? 'linear-gradient(120deg, #232526 60%, #232526 120%)' : '#2d1a1a';
    popup.style.color = '#fff';
    popup.style.padding = '32px 28px 22px 28px';
    popup.style.borderRadius = '16px';
    popup.style.boxShadow = '0 8px 32px rgba(0,0,0,0.35)';
    popup.style.zIndex = '9999';
    popup.style.fontSize = '1.1rem';
    popup.style.textAlign = 'center';
    popup.style.maxWidth = '90vw';
    popup.style.minWidth = '260px';
    popup.style.fontWeight = '500';
    popup.style.letterSpacing = '0.2px';
    popup.innerHTML = `<span id="ia-popup-msg">${msg}</span><br><button id="close-ia-popup" style="margin-top:18px;padding:8px 22px;border:none;border-radius:8px;background:#ff9900;color:#fff;font-weight:600;font-size:1rem;cursor:pointer;box-shadow:0 2px 8px #ff990033;transition:background 0.2s;">OK</button>`;
    document.body.appendChild(popup);
    document.getElementById('close-ia-popup').onclick = () => popup.remove();
  } else {
    document.getElementById('ia-popup-msg').innerHTML = msg;
    popup.style.display = 'block';
  }
}

async function executarIA() {
  const btn = document.getElementById("btnExecutarIA");
  btn.disabled = true;
  btn.textContent = "Executando...";
  // Mostra spinner no card de anomalias
  const div = document.getElementById("anomaliasVendas");
  div.innerHTML = `<div class='anomalias-spinner'><svg width='40' height='40' viewBox='0 0 40 40' fill='none'><circle cx='20' cy='20' r='16' stroke='#ff9900' stroke-width='4' opacity='0.18'/><path d='M36 20a16 16 0 0 1-16 16' stroke='#ff9900' stroke-width='4'><animateTransform attributeName='transform' type='rotate' from='0 20 20' to='360 20 20' dur='1s' repeatCount='indefinite'/></path></svg></div>`;
  try {
    const res = await fetch(`/DECKLOGISTIC/api/run_anomalias.php`);
    const data = await res.json();
    setTimeout(() => {
      loadAnomalias();
      btn.disabled = false;
      btn.textContent = "Executar IA de Anomalias";
    }, 800); // delay para UX
    // Mensagem amigável e simples
    let msgPopup = '';
    if (data && data.output) {
      if (data.output.includes('anomalia') && data.output.match(/\d+ anomalia/)) {
        const qtd = data.output.match(/(\d+) anomalia/)[1];
        if (parseInt(qtd) === 0) {
          msgPopup = 'Nenhuma nova anomalia foi encontrada. Tudo normal!';
        } else {
          msgPopup = `Análise concluída!<br><b>${qtd}</b> nova(s) anomalia(s) registrada(s).`;
        }
      } else if (data.output.toLowerCase().includes('erro')) {
        msgPopup = 'Ocorreu um erro ao rodar a análise. Tente novamente.';
      } else {
        msgPopup = 'Análise concluída!';
      }
      showIAPopup(msgPopup, true);
    }
  } catch (e) {
    btn.disabled = false;
    btn.textContent = "Executar IA de Anomalias";
    showIAPopup('Erro ao executar a IA. Tente novamente.', false);
  }
}



// Clique no card de anomalias carrega as anomalias
document.getElementById("cardAnomalias").addEventListener("click", function(e) {
  if (e.target && e.target.id === "btnExecutarIA") return;
  loadAnomalias();
});

// Carrega ao abrir a página
loadAnomalias();
loadProdutosMaisVendidos();


async function loadReabastecimento() {
  const lista = document.getElementById("reabastecimentoLista");
  lista.innerHTML = `<div class='reabastecimento-spinner'><svg width='40' height='40' viewBox='0 0 40 40' fill='none'><circle cx='20' cy='20' r='16' stroke='#ff9900' stroke-width='4' opacity='0.18'/><path d='M36 20a16 16 0 0 1-16 16' stroke='#ff9900' stroke-width='4'><animateTransform attributeName='transform' type='rotate' from='0 20 20' to='360 20 20' dur='1s' repeatCount='indefinite'/></path></svg></div>`;
  try {
    const res = await fetch('/DECKLOGISTIC/api/run_reabastecimento.php');
    const data = await res.json();
    if (!data || !data.recomendacoes) {
      lista.innerHTML = `<div class='reabastecimento-erro'>Erro ao buscar recomendações.</div>`;
      return;
    }
    if (data.recomendacoes.length === 0) {
      lista.innerHTML = `<div class='reabastecimento-vazio'>Nenhuma sugestão de reabastecimento encontrada.<br>Todos os produtos estão com estoque adequado ou sem dados suficientes.</div>`;
      return;
    }
    const ul = document.createElement('ul');
    data.recomendacoes.forEach(r => {
      const li = document.createElement('li');
      li.innerHTML = `
        <span style='font-weight:600;color:#ff9900;font-size:15px;'>${r.nome ? r.nome : 'Produto ' + r.produto_id}</span>
        <span style='color:#fff;font-size:13px;'>Sugestão: <b>${r.quantidade}</b> unidade(s)</span>
        <span style='color:#adb5bd;font-size:12px;'>Demanda prevista: ${r.demanda}</span>
      `;
      ul.appendChild(li);
    });
    lista.innerHTML = '';
    lista.appendChild(ul);
  } catch (e) {
    lista.innerHTML = `<div class='reabastecimento-erro'>Erro ao buscar recomendações.</div>`;
  }
}

async function executarReabastecimento() {
  const btn = document.getElementById("btnExecutarReabastecimento");
  btn.disabled = true;
  btn.textContent = "Executando...";
  await loadReabastecimento();
  btn.disabled = false;
  btn.textContent = "Executar IA de Reabastecimento";
}
// Carrega recomendações ao abrir a página
loadReabastecimento();



// Dica flutuante
const dicaBtn = document.getElementById('dica-btn-flutuante');
const dicaOverlay1 = document.getElementById('dica-overlay-1');
const dicaAvancar1 = document.getElementById('dica-avancar-1');
const dicaOverlay2 = document.getElementById('dica-overlay-2');
const dicaCard2 = document.getElementById('dica-card-2');
const dicaFechar2 = document.getElementById('dica-fechar-2');
const btnExecutarIA = document.getElementById('btnExecutarIA');

dicaBtn.addEventListener('click', function() {
    dicaOverlay1.style.display = 'flex';
});

dicaAvancar1.addEventListener('click', function() {
    dicaOverlay1.style.display = 'none';

    // Posiciona o card próximo ao botão Executar IA de Anomalias
    const rectIA = btnExecutarIA.getBoundingClientRect();
    const top = rectIA.top + window.scrollY - 70;
    const left = rectIA.right + window.scrollX + 30;

    dicaCard2.style.top = top + 'px';
    dicaCard2.style.left = left + 'px';

    dicaOverlay2.style.display = 'flex';
    dicaOverlay2.classList.add('active');

    btnExecutarIA.style.zIndex = 2500;
    btnExecutarIA.style.pointerEvents = 'auto';
});

dicaFechar2.addEventListener('click', function() {
    dicaOverlay2.style.display = 'none';
    dicaOverlay2.classList.remove('active');
    btnExecutarIA.style.zIndex = '';
    btnExecutarIA.style.pointerEvents = '';
});

// Fecha overlays ao clicar fora do card
document.addEventListener('click', function(e) {
    if (dicaOverlay1.style.display === 'flex' && !e.target.closest('#dica-card-1') && !e.target.closest('#dica-btn-flutuante')) {
        dicaOverlay1.style.display = 'none';
    }
    if (dicaOverlay2.style.display === 'flex' && !e.target.closest('#dica-card-2')) {
        dicaOverlay2.style.display = 'none';
        dicaOverlay2.classList.remove('active');
        btnExecutarIA.style.zIndex = '';
    }
});

// Impede propagação do clique dentro dos cards
document.querySelectorAll('.dica-card').forEach(card => {
    card.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

</body>
</html>
