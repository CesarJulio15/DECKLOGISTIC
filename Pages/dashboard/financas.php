<?php
session_start();
require_once __DIR__ . '/../../session_check.php';
$lojaId = $_SESSION['loja_id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Finanças - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon"/>
  <link rel="stylesheet" href="../../assets/financas.css">
  <link rel="stylesheet" href="../../assets/sidebar.css">
  
</head>

<!-- BLOQUEIO MOBILE -->
<div id="mobile-lock">
  <div class="mobile-container">
    <img src="../../img/logoDecklogistic.webp" alt="Logo" class="mobile-logo">
    <h1>Versão Desktop Necessária</h1>
    <p>Essa área do sistema foi projetada para telas grandes.  
    Acesse pelo seu computador para visualizar o painel financeiro completo.</p>
    <a href="../auth/config.php" class="mobile-btn">Acessar Configurações</a>
    <div class="mobile-footer">
      <p>© Decklogistic 2025 — Sistema Financeiro Empresarial</p>
    </div>
  </div>
</div>

<body>

<div class="sidebar">
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
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
    margin-top: auto;
}
.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>
<style>
/* Classe para botões ficarem acima do blur */
.fora-do-blur {
  position: relative;
  z-index: 10002 !important;
}
</style>

  <!-- Dashboard Cards -->
  <div class="dashboard">
    
    <div class="card">
      <h3>Lucro Bruto</h3>
      <div id="lucroBruto" class="value">R$ 0,00</div>
      <div id="chartBruto" style="height:60px; margin-top:10px;"></div>
      <button id="btnLucroBruto" class="btn-modern">Ver detalhes</button>
    </div>
    <div class="card">
      <h3>Lucro Líquido</h3>
      <div id="lucroLiquido" class="value">R$ 0,00</div>
      <div id="chartLiquido" style="height:60px; margin-top:10px;"></div>
      <button id="btnLucroLiquido" class="btn-modern">Ver detalhes</button>
    </div>
    <div class="card">
      <h3>Margem de Lucro</h3>
      <div id="margemLucro" class="value">0%</div>
      <div id="chartMargem" style="height:60px; margin-top:10px;"></div>
      <button id="btnLucroMargem" class="btn-modern">Ver detalhes</button>
    </div>
    <div class="card">
      <h3>Receita x Despesas</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
  </div>

  <div class="dashboard">
    <div class="card">
      <h3>Top 5 Maiores Despesas Recentes</h3>
      <div class="table-responsive">
        <table id="topDespesas">
          <thead>
            <tr>
              <th>Categoria</th>
              <th>Descrição</th>
              <th>Valor (R$)</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="4">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Custo Médio por Produto Vendido</h3>
      <div class="table-responsive">
        <table id="custoMedioProdutos">
          <thead>
            <tr>
              <th>Produto</th>
              <th>Custo Médio (R$)</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="2">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
  const lojaId = <?= $lojaId ?>;

  async function loadTopDespesas() {
    const data = await fetch(`/DECKLOGISTIC/api/top5_despesas.php?loja_id=${lojaId}`).then(r => r.json());
    const tbody = document.querySelector("#topDespesas tbody");
    tbody.innerHTML = '';

    if(data.length === 0){
      tbody.innerHTML = '<tr><td colspan="4">Nenhuma despesa encontrada</td></tr>';
      return;
    }

    data.forEach(d => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${d.categoria}</td>
        <td>${d.descricao}</td>
        <td>${parseFloat(d.valor).toFixed(2)}</td>
        <td>${d.data_transacao}</td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function loadCustoMedioProdutos() {
    // Corrige para garantir que o parâmetro loja_id é enviado
    const data = await fetch(`/DECKLOGISTIC/api/custo_medio_produto.php?loja_id=${lojaId}`).then(r => r.json());
    const tbody = document.querySelector("#custoMedioProdutos tbody");
    tbody.innerHTML = '';

    if(data.length === 0){
      tbody.innerHTML = '<tr><td colspan="2">Nenhum produto encontrado</td></tr>';
      return;
    }

    data.forEach(d => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${d.produto}</td>
        <td>${parseFloat(d.custo_medio).toFixed(2)}</td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function loadLucros() {
    try {
      const periodo = 'mes';
      const [bruto, liquido, margem] = await Promise.all([
        fetch(`/DECKLOGISTIC/api/lucro_bruto.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json()),
        fetch(`/DECKLOGISTIC/api/lucro_liquido.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json()),
        fetch(`/DECKLOGISTIC/api/margem_lucro.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json())
      ]);

      if (bruto.error || liquido.error || margem.error) {
        console.error("Erro API:", bruto.error || liquido.error || margem.error);
        return;
      }

      const lucroBrutoVal = parseFloat((bruto.total || bruto.lucro || 0).toString().replace(',', '.')).toFixed(2);
      const lucroLiquidoVal = parseFloat((liquido.total || liquido.lucro || 0).toString().replace(',', '.')).toFixed(2);
      const margemVal = parseFloat((margem.total || margem.percentual || 0)).toFixed(1);

      document.getElementById('lucroBruto').innerText = `R$ ${lucroBrutoVal}`;
      document.getElementById('lucroLiquido').innerText = `R$ ${lucroLiquidoVal}`;
      document.getElementById('margemLucro').innerText = `${margemVal}%`;

      // Corrige o gráfico de lucro bruto para exibir corretamente como os outros
      let brutoSeries = [];
      if (Array.isArray(bruto.series)) {
        brutoSeries = bruto.series.map(item => parseFloat(item.valor || item) || 0);
      } else if (Array.isArray(bruto.dados_receita)) {
        brutoSeries = bruto.dados_receita.map(v => parseFloat(v) || 0);
      }
      if (brutoSeries.length === 0) brutoSeries = [0,0,0,0,0,0];

      new ApexCharts(document.querySelector("#chartBruto"), {
        chart: { type: 'area', height: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth' },
        fill: { opacity: 0.3 },
        series: [{ data: brutoSeries }],
        colors: ['#10b981']
      }).render();

      const liquidoSeries = Array.isArray(liquido.series) ? liquido.series.map(item => parseFloat(item.valor || item) || 0) : [];
      const margemSeries = Array.isArray(margem.series) ? margem.series.map(item => parseFloat(item.valor || item) || 0) : [];

      new ApexCharts(document.querySelector("#chartLiquido"), {
        chart: { type: 'area', height: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth' },
        fill: { opacity: 0.3 },
        series: [{ data: liquidoSeries }],
        colors: ['#3b82f6']
      }).render();

      new ApexCharts(document.querySelector("#chartMargem"), {
        chart: { type: 'area', height: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth' },
        fill: { opacity: 0.3 },
        series: [{ data: margemSeries }],
        colors: ['#f59e0b']
      }).render();

    } catch (err) {
      console.error("Erro ao carregar lucros:", err);
    }
  }

  async function loadReceitaDespesa() {
    const data = await fetch(`/DECKLOGISTIC/api/receita_despesas.php?loja_id=${lojaId}`).then(r => r.json());
    const dias = [...new Set([...Object.keys(data.receita), ...Object.keys(data.despesa)])]
                  .map(d => new Date(d))
                  .sort((a,b)=>a-b)
                  .map(d=>d.toISOString().slice(0,10));

    const receita = dias.map(d => parseFloat(data.receita[d]||0));
    const despesa = dias.map(d => parseFloat(data.despesa[d]||0));

    new ApexCharts(document.querySelector("#chartReceitaDespesa"), {
      chart: { type: 'line', height: 300 },
      series: [
        { name: 'Receita', data: receita },
        { name: 'Despesa', data: despesa }
      ],
      xaxis: { categories: dias },
      stroke: { curve: 'smooth' },
      colors: ['#10b981', '#ef4444']
    }).render();
  }

  // Botões de redirecionamento
  document.getElementById('btnLucroLiquido').addEventListener('click', () => {
      window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/lucroL.php';
  });
  document.getElementById('btnLucroBruto').addEventListener('click', () => {
      window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/lucroB.php';
  });
  document.getElementById('btnLucroMargem').addEventListener('click', () => {
      window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/margem.php';
  });

  // Chamada inicial das funções
  loadLucros();
  loadReceitaDespesa();
  loadTopDespesas();
  loadCustoMedioProdutos();
</script>
<!-- Overlays de Boas-vindas -->
<!-- Overlay 1: cobre toda a tela -->
<!-- Blur atrás do overlay -->
<div id="overlay-blur" class="full-screen-blur" style="display:none;"></div>

<!-- Overlay 1: canto inferior direito -->
<div id="overlay-financas" style="display:none;">
  <div class="welcome-card">
       <h2>Finanças</h2>
    <p>Essa é a área de finanças da sua empresa, aqui você vai gerir e analisará detalhadamente o desempenho econômico da sua empresa.</p>
    <button id="closeOverlay1">Próximo</button>
  </div>
</div>

<!-- Overlay 2: próximo ao botão "Ver detalhes" -->
<div id="overlay-graficos" class="welcome-overlay" style="display:none;">
  <div class="welcome-card">
     <h2>Ver detalhes</h2>
    <p>Aqui você pode analisar os gráficos referentes a lucro bruto, lucro líquido e margem de lucro.</p>
    <button id="closeOverlay2">Próximo</button>
  </div>
</div>

<style>
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
    z-index: 9999; /* abaixo dos overlays */
}

/* Overlay de finanças */
#overlay-financas {
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
    background: transparent; /* não precisa do ::before */
}

/* Overlay gráfico */
#overlay-graficos {
    display: none;
    position: absolute; /* posicionado via JS */
    z-index: 10001;
    justify-content: center;
    align-items: center;
}

/* Overlay de finanças */
#welcome-overlay .welcome-card h2 {
    margin-bottom: 30px;
    font-size: 14px;
}


/* Card do overlay */
#overlay-financas .welcome-card {
    background: #000;            /* fundo preto do card */
    padding: 20px 30px;
    border-radius: 10px;
    max-width: 300px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    text-align: left;
    
    color: #fff;
}

/* Texto do card */
#overlay-financas .welcome-card p {
  margin-top:10px;
    font-size: 14px;
    margin-bottom: 15px;
}

/* Botão */
#overlay-financas .welcome-card button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    background: #ff6600;
    color: #fff;
    cursor: pointer;
}

/* Fundo borrado atrás do card */
#overlay-financas::before {
    content: '';
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: -1;  /* fica atrás do card */
}

/* Overlay de gráficos */
/* Card do overlay gráfico */
#overlay-graficos .welcome-card h2 {
    margin-bottom: 20px; /* espaço abaixo do título */
    font-size: 24px;
}
#welcome-overlay .welcome-card h2 {
    margin-bottom: 30px;
    font-size: 14px;
}

#welcome-overlay .welcome-card p {
    font-size: 14px;
    margin-top: 10px;
    margin-bottom: 15px;
}

/* Overlay gráfico */



#overlay-graficos .welcome-card {
    background: #000;
    padding: 20px 30px;
    border-radius: 10px;
    max-width: 300px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    text-align: left;
    color: #fff;
}

/* Botão do overlay gráfico */
#overlay-graficos .welcome-card button {
  margin-top: 15px;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    background: #ff6600;
    color: #fff;
    cursor: pointer;
}



/* Fundo borrado opcional (pode ser usado apenas se quiser blur no fundo do overlay2) */
#overlay-graficos::before {
    content: '';
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.0); /* transparente, pois blur já é controlado pelo overlay1 */
    z-index: -1;
}

/* Card das overlays igual produtos.php */
#welcome-overlay .welcome-card,
#overlay-financas .welcome-card,
#overlay-graficos .welcome-card {
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
#welcome-overlay .welcome-card h2,
#overlay-financas .welcome-card h2,
#overlay-graficos .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}
#welcome-overlay .welcome-card p,
#overlay-financas .welcome-card p,
#overlay-graficos .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}
#welcome-overlay .welcome-card button,
#overlay-financas .welcome-card button,
#overlay-graficos .welcome-card button {
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
    z-index: 9998; /* abaixo do blur (z-index: 9999), sempre atrás do blur */
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<button id="help-btn">?</button>

<script>
const helpBtn = document.getElementById('help-btn');
const overlay1 = document.getElementById('overlay-financas');
const overlay2 = document.getElementById('overlay-graficos');
const blur = document.getElementById('overlay-blur');
const btnClose1 = document.getElementById('closeOverlay1');
const btnClose2 = document.getElementById('closeOverlay2');

helpBtn.addEventListener('click', () => {
  overlay1.style.display = 'flex';
  blur.style.display = 'block';
});

btnClose1.addEventListener('click', () => {
  overlay1.style.display = 'none';
  
  // Ativa blur
  blur.style.display = 'block';

  // Pega posição do botão
  const btn = document.getElementById('btnLucroBruto');
  const rect = btn.getBoundingClientRect();

  // Posiciona overlay2 perto do botão
  overlay2.style.position = 'absolute';
  overlay2.style.top = `${rect.bottom + window.scrollY + 10}px`;
  overlay2.style.left = `${rect.left + window.scrollX}px`;
  overlay2.style.display = 'flex';
});


helpBtn.addEventListener('click', () => {
  overlay1.style.display = 'flex';
  blur.style.display = 'block';
});

btnClose1.addEventListener('click', () => {
  overlay1.style.display = 'none';
  
  // Mantém blur ativo
  blur.style.display = 'block';

  // Posiciona overlay2 próximo do botão
  const btn = document.getElementById('btnLucroBruto'); // você pode trocar pro outro botão se quiser
  const rect = btn.getBoundingClientRect();
  
  overlay2.style.position = 'absolute';
  overlay2.style.top = `${rect.bottom + window.scrollY + 10}px`;
  overlay2.style.left = `${rect.left + window.scrollX}px`;
  overlay2.style.display = 'flex';
  overlay2.style.zIndex = '10001';

  // Adiciona classe para os 3 botões ficarem acima do blur
  document.getElementById('btnLucroBruto').classList.add('fora-do-blur');
  document.getElementById('btnLucroLiquido').classList.add('fora-do-blur');
  document.getElementById('btnLucroMargem').classList.add('fora-do-blur');
});

btnClose2.addEventListener('click', () => {
  overlay2.style.display = 'none';
  blur.style.display = 'none'; // aqui remove o blur

  // Remove classe dos botões
  document.getElementById('btnLucroBruto').classList.remove('fora-do-blur');
  document.getElementById('btnLucroLiquido').classList.remove('fora-do-blur');
  document.getElementById('btnLucroMargem').classList.remove('fora-do-blur');
});

</script>
</body>
</html>
