<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../../conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_login'])) {
    die('Faça login para acessar o gerenciamento.');
}

$usuarioId = $_SESSION['usuario_id'];
$tipo_login = $_SESSION['tipo_login']; // 'empresa' ou 'funcionario'

// Define a loja_id corretamente
if ($tipo_login === 'empresa') {
    $lojaId = $_SESSION['loja_id'] ?? 0;
} else {
    $res = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
    $res->bind_param("i", $usuarioId);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    $lojaId = $row['loja_id'] ?? 0;
    $res->close();
}

if (!$lojaId) {
    die('Loja não encontrada para este usuário.');
}

$msg = '';

/* ======================================================
   TRATAMENTO DE AÇÕES (ADD / EDIT / DELETE / COMPRAR / VENDER)
   ====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {}
    $acao = $_POST['acao'] ?? '';

    // --- ADICIONAR PRODUTO ---
    if ($acao === 'adicionar_produto') {
        $nome = trim($_POST['nome'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $estoque = intval($_POST['estoque'] ?? 0);
        $lote = '';

        $stmt = $conn->prepare("
            INSERT INTO produtos (nome, preco_unitario, quantidade_estoque, lote, loja_id, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            die("Erro prepare produto: " . $conn->error);
        }

        $stmt->bind_param("sdisii", $nome, $preco, $estoque, $lote, $lojaId, $usuarioId);
        if (!$stmt->execute()) {
            die("Erro execute produto: " . $stmt->error);
        }

        $idNovoProduto = $stmt->insert_id;
        $stmt->close();

        // Histórico
 if ($tipo_login === 'empresa') {
    // Se for empresa → usuario_id = NULL
    $stmtHist = $conn->prepare("
        INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
        VALUES (?, ?, ?, 'adicionado', NULL, NOW())
    ");
    if (!$stmtHist) {
        die("Erro prepare histórico: " . $conn->error);
    }

    $stmtHist->bind_param("isi", $idNovoProduto, $nome, $estoque);
} else {
    // Se for funcionário → usuario_id = $usuarioId
    $stmtHist = $conn->prepare("
        INSERT INTO historico_produtos (produto_id, nome, quantidade, acao, usuario_id, criado_em)
        VALUES (?, ?, ?, 'adicionado', ?, NOW())
    ");
    if (!$stmtHist) {
        die("Erro prepare histórico: " . $conn->error);
    }

    $stmtHist->bind_param("isii", $idNovoProduto, $nome, $estoque, $usuarioId);
}

if (!$stmtHist->execute()) {
    die("Erro execute histórico: " . $stmtHist->error);
}
$stmtHist->close();

$msg = "✅ Produto cadastrado com sucesso!";

    // --- EDITAR PRODUTO ---
    if ($acao === 'editar_produto') {
        $id = intval($_POST['produto_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $estoque = intval($_POST['estoque'] ?? 0);

        $stmt = $conn->prepare("UPDATE produtos SET nome=?, preco_unitario=?, quantidade_estoque=? WHERE id=? AND loja_id=?");
        if (!$stmt) die("Erro prepare editar: " . $conn->error);

        $stmt->bind_param("sdiii", $nome, $preco, $estoque, $id, $lojaId);
        if ($stmt->execute()) {
            $msg = "✏️ Produto atualizado!";
        } else {
            $msg = "❌ Erro: " . $stmt->error;
        }
        $stmt->close();
    }

    // --- APAGAR PRODUTO ---
    if ($acao === 'apagar_produto') {
        $id = intval($_POST['produto_id'] ?? 0);

        // Remove tags associadas
        $stmt = $conn->prepare("DELETE FROM produto_tag WHERE produto_id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // Marca como excluído
        $stmt = $conn->prepare(
            "UPDATE produtos SET deletado_em = NOW(), usuario_exclusao_id = ? WHERE id = ? AND loja_id = ?"
        );
        if (!$stmt) die("Erro prepare apagar: " . $conn->error);

        $stmt->bind_param("iii", $usuarioId, $id, $lojaId);
        if ($stmt->execute()) {
            $msg = "🗑️ Produto apagado!";
        } else {
            $msg = "❌ Erro: " . $stmt->error;
        }
        $stmt->close();
    }

    // --- COMPRAR (ENTRADA) ---
    if ($acao === 'comprar_produto') {
        $id = intval($_POST['produto_id'] ?? 0);
        $qtd = intval($_POST['quantidade'] ?? 0);
        $data = $_POST['data_movimentacao'] ?? date('Y-m-d');

        $stmt = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id=? AND loja_id=?");
        if (!$stmt) die("Erro prepare comprar: " . $conn->error);

        $stmt->bind_param("iii", $qtd, $id, $lojaId);
        if ($stmt->execute()) {
            $tipo = 'entrada';
            $stmt2 = $conn->prepare("
                INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt2->bind_param("isisi", $id, $tipo, $qtd, $data, $usuarioId);
            $stmt2->execute();
            $stmt2->close();

            $msg = "📥 Compra registrada (+$qtd) em $data";
        } else {
            $msg = "❌ Erro ao registrar compra! " . $stmt->error;
        }
        $stmt->close();
    }

    // --- VENDER (SAÍDA) ---
    if ($acao === 'vender_produto') {
        $id = intval($_POST['produto_id'] ?? 0);
        $qtd = intval($_POST['quantidade'] ?? 0);
        $data = $_POST['data_movimentacao'] ?? date('Y-m-d');

        $stmt = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id=? AND loja_id=? AND quantidade_estoque >= ?");
        if (!$stmt) die("Erro prepare vender: " . $conn->error);

        $stmt->bind_param("iiii", $qtd, $id, $lojaId, $qtd);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $tipo = 'saida';
            $stmt2 = $conn->prepare("
                INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt2->bind_param("isisi", $id, $tipo, $qtd, $data, $usuarioId);
            $stmt2->execute();
            $stmt2->close();

            $msg = "📤 Venda registrada (-$qtd) em $data";
        } else {
            $msg = "❌ Estoque insuficiente ou erro! " . $stmt->error;
        }
        $stmt->close();
    }
}

/* ======================================================
   BUSCA PRODUTOS (LISTAGEM)
   ====================================================== */
