<?php
session_start();
include __DIR__ . '/../../conexao.php';

$loja_id = $_SESSION['usuario_id'] ?? 0;
//$usuario_id = $_SESSION['usuario_id'] ?? 0;
if (!$loja_id || !$usuario_id) {
    die('Faça login para acessar o gerenciamento.');
}

$msg = "";

/* ======================================================
   AÇÕES DE BACKEND (ADD / EDIT / DELETE / COMPRAR / VENDER)
   ====================================================== */
if (isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // ---------- ADICIONAR PRODUTO ----------
    if ($acao === 'adicionar_produto') {
        $nome = trim($_POST['nome']);
        $preco = floatval($_POST['preco']);
        $estoque = intval($_POST['estoque']);

        $stmt = $conn->prepare("INSERT INTO produtos (loja_id, usuario_id, nome, preco_unitario, quantidade_estoque) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisdi", $lojaId, $usuarioId, $nome, $preco, $estoque);
        $msg = $stmt->execute() ? "✅ Produto cadastrado com sucesso!" : "❌ Erro: ".$stmt->error;
        $stmt->close();
    }

    // ---------- EDITAR PRODUTO ----------
    if ($acao === 'editar_produto') {
        $id = intval($_POST['produto_id']);
        $nome = trim($_POST['nome']);
        $preco = floatval($_POST['preco']);
        $estoque = intval($_POST['estoque']);

        $stmt = $conn->prepare("UPDATE produtos SET nome=?, preco_unitario=?, quantidade_estoque=? WHERE id=? AND loja_id=?");
        $stmt->bind_param("sdiii", $nome, $preco, $estoque, $id, $lojaId);
        $msg = $stmt->execute() ? "✏️ Produto atualizado!" : "❌ Erro: ".$stmt->error;
        $stmt->close();
    }

// ---------- APAGAR PRODUTO ----------
if ($acao === 'apagar_produto') {
    $id = intval($_POST['produto_id']);

    // Remove tags associadas
    $stmt = $conn->prepare("DELETE FROM produto_tag WHERE produto_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Marca como excluído
    $stmt = $conn->prepare("
        UPDATE produtos 
        SET deletado_em = NOW(), usuario_exclusao_id = ? 
        WHERE id = ? AND loja_id = ?
    ");
    $stmt->bind_param("iii", $usuario_id, $id, $loja_id);

    if ($stmt->execute()) {
        $msg = "🗑️ Produto apagado!";
    } else {
        $msg = "❌ Erro: " . $stmt->error;
    }
    $stmt->close();
}


// ---------- COMPRAR (ENTRADA DE ESTOQUE) ----------
if ($acao === 'comprar_produto') {
    $id = intval($_POST['produto_id']);
    $qtd = intval($_POST['quantidade']);
    $data = $_POST['data_movimentacao'] ?? date('Y-m-d');

    // Atualiza estoque
    $stmt = $conn->prepare("
        UPDATE produtos 
        SET quantidade_estoque = quantidade_estoque + ? 
        WHERE id=? AND loja_id=?
    ");
    $stmt->bind_param("iii", $qtd, $id, $loja_id);

    if ($stmt->execute()) {
        // Registra movimentação
        $stmt2 = $conn->prepare("
            INSERT INTO movimentacoes_estoque 
            (produto_id, tipo, quantidade, data_movimentacao, usuario_id) 
            VALUES (?, 'entrada', ?, ?, ?)
        ");
        $stmt2->bind_param("iisi", $id, $qtd, $data, $usuario_id);
        $stmt2->execute();
        $stmt2->close();

        $msg = "📥 Compra registrada (+$qtd) em $data";
    } else {
        $msg = "❌ Erro ao registrar compra!";
    }
    $stmt->close();
}

// ---------- VENDER (SAÍDA DE ESTOQUE) ----------
if ($acao === 'vender_produto') {
    $id = intval($_POST['produto_id']);
    $qtd = intval($_POST['quantidade']);
    $data = $_POST['data_movimentacao'] ?? date('Y-m-d');

    // Atualiza estoque
    $stmt = $conn->prepare("
        UPDATE produtos 
        SET quantidade_estoque = quantidade_estoque - ? 
        WHERE id=? AND loja_id=? AND quantidade_estoque >= ?
    ");
    $stmt->bind_param("iiii", $qtd, $id, $loja_id, $qtd);

    if ($stmt->execute()) {
        // Registra movimentação
        $stmt2 = $conn->prepare("
            INSERT INTO movimentacoes_estoque 
            (produto_id, tipo, quantidade, data_movimentacao, usuario_id) 
            VALUES (?, 'saida', ?, ?, ?)
        ");
        $stmt2->bind_param("iisi", $id, $qtd, $data, $usuario_id);
        $stmt2->execute();
        $stmt2->close();

        $msg = "📤 Venda registrada (-$qtd) em $data";
    } else {
        $msg = "❌ Estoque insuficiente ou erro!";
    }
    $stmt->close();
}


}

/* ======================================================
   LISTAR PRODUTOS
   ====================================================== */
$produtos = [];
$res = $conn->query("
    SELECT * 
    FROM produtos 
    WHERE loja_id = $loja_id 
      AND deletado_em IS NULL
    ORDER BY id DESC
");
while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerenciamento de Produtos</title>
<link rel="stylesheet" href="../../assets/gerenciamento_produtos.css">
</head>
<body>

<header>
    <h1>📦 Gerenciamento de Produtos</h1>
</header>

<main>
    <?php if($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>

    <section class="novo-produto">
        <h2>➕ Adicionar Produto</h2>
        <form method="POST">
            <input type="hidden" name="acao" value="adicionar_produto">
            <input type="text" name="nome" placeholder="Nome" required>
            <input type="number" step="0.01" name="preco" placeholder="Preço" required>
            <input type="number" name="estoque" placeholder="Estoque inicial" required>
            <button type="submit" class="btn primary">Salvar</button>
        </form>
    </section>

    <section class="produtos">
        <h2>Lista de Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($produtos)): ?>
                    <?php foreach($produtos as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nome']) ?></td>
                        <td>R$ <?= number_format($p['preco_unitario'],2,',','.') ?></td>
                        <td><?= $p['quantidade_estoque'] ?></td>
                        <td>
                            <!-- Editar -->
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="acao" value="editar_produto">
                                <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                <input type="text" name="nome" value="<?= htmlspecialchars($p['nome']) ?>" required>
                                <input type="number" step="0.01" name="preco" value="<?= $p['preco_unitario'] ?>" required>
                                <input type="number" name="estoque" value="<?= $p['quantidade_estoque'] ?>" required>
                                <button type="submit" class="btn">✏️</button>
                            </form>

                            <!-- Comprar -->
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="acao" value="comprar_produto">
                                <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                <input type="number" name="quantidade" placeholder="Qtd" min="1" required>
                                <input type="date" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
                                <button type="submit" class="btn">📥</button>
                            </form>

                            <!-- Vender -->
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="acao" value="vender_produto">
                                <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                <input type="number" name="quantidade" placeholder="Qtd" min="1" required>
                                <input type="date" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
                                <button type="submit" class="btn">📤</button>
                            </form>


                            <!-- Apagar -->
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="acao" value="apagar_produto">
                                <input type="hidden" name="produto_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn delete" onclick="return confirm('Apagar este produto?')">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">Nenhum produto cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script>
    // Passando valores do PHP para JS
    const usuarioId = <?= json_encode($_SESSION['usuario_id'] ?? 0) ?>;
    const lojaId = <?= json_encode($_SESSION['loja_id'] ?? 0) ?>;

    console.log("👤 Usuário logado ID:", usuarioId);
    console.log("🏬 Loja logada ID:", lojaId);
</script>


</body>
</html>
