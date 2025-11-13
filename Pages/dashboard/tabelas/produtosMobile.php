<?php
session_start();
require_once '../../../conexao.php';

// Verificar autenticação
if (!isset($_SESSION['loja_id'])) {
    header('Location: ../../Pages/auth/login.php');
    exit;
}

$loja_id = $_SESSION['loja_id'];

// Configuração de paginação
$linhasPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$inicio = ($paginaAtual - 1) * $linhasPorPagina;

// Filtros
$tagFiltro = isset($_GET['tag']) ? intval($_GET['tag']) : 0;
$ordenacao = isset($_GET['ordem']) ? $_GET['ordem'] : 'nome';

// Query base
$whereClause = "p.loja_id = ?";
$params = [$loja_id];
$types = "i";

// Adicionar filtro por tag se necessário
if ($tagFiltro > 0) {
    $whereClause .= " AND EXISTS (SELECT 1 FROM produto_tag pt WHERE pt.produto_id = p.id AND pt.tag_id = ?)";
    $params[] = $tagFiltro;
    $types .= "i";
}

// Definir ordenação
$orderBy = $ordenacao === 'preco' ? 'p.preco_unitario ASC' : 'p.nome ASC';

// Contar total de produtos
$sqlCount = "SELECT COUNT(DISTINCT p.id) as total FROM produtos p WHERE $whereClause";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalProdutos = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPaginas = ceil($totalProdutos / $linhasPorPagina);

// Buscar produtos
$sql = "SELECT p.id, p.nome, p.preco_unitario, p.quantidade_estoque, p.lote 
        FROM produtos p 
        WHERE $whereClause 
        ORDER BY $orderBy 
        LIMIT ?, ?";

$params[] = $inicio;
$params[] = $linhasPorPagina;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar tags de cada produto
foreach ($produtos as &$produto) {
    $sqlTags = "SELECT t.id, t.nome, t.icone, t.cor 
                FROM tags t 
                INNER JOIN produto_tag pt ON t.id = pt.tag_id 
                WHERE pt.produto_id = ?";
    $stmtTags = $conn->prepare($sqlTags);
    $stmtTags->bind_param("i", $produto['id']);
    $stmtTags->execute();
    $produto['tags'] = $stmtTags->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Buscar todas as tags para o filtro
$sqlAllTags = "SELECT DISTINCT t.id, t.nome, t.icone, t.cor 
               FROM tags t 
               INNER JOIN produto_tag pt ON t.id = pt.tag_id 
               INNER JOIN produtos p ON pt.produto_id = p.id 
               WHERE p.loja_id = ?";
$stmtAllTags = $conn->prepare($sqlAllTags);
$stmtAllTags->bind_param("i", $loja_id);
$stmtAllTags->execute();
$todasTags = $stmtAllTags->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: #121212;
  color: #e0e0e0;
  padding-bottom: 20px;
}

/* Navbar Mobile */
.navbar {
  background: #1b1b1b;
  box-shadow: 0 2px 8px rgba(0,0,0,0.6);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
}

.logo-mobile {
  left: 20px;
  top: 10px;
  width: 140px;      /* define tamanho grande e proporcional */
  height: auto;      /* mantém proporção */}

.hamburger {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #e0e0e0;
  padding: 8px;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: -100%;
  width: 280px;
  height: 100vh;
  background: #1b1b1b;
  box-shadow: 2px 0 8px rgba(0,0,0,0.6);
  transition: left 0.3s ease;
  z-index: 2000;
  overflow-y: auto;
  padding: 20px;
}

.sidebar.active { left: 0; }

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s, visibility 0.3s;
  z-index: 1500;
}
.overlay.active {
  opacity: 1;
  visibility: visible;
}

.close-sidebar {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  float: right;
  padding: 8px;
  color: #ccc;
}

.logo-area {
  margin: 0px 0 60px;
  text-align: center;
  clear: both;
}
.logo-area img { max-width: 350px; }

/* Navegação */
.nav-list {
  list-style: none;
  margin-bottom: 20px;
}
.nav-list li { margin-bottom: 8px; }

.nav-list a {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  color: #ccc;
  text-decoration: none;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}
