<?php
session_start();

// ID da loja logada na sessão
$lojaId = $_SESSION['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Finanças - Decklogistic</title>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <link rel="stylesheet" href="../../assets/estoque.css">
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
    <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
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

   <!-- Dashboard Cards -->
   <div class="dashboard">
    <div class="card">
    <h3>Total Estoque</h3>
    <div id="estoque" class="value">...</div>
    <div id="chartEstoque" style="height:60px; margin-top:10px;"></div>
    </div>

    <div class="card">
      <h3>Produtos em Falta</h3>
      <div id="tabelaProdutosFalta" style="margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Produtos com Estoque Parado</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
    <div class="card">
      <h3>Entrada e Saída de Produtos</h3>
      <div id="chartReceitaDespesa" style="height:300px; margin-top:10px;"></div>
    </div>
  </div>
</div>


<script>
const lojaId = <?= $lojaId ?>;

async function loadEstoqueTotal() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/total_estoque.php`).then(r => r.json());
    const estoqueEl = document.querySelector("#estoque");

    if (!data || data.total === undefined) {
      estoqueEl.textContent = "Erro ao carregar estoque";
      return;
    }

    estoqueEl.textContent = data.total;
  } catch (error) {
    console.error("Erro ao carregar estoque:", error);
    document.querySelector("#estoque").textContent = "Erro";
  }
}

// =========================
// Produtos em Falta (Tabela)
// =========================
async function loadProdutosFalta() {
  try {
    const data = await fetch(`/DECKLOGISTIC/api/produtos_falta.php`).then(r => r.json());

    const tabelaContainer = document.querySelector("#tabelaProdutosFalta");

    if (!Array.isArray(data) || data.length === 0) {
      tabelaContainer.innerHTML = "<p>Nenhum produto em falta.</p>";
      return;
    }

    let tabela = `
      <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead style="background:#f3f4f6; text-align:left;">
          <tr>
            <th>Produto</th>
            <th>Quantidade</th>
          </tr>
        </thead>
        <tbody>
    `;

    data.forEach(p => {
      tabela += `
        <tr style="background:${p.quantidade_estoque <= 0 ? '#fee2e2' : '#fff'};">
          <td>${p.nome}</td>
          <td style="color:${p.quantidade_estoque <= 0 ? '#dc2626' : '#000'};">
            ${p.quantidade_estoque}
          </td>
        </tr>
      `;
    });

    tabela += `
        </tbody>
      </table>
    `;

    tabelaContainer.innerHTML = tabela;

  } catch (error) {
    console.error("Erro ao carregar produtos em falta:", error);
    document.querySelector("#tabelaProdutosFalta").innerHTML = "<p>Erro ao carregar dados</p>";
  }
}

loadEstoqueTotal();
loadProdutosFalta();
</script>


</body>
</html>



