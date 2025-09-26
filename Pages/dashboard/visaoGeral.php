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

  <div class="card">
  <h3>Anomalias de Vendas</h3>
  <div id="anomaliasVendas">
    <p>Carregando...</p>
  </div>
  <button class="btn-modern" onclick="executarIA()">Executar IA</button>
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

// Anomalias de Vendas
async function loadAnomalias() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/anomalias.php?loja_id=${lojaId}`).then(r => r.json());

    const div = document.getElementById("anomaliasVendas");
    div.innerHTML = '';

    if (data.length === 0) {
      div.innerHTML = '<p>Nenhuma anomalia detectada</p>';
      return;
    }

    const table = document.createElement('table');
    table.setAttribute('border', '1');
    table.setAttribute('cellpadding', '10');
    table.setAttribute('cellspacing', '0');

    const headerRow = document.createElement('tr');
    headerRow.innerHTML = '<th>Data</th><th>Detalhe</th><th>Score</th>';
    table.appendChild(headerRow);

    data.forEach(a => {
      const row = document.createElement('tr');
      row.innerHTML = `<td>${a.data_ocorrencia}</td>
                       <td>${a.detalhe}</td>
                       <td>${a.score.toFixed(2)}</td>`;
      table.appendChild(row);
    });

    div.appendChild(table);
  } catch (err) {
    console.error("Erro ao carregar anomalias:", err);
  }
}

async function executarIA() {
  try {
    const res = await fetch(`/DECKLOGISTIC/api/run_anomalias.php`);
    const data = await res.json();
    alert("IA executada!\n\nSaída:\n" + data.output);
    loadAnomalias(); // recarrega tabela
  } catch (e) {
    alert("Erro ao executar IA: " + e);
  }
}

// Chama a função ao carregar a página
loadAnomalias();

// Chama a função ao carregar a página
loadProdutosMaisVendidos();






</script>

</body>
</html>
