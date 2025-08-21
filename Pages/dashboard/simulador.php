<?php
session_start();
include __DIR__ . '/../../conexao.php';

$loja_id = $_SESSION['id'] ?? 0;
if (!$loja_id) die('Faça login para acessar a simulação.');

// Mensagem de retorno
$msg = '';

// ============================
// ADICIONAR PRODUTO
// ============================
if(isset($_POST['acao']) && $_POST['acao'] === 'adicionar_produto'){
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco_unitario = floatval($_POST['preco_unitario'] ?? 0);
    $quantidade_estoque = intval($_POST['quantidade_estoque'] ?? 0);
    $lote = $_POST['lote'] ?? '';
    $data_reabastecimento = $_POST['data_reabastecimento'] ?? date('Y-m-d');

    if(!$nome || !$preco_unitario){
        $msg = 'Campos obrigatórios faltando.';
    } else {
        $stmt = $conn->prepare("INSERT INTO produtos (loja_id, nome, descricao, preco_unitario, quantidade_estoque, lote, data_reabastecimento) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdiss", $loja_id, $nome, $descricao, $preco_unitario, $quantidade_estoque, $lote, $data_reabastecimento);
        if($stmt->execute()){
            $produto_id = $stmt->insert_id;
            if($quantidade_estoque > 0){
                // Movimentação de estoque
                $stmtMov = $conn->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, data_movimentacao) VALUES (?, 'entrada', ?, 'Reabastecimento inicial', ?)");
                $stmtMov->bind_param("iis", $produto_id, $quantidade_estoque, $data_reabastecimento);
                $stmtMov->execute();
                $stmtMov->close();
                // Simula transação financeira de saída (compra do estoque)
                $stmtFin = $conn->prepare("INSERT INTO transacoes_financeiras (loja_id, categoria, descricao, tipo, valor, data_transacao) VALUES (?, 'Compra de Estoque', ?, 'saida', ?, ?)");
                $valor_saida = $quantidade_estoque * $preco_unitario;
                $descricao = "Compra inicial do produto $nome";
                $stmtFin->bind_param("issds", $loja_id, $descricao, $valor_saida, $data_reabastecimento);
                $stmtFin->execute();
                $stmtFin->close();
            }
            $msg = 'Produto adicionado com sucesso!';
        } else {
            $msg = 'Erro: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================
// VENDER PRODUTO
// ============================
if(isset($_POST['acao']) && $_POST['acao'] === 'vender_produto'){
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);

    if($produto_id && $quantidade > 0){
        // Pega o preço unitário atual
        $resProd = $conn->query("SELECT nome, preco_unitario FROM produtos WHERE id = $produto_id AND loja_id = $loja_id");
        if($resProd->num_rows){
            $prod = $resProd->fetch_assoc();
            $preco_unitario = $prod['preco_unitario'];
            $nome = $prod['nome'];

            // Atualiza estoque
            $stmt = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ? AND loja_id = ?");
            $stmt->bind_param("iii", $quantidade, $produto_id, $loja_id);
            if($stmt->execute()){
                // Movimentação de estoque
                $stmtMov = $conn->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, data_movimentacao) VALUES (?, 'saida', ?, 'Venda simulada', NOW())");
                $stmtMov->bind_param("ii", $produto_id, $quantidade);
                $stmtMov->execute();
                $stmtMov->close();

                // Transação financeira de entrada (venda)
                $stmtFin = $conn->prepare("INSERT INTO transacoes_financeiras (loja_id, categoria, descricao, tipo, valor, data_transacao) VALUES (?, 'Venda', ?, 'entrada', ?, NOW())");
                $valor_entrada = $quantidade * $preco_unitario;
                $descricao = "Venda do produto $nome";
                $stmtFin->bind_param("isd", $loja_id, $descricao, $valor_entrada);
                $stmtFin->execute();
                $stmtFin->close();

                $msg = 'Venda registrada com sucesso!';
            } else {
                $msg = 'Erro na venda: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ============================
// COMPRAR/REABASTECER PRODUTO
// ============================
if(isset($_POST['acao']) && $_POST['acao'] === 'comprar_produto'){
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);

    if($produto_id && $quantidade > 0){
        $resProd = $conn->query("SELECT nome, preco_unitario FROM produtos WHERE id = $produto_id AND loja_id = $loja_id");
        if($resProd->num_rows){
            $prod = $resProd->fetch_assoc();
            $preco_unitario = $prod['preco_unitario'];
            $nome = $prod['nome'];

            $stmt = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id = ? AND loja_id = ?");
            $stmt->bind_param("iii", $quantidade, $produto_id, $loja_id);
            if($stmt->execute()){
                // Movimentação de estoque
                $stmtMov = $conn->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, data_movimentacao) VALUES (?, 'entrada', ?, 'Compra/Reabastecimento', NOW())");
                $stmtMov->bind_param("ii", $produto_id, $quantidade);
                $stmtMov->execute();
                $stmtMov->close();

                // Transação financeira de saída (compra)
                $stmtFin = $conn->prepare("INSERT INTO transacoes_financeiras (loja_id, categoria, descricao, tipo, valor, data_transacao) VALUES (?, 'Compra de Estoque', ?, 'saida', ?, NOW())");
                $valor_saida = $quantidade * $preco_unitario;
                $descricao = "Compra/Reabastecimento do produto $nome";
                $stmtFin->bind_param("isd", $loja_id, $descricao, $valor_saida);
                $stmtFin->execute();
                $stmtFin->close();

                $msg = 'Compra registrada com sucesso!';
            } else {
                $msg = 'Erro na compra: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ============================
// LISTAR PRODUTOS
// ============================
$produtos = [];
$res = $conn->query("SELECT * FROM produtos WHERE loja_id = $loja_id ORDER BY id DESC");
while($row = $res->fetch_assoc()){
    $produtos[] = $row;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simulação Loja</title>
<style>
body{font-family:Arial;padding:20px;background:#f5f5f5;}
h2,h3{margin:10px 0;}
.msg{margin:10px 0;color:green;font-weight:bold;}
form{margin-bottom:20px;background:#fff;padding:15px;border-radius:8px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
form input,form select,form button,form textarea{padding:8px;margin:5px 0;width:100%;font-size:14px;}
form button{cursor:pointer;background:#007BFF;color:#fff;border:none;border-radius:4px;}
table{width:100%;border-collapse:collapse;background:#fff;margin-bottom:20px;}
th,td{padding:8px;text-align:left;border:1px solid #ddd;}
thead{background:#007BFF;color:#fff;}
.table-container{display:flex;gap:20px;flex-wrap:wrap;}
.table-container .card{flex:1;min-width:300px;}
</style>
</head>
<body>

<h2>Simulação Loja</h2>
<?php if($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

<div class="table-container">

<!-- PRODUTOS -->
<div class="card">
<h3>Produtos</h3>
<table>
<thead><tr>
<th>ID</th>
<th>Nome</th>
<th>Preço Unit.</th>
<th>Qtd Estoque</th>
</tr></thead>
<tbody>
<?php foreach($produtos as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><?= $p['nome'] ?></td>
<td><?= number_format($p['preco_unitario'],2,',','.') ?></td>
<td><?= $p['quantidade_estoque'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h4>Adicionar Produto</h4>
<form method="POST">
<input type="hidden" name="acao" value="adicionar_produto">
<input type="text" name="nome" placeholder="Nome do Produto" required>
<textarea name="descricao" placeholder="Descrição"></textarea>
<input type="number" step="0.01" name="preco_unitario" placeholder="Preço Unitário" required>
<input type="number" name="quantidade_estoque" placeholder="Quantidade" value="0">
<input type="text" name="lote" placeholder="Lote">
<input type="date" name="data_reabastecimento" value="<?= date('Y-m-d') ?>" required>
<button type="submit">Adicionar Produto</button>
</form>
</div>

<!-- VENDER / COMPRAR -->
<div class="card">
<h3>Movimentações</h3>

<h4>Vender Produto</h4>
<form method="POST">
<input type="hidden" name="acao" value="vender_produto">
<select name="produto_id" required>
<option value="">Selecione Produto</option>
<?php foreach($produtos as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['nome'] ?> (Estoque: <?= $p['quantidade_estoque'] ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantidade" placeholder="Quantidade" required>
<button type="submit">Registrar Venda</button>
</form>

<h4>Comprar / Reabastecer Produto</h4>
<form method="POST">
<input type="hidden" name="acao" value="comprar_produto">
<select name="produto_id" required>
<option value="">Selecione Produto</option>
<?php foreach($produtos as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['nome'] ?> (Estoque: <?= $p['quantidade_estoque'] ?>)</option>
<?php endforeach; ?>
</select>
<input type="number" name="quantidade" placeholder="Quantidade" required>
<button type="submit">Registrar Compra</button>
</form>
</div>

</div>

</body>
</html>
