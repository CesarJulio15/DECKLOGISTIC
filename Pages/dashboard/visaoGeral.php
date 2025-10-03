<?php
session_start();
include __DIR__ . '/../../conexao.php';
include __DIR__ . '/../../header.php';
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

</div>

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



</script>

</body>
</html>
