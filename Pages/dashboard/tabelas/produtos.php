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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.add-tag-square { width:24px; height:24px; background:#000; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:bold; border-radius:6px; margin-left:5px; }
.tag-dropdown { display:none; position:absolute; background:#fff; border:1px solid #ccc; padding:5px; border-radius:4px; z-index:10; }
.tag-option { padding:2px 5px; cursor:pointer; }
</style>
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
        <button class="btn-novo" onclick="window.location.href='../gerenciamento_produtos.php'">Novo item +</button>
        <button class="btn-novo" data-bs-toggle="modal" data-bs-target="#importModal">Importar</button>
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
            <div class="tag-item" title="<?= htmlspecialchars($tag['nome']) ?>" data-tag-id="<?= $tag['id'] ?>" style="cursor:pointer;">
                <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i> <?= htmlspecialchars($tag['nome']) ?>
            </div>
        <?php endforeach; ?>
        <button class="btn-reset-filtro" onclick="resetFiltro()">
            <i class="fa-solid fa-xmark" style="color: #000000ff;"></i>
        
        </button>
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
    <td style="display:flex; align-items:center; gap:10px; position:relative;">
        <span class="tags-vinculadas" id="tags-produto-<?= $produto['id'] ?>" style="display:inline-flex; gap:5px; align-items:center;">
            <?php if (isset($produtoTags[$produto['id']])): ?>
                <?php foreach ($produtoTags[$produto['id']] as $tag): ?>
                    <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;" data-tag-id="<?= $tag['id'] ?>"></i>
                <?php endforeach; ?>
            <?php endif; ?>
        </span>

        <span><?= htmlspecialchars($produto['nome']) ?></span>

     
        <div class="add-tag-square" data-produto-id="<?= $produto['id'] ?>">+</div>
        <div class="tag-dropdown" id="tag-dropdown-<?= $produto['id'] ?>">
            <?php foreach($tags as $tag): ?>
                <div class="tag-option" data-tag-id="<?= $tag['id'] ?>">
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
// Seleção múltipla
const btnMultiDelete = document.getElementById('btn-multi-delete');
const confirmDeleteBtn = document.getElementById('confirm-delete');
let multiDeleteActive = false;

btnMultiDelete.addEventListener('click', function() {
    multiDeleteActive = !multiDeleteActive;

    document.querySelectorAll('.multi-checkbox').forEach(td => td.style.display = multiDeleteActive ? 'table-cell' : 'none');
    document.getElementById('multi-checkbox-header').style.display = multiDeleteActive ? 'table-cell' : 'none';
    confirmDeleteBtn.style.display = multiDeleteActive ? 'inline-block' : 'none';
});

// Confirmar exclusão
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
        if(data.trim() === 'ok'){
            checkboxes.forEach(chk => chk.closest('tr').remove());
            document.getElementById('confirm-delete').style.display = 'none';
            document.querySelectorAll('.multi-checkbox').forEach(td => td.style.display = 'none');
        } else {
            alert('Erro ao excluir produtos!');
        }
    });
});

// Pesquisa
document.getElementById('pesquisa').addEventListener('input', function() {
    const termo = this.value.toLowerCase();  // Converte o termo de pesquisa para minúsculo
    document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
        const nomeProduto = tr.querySelector('td:nth-child(2) span');
        if (nomeProduto) {
            const nome = nomeProduto.textContent.toLowerCase();  // Acessa o nome do produto e converte para minúsculo
            tr.style.display = nome.includes(termo) ? '' : 'none';  // Exibe ou esconde a linha com base na pesquisa
        }
    });
});

// Ordenação
// Ordenação
document.getElementById('ordenar').addEventListener('change', function() {
    const tbody = document.getElementById('tabela-produtos');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const val = this.value;

    rows.sort((a, b) => {
        let aText, bText;
        switch(val){
            case 'nome-asc':
                aText = a.querySelector('td:nth-child(2) span') ? a.querySelector('td:nth-child(2) span').textContent.trim().toLowerCase() : '';
                bText = b.querySelector('td:nth-child(2) span') ? b.querySelector('td:nth-child(2) span').textContent.trim().toLowerCase() : '';
                return aText.localeCompare(bText);  // Comparação alfabética
            case 'nome-desc':
                aText = a.querySelector('td:nth-child(2) span') ? a.querySelector('td:nth-child(2) span').textContent.trim().toLowerCase() : '';
                bText = b.querySelector('td:nth-child(2) span') ? b.querySelector('td:nth-child(2) span').textContent.trim().toLowerCase() : '';
                return bText.localeCompare(aText);  // Comparação alfabética inversa
            case 'preco-asc':
                aText = parseFloat(a.querySelector('td:nth-child(3)').textContent.replace('R$ ','').replace(',','.'));
                bText = parseFloat(b.querySelector('td:nth-child(3)').textContent.replace('R$ ','').replace(',','.'));
                return aText - bText;
            case 'preco-desc':
                aText = parseFloat(a.querySelector('td:nth-child(3)').textContent.replace('R$ ','').replace(',','.'));
                bText = parseFloat(b.querySelector('td:nth-child(3)').textContent.replace('R$ ','').replace(',','.'));
                return bText - aText;
            case 'quantidade-asc':
                aText = parseInt(a.querySelector('td:nth-child(4)').textContent);
                bText = parseInt(b.querySelector('td:nth-child(4)').textContent);
                return aText - bText;
            case 'quantidade-desc':
                aText = parseInt(a.querySelector('td:nth-child(4)').textContent);
                bText = parseInt(b.querySelector('td:nth-child(4)').textContent);
                return bText - aText;
            case 'lote-asc':
                aText = a.querySelector('td:nth-child(5)').textContent.trim().toLowerCase();
                bText = b.querySelector('td:nth-child(5)').textContent.trim().toLowerCase();
                return aText.localeCompare(bText);
            case 'lote-desc':
                aText = a.querySelector('td:nth-child(5)').textContent.trim().toLowerCase();
                bText = b.querySelector('td:nth-child(5)').textContent.trim().toLowerCase();
                return bText.localeCompare(aText);
            default: return 0;
        }
    });

    rows.forEach(r => tbody.appendChild(r)); // Reorganiza as linhas na tabela
});




