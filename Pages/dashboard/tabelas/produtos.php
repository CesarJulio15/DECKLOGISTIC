<?php
include '../../../conexao.php'; 

// Busca produtos
$sql = "SELECT id, nome, preco_unitario, quantidade_estoque, lote FROM produtos";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Erro na consulta: " . mysqli_error($conn));
}

// Busca todas as tags
$tags = [];
$tagResult = $conn->query("SELECT * FROM tags ORDER BY criado_em DESC");
if ($tagResult) {
    while ($row = $tagResult->fetch_assoc()) {
        $tags[] = $row;
    }
}

// Busca tags já vinculadas a cada produto
$produtoTags = [];
$tagVincResult = $conn->query("
    SELECT produto_tag.produto_id, produto_tag.tag_id, tags.icone, tags.cor 
    FROM produto_tag 
    JOIN tags ON produto_tag.tag_id = tags.id
");
if ($tagVincResult) {
    while ($row = $tagVincResult->fetch_assoc()) {
        $produtoTags[$row['produto_id']][] = [
            'id' => $row['tag_id'],
            'icone' => $row['icone'],
            'cor' => $row['cor']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos</title>
<link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
<link rel="stylesheet" href="../../../assets/produtos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
<aside class="sidebar">
    <div class="logo-area">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
            <ul class="nav-list top-section">
                <li><a href="/Pages/financeiro.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                <li><a href="/Pages/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
            </ul>
            <hr>
            <ul class="nav-list middle-section">
                <li><a href="/Pages/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="/Pages/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                <li class="active"><a href="produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                <li><a href="../tag.php"><span><img src="../../../img/tag.svg" alt="Tags"></span> Tags</a></li>
            </ul>
        </div>
        <div class="bottom-links">
            <a href="/Pages/conta.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="/Pages/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<main class="dashboard">
<div class="content">
<div class="conteudo">

<h1>Produtos</h1>

<div class="acoes">
    <div class="botoes">
        <div class="pesquisa-produtos" style="margin-bottom:15px;">
    <input type="text" id="pesquisa" placeholder="Pesquisar produto..." style="padding:6px 10px; width:250px; border-radius:4px; border:1px solid #ccc;">
</div>
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

 <div class="tags-area">
<?php foreach ($tags as $tag): ?>
  <div class="tag-item" title="<?= htmlspecialchars($tag['nome']) ?>" data-tag-id="<?= $tag['id'] ?>">
      <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i>
      <?= htmlspecialchars($tag['nome']) ?>
  </div>
<?php endforeach; ?>
</div>
</div>
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
            <td style="display:flex; align-items:center; gap:10px;">
                <!-- Ícones vinculados -->
                <span class="tags-vinculadas" id="tags-produto-<?= $produto['id'] ?>" style="display:inline-flex; gap:5px; align-items:center;">
                  <?php if (isset($produtoTags[$produto['id']])): ?>
                    <?php foreach ($produtoTags[$produto['id']] as $tag): ?>
                      <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;" data-tag-id="<?= $tag['id'] ?>"></i>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </span>

                <!-- Nome do produto -->
                <span><?= htmlspecialchars($produto['nome']) ?></span>

                <!-- Dropdown para adicionar tags -->
                <div class="tag-dropdown" style="position:relative;">
                    <button class="btn-add" data-id="<?= $produto['id'] ?>">+</button>
                    <div class="dropdown-content">
                        <?php foreach ($tags as $tag): ?>
                        <div class="tag-option" 
                             data-produto="<?= $produto['id'] ?>" 
                             data-tag="<?= $tag['id'] ?>"
                             data-icone="<?= htmlspecialchars($tag['icone']) ?>"
                             data-cor="<?= htmlspecialchars($tag['cor']) ?>">
                            <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" 
                               style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i>
                            <?= htmlspecialchars($tag['nome']) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </td>
            <td>R$ <?= number_format($produto['preco_unitario'], 2, ',', '.') ?></td>
            <td><?= intval($produto['quantidade_estoque']) ?></td>
            <td><?= htmlspecialchars($produto['lote']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>
</main>

<script>
// Ordenação
function sortTable(columnIndex, asc = true, isNumeric = false) {
    const table = document.querySelector("table tbody");
    const rows = Array.from(table.rows);
    rows.sort((a, b) => {
        let aVal = a.cells[columnIndex].innerText.trim();
        let bVal = b.cells[columnIndex].innerText.trim();
        if (isNumeric) {
            aVal = parseFloat(aVal.replace(/[R$\s.]/g, '').replace(',', '.'));
            bVal = parseFloat(bVal.replace(/[R$\s.]/g, '').replace(',', '.'));
        }
        if (aVal < bVal) return asc ? -1 : 1;
        if (aVal > bVal) return asc ? 1 : -1;
        return 0;
    });
    rows.forEach(row => table.appendChild(row));
}

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

// Dropdown de tags
document.querySelectorAll('.tag-option').forEach(option => {
    option.addEventListener('click', function() {
        let produtoId = this.dataset.produto;
        let tagId = this.dataset.tag;
        let icone = this.dataset.icone;
        let cor = this.dataset.cor;
        fetch('vincular_tag.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `produto_id=${produtoId}&tag_id=${tagId}`
        }).then(res => res.text())
        .then(data => {
            if (data.trim() === "ok") {
                const container = document.getElementById(`tags-produto-${produtoId}`);
                container.innerHTML = '';
                const iconElem = document.createElement('i');
                iconElem.className = `fa-solid ${icone}`;
                iconElem.style.color = cor;
                iconElem.style.marginLeft = "5px";
                container.appendChild(iconElem);
            }
        });
    });
});

// Botão + dropdown
document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const dropdown = this.parentElement.querySelector('.dropdown-content');
        dropdown.classList.toggle('show');
    });
});
document.addEventListener('click', function () {
    document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('show'));
});
document.getElementById('pesquisa').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let linhas = document.querySelectorAll('#tabela-produtos tr');
    linhas.forEach(linha => {
        let texto = linha.querySelector('td span:last-child').textContent.toLowerCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
// Filtrar por tag
document.querySelectorAll('.tag-item').forEach(tag => {
    tag.addEventListener('click', function() {
        let tagId = this.dataset.tagId;
        let linhas = document.querySelectorAll('#tabela-produtos tr');
        linhas.forEach(linha => {
            let produtoId = linha.querySelector('.btn-add').dataset.id;
            let container = document.getElementById(`tags-produto-${produtoId}`);
            if (container && container.querySelector(`[data-tag-id='${tagId}']`)) {
                linha.style.display = '';
            } else {
                linha.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>
