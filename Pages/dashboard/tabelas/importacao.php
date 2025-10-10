<?php
// importacao.php (versão limpa e tolerante a saída acidental)

require 'vendor/autoload.php';
require '../../../conexao.php';
// REMOVIDO: include '../../../header.php'; // header imprime HTML — não deve estar aqui

// Evita que warnings/notices quebrem o JSON retornado
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Começa sessão (se necessário)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Força cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Verifica login
if (!isset($_SESSION['loja_id'])) {
    // limpa qualquer saída acidental antes de retornar JSON limpo
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para importar produtos.']);
    exit;
}

$lojaId = $_SESSION['loja_id'];

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Verifica arquivo
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$tmp_name = $_FILES['excel_file']['tmp_name'];
$allowed_extensions = ['xlsx', 'xls'];
$file_info = pathinfo($_FILES['excel_file']['name']);
if (!isset($file_info['extension']) || !in_array(strtolower($file_info['extension']), $allowed_extensions)) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Formato de arquivo inválido. Use .xlsx ou .xls.']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($tmp_name);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove linhas vazias iniciais se houver
    while (!empty($rows) && empty(array_filter($rows[0]))) {
        array_shift($rows);
    }

    // Cabeçalho (se houver)
    if (!empty($rows)) {
        $header = array_shift($rows);
    } else {
        $header = [];
    }

    $imported = 0;
    $importedData = [];
    $errors = [];

    foreach ($rows as $index => $row) {
        // ignora linhas completamente vazias
        if (!is_array($row) || empty(array_filter($row))) continue;

        // A linha do Excel visualmente '2' corresponde ao index+2 (se header removido)
        $displayLine = $index + 2;

        // verificar nome (coluna 0)
        $nome = trim($row[0] ?? '');
        if ($nome === '') {
            $errors[] = "Linha {$displayLine}: Nome do produto é obrigatório";
            continue;
        }

        $descricao = trim($row[1] ?? '');
        $lote = trim($row[2] ?? '');
        $quantidade_estoque = intval($row[3] ?? 0);
        // Para valores numéricos com vírgula/ponto no Excel, a leitura deve já vir como float
        $preco_unitario = is_numeric($row[4]) ? floatval($row[4]) : floatval(str_replace(',', '.', str_replace('.', '', (string)($row[4] ?? 0))));
        $custo_unitario = is_numeric($row[5]) ? floatval($row[5]) : floatval(str_replace(',', '.', str_replace('.', '', (string)($row[5] ?? 0))));
        $data_reabastecimento = $row[6] ?? null;

        if ($data_reabastecimento) {
            if (is_numeric($data_reabastecimento)) {
                // Excel pode retornar número serial
                $dataObj = Date::excelToDateTimeObject($data_reabastecimento);
                $data_reabastecimento = $dataObj ? $dataObj->format('Y-m-d') : null;
            } else {
                $timestamp = strtotime($data_reabastecimento);
                $data_reabastecimento = $timestamp ? date('Y-m-d', $timestamp) : null;
            }
        } else {
            $data_reabastecimento = null;
        }

        // Prepara e executa insert
        $stmt = $conn->prepare("INSERT INTO produtos 
            (loja_id, nome, descricao, lote, quantidade_estoque, preco_unitario, custo_unitario, data_reabastecimento) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $errors[] = "Linha {$displayLine}: erro na preparação da query - " . $conn->error;
            continue;
        }

        // Tipos: i = int, s = string, d = double
        $stmt->bind_param("isssidds", $lojaId, $nome, $descricao, $lote, $quantidade_estoque, $preco_unitario, $custo_unitario, $data_reabastecimento);
        if ($stmt->execute()) {
            $imported++;
            $importedData[] = [
                'nome' => $nome,
                'descricao' => $descricao,
                'lote' => $lote,
                'quantidade_estoque' => $quantidade_estoque,
                'preco_unitario' => $preco_unitario,
                'custo_unitario' => $custo_unitario,
                'data_reabastecimento' => $data_reabastecimento
            ];
        } else {
            $errors[] = "Linha {$displayLine}: Erro ao inserir no banco - " . $stmt->error;
        }
        $stmt->close();
    }

    // limpa buffer (remove qualquer saída acidental), e retorna JSON limpo
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => $imported > 0,
        'imported' => $imported,
        'data' => $importedData,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);

    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()]);
    exit;
}