$produtos = [];
$stmt = $conn->prepare("SELECT id, nome, preco_unitario, quantidade_estoque, quantidade_inicial, lote FROM produtos WHERE loja_id = ? AND deletado_em IS NULL ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param("i", $lojaId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
    $stmt->close();
}
?>



<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gerenciamento de Produtos — Decklogistic</title>
    <link rel="stylesheet" href="../../assets/sidebar.css">

<style>
  /* ===== Reset + base ===== */
  :root{
    --bg:#1b1b1b;
    --card:#222;
    --muted:#e0e0e0;
    --accent:#ff9900;
    --danger:#ef4444;
    --success:#16a34a;
    --glass: rgba(255,255,255,0.04);
    --radius:10px;
    --shadow:0 8px 24px rgba(0,0,0,0.16);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body, html{background:var(--bg)!important;color:var(--muted)!important;}
  a{color:inherit}

  header{background:linear-gradient(90deg,#111,#222);color:#fff;padding:14px 20px;border-bottom:1px solid #333;}
  header .wrap{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
  header h1{font-size:18px;font-weight:700}

  .container{max-width:1200px;margin:22px auto;padding:0 16px;display:grid;grid-template-columns:360px 1fr;gap:20px}

  .panel{background:var(--card);padding:16px;border-radius:var(--radius);box-shadow:var(--shadow);min-height:120px;color:var(--muted);}
  .panel h2{font-size:14px;color:#fff;margin-bottom:8px}
  .small{font-size:13px;color:#bbb}

  .card{background:var(--card);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow);color:var(--muted);}
  .card h2{font-size:16px;margin-bottom:12px;color:#fff;}
  .form-row{display:flex;gap:8px;flex-wrap:wrap}
  .form-row input{flex:1;padding:10px;border-radius:8px;border:1px solid #444;background:#222;color:#fff;}
  .form-row input[type="number"]{max-width:140px}
  .actions{display:flex;gap:8px;margin-top:12px}

  /* ===== Botões unificados ===== */
  .btn,
  .btn.primary,
  .btnVoltar,
  .actions-cell .btn {
    background: linear-gradient(135deg, rgba(255, 153, 0, 0.9), rgba(255, 200, 0, 0.9));
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: rgba(255, 170, 0, 0.4) 0px 4px 12px;
    font-weight: 600;
    padding: 10px;
  }

  .btn:hover,
  .btn.primary:hover,
  .btnVoltar:hover,
  .actions-cell .btn:hover {
    transform: translateY(-2px);
    box-shadow: rgba(255, 170, 0, 0.6) 0px 6px 16px;
    background: linear-gradient(135deg, rgba(255, 200, 0, 0.9), rgba(255, 153, 0, 0.9));
    padding: 10px;
    color: #fff;
  }

  .btn.ghost{
    background:transparent;
    border:1px solid #444;
    color:#ff9900;
  }
  .btn.ghost:hover{
    background:#222;
    color:#fff;
    border-color:#ff9900;
  }

  .table-wrap{overflow:auto;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:12px}
  table{width:100%;border-collapse:collapse;font-size:14px;background:#222;color:#e0e0e0;}
  th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #333;}
  thead th{color:#ff9900;font-size:13px;background:#222;}
  tbody tr:hover{background:var(--glass)}
  tbody tr{background:#222;}

  .actions-cell .btn {
    font-size: 13px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 6px;
  }

  .actions-cell .btn.deleteBtn {
    background: #3a1a1a;
    color: #ff9900;
    border: 1px solid #ff9900;
    box-shadow: none;
  }
  .actions-cell .btn.deleteBtn:hover {
    background: #ff9900;
    color: #fff;
    border-color: #ff9900;
    transform: none;
    box-shadow: 0 4px 12px rgba(255,153,0,0.4);
  }

  .modal-backdrop{position:fixed;inset:0;background:rgba(2,6,23,0.85);display:none;align-items:center;justify-content:center;padding:20px;z-index:100;}
  .modal{width:100%;max-width:520px;background:var(--card);border-radius:12px;padding:18px;box-shadow:0 20px 40px rgba(2,6,23,0.55);color:#fff;}
  .modal h3{margin-bottom:12px;color:#fff;}
  .modal .row{display:flex;gap:8px;flex-wrap:wrap}
  .modal input{flex:1;padding:10px;border-radius:8px;border:1px solid #444;background:#222;color:#fff;}
  .modal .foot{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}

  @media (max-width:980px){
    .container{grid-template-columns:1fr}
  }

  /* Sidebar dark */
  .sidebar {
    width: 220px;
    height: 100vh;
    background-color: #111;
    color: #fff;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
  }
  .logo-area {
    padding: 0;
    text-align: center;
    border-bottom: 1px solid #333;
  }
  .logo-area img {
    width: 100%;
    height: auto;
    object-fit: cover;
    display: block;
  }
  .nav-section {
    display: flex;
    flex-direction: column;
    height: 100%;
  }
  .nav-menus {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
  }
  .nav-list {
    list-style: none;
    margin: 0;
    padding-left: 0;
  }
  .nav-list li {
    position: relative;
  }
  .nav-list li a span {
    margin-right: 10px;
    font-size: 16px;
  }
  .nav-list li a span img {
    width: 18px;
    height: 18px;
    object-fit: contain;
  }
  .nav-list li a:hover {
    background-color: #222;
    color: #ff9900;
  }
  .nav-list li.active a {
    background-color: #ff9900;
    border-radius: 4px;
    color: #fff;
  }
  .nav-list li.active a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background-color: #ff9900;
    border-radius: 2px;
  }
  hr {
    border: none;
    height: 1px;
    background-color: #444;
    margin: 15px 20px;
  }
  .bottom-links {
    padding: 15px 20px;
    border-top: 1px solid #333;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .bottom-links a {
    color: #ff9900;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    transition: color 0.2s;
  }
  .bottom-links a span {
    margin-right: 8px;
  }
  .bottom-links a span img {
    width: 16px;
    height: 16px;
    object-fit: contain;
  }
  .bottom-links a:hover {
    color: #fff;
  }
  @media (max-width: 768px) {
    .sidebar {
      width: 100%;
      height: 60px;
      flex-direction: row;
      align-items: center;
      justify-content: space-around;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 100;
      padding: 0;
      background-color: #111;
    }
    .logo-area {
      display: none;
    }
    .nav-section {
      display: flex;
      flex-direction: row;
      flex: 1;
      justify-content: space-around;
      height: 100%;
    }
    .nav-menus {
      flex-direction: row;
      gap: 10px;
      justify-content: space-around;
      width: 100%;
    }
    .nav-list {
      display: flex;
      flex-direction: row;
      gap: 10px;
      margin: 0;
    }
    .nav-list li a {
      padding: 10px 5px;
      justify-content: center;
      font-size: 0;
    }
    .nav-list li a span {
      margin: 0;
    }
    .bottom-links {
      display: none;
    }
    .dashboard {
      padding: 20px;
    }
    .conteudo {
      display: block;
      margin-left: 220px;
      width: calc(100% - 220px);
      box-sizing: border-box;
    }
    .conteudo table {
      width: 100%;
      border-collapse: collapse;
    }
  }
  .container, .conteudo, .dashboard, main {
    background: transparent !important;
  }
</style>

</head>
<body>

  <aside class="sidebar">
    <div class="logo-area">
      <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>

    <nav class="nav-section">
      <div class="nav-menus">
        <ul class="nav-list top-section">
          <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span>Financeiro</a></li>
          <li><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span>Estoque</a></li>
        </ul>

        <hr>

        <ul class="nav-list middle-section">
          <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span>Visão Geral</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span>Operações</a></li>
          <li><a  class="active" href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span>Produtos</a></li>
          <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span>Tags</a></li>
        </ul>
      </div>

      <div class="bottom-links">
        <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span>Conta</a>
        <a href="../auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span>Dicas</a>
      </div>
    </nav>
  </aside>

<div class="container">
  <!-- lateral: resumo rápido -->
<!-- lateral: resumo rápido -->
<aside class="panel">
    <h2>Painel</h2>
    <p class="small">Produtos cadastrados: <strong><?= count($produtos) ?></strong></p>
    <p class="small">Ações: adicionar, editar, comprar, vender e apagar</p>
    <?php if($msg): ?>
        <div style="margin-top:12px;padding:10px;border-radius:8px;background:#f3fdf6;color:#064e3b;font-weight:600"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
</aside>


  <!-- conteúdo principal -->
  <main>

    <!-- formulário de adicionar -->
    <div class="card">
      <h2>➕ Adicionar Produto</h2>
      <form id="formAdd" method="POST">
        <input type="hidden" name="acao" value="adicionar_produto">
        <div class="form-row">
          <input type="text" name="nome" placeholder="Nome do produto" required>
          <input type="number" step="0.01" name="preco" placeholder="Preço (R$)" required>
          <input type="number" name="estoque" placeholder="Estoque inicial" required>
        </div>
        <div class="actions">
            <button class="btn primary" type="submit">Salvar</button>
          <button class="btn ghost" type="reset">Limpar</button>
        </div>
      </form>
    </div>

    <!-- tabela produtos -->
    <div class="table-wrap" style="margin-top:18px">
      <table>
        <thead>
          <tr><th>ID</th><th>Produto</th><th>Preço</th><th>Estoque</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php if (count($produtos) === 0): ?>
            <tr><td colspan="5">Nenhum produto cadastrado.</td></tr>
          <?php else: ?>
            <?php foreach($produtos as $p): ?>
              <tr data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>" data-preco="<?= $p['preco_unitario'] ?>" data-quantidade="<?= $p['quantidade_estoque'] ?>">
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['nome']) ?></td>
                <td>R$ <?= number_format($p['preco_unitario'],2,',','.') ?></td>
                <td><?= $p['quantidade_estoque'] ?></td>
                    <td class="actions-cell">
                    <button class="btn icon editBtn" type="button" title="Editar">Editar</button>
                    <button class="btn icon buyBtn" type="button" title="Comprar">Entrada</button>
                    <button class="btn icon sellBtn" type="button" title="Vender">Saída</button>
                    <button class="btn icon deleteBtn" type="button" title="Apagar">Excluir</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<!-- Modal único para editar / compra / venda -->
<div class="modal-backdrop" id="modalBackdrop">
  <div class="modal" role="dialog" aria-modal="true">
    <h3 id="modalTitle">Editar produto</h3>
    <form id="modalForm" method="POST">
      <input type="hidden" name="acao" id="modalAcao" value="">
      <input type="hidden" name="produto_id" id="modalProdutoId" value="">

      <div class="row" id="rowNome">
        <input type="text" name="nome" id="modalNome" placeholder="Nome do produto">
      </div>

      <div class="row" id="rowPreco">
        <input type="number" step="0.01" name="preco" id="modalPreco" placeholder="Preço (R$)">
        <input type="number" name="estoque" id="modalEstoque" placeholder="Quantidade (estoque)">
      </div>

      <div class="row" id="rowQtd">
        <input type="number" name="quantidade" id="modalQuantidade" placeholder="Quantidade" min="1">
        <input type="date" name="data_movimentacao" id="modalData" value="<?= date('Y-m-d') ?>">
      </div>

      <div class="foot">
        <button type="button" class="btn ghost" id="closeModal">Cancelar</button>
        <button type="submit" class="btn primary" id="modalSubmit">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Dados úteis
const lojaId = <?= json_encode($lojaId) ?>;
const usuarioId = <?= json_encode($usuarioId) ?>;

// Modal controls
const backdrop = document.getElementById('modalBackdrop');
const modalTitle = document.getElementById('modalTitle');
const modalForm = document.getElementById('modalForm');
const modalAcao = document.getElementById('modalAcao');
const modalProdutoId = document.getElementById('modalProdutoId');
const modalNome = document.getElementById('modalNome');
const modalPreco = document.getElementById('modalPreco');
const modalEstoque = document.getElementById('modalEstoque');
const modalQuantidade = document.getElementById('modalQuantidade');
const modalData = document.getElementById('modalData');
const closeModalBtn = document.getElementById('closeModal');

function openModal(type, row) {
  // Reset
  modalForm.reset();
  modalQuantidade.style.display = '';
  modalData.style.display = '';
  modalNome.style.display = '';
  modalPreco.style.display = '';
  modalEstoque.style.display = '';

  modalAcao.value = type;
  modalProdutoId.value = row.dataset.id || '';
  modalNome.value = row.dataset.nome || '';
  modalPreco.value = row.dataset.preco || '';
  modalEstoque.value = row.dataset.quantidade || '';
  modalQuantidade.value = 1;
  modalData.value = new Date().toISOString().slice(0,10);

  if (type === 'editar_produto') {
    modalTitle.textContent = 'Editar produto';
    // Mostrar campos nome, preco, estoque
    modalNome.required = true;
    modalPreco.required = true;
    modalEstoque.required = true;
    modalQuantidade.style.display = 'none';
    modalData.style.display = 'none';
    modalEstoque.style.display = '';
  } else if (type === 'comprar_produto') {
    modalTitle.textContent = 'Registrar compra (entrada)';
    modalNome.style.display = 'none';
    modalPreco.style.display = 'none';
    modalEstoque.style.display = 'none';
    modalQuantidade.required = true;
  } else if (type === 'vender_produto') {
    modalTitle.textContent = 'Registrar venda (saída)';
    modalNome.style.display = 'none';
    modalPreco.style.display = 'none';
    modalEstoque.style.display = 'none';
    modalQuantidade.required = true;
  }

  backdrop.style.display = 'flex';
}

function closeModal() {
  backdrop.style.display = 'none';
}

// Attach events
document.querySelectorAll('.editBtn').forEach(btn => {
  btn.addEventListener('click', e => {
    const row = e.target.closest('tr');
    openModal('editar_produto', row);
  });
});

document.querySelectorAll('.buyBtn').forEach(btn => {
  btn.addEventListener('click', e => {
    const row = e.target.closest('tr');
    openModal('comprar_produto', row);
  });
});

document.querySelectorAll('.sellBtn').forEach(btn => {
  btn.addEventListener('click', e => {
    const row = e.target.closest('tr');
    openModal('vender_produto', row);
  });
});

// Delete action: confirm then submit a small form
document.querySelectorAll('.deleteBtn').forEach(btn => {
  btn.addEventListener('click', e => {
    const row = e.target.closest('tr');
    const id = row.dataset.id;
    if (!confirm('Apagar este produto? Esta ação é irreversível.')) return;

    // Cria form dinâmico e submete
    const f = document.createElement('form');
    f.method = 'POST';
    f.style.display = 'none';

    const acao = document.createElement('input'); acao.name = 'acao'; acao.value = 'apagar_produto';
    const pid = document.createElement('input'); pid.name = 'produto_id'; pid.value = id;
    f.appendChild(acao); f.appendChild(pid);
    document.body.appendChild(f);
    f.submit();
  });
});

closeModalBtn.addEventListener('click', closeModal);
backdrop.addEventListener('click', (ev) => { if (ev.target === backdrop) closeModal(); });

// Ao submeter modalForm, apenas submit normalmente (POST) — servidor processa

</script>

</body>
</html>
