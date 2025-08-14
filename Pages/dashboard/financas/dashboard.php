  <!DOCTYPE html>
  <html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <title>Finanças - Decklogistic</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
      <link rel="stylesheet" href="../../../assets/financas.css">
  </head>
  <body>

  <div class="content">
    <div class="sidebar">
      <link rel="stylesheet" href="../../../assets/sidebar.css">
      <div class="logo-area">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo">
      </div>
      <nav class="nav-section">
        <div class="nav-menus">
          <ul class="nav-list top-section">
            <li class="active"><a href="/Pages/financeiro.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
            <li><a href="/Pages/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
          </ul>
          <hr>
          <ul class="nav-list middle-section">
            <li><a href="/Pages/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
            <li><a href="/Pages/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
            <li><a href="/Pages/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          </ul>
        </div>
        <div class="bottom-links">
          <a href="/Pages/conta.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
          <a href="/Pages/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
      </nav>
    </div>

    <!-- Dashboard Cards -->
  <div class="dashboard">
    <div class="card">
      <h3>Lucro Bruto</h3>
      <div id="lucroBruto" class="value">R$ 0,00</div>
      <div id="chartBruto" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Lucro Líquido</h3>
      <div id="lucroLiquido" class="value">R$ 0,00</div>
      <div id="chartLiquido" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Margem de Lucro</h3>
      <div id="margemLucro" class="value">0%</div>
      <div id="chartMargem" style="height:60px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Receita x Despesas</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
      <!-- Entradas Totais -->
  <div class="card">
    <h3>Entradas</h3>
    <div id="entradasTotais" class="value">R$ 0,00</div>
    <div id="chartEntradas" style="height:60px; margin-top:10px;"></div>
    <div class="sub-info">
      <small>Vendas: <span id="totalVendas">R$ 0,00</span></small><br>
      <small>Outros Recebimentos: <span id="totalOutros">R$ 0,00</span></small>
    </div>
  </div>

  <!-- Saídas Totais -->
  <div class="card">
    <h3>Saídas</h3>
    <div id="saidasTotais" class="value">R$ 0,00</div>
    <div id="chartSaidas" style="height:60px; margin-top:10px;"></div>
    <div class="sub-info">
      <small>Custos Fixos: <span id="totalFixos">R$ 0,00</span></small><br>
      <small>Custos Variáveis: <span id="totalVariaveis">R$ 0,00</span></small><br>
      <small>Imprevistos: <span id="totalImprevistos">R$ 0,00</span></small>
    </div>
  </div>

  </div>

  </div>

  <script>
  async function loadLucros() {
    const lojaId = 1;
    const periodo = 'mes';

    const bruto = await fetch(`/DECKLOGISTIC/api/lucro_bruto.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json());
    const liquido = await fetch(`/DECKLOGISTIC/api/lucro_liquido.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json());
    const margem = await fetch(`/DECKLOGISTIC/api/margem_lucro.php?loja_id=${lojaId}&periodo=${periodo}`).then(r => r.json());

    const lucroBrutoVal = parseFloat(bruto.lucro_bruto || 0).toFixed(2);
    const lucroLiquidoVal = parseFloat(liquido.lucro_liquido || 0).toFixed(2);
    const margemVal = parseFloat(margem.margem_lucro_percent || 0).toFixed(2);

    document.getElementById('lucroBruto').innerText = `R$ ${lucroBrutoVal}`;
    document.getElementById('lucroLiquido').innerText = `R$ ${lucroLiquidoVal}`;
    document.getElementById('margemLucro').innerText = `${margemVal}%`;

    // Gráficos pequenos
    const optionsBruto = {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3, gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0 } },
      series: [{ data: [10, 15, 12, 18, 25, parseFloat(lucroBrutoVal)] }],
      colors: ['#10b981']
    };
    const optionsLiquido = {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3 },
      series: [{ data: [5, 8, 6, 12, 15, parseFloat(lucroLiquidoVal)] }],
      colors: ['#3b82f6']
    };
    const optionsMargem = {
      chart: { type: 'area', height: 60, sparkline: { enabled: true } },
      stroke: { curve: 'smooth' },
      fill: { opacity: 0.3 },
      series: [{ data: [2, 4, 3, 5, 6, parseFloat(margemVal)] }],
      colors: ['#f59e0b']
    };

    new ApexCharts(document.querySelector("#chartBruto"), optionsBruto).render();
    new ApexCharts(document.querySelector("#chartLiquido"), optionsLiquido).render();
    new ApexCharts(document.querySelector("#chartMargem"), optionsMargem).render();
  }

  async function loadReceitaDespesa() {
    const lojaId = 1;
    const data = await fetch(`/DECKLOGISTIC/api/receita_despesas.php?loja_id=${lojaId}`)
                      .then(r => r.json());

    // Transformar as chaves em datas válidas e ordenar
    const dias = [...new Set([...Object.keys(data.receita), ...Object.keys(data.despesa)])]
                .map(d => new Date(d))
                .sort((a,b) => a - b)
                .map(d => d.toISOString().slice(0,10)); // voltar para YYYY-MM-DD

    // Gerar arrays de valores
    const receita = dias.map(d => parseFloat(data.receita[d] || 0));
    const despesa = dias.map(d => parseFloat(data.despesa[d] || 0));

    const options = {
      chart: { type: 'line', height: 300 },
      series: [
        { name: 'Receita', data: receita },
        { name: 'Despesa', data: despesa }
      ],
      xaxis: { categories: dias },
      stroke: { curve: 'smooth' },
      colors: ['#10b981', '#ef4444']
    };

    new ApexCharts(document.querySelector("#chartReceitaDespesa"), options).render();
  }

