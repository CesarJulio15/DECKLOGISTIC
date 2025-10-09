<?php 
include '../../../conexao.php'; 
include '../../../header.php';
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

/* Botão flutuante */
#dica-btn-flutuante {

  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #ff6600;
  color: #fff;
  border: none;
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
  z-index: 10002;
  display: flex;
  align-items: center;
  justify-content: center;

}
#dica-btn-flutuante:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.28);
}

/* Overlay 1 */
#dica-overlay-1 {
    position: fixed;
    right: 90px;
    bottom: 90px;
    z-index: 1300;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    pointer-events: none;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
}
#dica-overlay-1 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-1 .dica-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 320px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    margin-bottom: 10px;
    z-index: 2;
}

/* Overlay 2 */
#dica-overlay-2 {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    z-index: 1400;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
}
#dica-overlay-2 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-2 .dica-card {
    position: absolute;
    z-index: 3000; /* Bem acima do blur e dos botões */
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
}
#dica-overlay-2 .dica-card h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}
#dica-overlay-2 .dica-card button {
    margin-top: 12px;
    background: #ff6600;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
}

/* Destaca botões reais */
#dica-overlay-2.active #acoes-itens-btn,
#dica-overlay-2.active #import-btn {
    position: relative !important;
    z-index: 2500 !important;
    box-shadow: 0 0 0 5px #fff, 0 0 0 10px #ff6600;
    outline: 2px solid #ff6600;
    transition: box-shadow 0.2s;
    pointer-events: auto !important;
    filter: none !important;
}

/* Garante que outros elementos fiquem borrados */
#dica-overlay-2.active > *:not(.dica-card):not(.dica-blur-bg):not(#acoes-itens-btn):not(#import-btn) {
    filter: blur(2px);
    pointer-events: none;
}
</style>
</head>
<body>
<div class="content">
  <!-- Sidebar -->
<div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../../img/logo2.svg" alt="Logo">
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
          <li><a href="../../dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
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
  
<!-- Primeiro overlay -->
<div id="welcome-overlay">
    <div class="welcome-card">
        <h2>Seja bem-vindo!</h2>
        <p>Essa é a página de produtos. Aqui você pode gerenciar seus itens e tags.</p>
        <button id="close-welcome">Entendi</button>
    </div>
</div>

<style>
/* Primeiro overlay ocupa toda a tela */
#welcome-overlay {
     display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    justify-content: flex-end;
    align-items: flex-start;
    z-index: 1000;
    padding: 30px;
    padding-top: 700px; /* ajusta altura se quiser */
}

/* Card do overlay */
#welcome-overlay .welcome-card {
    background: #000;
    padding: 20px 30px;
    border-radius: 10px;
    max-width: 300px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    text-align: left;
    color: #fff;
}

#welcome-overlay .welcome-card h2 {
    margin-bottom: 10px;
    font-size: 18px;
}

#welcome-overlay .welcome-card p {
    font-size: 14px;
    margin-bottom: 15px;
}

/* Botão */
#welcome-overlay .welcome-card button {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    background: #ff6600;
    color: #fff;
    cursor: pointer;
}
</style>

<script>
document.getElementById('close-welcome').addEventListener('click', function() {
    document.getElementById('welcome-overlay').style.display = 'none';

    const overlay = document.getElementById('acoes-overlay');
    const card = overlay.querySelector('.welcome-card');
    const btn = document.getElementById('acoes-itens-btn');
    const rect = btn.getBoundingClientRect();

    card.style.top = (rect.bottom + window.scrollY + 10) + 'px';
    card.style.left = (rect.left + window.scrollX) + 'px';

    overlay.style.display = 'block';
    btn.style.position = 'relative';
    btn.style.zIndex = 2000;
});
</script>

<main class="dashboard">
<div class="content">
<div class="conteudo">
<h1>Produtos</h1>

<div class="acoes">
    <div class="botoes">
        <div class="pesquisa-produtos" style="margin-bottom:15px;">
            <input type="text" id="pesquisa" placeholder="Pesquisar produto..." style="padding:8px 12px; width:350px; height: 45px; border-radius:36px; border:1px solid #ccc; font-size:14px; outline:none; transition:all 0.2s ease;">
        </div>
   <button class="btn-novo" id="acoes-itens-btn" onclick="window.location.href='../gerenciamento_produtos.php'">Ações Itens</button>

<!-- Segundo overlay -->
<div id="acoes-overlay" style="display:none;">
    <div class="blur-bg"></div>
    <div class="welcome-card">
        <h2>Ações Itens</h2>
        <p>Você pode gerenciar os produtos clicando nos botões abaixo.</p>
        <button id="close-acoes">Fechar</button>
    </div>
</div>

<style>
/* Overlay */
#acoes-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 1000;
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
}

/* Blur cobrindo a tela */
#acoes-overlay .blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    backdrop-filter: blur(4px);
    background: rgba(0,0,0,0.3);
    z-index: 1;
}

