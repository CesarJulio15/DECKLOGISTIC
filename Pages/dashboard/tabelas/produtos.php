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
$tagResult = $conn->query("SELECT * FROM tags WHERE deletado_em IS NULL ORDER BY criado_em DESC");
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
<link rel="stylesheet" href="../../../assets/sidebar.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo-area">
        <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <ul class="nav-list top-section">
                <li class="<?= ($currentPage=='financas.php') ? 'active' : '' ?>">
                    <a href="../financas.php">
                        <span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro
                    </a>
                </li>
                <li class="<?= ($currentPage=='estoque.php') ? 'active' : '' ?>">
                    <a href="../estoque.php">
                        <span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque
                    </a>
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
            <input type="text" id="pesquisa" placeholder="Pesquisar produto..." 
                   style="padding:8px 12px; width:350px; height: 45px; border-radius:36px; border:1px solid #ccc; font-size:14px; outline:none; transition:all 0.2s ease;">
        </div>
        <button class="btn-novo" onclick="window.location.href='../simulador.php'">Novo item +</button>
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

    <div class="tags-area" style="display:flex; align-items:center; gap:10px;">
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

        <!-- Ícone de lixo ao lado de Nova Tag -->
        <i id="btn-multi-delete" class="fa-solid fa-trash" style="cursor:pointer; font-size:18px;"></i>
        <button id="confirm-delete" style="display:none;">Confirmar Remoção</button>
    </div>
</div>

<table>
<thead>
<tr>
    <th style="width:30px; display:none;" id="multi-checkbox-header"></th>
    <th>Nome</th>
    <th>Preço Unitário</th>
    <th>Quantidade</th>
    <th>Lote</th>
</tr>
</thead>
<tbody id="tabela-produtos">
<?php while ($produto = mysqli_fetch_assoc($result)): ?>
<tr>
    <td class="multi-checkbox" style="display:none;">
        <input type="checkbox" class="chk-delete" data-id="<?= $produto['id'] ?>">
    </td>
    <td style="display:flex; align-items:center; gap:10px;">
        <span class="tags-vinculadas" id="tags-produto-<?= $produto['id'] ?>" style="display:inline-flex; gap:5px; align-items:center;">
            <?php if (isset($produtoTags[$produto['id']])): ?>
                <?php foreach ($produtoTags[$produto['id']] as $tag): ?>
                    <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" 
                       style="color: <?= htmlspecialchars($tag['cor']) ?>;" 
                       data-tag-id="<?= $tag['id'] ?>"></i>
                <?php endforeach; ?>
            <?php endif; ?>
        </span>

        <span><?= htmlspecialchars($produto['nome']) ?></span>

        <!-- Formulário para vincular tag -->
        <div class="add-tag-square" data-produto-id="<?= $produto['id'] ?>" style="width:24px; height:24px; background:#000; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:bold; border-radius:6px; margin-left:5px;">
    +
</div>
<div class="tag-dropdown" id="tag-dropdown-<?= $produto['id'] ?>" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; padding:5px; border-radius:4px; z-index:10;">
    <?php foreach($tags as $tag): ?>
        <div class="tag-option" data-tag-id="<?= $tag['id'] ?>" style="padding:2px 5px; cursor:pointer;">
            <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i> <?= htmlspecialchars($tag['nome']) ?>
        </div>
    <?php endforeach; ?>
</div>
    </td>
    <td>R$ <?= number_format($produto['preco_unitario'], 2, ',', '.') ?></td>
    <td><?= intval($produto['quantidade_estoque']) ?></td>
    <td><?= htmlspecialchars($produto['lote']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
// Botão para ativar seleção em massa
document.getElementById('btn-multi-delete').addEventListener('click', function() {
    document.querySelectorAll('.multi-checkbox').forEach(td => td.style.display = 'table-cell');
    document.getElementById('multi-checkbox-header').style.display = 'table-cell';
    document.getElementById('confirm-delete').style.display = 'inline-block';
});

// Confirmar delete múltiplo
document.getElementById('confirm-delete').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.chk-delete:checked');
    if (checkboxes.length === 0) return alert("Selecione pelo menos um produto.");
    if (!confirm("Deseja realmente excluir os produtos selecionados?")) return;

    let ids = Array.from(checkboxes).map(chk => chk.dataset.id);

    fetch('excluir_produto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `produto_ids=${ids.join(',')}`
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        if (data.trim() === "ok") {
            checkboxes.forEach(chk => chk.closest('tr').remove());
            document.getElementById('confirm-delete').style.display = 'none';
            document.querySelectorAll('.multi-checkbox').forEach(td => td.style.display = 'none');
        } else {
            alert("Erro ao excluir produtos!");
        }
    });
});
// Abrir dropdown de tags
document.querySelectorAll('.add-tag-square').forEach(square => {
    square.addEventListener('click', function(e) {
        const produtoId = this.dataset.produtoId;
        const dropdown = document.getElementById('tag-dropdown-' + produtoId);
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        dropdown.style.top = e.target.getBoundingClientRect().bottom + window.scrollY + 'px';
        dropdown.style.left = e.target.getBoundingClientRect().left + 'px';
    });
});

// Selecionar uma tag
document.querySelectorAll('.tag-option').forEach(option => {
    option.addEventListener('click', function() {
        const produtoId = this.closest('.tag-dropdown').id.split('-')[2];
        const tagId = this.dataset.tagId;

        fetch('vincular_tag.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `produto_id=${produtoId}&tag_id=${tagId}`
        })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'ok'){
                // Atualiza ícones da tag
                const icon = this.querySelector('i').cloneNode(true);
                const container = document.getElementById('tags-produto-' + produtoId);
                container.appendChild(icon);
            } else {
                alert('Erro ao vincular tag!');
            }
            this.closest('.tag-dropdown').style.display = 'none';
        });
    });
});

// Fecha dropdown clicando fora
document.addEventListener('click', function(e){
    document.querySelectorAll('.tag-dropdown').forEach(dd => {
        if(!dd.contains(e.target) && !document.querySelector('.add-tag-square[data-produto-id="'+dd.id.split('-')[2]+'"]').contains(e.target)){
            dd.style.display = 'none';
        }
    });
});

</script>

</div>
</div>
</main>
</body>
</html>
