  <!DOCTYPE html>
  <html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <title>Finanças - Decklogistic</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
      <link rel="stylesheet" href="../../assets/financas.css">
      <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
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
            <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
            <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
            <li><a href="/Pages/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
              <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
          </ul>
        </div>
        <div class="bottom-links">
          <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
          <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
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

  <script>
    
    async function loadTopDespesas() {
  const lojaId = 1;
  const data = await fetch(`/DECKLOGISTIC/api/top5_despesas.php?loja_id=${lojaId}`)
                      .then(r => r.json());

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


  loadLucros();
  loadReceitaDespesa();
  loadTopDespesas();

  </script>
  </body>
  </html>
