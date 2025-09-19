<?php
require 'vendor/autoload.php';
require '../../../conexao.php';

session_start();

header('Content-Type: application/json');

// Verifica login
if (!isset($_SESSION['loja_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para importar produtos.']);
    exit;
}

$lojaId = $_SESSION['loja_id'];

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Verifica arquivo
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$tmp_name = $_FILES['excel_file']['tmp_name'];
$allowed_extensions = ['xlsx', 'xls'];
$file_info = pathinfo($_FILES['excel_file']['name']);
if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Formato de arquivo inválido. Use .xlsx ou .xls.']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($tmp_name);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    $header = array_shift($rows);
    
    $imported = 0;
    $importedData = [];
    $errors = [];
    
    foreach ($rows as $index => $row) {
        if (empty(array_filter($row))) continue;
        if (empty($row[0])) {
            $errors[] = "Linha " . ($index + 2) . ": Nome do produto é obrigatório";
            continue;
        }

        $nome = $row[0];
        $descricao = $row[1] ?? '';
        $lote = $row[2] ?? '';
        $quantidade_estoque = intval($row[3] ?? 0);
        $preco_unitario = floatval($row[4] ?? 0);
        $custo_unitario = floatval($row[5] ?? 0);
        $data_reabastecimento = $row[6] ?? null;

        if ($data_reabastecimento) {
            if (is_numeric($data_reabastecimento)) {
                $data_reabastecimento = Date::excelToDateTimeObject($data_reabastecimento)->format('Y-m-d');
            } else {
                $timestamp = strtotime($data_reabastecimento);
                $data_reabastecimento = $timestamp ? date('Y-m-d', $timestamp) : null;
            }
        }

        $stmt = $conn->prepare("INSERT INTO produtos 
            (loja_id, nome, descricao, lote, quantidade_estoque, preco_unitario, custo_unitario, data_reabastecimento) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
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
            $errors[] = "Linha " . ($index + 2) . ": Erro ao inserir no banco";
        }

        $stmt->close();
    }

    echo json_encode([
        'success' => $imported > 0,
        'imported' => $imported,
        'data' => $importedData,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()]);
}
