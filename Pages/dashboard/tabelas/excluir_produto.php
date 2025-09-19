<?php
session_start();
include '../../../conexao.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$loja_id = $_SESSION['loja_id'] ?? null;

if (!empty($_POST['produto_ids']) && $usuario_id && $loja_id) {
    $ids = array_map('intval', explode(',', $_POST['produto_ids']));
    if (count($ids)) {
        $ids_placeholders = implode(',', $ids);

        // Busca produtos da loja do usuário
        $produtosRes = $conn->query("SELECT id, nome, quantidade_estoque, lote FROM produtos WHERE id IN ($ids_placeholders) AND loja_id = $loja_id");
        $produtos = $produtosRes->fetch_all(MYSQLI_ASSOC);

        if (count($produtos)) {
            // Prepara histórico
            $stmtHist = $conn->prepare("INSERT INTO historico_produtos (produto_id, nome, quantidade, lote, acao, usuario_id, criado_em) VALUES (?, ?, ?, ?, 'excluido', ?, NOW())");

            foreach ($produtos as $p) {
                // Garantindo tipos corretos
                $quantidade = (int)$p['quantidade_estoque'];
                $lote = (string)$p['lote'];
                $stmtHist->bind_param("isisi", $p['id'], $p['nome'], $quantidade, $lote, $usuario_id);
                $stmtHist->execute();
            }
            $stmtHist->close();

            // Deleta tags e produtos
            $conn->query("DELETE FROM produto_tag WHERE produto_id IN ($ids_placeholders)");
            $conn->query("DELETE FROM produtos WHERE id IN ($ids_placeholders)");

            echo 'ok';
            exit;
        }
    }
}
echo 'erro';
