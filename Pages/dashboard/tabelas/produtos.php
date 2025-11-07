<?php 
include '../../../conexao.php'; 
session_start();
require_once __DIR__ . '/../../../session_check.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$tipo_login = $_SESSION['tipo_login'];

// Descobre a loja_id correta
if ($tipo_login === 'empresa') {
    $lojaId = $_SESSION['loja_id']; 
} else {
    $res = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
    $res->bind_param("i", $usuarioId);
    $res->execute();
    $res = $res->get_result();
    $lojaId = $res->fetch_assoc()['loja_id'] ?? 0;
}

if (!$lojaId) {
    die("Acesso negado. Loja não encontrada.");
}

// ====== BACKEND GERENCIAMENTO PRODUTOS (AJAX) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Previne qualquer saída HTML
    ob_clean();
    header('Content-Type: application/json');
    
    // Garante que nenhum HTML será incluído na resposta
    error_reporting(0);
    
    $acao = $_POST['acao'] ?? '';

    // ADICIONAR PRODUTO
if ($acao === 'adicionar_produto') {
    $nome = trim($_POST['nome'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $custo = floatval($_POST['custo'] ?? 0);
    $estoque = intval($_POST['estoque'] ?? 0);
    $lote = '';

    // Se usuário for empresa ($tipo_login === 'empresa'), deixamos null (ou 0) no campo usuario_id do produto
    $usuario_id_produto = ($tipo_login === 'empresa') ? null : $usuarioId;

    $stmt = $conn->prepare("
        INSERT INTO produtos (nome, preco_unitario, custo_unitario, quantidade_estoque, lote, loja_id, usuario_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt) {
        // Ajuste de tipos: s = string, d = double, i = integer
        // nome (s), preco (d), custo (d), estoque (i), lote (s), lojaId (i), usuario_id_produto (i)
        // Para evitar problemas com NULL no bind, convertemos usuario_id_produto para 0 quando for null
        $usuario_id_for_bind = $usuario_id_produto === null ? 0 : $usuario_id_produto;
        $stmt->bind_param("sddisii", $nome, $preco, $custo, $estoque, $lote, $lojaId, $usuario_id_for_bind);
        $stmt->execute();
        $idNovoProduto = $stmt->insert_id;
        $stmt->close();

        // Inserir histórico de adição (já existente)
        $stmtHist = $conn->prepare("
            INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
            VALUES (?, ?, ?, 'adicionado', ?, NOW())
        ");
        if ($stmtHist) {
            $stmtHist->bind_param("isii", $idNovoProduto, $nome, $estoque, $usuario_id_for_bind);
            $stmtHist->execute();
            $stmtHist->close();
        }

        // --- NOVO: Registrar movimentação de ENTRADA para a quantidade inicial ---
        if ($estoque > 0) {
            $tipo = 'entrada';
            $data_mov = date('Y-m-d');
            // custo_mov usamos o custo informado; se custo for zero, registra 0
            $custo_mov = $custo;

            $stmtMov = $conn->prepare("
                INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em, custo_unitario)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            if ($stmtMov) {
                // tipos: i (produto_id), s (tipo), i (quantidade), s (data), i (usuario_id), d (custo)
                $stmtMov->bind_param("isisid", $idNovoProduto, $tipo, $estoque, $data_mov, $usuario_id_for_bind, $custo_mov);
                $stmtMov->execute();
                $stmtMov->close();
            }
        }

        echo json_encode(['success' => true, 'message' => '✅ Produto adicionado com sucesso!']);
        exit;
    }
}


    // EDITAR PRODUTO (AJAX)
    if ($acao === 'editar_produto') {
        $id = intval($_POST['produto_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $estoque = intval($_POST['estoque'] ?? 0);

        $stmt = $conn->prepare("UPDATE produtos SET nome=?, preco_unitario=?, quantidade_estoque=? WHERE id=? AND loja_id=?");
        if ($stmt) {
            $stmt->bind_param("sdiii", $nome, $preco, $estoque, $id, $lojaId);
            $stmt->execute();
            $stmt->close();

            $stmtHist = $conn->prepare("
                INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
                VALUES (?, ?, ?, 'editado', ?, NOW())
            ");
            if ($stmtHist) {
                $stmtHist->bind_param("isii", $id, $nome, $estoque, $usuarioId);
                $stmtHist->execute();
                $stmtHist->close();
            }
            echo json_encode(['success' => true, 'message' => '✏️ Produto atualizado com sucesso!']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => '❌ Erro ao atualizar produto.']);
        exit;
    }

    // APAGAR PRODUTO (AJAX)
    if ($acao === 'apagar_produto') {
        header('Content-Type: application/json');
        $id = intval($_POST['produto_id'] ?? 0);

        try {
            // Remove tags associadas
            $stmt = $conn->prepare("DELETE FROM produto_tag WHERE produto_id=?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }


            // Remove movimentações de estoque
            $stmt = $conn->prepare("DELETE FROM movimentacoes_estoque WHERE produto_id=?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            // Remove itens de venda
            $stmt = $conn->prepare("DELETE FROM itens_venda WHERE produto_id=?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            // Remove histórico do produto
            $stmt = $conn->prepare("DELETE FROM historico_produtos WHERE produto_id=?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }

            // Apaga produto do banco
            $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ? AND loja_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $id, $lojaId);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $stmt->close();
                        echo json_encode(['success' => true, 'message' => '🗑️ Produto excluído com sucesso!']);
                        exit;
                    } else {
                        $stmt->close();
                        echo json_encode(['success' => false, 'message' => '❌ Produto não encontrado ou já foi excluído.']);
                        exit;
                    }
                }
                $stmt->close();
            }

            echo json_encode(['success' => false, 'message' => '❌ Erro ao preparar consulta de exclusão.']);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '❌ Erro ao excluir produto: ' . $e->getMessage()]);
            exit;
        }
    }

    // ENTRADA (COMPRAR PRODUTO) - AJAX
    if ($acao === 'comprar_produto') {
        header('Content-Type: application/json');
        $id = intval($_POST['produto_id'] ?? 0);
        $qtd = intval($_POST['quantidade'] ?? 0);
        $data = $_POST['data_movimentacao'] ?? date('Y-m-d');
        $custo_compra = isset($_POST['custo']) ? floatval($_POST['custo']) : null;

        $stmtProduto = $conn->prepare("SELECT quantidade_estoque, custo_unitario FROM produtos WHERE id=? AND loja_id=?");
        $stmtProduto->bind_param("ii", $id, $lojaId);
        $stmtProduto->execute();
        $produto = $stmtProduto->get_result()->fetch_assoc();
        $stmtProduto->close();

        $estoque_atual = (float)$produto['quantidade_estoque'];
        $custo_atual = (float)$produto['custo_unitario'];

        if ($qtd > 0 && $custo_compra !== null) {
            $novo_custo = (($custo_atual * $estoque_atual) + ($custo_compra * $qtd)) / ($estoque_atual + $qtd);
        } else {
            $novo_custo = $custo_atual;
        }

        $stmtUpdate = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ?, custo_unitario = ? WHERE id=? AND loja_id=?");
        $stmtUpdate->bind_param("idii", $qtd, $novo_custo, $id, $lojaId);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $custo_mov = $custo_compra !== null ? $custo_compra : $custo_atual;

        $tipo = 'entrada';
        $stmt2 = $conn->prepare("
            INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em, custo_unitario) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt2->bind_param("isisid", $id, $tipo, $qtd, $data, $usuarioId, $custo_mov);
        $stmt2->execute();
        $stmt2->close();

        $stmtHist = $conn->prepare("
            INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
            SELECT id, nome, quantidade_estoque, 'comprado', ?, NOW()
            FROM produtos WHERE id=? AND loja_id=?
        ");
        $stmtHist->bind_param("iii", $usuarioId, $id, $lojaId);
        $stmtHist->execute();
        $stmtHist->close();

        $resProduto = $conn->prepare("SELECT nome, custo_unitario FROM produtos WHERE id=? AND loja_id=?");
        $resProduto->bind_param("ii", $id, $lojaId);
        $resProduto->execute();
        $produto = $resProduto->get_result()->fetch_assoc();
        $resProduto->close();

        $custo_total = $produto['custo_unitario'] * $qtd;
        $nome = $produto['nome'];

        $stmtDesp = $conn->prepare("
            INSERT INTO transacoes_financeiras (loja_id, tipo, valor, descricao, data_transacao)
            VALUES (?, 'saida', ?, ?, ?)
        ");
        $descricao = "Compra do produto $nome (x$qtd)";
        $stmtDesp->bind_param("idss", $lojaId, $custo_total, $descricao, $data);
        $stmtDesp->execute();
        $stmtDesp->close();

        echo json_encode(['success' => true, 'message' => "📥 Entrada registrada! (+$qtd unidades)"]);
        exit;
    }

    // SAÍDA (VENDER PRODUTO) - AJAX
    if ($acao === 'vender_produto') {
        header('Content-Type: application/json');
        $produto_id = intval($_POST['produto_id'] ?? 0);
        $quantidade = intval($_POST['quantidade'] ?? 0);
        $data_movimentacao = $_POST['data_movimentacao'] ?? date('Y-m-d');

        if ($produto_id <= 0 || $quantidade <= 0) {
            echo json_encode(['success' => false, 'message' => '❌ Dados inválidos para venda.']);
            exit;
        }

        $stmtEstoque = $conn->prepare("SELECT quantidade_estoque, preco_unitario, custo_unitario FROM produtos WHERE id=? AND loja_id=? AND deletado_em IS NULL");
        $stmtEstoque->bind_param("ii", $produto_id, $lojaId);
        $stmtEstoque->execute();
        $result = $stmtEstoque->get_result();
        $prod = $result->fetch_assoc();
        $stmtEstoque->close();

        if (!$prod || $prod['quantidade_estoque'] < $quantidade) {
            echo json_encode(['success' => false, 'message' => '❌ Estoque insuficiente para venda.']);
            exit;
        }

        $stmtUpdate = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id=? AND loja_id=? AND quantidade_estoque >= ?");
        $stmtUpdate->bind_param("iiii", $quantidade, $produto_id, $lojaId, $quantidade);
        if (!$stmtUpdate->execute() || $stmtUpdate->affected_rows === 0) {
            $stmtUpdate->close();
            echo json_encode(['success' => false, 'message' => '❌ Falha ao decrementar estoque.']);
            exit;
        }
        $stmtUpdate->close();

        $stmtMov = $conn->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em) VALUES (?, 'saida', ?, ?, ?, NOW())");
        $stmtMov->bind_param("iisi", $produto_id, $quantidade, $data_movimentacao, $usuarioId);
        $stmtMov->execute();
        $stmtMov->close();

        $stmtHist = $conn->prepare("
            INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
            SELECT id, nome, quantidade_estoque, 'vendido', ?, NOW()
            FROM produtos WHERE id=? AND loja_id=?
        ");
        $stmtHist->bind_param("iii", $usuarioId, $produto_id, $lojaId);
        $stmtHist->execute();
        $stmtHist->close();

        $valor_total = $prod['preco_unitario'] * $quantidade;
        $custo_total = $prod['custo_unitario'] * $quantidade;
        $stmtVenda = $conn->prepare("INSERT INTO vendas (loja_id, data_venda, valor_total, custo_total, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmtVenda->bind_param("isddi", $lojaId, $data_movimentacao, $valor_total, $custo_total, $usuarioId);
        $stmtVenda->execute();
        $venda_id = $stmtVenda->insert_id;
        $stmtVenda->close();

        $stmtItem = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, custo_unitario, data_venda) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtItem->bind_param("iiidds", $venda_id, $produto_id, $quantidade, $prod['preco_unitario'], $prod['custo_unitario'], $data_movimentacao);
        $stmtItem->execute();
        $stmtItem->close();

        echo json_encode(['success' => true, 'message' => "📤 Saída registrada! (-$quantidade unidades)"]);
        exit;
    }
}

// ---- PAGINAÇÃO ----
$linhasPorPagina = 14;
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

$totalProdutosResult = $conn->prepare("SELECT COUNT(*) as total FROM produtos WHERE loja_id = ?");
$totalProdutosResult->bind_param("i", $lojaId);
$totalProdutosResult->execute();
$totalProdutos = $totalProdutosResult->get_result()->fetch_assoc()['total'];

$totalPaginas = ceil($totalProdutos / $linhasPorPagina);
$inicio = ($paginaAtual - 1) * $linhasPorPagina;

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
.add-tag-square { 
    width:24px; 
    height:24px; 
    background:#000; 
    color:#fff; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    cursor:pointer; 
    font-weight:bold; 
    border-radius:6px; 
    flex-shrink: 0;
}
.tag-dropdown { 
    display:none; 
    position:fixed; 
    background:#fff; 
    border:1px solid #ccc; 
    padding:8px; 
    border-radius:6px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index:10000;
    min-width: 160px;
}
.tag-option { 
    padding:6px 8px; 
    cursor:pointer;
    border-radius:4px;
    transition: background 0.2s;
}
.tag-option:hover {
    background: #f0f0f0;
}

/* Toast notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

/* Blur que cobre toda a tela */
#overlay-blur {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
}

/* Overlay de produtos */
#overlay-produtos {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    justify-content: flex-end;
    align-items: flex-start;
    z-index: 10000;
    padding: 30px;
    padding-top: 700px;
    background: transparent;
}

#overlay-produtos .welcome-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    z-index: 2;
    text-align: left;
}

#overlay-produtos .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

#overlay-produtos .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}

#overlay-produtos .welcome-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

/* Overlay de ações */
#overlay-acoes {
    display: none;
    position: absolute;
    z-index: 10001;
    justify-content: center;
    align-items: center;
}

#overlay-acoes .welcome-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    z-index: 2;
    text-align: left;
}

#overlay-acoes .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

#overlay-acoes .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}

#overlay-acoes .welcome-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

/* Botão de ajuda flutuante */
#help-btn-produtos {
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
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Classe para seção ficar acima do blur */
.fora-do-blur {
    position: relative;
    z-index: 10002 !important;
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
          <li class="active"><a href="../../dashboard/tabelas/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../../dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../../Pages/auth/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

<!-- Toast Container -->
<div class="toast-container">
  <div id="actionToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
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
        
        <button class="btn-novo" id="import-btn" data-bs-toggle="modal" data-bs-target="#importModal">Importar</button>
        <button class="btn-novo" id="criar-tag-btn" onclick="window.location.href='../tag.php'">Criar Tag</button>
        
        <select id="ordenar">
            <option value="">Ordenar...</option>
            <option value="nome-asc">Nome (A-Z)</option>
            <option value="nome-desc">Nome (Z-A)</option>
            <option value="preco-asc">Preço (Menor→Maior)</option>
            <option value="preco-desc">Preço (Maior→Menor)</option>
            <option value="quantidade-asc">Quantidade (Menor→Maior)</option>
            <option value="quantidade-desc">Quantidade (Maior→Maior)</option>
        </select>
    </div>
    
    <h4 style="color: #ffffff;">Adicionar novo produto</h4>
    
    <form method="POST" style="margin-bottom:18px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <input type="hidden" name="acao" value="adicionar_produto">
      <input type="text" name="nome" id="novo_nome" placeholder="Nome do produto" required 
          style="padding:8px; border-radius:6px; border:1px solid #444; background:#222; color:#fff;">
      <input type="number" step="0.01" name="preco" id="novo_preco" placeholder="Preço (R$)" required 
          style="padding:8px; border-radius:6px; border:1px solid #444; background:#222; color:#fff; max-width:100px;">
      <input type="number" step="0.01" name="custo" id="novo_custo" placeholder="Custo (R$)" required 
          style="padding:8px; border-radius:6px; border:1px solid #444; background:#222; color:#fff; max-width:100px;">
      <input type="number" name="estoque" id="novo_estoque" placeholder="Estoque inicial" required 
          style="padding:8px; border-radius:6px; border:1px solid #444; background:#222; color:#fff; max-width:100px;">
      <button type="button" id="btnAdicionarProduto"
        style="padding:8px 16px; border-radius:6px; border:1px solid #444; background:linear-gradient(135deg, #ff9900 80%, #ffc800 100%); color:#fff; font-weight:600;">
     Salvar
      </button>
      <button type="reset" 
        style="padding:8px 16px; border-radius:6px; border:1px solid #fff; background:#222; color:#fff;">
     Limpar
      </button>
    </form>

<script>
document.getElementById('btnAdicionarProduto').onclick = function() {
    const nome = document.getElementById('novo_nome').value.trim();
    const preco = document.getElementById('novo_preco').value;
    const custo = document.getElementById('novo_custo').value;
    const estoque = document.getElementById('novo_estoque').value;
    if (!nome || !preco || !custo || !estoque) {
     showToast('Preencha todos os campos!', 'danger');
     return;
    }
    const formData = new FormData();
    formData.append('acao', 'adicionar_produto');
    formData.append('nome', nome);
    formData.append('preco', preco);
    formData.append('custo', custo);
    formData.append('estoque', estoque);
    fetch('', {
     method: 'POST',
     headers: { 'X-Requested-With': 'XMLHttpRequest' },
     body: formData
    })
    .then(res => res.json())
    .then(data => {
     if (data.success) {
         showToast(data.message, 'success');
         setTimeout(() => window.location.reload(), 800);
     } else {
         showToast(data.message || 'Erro ao adicionar produto', 'danger');
     }
    })
    .catch(() => showToast('Erro ao adicionar produto', 'danger'));
};
</script>

    <div class="tags-area" style="display:flex; align-items:center; gap:10px;">
        <?php foreach ($tags as $tag): ?>
            <div class="tag-item" title="<?= htmlspecialchars($tag['nome']) ?>" data-tag-id="<?= $tag['id'] ?>" style="cursor:pointer;">
                <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i> <?= htmlspecialchars($tag['nome']) ?>
            </div>
        <?php endforeach; ?>
        <button class="btn-reset-filtro" onclick="resetFiltro()">
            <i class="fa-solid fa-xmark" style="color: #fff;"></i>
        </button>
    </div>
</div>

<table>
<thead>
<tr>
    <th>Nome</th>
    <th>Preço Unitário</th>
    <th>Quantidade</th>
    <th>Ações</th>
</tr>
</thead>
<tbody id="tabela-produtos">
<?php while ($produto = mysqli_fetch_assoc($result)): ?>
<tr data-id="<?= $produto['id'] ?>" data-nome="<?= htmlspecialchars($produto['nome']) ?>" data-preco="<?= $produto['preco_unitario'] ?>" data-quantidade="<?= $produto['quantidade_estoque'] ?>">
    <td style="display:flex; align-items:center; gap:10px; position:relative;">
        <!-- Botão de adicionar tag -->
        <div class="add-tag-square" data-produto-id="<?= $produto['id'] ?>" tabindex="0" title="Adicionar tag">+</div>
        <!-- Dropdown de tags (inicialmente oculto) -->
        <div class="tag-dropdown" id="tag-dropdown-<?= $produto['id'] ?>">
            <?php foreach ($tags as $tag): ?>
                <div class="tag-option" 
                     data-tag-id="<?= $tag['id'] ?>" 
                     data-produto-id="<?= $produto['id'] ?>"
                     style="display:flex; align-items:center; gap:6px;">
                    <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i>
                    <?= htmlspecialchars($tag['nome']) ?>
                </div>
            <?php endforeach; ?>
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
    <td>
        <button class="btn btn-sm editBtn" type="button" title="Editar" 
          style="color:#fff; background:transparent; border:1px solid #fff; padding:6px 10px; border-radius:6px; cursor:pointer; transition:0.2s;">
          Editar
        </button>
        <button class="btn btn-sm buyBtn" type="button" title="Comprar"
          style="color:#fff; background:transparent; border:1px solid #fff; padding:6px 10px; border-radius:6px; cursor:pointer; transition:0.2s;">
          Entrada
        </button>
        <button class="btn btn-sm sellBtn" type="button" title="Vender"
          style="color:#fff; background:transparent; border:1px solid #fff; padding:6px 10px; border-radius:6px; cursor:pointer; transition:0.2s;">
          Saída
        </button>
        <button class="btn btn-sm deleteBtn" type="button" title="Apagar"
          style="color:#fff; background:transparent; border:1px solid #fff; padding:6px 10px; border-radius:6px; cursor:pointer; transition:0.2s;">
          Excluir
        </button>
    </td>
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

<!-- Modal Editar Produto -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">✏️ Editar Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" id="edit_produto_id" name="produto_id">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control" id="edit_nome" name="nome" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Preço (R$)</label>
            <input type="number" step="0.01" class="form-control" id="edit_preco" name="preco" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" class="form-control" id="edit_estoque" name="estoque" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="submitEdit()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Entrada (Compra) -->
<div class="modal fade" id="buyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">📥 Registrar Entrada</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="buyForm">
          <input type="hidden" id="buy_produto_id" name="produto_id">
          <div class="mb-3">
            <label class="form-label">Produto</label>
            <input type="text" class="form-control" id="buy_nome" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" class="form-control" id="buy_quantidade" name="quantidade" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Custo Unitário (R$)</label>
            <input type="number" step="0.01" class="form-control" id="buy_custo" name="custo" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" class="form-control" id="buy_data" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="submitBuy()">Registrar Entrada</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Saída (Venda) -->
<div class="modal fade" id="sellModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">📤 Registrar Saída</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="sellForm">
          <input type="hidden" id="sell_produto_id" name="produto_id">
          <div class="mb-3">
            <label class="form-label">Produto</label>
            <input type="text" class="form-control" id="sell_nome" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" class="form-control" id="sell_quantidade" name="quantidade" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" class="form-control" id="sell_data" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" onclick="submitSell()">Registrar Saída</button>
      </div>
    </div>
  </div>
</div>

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
                                    <button type="submit" class="btn btn-primary" id="submitImport">
                                        <i class="fas fa-upload me-2"></i>Fazer Upload e Importar
                                    </button>
                                </form>
                                
                                <script>
                                document.getElementById('uploadForm').addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    
                                    const formData = new FormData(this);
                                    const submitBtn = document.getElementById('submitImport');
                                    const importResult = document.getElementById('importResult');
                                    const importedData = document.getElementById('importedData');
                                    
                                    // Desabilita botão durante upload
                                    submitBtn.disabled = true;
                                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
                                    
                                    fetch('importacao.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log('Resposta da importação:', data); // Debug
                                        
                                        if (data.success) {
                                            document.getElementById('successMessage').textContent = `${data.imported} produtos importados com sucesso!`;
                                            
                                            // Limpa e preenche tabela de resultados
                                            importedData.innerHTML = '';
                                            data.data.forEach(produto => {
                                                importedData.innerHTML += `
                                                    <tr>
                                                        <td>${produto.nome}</td>
                                                        <td>${produto.descricao || '-'}</td>
                                                        <td>${produto.lote || '-'}</td>
                                                        <td>${produto.quantidade_estoque}</td>
                                                        <td>R$ ${parseFloat(produto.preco_unitario).toFixed(2)}</td>
                                                        <td>R$ ${parseFloat(produto.custo_unitario).toFixed(2)}</td>
                                                        <td>${produto.data_reabastecimento || '-'}</td>
                                                    </tr>
                                                `;
                                            });
                                            
                                            // Mostra área de resultados
                                            importResult.classList.remove('d-none');
                                            
                                            // Atualiza a tabela de produtos principal
                                            window.location.reload();
                                            
                                        } else {
                                            // Mostra erros se houver
                                            const errorList = data.errors.join('<br>');
                                            document.getElementById('successMessage').innerHTML = `
                                                <div class="alert alert-danger">
                                                    <strong>Erros na importação:</strong><br>
                                                    ${errorList}
                                                </div>
                                            `;
                                            importResult.classList.remove('d-none');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erro na importação:', error);
                                        
                                        // Tenta ler a resposta como texto para debug
                                        error.response?.text().then(text => {
                                            console.log('Resposta do servidor:', text);
                                        }).catch(() => {});
                                        
                                        let errorMessage = 'Erro desconhecido ao processar importação.';
                                        
                                        if (error.response) {
                                            errorMessage = `Erro do servidor: ${error.response.status} ${error.response.statusText}`;
                                        } else if (error.message) {
                                            errorMessage = error.message;
                                        }
                                        
                                        document.getElementById('successMessage').innerHTML = `
                                            <div class="alert alert-danger">
                                                <strong>Erro ao processar importação:</strong><br>
                                                ${errorMessage}<br><br>
                                                <small>Verifique o console do navegador (F12) para mais detalhes.</small>
                                            </div>
                                        `;
                                        importResult.classList.remove('d-none');
                                    })
                                    .finally(() => {
                                        // Reativa botão
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Fazer Upload e Importar';
                                    });
                                });
                                </script>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Overlay Blur de Fundo -->
