<?php
require 'vendor/autoload.php';
require '../../../conexao.php'; // Sua conexão já existe aqui

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

// Verificar se o arquivo foi enviado
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo enviado ou erro no upload.'
    ]);
    exit;
}

$tmp_name = $_FILES['excel_file']['tmp_name'];

// Verificar se é um arquivo Excel
$allowed_extensions = ['xlsx', 'xls'];
$file_info = pathinfo($_FILES['excel_file']['name']);
if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
    echo json_encode([
        'success' => false,
        'message' => 'Formato de arquivo inválido. Use .xlsx ou .xls.'
    ]);
    exit;
}

try {
    // Carrega o arquivo Excel
    $spreadsheet = IOFactory::load($tmp_name);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove o cabeçalho (primeira linha)
    $header = array_shift($rows);
    
    $imported = 0;
    $importedData = [];
    $errors = [];
    
    foreach ($rows as $index => $row) {
        // Pular linhas vazias
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Validação básica dos dados
        if (empty($row[0])) {
            $errors[] = "Linha " . ($index + 2) . ": Nome do produto é obrigatório";
            continue;
        }
        
        // Preparar os dados
        $nome = $row[0];
        $descricao = $row[1] ?? '';
        $lote = $row[2] ?? '';
        $quantidade_estoque = intval($row[3] ?? 0);
        $preco_unitario = floatval($row[4] ?? 0);
        $custo_unitario = floatval($row[5] ?? 0);
        $data_reabastecimento = $row[6] ?? null;
        
        // Processar data
        if ($data_reabastecimento) {
            if (is_numeric($data_reabastecimento)) {
                // Se for um número serial do Excel, converter para data
                $data_reabastecimento = Date::excelToDateTimeObject($data_reabastecimento)->format('Y-m-d');
            } else {
                // Tentar converter para formato de data
                $timestamp = strtotime($data_reabastecimento);
                if ($timestamp !== false) {
                    $data_reabastecimento = date('Y-m-d', $timestamp);
                } else {
                    $data_reabastecimento = null;
                }
            }
        } else {
            $data_reabastecimento = null;
        }
        
        try {
            // Inserir no banco de dados
            $sql = "INSERT INTO produto (nome, descricao, lote, quantidade_estoque, preco_unitario, custo_unitario, data_reabastecimento) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssidds", 
                $nome, 
                $descricao, 
                $lote, 
                $quantidade_estoque, 
                $preco_unitario, 
                $custo_unitario, 
                $data_reabastecimento
            );
            
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
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    if ($imported > 0) {
        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'data' => $importedData,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum dado foi importado. Verifique o formato do arquivo.',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()
    ]);
}
?>