.nav-list a:hover { background: #222; color: #ff9900; }
.nav-list a.active {
  background: #ff9900;
  color: #121212;
}

.nav-list a span {
  display: inline-flex;
  align-items: center;
  margin-right: 12px;
  width: 24px;
}
.nav-list a span img { width: 20px; height: 20px; }

hr {
  border: none;
  border-top: 1px solid #333;
  margin: 20px 0;
}

/* Links inferiores */
.bottom-links {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #333;
}
.bottom-links a {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  color: #ccc;
  text-decoration: none;
  border-radius: 8px;
  margin-bottom: 8px;
  transition: background 0.2s, color 0.2s;
}
.bottom-links a:hover { background: #222; color: #ff9900; }
.bottom-links a.active { color: #ff9900; font-weight: bold; }

.bottom-links a span {
  display: inline-flex;
  align-items: center;
  margin-right: 12px;
  width: 24px;
}
.bottom-links a span img { width: 20px; height: 20px; }

/* Conteúdo Principal */
.container {
  margin-top: 70px;
  padding: 16px;
}

/* Header e filtros */
.header-section {
  background: #1b1b1b;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.5);
}

.search-box {
  position: relative;
  margin-bottom: 16px;
}
.search-box input {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 1px solid #333;
  border-radius: 8px;
  font-size: 15px;
  background: #1e1e1e;
  color: #e0e0e0;
  outline: none;
  transition: border 0.2s;
}
.search-box input:focus {
  border-color: #ff9900;
}
.search-box i {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.filter-btn {
  padding: 8px 16px;
  border: 1px solid #333;
  background: #1b1b1b;
  color: #ccc;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}
.filter-btn:hover { background: #222; }
.filter-btn.active {
  background: #ff9900;
  color: #121212;
  border-color: #ff9900;
}

.tag-filters {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 8px 0;
  -webkit-overflow-scrolling: touch;
}
.tag-filter {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 20px;
  cursor: pointer;
  white-space: nowrap;
  font-size: 13px;
  border: 2px solid transparent;
  transition: all 0.2s;
  min-width: fit-content;
}
.tag-filter.active {
  border-color: #ff9900;
  color: #ff9900;
  font-weight: 600;
}
.tag-filter i { font-size: 14px; }

/* Produtos */
.produtos-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.produto-card {
  background: #1b1b1b;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.5);
  transition: box-shadow 0.2s;
}
.produto-card:active {
  box-shadow: 0 4px 12px rgba(0,0,0,0.6);
}

.produto-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 12px;
}
.produto-nome {
  font-size: 16px;
  font-weight: 600;
  color: #e0e0e0;
  flex: 1;
  line-height: 1.4;
}
.produto-preco {
  font-size: 18px;
  font-weight: 700;
  color: #ff9900;
  white-space: nowrap;
  margin-left: 12px;
}

.produto-info {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
  color: #aaa;
  font-size: 14px;
}
.info-item {
  display: flex;
  align-items: center;
  gap: 6px;
}
.info-item i { color: #888; font-size: 12px; }

.produto-tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.tag-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background: #222;
  color: #ff9900;
}
.tag-badge i { font-size: 11px; }

/* Paginação */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 24px;
  padding: 16px;
}
.pagination a,
.pagination span {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 40px;
  padding: 0 12px;
  border-radius: 8px;
  text-decoration: none;
  color: #ccc;
  background: #1b1b1b;
  border: 1px solid #333;
  transition: all 0.2s;
}
.pagination a:hover {
  background: #222;
  border-color: #ff9900;
  color: #ff9900;
}
.pagination .current {
  background: #ff9900;
  color: #121212;
  border-color: #ff9900;
  font-weight: 600;
}

/* Estado vazio */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}
.empty-state i {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}
.empty-state p { font-size: 16px; }

@media (max-width: 480px) {
  .container { padding: 12px; }
  .produto-card { padding: 14px; }
}
</style>

</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <img src="../../../img/logo2.svg" alt="Logo" class="logo-mobile">
        <button class="hamburger" id="hamburger">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button class="close-sidebar" id="closeSidebar">
            <i class="fas fa-times"></i>
        </button>
        <div class="logo-area">
            <img src="../../../img/logo2.svg" alt="Logo">
        </div>
        <nav class="nav-section">
            <div class="nav-menus">
                <ul class="nav-list middle-section">
                    <li><a href="produtosMobile.php" class="active"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    <li><a href="../operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
                </ul>
            </div>
            <div class="bottom-links">
                <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            </div>
        </nav>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container">
        <div class="header-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar produto...">
                <i class="fas fa-search"></i>
            </div>

            <div class="filters">
                <select class="filter-btn" id="ordenacao" onchange="window.location.href='?ordem='+this.value+'<?php echo $tagFiltro ? '&tag='.$tagFiltro : ''; ?>'">
                    <option value="nome" <?php echo $ordenacao === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="preco" <?php echo $ordenacao === 'preco' ? 'selected' : ''; ?>>Preço</option>
                </select>
            </div>

            <?php if (count($todasTags) > 0): ?>
            <div class="tag-filters">
                <div class="tag-filter <?php echo $tagFiltro === 0 ? 'active' : ''; ?>" 
                     style="background: #e0e0e0; color: #555;"
                     onclick="window.location.href='?ordem=<?php echo $ordenacao; ?>'">
                    <i class="fas fa-list"></i>
                    <span>Todas</span>
                </div>
                <?php foreach ($todasTags as $tag): ?>
                <div class="tag-filter <?php echo $tagFiltro === $tag['id'] ? 'active' : ''; ?>" 
                     style="background: <?php echo htmlspecialchars($tag['cor']); ?>20; color: <?php echo htmlspecialchars($tag['cor']); ?>;"
                     onclick="window.location.href='?tag=<?php echo $tag['id']; ?>&ordem=<?php echo $ordenacao; ?>'">
                    <i class="<?php echo htmlspecialchars($tag['icone']); ?>"></i>
                    <span><?php echo htmlspecialchars($tag['nome']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="produtos-grid" id="produtosGrid">
            <?php if (count($produtos) > 0): ?>
                <?php foreach ($produtos as $produto): ?>
                <div class="produto-card" data-nome="<?php echo strtolower(htmlspecialchars($produto['nome'])); ?>">
                    <div class="produto-header">
                        <div class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></div>
                        <div class="produto-preco">R$ <?php echo number_format($produto['preco_unitario'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="produto-info">
                        <div class="info-item">
                            <i class="fas fa-boxes"></i>
                            <span><?php echo $produto['quantidade_estoque']; ?> un.</span>
                        </div>
                        <?php if ($produto['lote']): ?>
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <span>Lote: <?php echo htmlspecialchars($produto['lote']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($produto['tags']) > 0): ?>
                    <div class="produto-tags">
                        <?php foreach ($produto['tags'] as $tag): ?>
                        <span class="tag-badge" style="background: <?php echo htmlspecialchars($tag['cor']); ?>20; color: <?php echo htmlspecialchars($tag['cor']); ?>;">
                            <i class="<?php echo htmlspecialchars($tag['icone']); ?>"></i>
                            <?php echo htmlspecialchars($tag['nome']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>Nenhum produto encontrado</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPaginas > 1): ?>
        <div class="pagination">
            <?php if ($paginaAtual > 1): ?>
                <a href="?pagina=<?php echo $paginaAtual - 1; ?>&ordem=<?php echo $ordenacao; ?><?php echo $tagFiltro ? '&tag='.$tagFiltro : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $inicio_pag = max(1, $paginaAtual - 2);
            $fim_pag = min($totalPaginas, $paginaAtual + 2);
            
            for ($i = $inicio_pag; $i <= $fim_pag; $i++):
            ?>
                <?php if ($i === $paginaAtual): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?pagina=<?php echo $i; ?>&ordem=<?php echo $ordenacao; ?><?php echo $tagFiltro ? '&tag='.$tagFiltro : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="?pagina=<?php echo $paginaAtual + 1; ?>&ordem=<?php echo $ordenacao; ?><?php echo $tagFiltro ? '&tag='.$tagFiltro : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Menu hambúrguer
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const closeSidebar = document.getElementById('closeSidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburger.addEventListener('click', toggleSidebar);
        closeSidebar.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Busca em tempo real
        const searchInput = document.getElementById('searchInput');
        const produtosGrid = document.getElementById('produtosGrid');
        const produtoCards = produtosGrid.querySelectorAll('.produto-card');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;

            produtoCards.forEach(card => {
                const produtoNome = card.dataset.nome;
                if (produtoNome.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Mostrar mensagem se não houver resultados
            const emptyState = produtosGrid.querySelector('.empty-state');
            if (visibleCount === 0 && !emptyState) {
                produtosGrid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>Nenhum produto encontrado para "${searchTerm}"</p>
                    </div>
                `;
            } else if (visibleCount > 0 && emptyState) {
                produtoCards.forEach(card => {
                    if (card.dataset.nome.includes(searchTerm)) {
                        card.style.display = 'block';
                    }
                });
            }
        });

        // Prevenir scroll do body quando sidebar estiver aberta
        sidebar.addEventListener('transitionend', function() {
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    </script>
</body>
</html>