async function loadFinanceiroDetalhado() {
  const lojaId = 1;

  // Pegando dados
  const entradas = await fetch(`/DECKLOGISTIC/api/entradas.php?loja_id=${lojaId}`).then(r => r.json());
  const saidas = await fetch(`/DECKLOGISTIC/api/saidas.php?loja_id=${lojaId}`).then(r => r.json());

  // Somas totais
  const totalVendas = Object.values(entradas.vendas).reduce((a,b)=>a+b,0).toFixed(2);
  const totalOutros = Object.values(entradas.outros).reduce((a,b)=>a+b,0).toFixed(2);
  const totalFixos = Object.values(saidas.fixos).reduce((a,b)=>a+b,0).toFixed(2);
  const totalVariaveis = Object.values(saidas.variaveis).reduce((a,b)=>a+b,0).toFixed(2);
  const totalImprevistos = Object.values(saidas.imprevistos).reduce((a,b)=>a+b,0).toFixed(2);

  const totalEntrada = (parseFloat(totalVendas)+parseFloat(totalOutros)).toFixed(2);
  const totalSaida = (parseFloat(totalFixos)+parseFloat(totalVariaveis)+parseFloat(totalImprevistos)).toFixed(2);
  const lucroLiquido = (totalEntrada - totalSaida).toFixed(2);
  const margem = totalEntrada>0 ? ((lucroLiquido/totalEntrada)*100).toFixed(2) : 0;

  // Atualiza cards
  document.getElementById('lucroBruto').innerText = `R$ ${totalEntrada}`;
  document.getElementById('lucroLiquido').innerText = `R$ ${lucroLiquido}`;
  document.getElementById('margemLucro').innerText = `${margem}%`;

  // Mini gráficos
  const optionsEntrada = {
    chart: { type: 'area', height: 60, sparkline: { enabled: true } },
    series: [{ data: Object.values(entradas.vendas).map(parseFloat) }],
    colors: ['#10b981']
  };
  const optionsSaida = {
    chart: { type: 'area', height: 60, sparkline: { enabled: true } },
    series: [{ data: Object.values(saidas.fixos).map(parseFloat) }],
    colors: ['#ef4444']
  };
  const optionsMargem = {
    chart: { type: 'area', height: 60, sparkline: { enabled: true } },
    series: [{ data: [margem] }],
    colors: ['#f59e0b']
  };

  new ApexCharts(document.querySelector("#chartBruto"), optionsEntrada).render();
  new ApexCharts(document.querySelector("#chartLiquido"), optionsSaida).render();
  new ApexCharts(document.querySelector("#chartMargem"), optionsMargem).render();

  // Gráfico detalhado Receita x Despesa
  const dias = [...new Set([
    ...Object.keys(entradas.vendas),
    ...Object.keys(entradas.outros),
    ...Object.keys(saidas.fixos),
    ...Object.keys(saidas.variaveis),
    ...Object.keys(saidas.imprevistos)
  ])].sort();

  const receitaTotal = dias.map(d => (parseFloat(entradas.vendas[d]||0)+parseFloat(entradas.outros[d]||0)));
  const despesaTotal = dias.map(d => 
    (parseFloat(saidas.fixos[d]||0)+parseFloat(saidas.variaveis[d]||0)+parseFloat(saidas.imprevistos[d]||0))
  );

  const optionsDetalhado = {
    chart: { type: 'line', height: 300 },
    series: [
      { name: 'Entradas', data: receitaTotal },
      { name: 'Saídas', data: despesaTotal }
    ],
    xaxis: { categories: dias },
    stroke: { curve: 'smooth' },
    colors: ['#10b981','#ef4444']
  };

  new ApexCharts(document.querySelector("#chartReceitaDespesa"), optionsDetalhado).render();
}


  loadFinanceiroDetalhado();
  loadLucros();
  loadReceitaDespesa();
  </script>
  </body>
  </html>
