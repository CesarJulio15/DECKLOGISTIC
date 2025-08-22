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
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
       <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<ul class="nav-list top-section">
    <li class="<?= ($currentPage=='financas.php') ? 'active' : '' ?>">
        <a href="../financas.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a>
    </li>
    <li class="<?= ($currentPage=='estoque.php') ? 'active' : '' ?>">
        <a href="../estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a>
    </li>
</ul>
            <hr>
            
          <ul class="nav-list middle-section">
    <li><a href="../visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
    <li><a href="../operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
   <li class="<?= $currentPage=='produtos.php' ? 'active' : '' ?>">
    <a href="produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a>
</li>
    <li><a href="../tag.php"><span><img src="../../../img/tag.svg" alt="Tags"></span> Tags</a></li>
</ul>
        </div>
        <div class="bottom-links">
            <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
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
            <input type="text" id="pesquisa" placeholder="Pesquisar produto..." style="padding:8px 12px; width:350px; height: 45px; border-radius:36px; border:1px solid #ccc; font-size:14px; outline:none; transition:all 0.2s ease;">
        </div>
        <button class="btn-novo">Novo item +</button>
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
<button class="btn-reset-filtro" onclick="resetFiltro()">
    <i class="fa-solid fa-xmark" style="color: #000000ff;"></i>
</button>
<button class="btn-nova-tag" onclick="window.location.href='../tag.php'">Nova Tag +</button>
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

    <span class="tags-vinculadas" id="tags-produto-<?= $produto['id'] ?>" style="display:inline-flex; gap:5px; align-items:center;">
      <?php if (isset($produtoTags[$produto['id']])): ?>
        <?php foreach ($produtoTags[$produto['id']] as $tag): ?>
          <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;" data-tag-id="<?= $tag['id'] ?>"></i>
        <?php endforeach; ?>
      <?php endif; ?>
    </span>

    <span><?= htmlspecialchars($produto['nome']) ?></span>
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
        return (aVal < bVal ? -1 : 1) * (asc ? 1 : -1);
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

// Dropdown e vincular tag
document.querySelectorAll('.tag-option').forEach(option => {
    option.addEventListener('click', function() {
        const produtoId = this.dataset.produto;
        const tagId = this.dataset.tag;
        const icone = this.dataset.icone;
        const cor = this.dataset.cor;
        const container = document.getElementById(`tags-produto-${produtoId}`);
        
        fetch('vincular_tag.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `produto_id=${produtoId}&tag_id=${tagId}`
        }).then(res => res.text())
          .then(data => {
            if (data.trim() === "ok") {
                // Verifica duplicidade depois da resposta
                if (!container.querySelector(`[data-tag-id='${tagId}']`)) {
                    const iconElem = document.createElement('i');
                    iconElem.className = `fa-solid ${icone}`;
                    iconElem.style.color = cor;
                    iconElem.style.marginLeft = "5px";
                    iconElem.dataset.tagId = tagId;
                    container.appendChild(iconElem);

                    // Atualiza filtro se houver tag ativa
                    const ativa = document.querySelector('.tags-area .tag-item.active');
                    if (ativa) {
                        filtrarPorTag(ativa.dataset.tagId);
                    }
                }
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

// Pesquisa
document.getElementById('pesquisa').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    document.querySelectorAll('#tabela-produtos tr').forEach(linha => {
        let textoLinha = linha.innerText.toLowerCase();
        linha.style.display = textoLinha.includes(filtro) ? '' : 'none';
    });
});

// Reset filtro
function resetFiltro() {
    document.getElementById('pesquisa').value = '';
    document.querySelectorAll('#tabela-produtos tr').forEach(linha => linha.style.display = '');
    document.querySelectorAll('.tags-area .tag-item').forEach(tag => tag.classList.remove('active'));
}

// Filtrar por tag
function filtrarPorTag(tagId) {
    document.querySelectorAll('#tabela-produtos tr').forEach(linha => {
        const container = linha.querySelector('.tags-vinculadas');
        linha.style.display = (container && container.querySelector(`[data-tag-id='${tagId}']`)) ? '' : 'none';
    });
}

document.querySelector('.tags-area').addEventListener('click', function(e) {
    const tag = e.target.closest('.tag-item');
    if (!tag) return;

    document.querySelectorAll('.tags-area .tag-item').forEach(t => t.classList.remove('active'));
    tag.classList.add('active');

    filtrarPorTag(tag.dataset.tagId);
});
</script>
</body>
</html>
