<?php 
include '../../../conexao.php'; 
session_start();

// Verifica se está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$tipo_login = $_SESSION['tipo_login']; // 'empresa' ou 'funcionario'

// Descobre a loja_id correta
if ($tipo_login === 'empresa') {
    $lojaId = $_SESSION['loja_id']; 
} else {
    // Se for funcionário, pega a loja dele
    $res = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
    $res->bind_param("i", $usuarioId);
    $res->execute();
    $res = $res->get_result();
    $lojaId = $res->fetch_assoc()['loja_id'] ?? 0;
}

// Se não achou loja, bloqueia
if (!$lojaId) {
    die("Acesso negado. Loja não encontrada.");
}

// ---- PAGINAÇÃO ----
$linhasPorPagina = 14;
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Total de produtos
$totalProdutosResult = $conn->prepare("SELECT COUNT(*) as total FROM produtos WHERE loja_id = ?");
$totalProdutosResult->bind_param("i", $lojaId);
$totalProdutosResult->execute();
$totalProdutos = $totalProdutosResult->get_result()->fetch_assoc()['total'];

$totalPaginas = ceil($totalProdutos / $linhasPorPagina);
$inicio = ($paginaAtual - 1) * $linhasPorPagina;

// Busca produtos
$sql = "SELECT id, nome, preco_unitario, quantidade_estoque, lote 
        FROM produtos 
        WHERE loja_id = ? 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $lojaId, $inicio, $linhasPorPagina);
$stmt->execute();
$result = $stmt->get_result();

// ---- TAGS ----
$tags = [];
$tagResult = $conn->query("SELECT * FROM tags WHERE deletado_em IS NULL AND loja_id = $lojaId ORDER BY criado_em DESC");
if ($tagResult) {
    while ($row = $tagResult->fetch_assoc()) {
        $tags[] = $row;
    }
}

$produtoTags = [];
$tagVincResult = $conn->query("
    SELECT pt.produto_id, pt.tag_id, t.icone, t.cor 
    FROM produto_tag pt
    JOIN tags t ON pt.tag_id = t.id
    WHERE pt.produto_id IN (SELECT id FROM produtos WHERE loja_id = $lojaId)
      AND t.deletado_em IS NULL
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.add-tag-square { width:24px; height:24px; background:#000; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:bold; border-radius:6px; margin-left:5px; }
.tag-dropdown { display:none; position:absolute; background:#fff; border:1px solid #ccc; padding:5px; border-radius:4px; z-index:10; }
.tag-option { padding:2px 5px; cursor:pointer; }
</style>
</head>
<body>
<div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li><a href="../financas.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li class="active"><a href="../../dashboard/tabelas/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../tag.php"><span><img src="../../../img/tag.svg" alt="Tags"></span> Tags</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../../Pages/auth/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

<main class="dashboard">
<div class="content">
<div class="conteudo">
<h1>Produtos</h1>

<div class="acoes">
    <div class="botoes">
        <div class="pesquisa-produtos" style="margin-bottom:15px;">
            <input type="text" id="pesquisa" placeholder="Pesquisar produto..." style="padding:8px 12px; width:350px; height: 45px; border-radius:36px; border:1px solid #ccc; font-size:14px; outline:none; transition:all 0.2s ease;">
        </div>
        <button class="btn-novo" onclick="window.location.href='../gerenciamento_produtos.php'">Ações Itens</button>
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
            <i class="fa-solid fa-xmark" style="color: #ffffffff;"></i>
        </button>
        
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
<?php if ($totalPaginas > 1): ?>
<div style="margin-top:10px; display:flex; justify-content:center; gap:5px;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>" 
           class="<?= ($i == $paginaAtual) ? 'active' : '' ?>"
           style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; 
                  border:1px solid #555; border-radius:4px; text-decoration:none; color:#fff;">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

<style>
a.active {
    border: 2px solid #ff6600 !important; /* só a borda laranja */
    color: #fff !important;               /* mantém o texto branco */
    font-weight: normal;                   /* opcional, sem negrito */
    background-color: transparent;         /* mantém fundo transparente */
}
</style>
<?php endif; ?>

<script>



// Pesquisa
document.getElementById('pesquisa').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
        const spans = tr.querySelectorAll('td:nth-child(2) span');
        const nome = spans.length ? spans[spans.length - 1].textContent.toLowerCase() : '';
        tr.style.display = nome.includes(termo) ? '' : 'none';
    });
});

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
</script>
<script>
// Abrir/fechar dropdown ao clicar no "+"
document.querySelectorAll('.add-tag-square').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const produtoId = this.dataset.produtoId;
        const dropdown = document.getElementById('tag-dropdown-' + produtoId);

        // Fecha outros dropdowns
        document.querySelectorAll('.tag-dropdown').forEach(d => {
            if (d !== dropdown) d.style.display = 'none';
        });

        // Alterna visibilidade
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
});

// Selecionar uma tag no dropdown
// Selecionar uma tag no dropdown
document.querySelectorAll('.tag-option').forEach(opt => {
    opt.addEventListener('click', function() {
        const tagId = this.dataset.tagId;
        const produtoId = this.closest('.tag-dropdown').id.replace('tag-dropdown-', '');

        fetch('vincular_tag.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `produto_id=${produtoId}&tag_id=${tagId}`
        })
        .then(res => res.text())
        .then(data => {
            if (data.trim() === 'ok') {
                const container = document.getElementById('tags-produto-' + produtoId);

                // Remove qualquer tag anterior
                container.innerHTML = '';

                // Adiciona a nova tag e garante o data-tag-id
                const icone = this.querySelector('i').cloneNode(true);
                icone.dataset.tagId = tagId; // <<< AQUI o segredo
                container.appendChild(icone);

                this.closest('.tag-dropdown').style.display = 'none';
            } else {
                alert('Erro ao vincular tag: ' + data);
            }
        });
    });
});

// Fecha dropdowns ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.add-tag-square') && !e.target.closest('.tag-dropdown')) {
        document.querySelectorAll('.tag-dropdown').forEach(d => d.style.display = 'none');
    }
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
                        <p>Baixe nosso template para garantir que sua planilha tenha o formato correto para importação</p>
                        <a href="../../../assets/templates/ProdutoTemplate.xlsx" class="btn btn-success" download>
                            <i class="fas fa-download me-2"></i>Baixar Template
                        </a>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
// Filtro por tag
document.querySelectorAll('.tag-item').forEach(tag => {
    tag.addEventListener('click', function() {
        const tagId = this.dataset.tagId;

        document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
            const icones = tr.querySelectorAll('.tags-vinculadas i');
            let possuiTag = false;

            icones.forEach(icon => {
                if (icon.dataset.tagId === tagId) {
                    possuiTag = true;
                }
            });

            tr.style.display = possuiTag ? '' : 'none';
        });
    });
});

// Resetar filtro
function resetFiltro() {
    document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
        tr.style.display = '';
    });
}
</script>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