<div id="overlay-blur" class="full-screen-blur" style="display:none;"></div>

<!-- Overlay 1: Produtos - canto inferior direito -->
<div id="overlay-produtos" style="display:none;">
  <div class="welcome-card">
    <h2>Produtos</h2>
    <p>Esta é a área de gerenciamento de produtos da sua empresa. Aqui você pode adicionar, editar, visualizar e controlar o estoque de todos os seus produtos.</p>
    <button id="closeOverlayProdutos1">Próximo</button>
  </div>
</div>

<!-- Overlay 2: Ações - próximo à seção de ações -->
<div id="overlay-acoes" class="welcome-overlay" style="display:none;">
  <div class="welcome-card">
    <h2>Ações Rápidas</h2>
    <p>Nesta seção você pode pesquisar produtos, importar planilhas, criar tags personalizadas e ordenar sua lista de produtos.</p>
    <button id="closeOverlayProdutos2">Entendi</button>
  </div>
</div>

<!-- Botão de Ajuda Flutuante -->
<button id="help-btn-produtos">?</button>

<style>
/* Blur que cobre toda a tela */
#overlay-blur {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
}

/* Overlay de produtos */
#overlay-produtos {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    justify-content: flex-end;
    align-items: flex-start;
    z-index: 10000;
    padding: 30px;
    padding-top: 700px;
    background: transparent;
}

