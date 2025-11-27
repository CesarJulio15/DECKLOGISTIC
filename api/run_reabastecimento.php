<?php
session_start();

if (!isset($_SESSION['loja_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Loja não autenticada"]);
    exit;
}

$lojaId = $_SESSION['loja_id'];
$python = "C:\\xampp\\htdocs\\DECKLOGISTIC\\.venv\\Scripts\\python.exe";
$script = "C:\\xampp\\htdocs\\DECKLOGISTIC\\ml\\run_reabastecimento.py";

// Passando loja_id e capturando stdout + stderr
$cmd = "$python $script $lojaId 2>&1";
$output = shell_exec($cmd);

// DEBUG: salva todo o output do Python
file_put_contents(__DIR__ . '/debug_reabastecimento.log', $output);

$recomendacoes = [];
$linhas = [];

if ($output !== null) {
    $linhas = explode("\n", $output);

    foreach ($linhas as $linha) {
        error_log("Linha Python: " . $linha);
        if (preg_match('/Produto (\d+) \((.*?)\): Reabasteca (\d+) unidades \(demanda prevista: (\d+)\)/', $linha, $m)) {
            $recomendacoes[] = [
                'produto_id' => (int)$m[1],
                'nome' => $m[2],
                'quantidade' => (int)$m[3],
                'demanda' => (int)$m[4]
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'recomendacoes' => $recomendacoes,
    'debug_total_linhas' => count($linhas)
]);
