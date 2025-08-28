<?php
header('Content-Type: application/json');
echo json_encode([
    ["produto"=>"Produto A","qtd"=>10],
    ["produto"=>"Produto B","qtd"=>0],
    ["produto"=>"Produto C","qtd"=>5]
]);