#overlay-produtos .welcome-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    z-index: 2;
    text-align: left;
}

#overlay-produtos .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

#overlay-produtos .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}

#overlay-produtos .welcome-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

/* Overlay de ações */
#overlay-acoes {
    display: none;
    position: absolute;
    z-index: 10001;
    justify-content: center;
    align-items: center;
}

#overlay-acoes .welcome-card {
    background: #222;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    padding: 22px 28px;
    max-width: 340px;
    font-size: 15px;
    pointer-events: auto;
    position: relative;
    z-index: 2;
    text-align: left;
}

#overlay-acoes .welcome-card h2 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

#overlay-acoes .welcome-card p {
    font-size: 15px;
    margin-bottom: 18px;
}

#overlay-acoes .welcome-card button {
    margin-top: 12px;
    background: #ff6600 !important;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

/* Botão de ajuda flutuante */
#help-btn-produtos {
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
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Classe para seção ficar acima do blur */
.fora-do-blur {
    position: relative;
    z-index: 10002 !important;
}
</style>

<script>
// ========== FUNÇÕES UTILITÁRIAS ==========
function showToast(message, type = 'success') {
    const toast = document.getElementById('actionToast');
    const toastBody = document.getElementById('toastMessage');
    
    toast.classList.remove('bg-success', 'bg-danger', 'bg-warning');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    
    toastBody.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function updateTableRow(produtoId) {
    // Recarrega apenas a linha afetada via AJAX
    fetch(`get_produto.php?id=${produtoId}`)
        .then(res => res.json())
        .then (data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-id="${produtoId}"]`);
                if (row) {
                    row.dataset.preco = data.produto.preco_unitario;
                    row.dataset.quantidade = data.produto.quantidade_estoque;
                    row.querySelector('td:nth-child(2)').textContent = `R$ ${parseFloat(data.produto.preco_unitario).toFixed(2).replace('.', ',')}`;
                    row.querySelector('td:nth-child(3)').textContent = data.produto.quantidade_estoque;
                }
            }
        });
}

// Função utilitária para normalizar string (usada na pesquisa)
function normalizeStr(str) {
    return (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}
// Função para pegar o nome do produto na linha (usada na pesquisa/ordenação)
function textoNomeDoRow(tr) {
    const span = tr.querySelector('span:last-of-type');
    return normalizeStr(span ? span.textContent : '');
}

// ========== EDITAR PRODUTO ==========
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('editBtn') || e.target.closest('.editBtn')) {
        const row = e.target.closest('tr');
        const id = row.dataset.id;
        const nome = row.dataset.nome;
        const preco = row.dataset.preco;
        const quantidade = row.dataset.quantidade;
        
        document.getElementById('edit_produto_id').value = id;
        document.getElementById('edit_nome').value = nome;
        document.getElementById('edit_preco').value = preco;
        document.getElementById('edit_estoque').value = quantidade;
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
});

function submitEdit() {
    const formData = new FormData(document.getElementById('editForm'));
    formData.append('acao', 'editar_produto');
    
    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Pega os valores do formulário
            const produtoId = document.getElementById('edit_produto_id').value;
            const novoNome = document.getElementById('edit_nome').value;
            const novoPreco = parseFloat(document.getElementById('edit_preco').value);
            const novaQuantidade = parseInt(document.getElementById('edit_estoque').value);
            
            // Atualiza a linha na tabela
            const row = document.querySelector(`tr[data-id="${produtoId}"]`);
            if (row) {
                // Atualiza os atributos data-*
                row.dataset.nome = novoNome;
                row.dataset.preco = novoPreco;
                row.dataset.quantidade = novaQuantidade;
                
                // Atualiza o nome do produto na primeira coluna (span)
                const nomeSpan = row.querySelector('td:first-child span:last-of-type');
                if (nomeSpan) {
                    nomeSpan.textContent = novoNome;
                }
                
                // Atualiza o preço na segunda coluna
                const precoTd = row.querySelector('td:nth-child(2)');
                if (precoTd) {
                    precoTd.textContent = `R$ ${novoPreco.toFixed(2).replace('.', ',')}`;
                }
                
                // Atualiza a quantidade na terceira coluna
                const quantidadeTd = row.querySelector('td:nth-child(3)');
                if (quantidadeTd) {
                    quantidadeTd.textContent = novaQuantidade;
                }
                
                // Efeito visual de sucesso
                row.classList.add('table-success');
                setTimeout(() => row.classList.remove('table-success'), 1200);
            }
            
            // Fecha o modal
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        } else {
            throw new Error(data.message || 'Erro ao editar produto');
        }
    })
    .catch(err => showToast(err.message || 'Erro ao editar produto', 'danger'));
}

// ========== ENTRADA (COMPRA) ==========
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('buyBtn') || e.target.closest('.buyBtn')) {
        const row = e.target.closest('tr');
        const id = row.dataset.id;
        const nome = row.dataset.nome;
        
        document.getElementById('buy_produto_id').value = id;
        document.getElementById('buy_nome').value = nome;
        document.getElementById('buy_quantidade').value = 1;
        document.getElementById('buy_custo').value = '';
        
        new bootstrap.Modal(document.getElementById('buyModal')).show();
    }
});

function submitBuy() {
    const formData = new FormData(document.getElementById('buyForm'));
    formData.append('acao', 'comprar_produto');

    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const produtoId = document.getElementById('buy_produto_id').value;
            updateTableRow(produtoId);
            bootstrap.Modal.getInstance(document.getElementById('buyModal')).hide();
        } else {
            throw new Error(data.message || 'Erro ao registrar entrada');
        }
    })
    .catch(err => showToast(err.message || 'Erro ao registrar entrada', 'danger'));
}

// ========== SAÍDA (VENDA) ==========
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('sellBtn') || e.target.closest('.sellBtn')) {
        const row = e.target.closest('tr');
        const id = row.dataset.id;
        const nome = row.dataset.nome;
        
        document.getElementById('sell_produto_id').value = id;
        document.getElementById('sell_nome').value = nome;
        document.getElementById('sell_quantidade').value = 1;
        
        new bootstrap.Modal(document.getElementById('sellModal')).show();
    }
});

function submitSell() {
    const formData = new FormData(document.getElementById('sellForm'));
    formData.append('acao', 'vender_produto');
    
    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const produtoId = document.getElementById('sell_produto_id').value;
            updateTableRow(produtoId);
            bootstrap.Modal.getInstance(document.getElementById('sellModal')).hide();
        } else {
            throw new Error(data.message || 'Erro ao registrar saída');
        }
    })
    .catch(err => showToast(err.message || 'Erro ao registrar saída', 'danger'));
}

// ========== EXCLUIR PRODUTO ==========
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('deleteBtn') || e.target.closest('.deleteBtn')) {
        const row = e.target.closest('tr');
        const id = row.dataset.id;
        const nome = row.dataset.nome;
        
        if (!confirm(`Deseja realmente excluir o produto "${nome}"?\n\nEsta ação é irreversível.`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('acao', 'apagar_produto');
        formData.append('produto_id', id);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then (data => {
            if (data.success) {
                showToast(data.message, 'success');
                row.remove();
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(err => showToast('Erro ao excluir produto', 'danger'));
    }
});

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

function resetFiltro() {
    document.querySelectorAll('#tabela-produtos tr').forEach(tr => {
        tr.style.display = '';
    });
}

// ========== BIND DROPDOWN DE TAGS ==========
function bindTagDropdownEvents() {
    // Botão de adicionar tag
    document.querySelectorAll('.add-tag-square').forEach(btn => {
        btn.onclick = function(e) {
            e.stopPropagation();
            // Fecha outros dropdowns
            document.querySelectorAll('.tag-dropdown').forEach(dd => dd.style.display = 'none');
            // Abre o dropdown deste produto
            const produtoId = this.dataset.produtoId;
            const dropdown = document.getElementById('tag-dropdown-' + produtoId);
            if (dropdown) {
                // Usa position fixed e calcula posição em relação à viewport
                const btnRect = this.getBoundingClientRect();
                dropdown.style.display = 'block';
                dropdown.style.position = 'fixed';
                dropdown.style.left = (btnRect.right + 8) + 'px'; // 8px à direita do botão
                dropdown.style.top = btnRect.top + 'px';
            }
        };
    });

    // Ao clicar em uma tag do dropdown, faz o vínculo via AJAX
    document.querySelectorAll('.tag-dropdown .tag-option').forEach(opt => {
        opt.onclick = function(e) {
            e.stopPropagation();
            const produtoId = this.dataset.produtoId;
            const tagId = this.dataset.tagId;
            fetch('vincular_tag.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `produto_id=${produtoId}&tag_id=${tagId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Atualiza os ícones de tags na linha
                    const tagsSpan = document.getElementById('tags-produto-' + produtoId);
                    if (tagsSpan) {
                        // Adiciona o ícone da tag se não existir
                        if (!tagsSpan.querySelector('[data-tag-id="' + tagId + '"]')) {
                            const icon = document.createElement('i');
                            icon.className = 'fa-solid ' + data.icone;
                            icon.style.color = data.cor;
                            icon.setAttribute('data-tag-id', tagId);
                            tagsSpan.appendChild(icon);
                        }
                    }
                    showToast('Tag vinculada ao produto!', 'success');
                } else {
                    showToast('Erro ao vincular tag', 'danger');
                }
                // Fecha dropdown
                document.getElementById('tag-dropdown-' + produtoId).style.display = 'none';
            })
            .catch(err => {
                console.error(err);
                showToast('Erro ao vincular tag', 'danger');
            });
        };
    });
}

