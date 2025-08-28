<?php
session_start();
$lojaId = $_SESSION['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estoque - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="../../assets/estoque.css?v=2">
</head>
<body>

<div class="content">
  <div class="sidebar">
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
      <ul class="nav-list top-section">
        <li><a href="financas.php"><img src="../../img/icon-finan.svg" alt=""> Financeiro</a></li>
        <li class="active"><a href="estoque.php"><img src="../../img/icon-estoque.svg" alt=""> Estoque</a></li>
      </ul>
      <hr>
      <ul class="nav-list middle-section">
        <li><a href="/Pages/visaoGeral.php"><img src="../../img/icon-visao.svg" alt=""> Visão Geral</a></li>
        <li><a href="/Pages/operacoes.php"><img src="../../img/icon-operacoes.svg" alt=""> Operações</a></li>
        <li><a href="../dashboard/tabelas/produtos.php"><img src="../../img/icon-produtos.svg" alt=""> Produtos</a></li>
        <li><a href="tag.php"><img src="../../img/tag.svg" alt=""> Tags</a></li>
      </ul>
      <div class="bottom-links">
        <a href="../auth/config.php"><img src="../../img/icon-config.svg" alt=""> Conta</a>
        <a href="/Pages/dicas.php"><img src="../../img/icon-dicas.svg" alt=""> Dicas</a>
      </div>
    </nav>
  </div>

  <div class="dashboard">
    <!-- Total Estoque -->
    <div class="card">
      <h3>Total Estoque</h3>
      <div class="value" id="estoque">...</div>
      <div id="chartMiniEstoque" style="height:50px; margin-top:5px;"></div>
      <button id="btnVerMais">Ver mais</button>
    </div>

    <!-- Produtos em Falta -->
    <div class="card">
      <h3>Produtos em Falta</h3>
      <div id="tabelaProdutosFalta" style="margin-top:10px;"></div>
    </div>

    <!-- Estoque Parado -->
    <div class="card">
      <h3>Estoque Parado</h3>
      <div id="chartEstoqueParado" style="height:300px; margin-top:10px;"></div>
    </div>

    <!-- Entrada x Saída -->
    <div class="card">
      <h3>Entrada x Saída de Produtos</h3>
      <div id="chartEntradaSaida" style="height:300px; margin-top:10px;"></div>
    </div>
  </div>
</div>

<script>
const lojaId = <?= $lojaId ?>;

// ===== Total Estoque + Mini Gráfico =====
async function loadEstoqueTotal() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/total_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    const total = parseFloat(data.total || 0).toFixed(2);
    document.querySelector("#estoque").textContent = total;

    new ApexCharts(document.querySelector("#chartMiniEstoque"), {
      chart: { type: 'bar', height: 50, sparkline: { enabled: true } },
      series: [{ name: 'Total', data: [total] }],
      colors: ['#10b981']
    }).render();

  } catch(e) {
    console.error(e);
    document.querySelector("#estoque").textContent = "Erro";
  }
}

// ===== Produtos em Falta =====
async function loadProdutosFalta() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php`).then(r => r.json());
    const container = document.querySelector("#tabelaProdutosFalta");

    if (!Array.isArray(data) || data.length === 0) {
      container.innerHTML = "<p>Nenhum produto em falta.</p>";
      return;
    }

    let tabela = `<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px;">
      <thead style="background:#f3f4f6; text-align:left;">
        <tr><th>Produto</th><th>Quantidade</th></tr>
      </thead><tbody>`;

    data.forEach(p => {
      tabela += `<tr style="background:${p.quantidade_estoque <= 0 ? '#ddd' : '#fff'};">
        <td>${p.nome}</td>
        <td>${p.quantidade_estoque}</td>
      </tr>`;
    });

    tabela += "</tbody></table>";
    container.innerHTML = tabela;

  } catch(e) {
    console.error(e);
    document.querySelector("#tabelaProdutosFalta").innerHTML = "<p>Erro ao carregar dados</p>";
  }
}

// ===== Estoque Parado =====
async function loadEstoqueParado() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/grafico_estoque.php?loja_id=${lojaId}`).then(r => r.json());
    if (!Array.isArray(data)) {
      document.querySelector("#chartEstoqueParado").innerHTML = "<p>Dados inválidos</p>";
      return;
    }

    const produtos = data.map(d => d.produto);
    const quantidades = data.map(d => parseInt(d.qtd));

    new ApexCharts(document.querySelector("#chartEstoqueParado"), {
      chart: { type: 'bar', height: 300 },
      series: [{ name: 'Estoque Parado', data: quantidades }],
      xaxis: { categories: produtos },
      colors: ['#0b42f5ff'],
      dataLabels: { enabled: true }
    }).render();

  } catch(e) {
    console.error(e);
    document.querySelector("#chartEstoqueParado").innerHTML = "<p>Erro ao carregar gráfico</p>";
  }
}

// ===== Entrada x Saída =====
async function loadEntradaSaida() {
  try {
    const data = await fetch('/DECKLOGISTIC/api/teste_entrada_saida.php').then(r => r.json());
    console.log("Entrada x Saída:", data);

    if (!Array.isArray(data) || data.length === 0) {
      document.querySelector("#chartEntradaSaida").innerHTML = "<p>Dados inválidos</p>";
      return;
    }

    const labels = data.map(d => d.data_movimentacao);
    const entrada = data.map(d => parseInt(d.entrada || 0));
    const saida = data.map(d => parseInt(d.saida || 0));

    new ApexCharts(document.querySelector("#chartEntradaSaida"), {
      chart: { type: 'line', height: 300 },
      series: [
        { name: 'Entrada', data: entrada },
        { name: 'Saída', data: saida }
      ],
      xaxis: { categories: labels },
      stroke: { curve: 'smooth' },
      colors: ['#10b981', '#ef4444']
    }).render();

  } catch(e) {
    console.error(e);
    document.querySelector("#chartEntradaSaida").innerHTML = "<p>Erro ao carregar gráfico</p>";
  }
}

// ===== Inicialização =====
loadEstoqueTotal();
loadProdutosFalta();
loadEstoqueParado();
loadEntradaSaida();
</script>
</body>
</html>