document.querySelectorAll('.tag-item').forEach(tag => {
    tag.addEventListener('click', function() {
        const tagId = this.dataset.tagId;
        document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
            const tagsProduto = Array.from(tr.querySelectorAll('.tags-vinculadas i')).map(i => i.dataset.tagId);
            tr.style.display = tagsProduto.includes(tagId) ? '' : 'none';
        });
    });
});

function resetFiltro(){
    document.querySelectorAll('#tabela-produtos tr').forEach(tr => tr.style.display = '');
    document.getElementById('pesquisa').value = '';
}


document.querySelectorAll('.add-tag-square').forEach(square => {
    const dropdown = square.nextElementSibling; 
    square.addEventListener('click', function(e) {
        
        document.querySelectorAll('.tag-dropdown').forEach(dd => { if(dd !== dropdown) dd.style.display = 'none'; });

       
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
});


document.addEventListener('click', function(e){
    document.querySelectorAll('.tag-dropdown').forEach(dd => {
        if(!dd.contains(e.target) && !dd.previousElementSibling.contains(e.target)){
            dd.style.display = 'none';
        }
    });
});



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
<!-- Modal Importação -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-body p-0">
        <!-- Aqui entra o conteúdo do importador -->
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-header text-center py-3">
                            <h2><i class="fas fa-file-import me-2"></i>Importador de Produtos</h2>
                            <p class="mb-0">Faça upload de planilhas Excel para importar dados para o banco de dados</p>
                        </div>
                        <div class="card-body">
                            <!-- Upload -->
                            <div class="mb-4">
                                <h4><span class="step-number">1</span>Selecione o arquivo Excel</h4>
                                <div class="instructions mb-3">
                                    <p><i class="fas fa-info-circle me-2"></i>Formatos suportados: .xlsx, .xls</p>
                                    <p><i class="fas fa-info-circle me-2"></i>O arquivo deve seguir a estrutura da tabela de produtos</p>
                                </div>
                                <form id="uploadForm" enctype="multipart/form-data">
                                    <input class="form-control mb-3" type="file" id="formFile" name="excel_file" accept=".xlsx, .xls" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Fazer Upload e Importar
                                    </button>
                                </form>
                            </div>

                            <hr>

                            <!-- Resultado -->
                            <div id="importResult" class="d-none">
                                <h4><span class="step-number">2</span>Resultado da Importação</h4>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span id="successMessage">Dados importados com sucesso!</span>
                                </div>

                                <h5 class="mt-4">Dados Importados:</h5>
                                <div class="table-responsive mt-3">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Descrição</th>
                                                <th>Lote</th>
                                                <th>Estoque</th>
                                                <th>Preço</th>
                                                <th>Custo</th>
                                                <th>Data Reabastecimento</th>
                                            </tr>
                                        </thead>
                                        <tbody id="importedData"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template download -->
                    <div class="template-download mt-3 p-3 bg-light border rounded">
                        <h5><i class="fas fa-download me-2"></i>Template de Planilha</h5>
                        <p>Baixe nosso template para garantir que sua planilha tenha o formato correto.</p>
                        <a href="template_produtos.xlsx" class="btn btn-outline-success">
                            <i class="fas fa-file-excel me-2"></i>Baixar Template
                        </a>
                    </div>

                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const importResult = document.getElementById('importResult');
    const importedData = document.getElementById('importedData');
    const successMessage = document.getElementById('successMessage');

    if(uploadForm){
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importando...';
            submitBtn.disabled = true;

            fetch('importacao.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = `${data.imported} registros importados com sucesso!`;
                    displayImportedData(data.data);
                    importResult.classList.remove('d-none');
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(err => alert('Erro na importação: ' + err.message))
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    function displayImportedData(data) {
        importedData.innerHTML = '';
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.nome}</td>
                <td>${row.descricao}</td>
                <td>${row.lote}</td>
                <td>${row.quantidade_estoque}</td>
                <td>R$ ${parseFloat(row.preco_unitario).toFixed(2)}</td>
                <td>R$ ${parseFloat(row.custo_unitario).toFixed(2)}</td>
                <td>${row.data_reabastecimento}</td>
            `;
            importedData.appendChild(tr);
        });
    }
});
</script>


</body>
</html>
