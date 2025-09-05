<?php
session_start();
include '../../../conexao.php';

if (isset($_POST['produto_ids'])) {
    $ids = explode(',', $_POST['produto_ids']);
    $ids = array_map('intval', $ids);
    $ids_str = implode(',', $ids);
    
    // Corrija para pegar o ID do usuário correto
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if (!$usuario_id) {
        echo "erro: usuário não logado";
        exit;
    }

    foreach ($ids as $id) {
        $p = $conn->query("SELECT * FROM produtos WHERE id = $id")->fetch_assoc();
        if ($p) {
            $conn->query("
                INSERT INTO historico_produtos
                (produto_id, nome, quantidade, lote, usuario_id, acao, criado_em)
                VALUES
                ({$p['id']}, '{$p['nome']}', {$p['quantidade_estoque']}, '{$p['lote']}', $usuario_id, 'excluido', NOW())
            ");
        }
    }

    $conn->query("DELETE FROM movimentacoes_estoque WHERE produto_id IN ($ids_str)");
    echo $conn->query("DELETE FROM produtos WHERE id IN ($ids_str)") ? "ok" : "erro";
    exit;
}

echo "erro";