/* Card acima do blur */
#acoes-overlay .welcome-card {
    position: absolute;
    z-index: 2;
    background: #000;
    padding: 20px 30px;
    border-radius: 10px;
    max-width: 300px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    color: #fff;
}

/* Botão dentro do card */
#acoes-overlay .welcome-card button {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    background: #ff6600;
    color: #fff;
    cursor: pointer;
}

/* Botão Ações Itens normal (não afeta primeiro overlay) */
#acoes-itens-btn {
    padding: 8px 16px;
    border-radius: 6px;
    background: linear-gradient(135deg, rgba(255, 153, 0, 0.9), rgba(255, 200, 0, 0.9));
    color: #fff;
    border: none;
    cursor: pointer;
    position: static; /* normal */
    z-index: auto;
}
</style>

<script>
document.getElementById('close-acoes').addEventListener('click', function() {
    document.getElementById('acoes-overlay').style.display = 'none';

    // Restaura o botão Ações Itens
    const btnAcoes = document.getElementById('acoes-itens-btn');
    btnAcoes.style.position = 'static';
    btnAcoes.style.zIndex = 'auto';

    // Abre o terceiro overlay automaticamente
    const overlayImport = document.getElementById('import-overlay');
    const cardImport = overlayImport.querySelector('.welcome-card');
    const btnImport = document.getElementById('import-btn');
    const rectImport = btnImport.getBoundingClientRect();

    cardImport.style.top = (rectImport.bottom + window.scrollY + 10) + 'px';
    cardImport.style.left = (rectImport.left + window.scrollX) + 'px';

    overlayImport.style.display = 'block';
    btnImport.style.position = 'relative';
    btnImport.style.zIndex = 2000;
});

</script>


    
    <!-- Botão Importar -->
<button class="btn-novo" id="import-btn" data-bs-toggle="modal" data-bs-target="#importModal">Importar</button>

<!-- Terceiro overlay -->
<div id="import-overlay" style="display:none;">
    <div class="blur-bg"></div> <!-- blur de fundo -->
    <div class="welcome-card">
        <h2>Importar Produtos</h2>
        <p>Aqui você pode importar seus produtos diretamente de um arquivo do Excel.</p>
        <button id="close-import">Fechar</button>
    </div>
</div>

<style>
/* Overlay terceiro */
#import-overlay {
    display: none;
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    z-index:1000;
    display:flex;
    justify-content:flex-start;
    align-items:flex-start;
}

/* Blur cobrindo toda a tela */
#import-overlay .blur-bg {
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    backdrop-filter: blur(4px);
    background: rgba(0,0,0,0.3);
    z-index:1;
}

/* Card acima do blur */
#import-overlay .welcome-card {
    position: absolute;
    z-index:2;
    background:#000;
    padding:20px 30px;
    border-radius:10px;
    max-width:300px;
    box-shadow:0 0 15px rgba(0,0,0,0.3);
    color:#fff;
}

/* Botões dentro do card */
#import-overlay .welcome-card button {
    padding:6px 12px;
    border:none;
    border-radius:6px;
    background:#ff6600;
    color:#fff;
    cursor:pointer;
}

/* Botão Importar fora do blur */
#import-btn {
    position: static; /* será alterado quando o overlay abrir */
    z-index: auto;
    padding: 8px 16px;
    border-radius:6px;
       background: linear-gradient(135deg, rgba(255, 153, 0, 0.9), rgba(255, 200, 0, 0.9));
    color:#fff;
    border:none;
    cursor:pointer;
}
</style>

<script>
document.getElementById('import-btn').addEventListener('click', function() {
    const overlay = document.getElementById('import-overlay');
    const card = overlay.querySelector('.welcome-card');
    const btn = document.getElementById('import-btn');
    const rect = btn.getBoundingClientRect();

    // Posiciona o card abaixo do botão
    card.style.top = (rect.bottom + window.scrollY + 10) + 'px';
    card.style.left = (rect.left + window.scrollX) + 'px';

    overlay.style.display = 'block';

    // Botão fora do blur
    btn.style.position = 'relative';
    btn.style.zIndex = 2000;
});

document.getElementById('close-import').addEventListener('click', function() {
    const overlay = document.getElementById('import-overlay');
    overlay.style.display = 'none';

    // Restaura botão
    const btn = document.getElementById('import-btn');
    btn.style.position = 'static';
    btn.style.zIndex = 'auto';
});
</script>

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
    .then data => {
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

<!-- Botão de dica flutuante -->
<button id="dica-btn-flutuante" title="Dica rápida">
 ?
</button>

<!-- Overlay 1: Dica inicial -->
<div id="dica-overlay-1" style="display:none;">
    <div class="dica-blur-bg"></div>
    <div class="dica-card" id="dica-card-1">
        <h3>Dica rápida</h3>
        <p>Esta página permite visualizar, pesquisar, ordenar e gerenciar os produtos da sua loja. Você pode também vincular tags e importar produtos via Excel.</p>
        <button id="dica-avancar-1">Avançar</button>
    </div>
</div>

<!-- Overlay 2: Dica sobre ações/importação -->
<div id="dica-overlay-2" style="display:none;">
    <div class="dica-blur-bg"></div>
    <div class="dica-card" id="dica-card-2">
        <h3>Gerencie seus produtos</h3>
        <p>Use os botões <b>Ações Itens</b> para editar/excluir produtos e <b>Importar</b> para adicionar produtos em lote via planilha Excel.</p>
        <button id="dica-fechar-2">Fechar</button>
    </div>
</div>

<style>
/* Botão flutuante */
#dica-btn-flutuante {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #ff6600;
  color: #fff;
  border: none;
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
  z-index: 10002;
  display: flex;
  align-items: center;
  justify-content: center;
}
#dica-btn-flutuante:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.28);
}

