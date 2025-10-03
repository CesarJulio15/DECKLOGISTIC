<?php
session_start();

// Verifica se a loja está logada
if (!isset($_SESSION['loja_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}

$lojaId = intval($_SESSION['loja_id']);

// Caminho completo do Python (no ambiente virtual)
$python = '"C:\\xampp\\htdocs\\DECKLOGISTIC\\venv\\Scripts\\python.exe"';
$script = __DIR__ . "/../ml/detectar_anomalias_vendas.py";

// Comando para executar Python passando o ID da loja
$cmd = "$python $script $lojaId 2>&1";
$output = shell_exec($cmd);

// Se não retornou nada, evita JSON vazio
if ($output === null) {
    $output = "Erro: não foi possível executar o script Python.";
}

$cmd = "$python $script $lojaId > C:\\xampp\\htdocs\\DECKLOGISTIC\\log_ia.txt 2>&1";
shell_exec($cmd);

// Retorna o resultado
header('Content-Type: application/json');
echo json_encode([
    "status" => "ok",
    "output" => $output
]);

