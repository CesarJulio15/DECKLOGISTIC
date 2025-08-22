<?php
require_once '../conexao.php';

$res = mysqli_query($conn, "SELECT SUM(quantidade_estoque) AS total FROM produtos");
$total = 0;
if($row = mysqli_fetch_assoc($res)) {
    $total = (int)$row['total'];
}

echo json_encode(['total' => $total]);
?>
