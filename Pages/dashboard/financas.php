<?php
session_start();

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
  <title>Finanças - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="../../assets/financas.css">
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
          <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
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
    const periodo = 'mes'; 

    // Consome as APIs
    const [bruto, liquido, margem] = await Promise.all([
      fetch(`/DECKLOGISTIC/api/lucro_bruto.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json()),
      fetch(`/DECKLOGISTIC/api/lucro_liquido.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json()),
      fetch(`/DECKLOGISTIC/api/margem_lucro.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json())
    ]);

    // Valores totais
    const lucroBrutoVal = parseFloat(bruto.total || 0).toFixed(2);
    const lucroLiquidoVal = parseFloat(liquido.total || 0).toFixed(2);
    const margemVal = parseFloat(margem.total || 0).toFixed(2);

    document.getElementById('lucroBruto').innerText = `R$ ${lucroBrutoVal}`;
    document.getElementById('lucroLiquido').innerText = `R$ ${lucroLiquidoVal}`;
    document.getElementById('margemLucro').innerText = `${margemVal}%`;

    // Séries históricas
const brutoSeries = (bruto.series || []).map(item => item.valor || 0);
const liquidoSeries = (liquido.series || []).map(item => item.valor || 0);
const margemSeries = (margem.series || []).map(item => item.valor || 0);

    // Sparkline Lucro Bruto
    new ApexCharts(document.querySelector("#chartBruto"), {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3 },
      series: [{ data: brutoSeries }],
      colors: ['#10b981']
    }).render();

    // Sparkline Lucro Líquido
    new ApexCharts(document.querySelector("#chartLiquido"), {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3 },
      series: [{ data: liquidoSeries }],
      colors: ['#3b82f6']
    }).render();

    // Sparkline Margem de Lucro
    new ApexCharts(document.querySelector("#chartMargem"), {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3 },
      series: [{ data: margemSeries }],
      colors: ['#f59e0b']
    }).render();
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
// Redireciona ao clicar no botão
document.getElementById('btnLucroLiquido').addEventListener('click', () => {
    window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/lucroL.php';
});
document.getElementById('btnLucroBruto').addEventListener('click', () => {
    window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/lucroB.php';
});
document.getElementById('btnLucroMargem').addEventListener('click', () => {
    window.location.href = '/DECKLOGISTIC/Pages/auth/lojas/margem.php';
});

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

    const lucroBrutoVal = parseFloat(bruto.total || 0).toFixed(2);
    const lucroLiquidoVal = parseFloat(liquido.total || 0).toFixed(2);
    const margemVal = parseFloat(margem.total || 0).toFixed(2);

    document.getElementById('lucroBruto').innerText = `R$ ${lucroBrutoVal}`;
    document.getElementById('lucroLiquido').innerText = `R$ ${lucroLiquidoVal}`;
    document.getElementById('margemLucro').innerText = `${margemVal}%`;

    const brutoSeries = (bruto.series || []).map(item => item.valor || 0);
    const liquidoSeries = (liquido.series || []).map(item => item.valor || 0);
    const margemSeries = (margem.series || []).map(item => item.valor || 0);

    // Render ApexCharts...
  } catch(err) {
    console.error("Erro ao carregar lucros:", err);
  }
}

  // Chamada inicial das funções
  loadLucros();
  loadReceitaDespesa();
  loadTopDespesas();
  loadCustoMedioProdutos();
</script>

</body>
</html>
