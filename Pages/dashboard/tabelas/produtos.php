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
          <li><a href="../../dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Histórico</a></li>
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
document.getElementById('import-btn').addEventListener('click', function(e) {
    // evita que o clique dispare handlers globais/propagação
    e.preventDefault();
    e.stopPropagation();

    // marca que abrimos o import diretamente (suprime dicas)
    window._suppressDica = true;

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


document.getElementById('close-import').addEventListener('click', function(e) {
    e.stopPropagation();
    const overlay = document.getElementById('import-overlay');
    overlay.style.display = 'none';

    // Restaura botão
    const btn = document.getElementById('import-btn');
    btn.style.position = 'static';
    btn.style.zIndex = 'auto';

    // Permite que as dicas voltem a abrir normalmente
    window._suppressDica = false;
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
// ------------- UTILIDADES -------------
function normalizeStr(s) {
    if (!s && s !== 0) return '';
    // trim, lowercase, remove acentos (normalização NFD)
    try {
        return String(s).trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    } catch (e) {
        // fallback simples se normalize não existir
        return String(s).trim().toLowerCase().replace(/[\u0300-\u036f]/g, '');
    }
}

function textoNomeDoRow(tr) {
    // pega a célula da "coluna nome" (2ª coluna lógica, considerando que a coluna de checkbox existe)
    const td = tr.querySelector('td:nth-child(2)');
    if (!td) return '';

    // 1) tenta achar o <span> que NÃO é .tags-vinculadas (esse é o nome)
    let nameSpan = td.querySelector('span:not(.tags-vinculadas)');
    if (nameSpan && nameSpan.textContent.trim() !== '') {
        return normalizeStr(nameSpan.textContent);
    }

    // 2) fallback: monta o texto a partir de nós de texto diretos na td (caso a estrutura mude)
    let textParts = [];
    td.childNodes.forEach(node => {
        if (node.nodeType === Node.TEXT_NODE) {
            const t = node.textContent.trim();
            if (t) textParts.push(t);
        } else if (node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === 'span' && !node.classList.contains('tags-vinculadas')) {
            const t = node.textContent.trim();
            if (t) textParts.push(t);
        }
    });
    return normalizeStr(textParts.join(' ').trim());
}

function parsePrecoText(text) {
    // Recebe algo como "R$ 1.234,56" ou "R$ 12,34" e retorna número
    if (!text) return 0;
    let s = String(text).replace(/\s/g, '').replace('R$', '').trim();
    s = s.replace(/\./g, ''); // remove pontos de milhar
    s = s.replace(',', '.'); // vírgula decimal -> ponto
    const n = parseFloat(s);
    return isNaN(n) ? 0 : n;
}

// ------------- PESQUISA -------------
const pesquisaInput = document.getElementById('pesquisa');
if (pesquisaInput) {
    pesquisaInput.addEventListener('input', function() {
        const termo = normalizeStr(this.value);
        document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
            const nome = textoNomeDoRow(tr);

            const loteTd = tr.querySelector('td:nth-child(5)');
            const lote = loteTd ? normalizeStr(loteTd.textContent) : '';

            const precoTd = tr.querySelector('td:nth-child(3)');
            const preco = precoTd ? normalizeStr(precoTd.textContent) : '';

            const matches = nome.includes(termo) || lote.includes(termo) || preco.includes(termo);
            tr.style.display = matches ? '' : 'none';
        });
    });
}

// ------------- ORDENAÇÃO -------------
const ordenarSelect = document.getElementById('ordenar');
if (ordenarSelect) {
    ordenarSelect.addEventListener('change', function() {
        const tbody = document.getElementById('tabela-produtos');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const val = this.value;

        rows.sort((a, b) => {
            switch (val) {
                case 'nome-asc': {
                    const aText = textoNomeDoRow(a);
                    const bText = textoNomeDoRow(b);
                    return aText.localeCompare(bText, 'pt', { sensitivity: 'base' });
                }
                case 'nome-desc': {
                    const aText = textoNomeDoRow(a);
                    const bText = textoNomeDoRow(b);
                    return bText.localeCompare(aText, 'pt', { sensitivity: 'base' });
                }
                case 'preco-asc': {
                    const aNum = parsePrecoText(a.querySelector('td:nth-child(3)')?.textContent || '');
                    const bNum = parsePrecoText(b.querySelector('td:nth-child(3)')?.textContent || '');
                    return aNum - bNum;
                }
                case 'preco-desc': {
                    const aNum = parsePrecoText(a.querySelector('td:nth-child(3)')?.textContent || '');
                    const bNum = parsePrecoText(b.querySelector('td:nth-child(3)')?.textContent || '');
                    return bNum - aNum;
                }
                case 'quantidade-asc': {
                    const aNum = parseInt(a.querySelector('td:nth-child(4)')?.textContent || '0') || 0;
                    const bNum = parseInt(b.querySelector('td:nth-child(4)')?.textContent || '0') || 0;
                    return aNum - bNum;
                }
                case 'quantidade-desc': {
                    const aNum = parseInt(a.querySelector('td:nth-child(4)')?.textContent || '0') || 0;
                    const bNum = parseInt(b.querySelector('td:nth-child(4)')?.textContent || '0') || 0;
                    return bNum - aNum;
                }
                default:
                    return 0;
            }
        });

        rows.forEach(r => tbody.appendChild(r));
    });
}
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

dicaBtn.addEventListener('click', function(e) {
    // se estivermos com supressão ativa (ex: abrimos import), não abrir a dica
    if (window._suppressDica) {
        // opcional: debug no console
        // console.log('Dica suprimida porque import foi acionado.');
        return;
    }
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

<script>
// garante flag padrão
window._suppressDica = window._suppressDica || false;

(function(){
  const dicaBtn = document.getElementById('dica-btn-flutuante');
  const dicaOverlay1 = document.getElementById('dica-overlay-1');
  const dicaAvancar1 = document.getElementById('dica-avancar-1');
  const dicaOverlay2 = document.getElementById('dica-overlay-2');

  const importBtn = document.getElementById('import-btn');
  const importModalEl = document.getElementById('importModal');

  // Se o Bootstrap estiver presente, escuta os eventos do modal para suprimir dicas
  if (importModalEl && typeof bootstrap !== 'undefined') {
    importModalEl.addEventListener('show.bs.modal', function() {
      window._suppressDica = true;
      // Fecha imediatamente qualquer dica visível
      if (dicaOverlay1) dicaOverlay1.style.display = 'none';
      if (dicaOverlay2) {
        dicaOverlay2.style.display = 'none';
        dicaOverlay2.classList.remove('active');
      }
    });

    importModalEl.addEventListener('hidden.bs.modal', function() {
      // reativa dicas quando o modal for fechado
      window._suppressDica = false;
    });
  }

  // Protege abertura via botão de dica
  if (dicaBtn) {
    dicaBtn.addEventListener('click', function(e) {
      if (window._suppressDica) return;
      // já existente: abre a dica 1
      dicaOverlay1.style.display = 'flex';
    });
  }

  // Protege botão avançar (se alguém tentar avançar programaticamente)
  if (dicaAvancar1) {
    dicaAvancar1.addEventListener('click', function() {
      if (window._suppressDica) return;
      // restante da lógica já existente para posicionar e abrir overlay2
      // (se você já tem esse código, ele continuará rodando; essa checagem evita abertura)
      // ... seu código de posicionamento permanece
    });
  }

  // Em caso de outros handlers que abram dicas programaticamente, você pode usar:
  // if (window._suppressDica) return;  antes de qualquer dicaOverlayX.style.display = '...';
})();
</script>

</body>
</html>
</script>
</body>
</html>
