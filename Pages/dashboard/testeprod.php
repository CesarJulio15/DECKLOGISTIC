<?php
session_start();

// Exibe erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ID da loja logada
$loja_id = $_SESSION['id'] ?? 0;

if (!$loja_id) {
    die('Loja não identificada. Faça login.');
}

// Conexão com o banco
include __DIR__ . '/../../conexao.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? null;
    $descricao = $_POST['descricao'] ?? '';
    $preco_unitario = $_POST['preco_unitario'] ?? 0;
    $quantidade_estoque = $_POST['quantidade_estoque'] ?? 0;
    $lote = $_POST['lote'] ?? '';
    $data_reabastecimento = $_POST['data_reabastecimento'] ?? null;

    if (!$nome || !$preco_unitario || !$data_reabastecimento) {
        $msg = 'Campos obrigatórios faltando.';
    } else {
        // Inserir produto
        $stmt = $conn->prepare("
            INSERT INTO produtos 
            (loja_id, nome, descricao, preco_unitario, quantidade_estoque, lote, data_reabastecimento)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdiss", $loja_id, $nome, $descricao, $preco_unitario, $quantidade_estoque, $lote, $data_reabastecimento);

        if ($stmt->execute()) {
            $produto_id = $stmt->insert_id;

            // Inserir movimentação inicial
            if($quantidade_estoque > 0){
                $stmtMov = $conn->prepare("
                    INSERT INTO movimentacoes_estoque
                    (produto_id, tipo, quantidade, motivo, data_movimentacao)
                    VALUES (?, 'entrada', ?, 'Reabastecimento inicial', ?)
                ");
                $stmtMov->bind_param("iis", $produto_id, $quantidade_estoque, $data_reabastecimento);
                $stmtMov->execute();
                $stmtMov->close();
            }

            $msg = 'Produto adicionado com sucesso!';
        } else {
            $msg = 'Erro ao adicionar produto: ' . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adicionar Produto</title>
<style>
    body { font-family: Arial; padding: 20px; }
    form { max-width: 400px; margin:auto; display:flex; flex-direction:column; gap:10px; }
    input, textarea, button { padding:8px; font-size:14px; }
    button { cursor:pointer; background:#007BFF; color:#fff; border:none; }
    .msg { margin-top:10px; font-weight:bold; }
</style>
</head>
<body>

<h2>Adicionar Produto</h2>

<?php if($msg): ?>
    <div class="msg"><?= $msg ?></div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="nome" placeholder="Nome do Produto" required>
    <textarea name="descricao" placeholder="Descrição"></textarea>
    <input type="number" step="0.01" name="preco_unitario" placeholder="Preço Unitário" required>
    <input type="number" name="quantidade_estoque" placeholder="Quantidade em Estoque" value="0">
    <input type="text" name="lote" placeholder="Lote">
    <input type="date" name="data_reabastecimento" required>
    <button type="submit">Adicionar Produto</button>
</form>

</body>
</html>
