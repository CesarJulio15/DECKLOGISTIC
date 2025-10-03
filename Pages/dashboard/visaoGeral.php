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
    <li ><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li class="active"><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
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
  <div class="dashboard">
    <div class="card">
          <h3>Valor do Estoque Atual</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/valorTotalEstoque.php'; ?> <!-- Inclui o cálculo do valor do estoque -->
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>
      
    
    <div class="card">
          <h3>Custo do Estoque Atual</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/custoTotalEstoque.php'; ?> <!-- Inclui o cálculo do valor do estoque -->
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>

     <div class="card">
          <h3>Margem de Lucro Média</h3>
      <div id="totalEstoque" class="value">
        <?php include '../../api/margemMediaProdutos.php'; ?> <!-- Inclui o cálculo do valor do estoque -->
      </div>
      <div id="chartTotal" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card">
    <h3>Produtos Mais Vendidos</h3>
    <div id="produtosMaisVendidos">
        <?php include '../../api/produtosMaisVendidos.php'; ?> <!-- Inclui o cálculo dos produtos mais vendidos -->
    </div>
  </div>


  <div class="card" id="cardAnomalias" style="overflow:hidden;">
    <h3>Anomalias de Vendas</h3>

    <div id="anomaliasVendas" style="min-height:90px;max-height:220px;overflow-y:auto;transition:background 0.2s;position:relative;">
        <div id="spinnerAnomalia" style="display:none;justify-content:center;align-items:center;height:80px;position:absolute;top:0;left:0;width:100%;background:#fff;z-index:2;">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="16" stroke="#1a1b1b" stroke-width="4" opacity="0.2"/><path d="M36 20a16 16 0 0 1-16 16" stroke="#1a1b1b" stroke-width="4"><animateTransform attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="1s" repeatCount="indefinite"/></path></svg>
      </div>
      <p id="anomaliaMsg">Clique para ver as anomalias...</p>
      <div id="anomaliasLista"></div>
        </div>
        <button class="btn-modern" onclick="executarIA()" id="btnExecutarIA">Executar</button>
      </div>
    </div>
  </div>
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



// Anomalias de Vendas - spinner fixo, lista separada
async function loadAnomalias() {
  const div = document.getElementById("anomaliasVendas");
  const spinner = document.getElementById("spinnerAnomalia");
  const msg = document.getElementById("anomaliaMsg");
  const lista = document.getElementById("anomaliasLista");

  spinner.style.display = "flex";
  msg.style.display = "none";
  lista.innerHTML = '';

  // fundo do container também escuro
  div.style.background = "#121212";
  div.style.borderRadius = "14px";
  div.style.padding = "12px";
  div.style.boxShadow = "0 4px 16px rgba(0,0,0,0.6)";

  try {
    const data = await fetch(`/DECKLOGISTIC/api/anomalias.php?loja_id=${lojaId}`).then(r => r.json());
    spinner.style.display = "none";

    if (!data || data.length === 0) {
      msg.style.display = "block";
      msg.style.color = "#ccc";
      msg.textContent = "Nenhuma anomalia detectada nos últimos dias.";
      lista.innerHTML = '';
      return;
    }

    msg.style.display = "none";

    // Layout dark e moderno
    const list = document.createElement('ul');
    list.style.listStyle = 'none';
    list.style.padding = '8px 0';
    list.style.margin = '0';
    list.style.background = '#1a1a1a'; // leve contraste fosco
    list.style.borderRadius = '12px';
    list.style.border = '1px solid #2a2a2a';
    list.style.overflow = 'hidden';

    data.forEach((a, index) => {
      const li = document.createElement('li');
      li.style.padding = '16px 22px';
      li.style.display = 'flex';
      li.style.flexDirection = 'column';
      li.style.gap = '6px';
      li.style.transition = 'background 0.25s ease, transform 0.15s ease';

      // efeito hover elegante
      li.addEventListener('mouseenter', () => {
        li.style.background = '#2a2a2a';
        li.style.transform = 'translateX(4px)';
      });
      li.addEventListener('mouseleave', () => {
        li.style.background = 'transparent';
        li.style.transform = 'translateX(0)';
      });

      // divisor fosco
      if (index < data.length - 1) {
        li.style.borderBottom = '1px solid #2f2f2f';
      }

      li.innerHTML = `
        <span style="font-weight:600;color:#f8f9fa;font-size:15px;letter-spacing:0.3px;">
          ${formatarData(a.data_ocorrencia)}
        </span>
        <span style="color:#ff6b6b;font-size:13px;font-weight:500;line-height:1.5;">
          ${explicacaoAnomalia(a.detalhe, a.score)}
        </span>
        <span style="color:#adb5bd;font-size:12px;">
        </span>
      `;

      list.appendChild(li);
    });

    lista.appendChild(list);

  } catch (err) {
    spinner.style.display = "none";
    msg.style.display = "block";
    msg.style.color = "#ff6b6b";
    msg.textContent = "Erro ao carregar anomalias";
    lista.innerHTML = '';
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
  const spinner = document.getElementById("spinnerAnomalia");
  const msg = document.getElementById("anomaliaMsg");
  const lista = document.getElementById("anomaliasLista");
  spinner.style.display = "flex";
  msg.style.display = "none";
  lista.innerHTML = '';
  try {
    const res = await fetch(`/DECKLOGISTIC/api/run_anomalias.php`);
    const data = await res.json();
    setTimeout(() => {
      loadAnomalias();
      btn.disabled = false;
      btn.textContent = "Executar IA";
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
    btn.textContent = "Executar IA";
    showIAPopup('Erro ao executar a IA. Tente novamente.', false);
  }
}


// Clique no card de anomalias carrega as anomalias
document.getElementById("cardAnomalias").addEventListener("click", function(e) {
  // Evita disparar ao clicar no botão
  if (e.target && e.target.id === "btnExecutarIA") return;
  loadAnomalias();
});

// Carrega ao abrir a página (pode deixar só a mensagem inicial)
// loadAnomalias();
loadProdutosMaisVendidos();






</script>

</body>
</html>
