<?php

include '../../../conexao.php'; 
$sql = "SELECT nome, preco_unitario, quantidade_estoque, lote FROM produtos";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>
      <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
<!-- <link rel="stylesheet" href="../../../assets/sidebar.css"> -->
  <link rel="stylesheet" href="../../../assets/produtos.css">

</head>

<body>



  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo-area">
      <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>

    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="/Pages/financeiro.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
          <li ><a href="/Pages/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
        </ul>

        <hr>

        <ul class="nav-list middle-section">
          <li><a href="/Pages/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="/Pages/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li class="active"><a href="produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
        </ul>
      </div>

      <div class="bottom-links">
        <a href="/Pages/conta.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="/Pages/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </aside>

  <!-- Conteúdo principal -->
  <main class="dashboard">
    <div class="content">

<div class="conteudo">
    <h1>Produtos</h1>

   <div class="acoes">
    <div class="botoes">
        <button class="btn-novo">Novo item <span><img class="icon" src="../../../img/icon-plus.svg" alt="Adicionar"></span></button>
        <select id="ordenar">
            <option value="">Ordenar...</option>
            <option value="nome-asc">Nome (A-Z)</option>
            <option value="nome-desc">Nome (Z-A)</option>
            <option value="preco-asc">Preço (Menor→Maior)</option>
            <option value="preco-desc">Preço (Maior→Menor)</option>
            <option value="quantidade-asc">Quantidade (Menor→Maior)</option>
            <option value="quantidade-desc">Quantidade (Maior→Menor)</option>
            <option value="lote-asc">Lote (A-Z)</option>
            <option value="lote-desc">Lote (Z-A)</option>
        </select>
    </div>
    <input type="text" id="pesquisa" placeholder="Procurar pelo produto...">
</div>


    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Preço Unitário</th>
                <th>Quantidade</th>
                <th>Lote</th>
            </tr>
        </thead>
        <tbody id="tabela-produtos">
            <?php while ($produto = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td>R$ <?= number_format($produto['preco_unitario'], 2, ',', '.') ?></td>
                    <td><?= intval($produto['quantidade_estoque']) ?></td>
                    <td><?= htmlspecialchars($produto['lote']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
// Filtro de pesquisa
document.getElementById('pesquisa').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let linhas = document.querySelectorAll('#tabela-produtos tr');
    linhas.forEach(linha => {
        let texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
</script>


    </div>
  </main>

<script>
function sortTable(columnIndex, asc = true, isNumeric = false) {
    const table = document.querySelector("table tbody");
    const rows = Array.from(table.rows);

    rows.sort((a, b) => {
        let aVal = a.cells[columnIndex].innerText.trim();
        let bVal = b.cells[columnIndex].innerText.trim();

        if (isNumeric) {
            // Remove "R$" e vírgulas/pontos para comparação numérica
            aVal = parseFloat(aVal.replace(/[R$\s.]/g, '').replace(',', '.'));
            bVal = parseFloat(bVal.replace(/[R$\s.]/g, '').replace(',', '.'));
        }

        if (aVal < bVal) return asc ? -1 : 1;
        if (aVal > bVal) return asc ? 1 : -1;
        return 0;
    });

    rows.forEach(row => table.appendChild(row));
}

// Ordenar ao selecionar no <select>
document.getElementById('ordenar').addEventListener('change', function() {
    const value = this.value;
    switch(value) {
        case 'nome-asc': sortTable(0, true, false); break;
        case 'nome-desc': sortTable(0, false, false); break;
        case 'preco-asc': sortTable(1, true, true); break;
        case 'preco-desc': sortTable(1, false, true); break;
        case 'quantidade-asc': sortTable(2, true, true); break;
        case 'quantidade-desc': sortTable(2, false, true); break;
        case 'lote-asc': sortTable(3, true, false); break;
        case 'lote-desc': sortTable(3, false, false); break;
    }
});

// Filtro de pesquisa
document.getElementById('pesquisa').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let linhas = document.querySelectorAll('#tabela-produtos tr');
    linhas.forEach(linha => {
        let texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
</script>


</body>
</html>