// Chama o bind ao carregar a página
bindTagDropdownEvents();

// Fecha dropdown ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.tag-dropdown') && !e.target.closest('.add-tag-square')) {
        document.querySelectorAll('.tag-dropdown').forEach(dd => dd.style.display = 'none');
    }
});

// ========== OVERLAYS DE AJUDA ==========
const helpBtnProdutos = document.getElementById('help-btn-produtos');
const overlayProdutos1 = document.getElementById('overlay-produtos');
const overlayAcoes = document.getElementById('overlay-acoes');
const blurProdutos = document.getElementById('overlay-blur');
const btnCloseProdutos1 = document.getElementById('closeOverlayProdutos1');
const btnCloseProdutos2 = document.getElementById('closeOverlayProdutos2');

helpBtnProdutos.addEventListener('click', () => {
    overlayProdutos1.style.display = 'flex';
    blurProdutos.style.display = 'block';
});

btnCloseProdutos1.addEventListener('click', () => {
    overlayProdutos1.style.display = 'none';
    
    // Mantém blur ativo
    blurProdutos.style.display = 'block';
    
    // Pega a seção de ações e adiciona classe para ficar acima do blur
    const acoesSection = document.querySelector('.acoes');
    acoesSection.classList.add('fora-do-blur');
    
    // Posiciona overlay2 próximo da seção de ações
    const rect = acoesSection.getBoundingClientRect();
    
    overlayAcoes.style.position = 'absolute';
    overlayAcoes.style.top = `${rect.bottom + window.scrollY + 10}px`;
    overlayAcoes.style.left = `${rect.left + window.scrollX}px`;
    overlayAcoes.style.display = 'flex';
    overlayAcoes.style.zIndex = '10001';
});

btnCloseProdutos2.addEventListener('click', () => {
    overlayAcoes.style.display = 'none';
    blurProdutos.style.display = 'none';
    
    // Remove classe da seção de ações
    const acoesSection = document.querySelector('.acoes');
    acoesSection.classList.remove('fora-do-blur');
});

// Fecha overlays ao clicar no blur
blurProdutos.addEventListener('click', () => {
    overlayProdutos1.style.display = 'none';
    overlayAcoes.style.display = 'none';
    blurProdutos.style.display = 'none';
    
    const acoesSection = document.querySelector('.acoes');
    acoesSection.classList.remove('fora-do-blur');
});
</script>

</body>
</html>