/* Overlay 1 */
#dica-overlay-1 {
    position: fixed;
    right: 90px;
    bottom: 90px;
    z-index: 1300;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    pointer-events: none;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
}
#dica-overlay-1 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-1 .dica-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 320px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    margin-bottom: 10px;
    z-index: 2;
}

/* Overlay 2 */
#dica-overlay-2 {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    z-index: 1400;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
}
#dica-overlay-2 .dica-blur-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    backdrop-filter: blur(10px);
    background: rgba(0,0,0,0.25);
    z-index: 1;
    pointer-events: none;
}
#dica-overlay-2 .dica-card {
    position: absolute;
    z-index: 3000; /* Bem acima do blur e dos botões */
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
}
#dica-overlay-2 .dica-card h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}
#dica-overlay-2 .dica-card button {
    margin-top: 12px;
    background: #ff6600;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
}

/* Destaca botões reais */
#dica-overlay-2.active #acoes-itens-btn,
#dica-overlay-2.active #import-btn {
    position: relative !important;
    z-index: 2500 !important;
    box-shadow: 0 0 0 5px #fff, 0 0 0 10px #ff6600;
    outline: 2px solid #ff6600;
    transition: box-shadow 0.2s;
    pointer-events: auto !important;
    filter: none !important;
}

/* Garante que outros elementos fiquem borrados */
#dica-overlay-2.active > *:not(.dica-card):not(.dica-blur-bg):not(#acoes-itens-btn):not(#import-btn) {
    filter: blur(2px);
    pointer-events: none;
}
</style>

<script>
// Dica flutuante
const dicaBtn = document.getElementById('dica-btn-flutuante');
const dicaOverlay1 = document.getElementById('dica-overlay-1');
const dicaAvancar1 = document.getElementById('dica-avancar-1');
const dicaOverlay2 = document.getElementById('dica-overlay-2');
const dicaCard2 = document.getElementById('dica-card-2');
const dicaFechar2 = document.getElementById('dica-fechar-2');
const btnAcoes = document.getElementById('acoes-itens-btn');
const btnImport = document.getElementById('import-btn');

dicaBtn.addEventListener('click', function() {
    dicaOverlay1.style.display = 'flex';
});

dicaAvancar1.addEventListener('click', function() {
    dicaOverlay1.style.display = 'none';

    // Calcula posição dos botões reais
    const rectAcoes = btnAcoes.getBoundingClientRect();
    const rectImport = btnImport.getBoundingClientRect();

    // Posiciona o card ao lado direito e acima dos botões, sem cobrir
    const top = Math.min(rectAcoes.top, rectImport.top) + window.scrollY - 70;
    const left = rectImport.right + window.scrollX + 30;

    dicaCard2.style.top = top + 'px';
    dicaCard2.style.left = left + 'px';

    dicaOverlay2.style.display = 'flex';
    dicaOverlay2.classList.add('active');

    btnAcoes.style.zIndex = 2500;
    btnAcoes.style.pointerEvents = 'auto';
    btnImport.style.zIndex = 2500;
    btnImport.style.pointerEvents = 'auto';
});

dicaFechar2.addEventListener('click', function() {
    dicaOverlay2.style.display = 'none';
    dicaOverlay2.classList.remove('active');
    btnAcoes.style.zIndex = '';
    btnAcoes.style.pointerEvents = '';
    btnImport.style.zIndex = '';
    btnImport.style.pointerEvents = '';
});

// Fecha overlays ao clicar fora do card
document.addEventListener('click', function(e) {
    if (dicaOverlay1.style.display === 'flex' && !e.target.closest('#dica-card-1') && !e.target.closest('#dica-btn-flutuante')) {
        dicaOverlay1.style.display = 'none';
    }
    if (dicaOverlay2.style.display === 'flex' && !e.target.closest('#dica-card-2')) {
        dicaOverlay2.style.display = 'none';
        dicaOverlay2.classList.remove('active');
        btnAcoes.style.zIndex = '';
        btnImport.style.zIndex = '';
    }
});

// Impede propagação do clique dentro dos cards
document.querySelectorAll('.dica-card').forEach(card => {
    card.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>
</body>
</html>
</script>
</body>
</html>
