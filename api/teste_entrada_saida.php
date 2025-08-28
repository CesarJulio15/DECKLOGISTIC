<?php
header('Content-Type: application/json');

// Dados de teste
echo json_encode([
    ["data_movimentacao" => "2025-08-25", "entrada" => 10, "saida" => 5],
    ["data_movimentacao" => "2025-08-26", "entrada" => 8,  "saida" => 3],
    ["data_movimentacao" => "2025-08-27", "entrada" => 15, "saida" => 7],
]);