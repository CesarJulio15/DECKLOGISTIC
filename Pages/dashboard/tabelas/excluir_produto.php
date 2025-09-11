<?php
include '../../../conexao.php';

if(!empty($_POST['produto_ids'])){
    $ids = explode(',', $_POST['produto_ids']);
    $ids = array_map('intval', $ids); // garante números inteiros

    if(count($ids)){
        // Remove tags vinculadas
        $ids_str = implode(',', $ids);
        $conn->query("DELETE FROM produto_tag WHERE produto_id IN ($ids_str)");

        // Remove produtos
        if($conn->query("DELETE FROM produtos WHERE id IN ($ids_str)")){
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