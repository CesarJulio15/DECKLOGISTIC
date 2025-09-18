<?php
session_start();
include '../../../conexao.php';

if (!empty($_POST['produto_ids'])) {
    $ids = explode(',', $_POST['produto_ids']);
    $ids = array_map('intval', $ids); // garante números inteiros

    if (count($ids)) {
        foreach ($ids as $id) {
            $usuario_id = $_SESSION['usuario_id'] ?? null;

            // Pega os dados do produto antes de excluir
            $sqlProduto = "SELECT nome, quantidade_estoque, lote FROM produtos WHERE id = $id";
            $res = $conn->query($sqlProduto);

            if ($res && $res->num_rows > 0) {
                $produto = $res->fetch_assoc();

                // Insere no histórico
                $stmt = $conn->prepare("
                    INSERT INTO historico_produtos 
                    (produto_id, nome, quantidade, lote, acao, usuario_id, criado_em)
                    VALUES (?, ?, ?, ?, 'excluido', ?, NOW())
                ");
                $stmt->bind_param(
                    "isisi",
                    $id,
                    $produto['nome'],
                    $produto['quantidade_estoque'],
                    $produto['lote'],
                    $usuario_id
                );
                $stmt->execute();
            }
        }

        // Remove tags vinculadas
        $ids_str = implode(',', $ids);
        $conn->query("DELETE FROM produto_tag WHERE produto_id IN ($ids_str)");

        // Remove produtos
        if ($conn->query("DELETE FROM produtos WHERE id IN ($ids_str)")) {
            echo 'ok';
        } else {
            echo 'erro';
        }
    } else {
        echo 'erro';
    }
} else {
    echo 'erro';
}
