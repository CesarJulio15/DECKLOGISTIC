<?php
// importacao.php (versão limpa e tolerante a saída acidental)

// Primeiro limpa qualquer saída anterior
ob_clean();

// Força o PHP a mostrar erros como JSON em vez de HTML
function exception_handler($e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
set_exception_handler('exception_handler');

// Define cabeçalhos antes de qualquer saída
header('Content-Type: application/json; charset=utf-8');

// Previne warning de timezone
date_default_timezone_set('America/Sao_Paulo');

require './vendor/autoload.php';
require '../../../conexao.php';

// Durante desenvolvimento, vamos exibir erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log de erros para um arquivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/importacao_error.log');

// Configura timezone
date_default_timezone_set('America/Sao_Paulo');

// Força MySQL a usar o modo estrito de datas
if (isset($conn)) {
    $conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
    // Configura timezone do MySQL para combinar com PHP
    $conn->query("SET time_zone = '-03:00'");
    
    error_log("Conexão MySQL configurada com modo estrito e timezone");
}

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

        // Processamento da data de reabastecimento
        try {
            error_log("Valor original da data: " . print_r($data_reabastecimento, true));
            
            // Se for null ou vazio, usa a data atual
            if (empty($data_reabastecimento)) {
                $data_reabastecimento = date('Y-m-d');
            }
            // Se for apenas um ano
            else if (is_numeric($data_reabastecimento) && strlen($data_reabastecimento) == 4) {
                $data_reabastecimento = $data_reabastecimento . '-01-01';
            }
            // Se for um número serial do Excel
            else if (is_numeric($data_reabastecimento)) {
                $dataObj = Date::excelToDateTimeObject($data_reabastecimento);
                $data_reabastecimento = $dataObj ? $dataObj->format('Y-m-d') : date('Y-m-d');
            }
            // Se for uma string de data
            else {
                $timestamp = strtotime($data_reabastecimento);
                if ($timestamp === false) {
                    throw new Exception("Formato de data inválido");
                }
                $data_reabastecimento = date('Y-m-d', $timestamp);
            }
            
            // Validação final
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_reabastecimento)) {
                throw new Exception("Data não está no formato correto YYYY-MM-DD");
            }
            
            $ano = (int)substr($data_reabastecimento, 0, 4);
            if ($ano < 2000 || $ano > 2100) {
                throw new Exception("Ano fora do intervalo permitido (2000-2100)");
            }
            
            error_log("Data processada: $data_reabastecimento");
            
        } catch (Exception $e) {
            error_log("Erro ao processar data: " . $e->getMessage());
            $data_reabastecimento = date('Y-m-d'); // usa data atual como fallback
        }

        // Prepara e executa insert
        // Inicia transação para garantir consistência
        $conn->begin_transaction();
        
        try {
            // Insere o produto
            error_log("Tentando inserir produto: loja_id=$lojaId, nome=$nome, lote=$lote");
            
            // Validações adicionais
            if (!is_numeric($quantidade_estoque) || $quantidade_estoque < 0) {
                throw new Exception("Quantidade inválida: $quantidade_estoque");
            }
            if (!is_numeric($preco_unitario) || $preco_unitario < 0) {
                throw new Exception("Preço unitário inválido: $preco_unitario");
            }
            if (!is_numeric($custo_unitario) || $custo_unitario < 0) {
                throw new Exception("Custo unitário inválido: $custo_unitario");
            }
            
            $stmt = $conn->prepare("INSERT INTO produtos 
                (loja_id, nome, descricao, lote, quantidade_estoque, preco_unitario, custo_unitario, data_reabastecimento) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                error_log("Erro na preparação da query: " . $conn->error);
                throw new Exception("Erro na preparação da query - " . $conn->error);
            }

            // Tipos: i = int, s = string, d = double
            if (!$stmt->bind_param("isssidds", $lojaId, $nome, $descricao, $lote, $quantidade_estoque, $preco_unitario, $custo_unitario, $data_reabastecimento)) {
                error_log("Erro no bind_param: " . $stmt->error);
                throw new Exception("Erro no bind_param - " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                error_log("Erro ao inserir produto: " . $stmt->error);
                throw new Exception("Erro ao inserir produto - " . $stmt->error);
            }
            
            error_log("Produto inserido com sucesso: ID=" . $stmt->insert_id);
            $produto_id = $stmt->insert_id;
            $stmt->close();

            // Se tem quantidade em estoque, registra como entrada
            if ($quantidade_estoque > 0) {
                // Primeiro valida e formata a data de movimentação
                $data_mov = null;
                try {
                    // Se recebemos apenas um ano
                    if (is_numeric($data_reabastecimento) && strlen($data_reabastecimento) == 4) {
                        $data_mov = $data_reabastecimento . '-01-01'; // Define como primeiro dia do ano
                    }
                    // Se temos uma data completa
                    else if ($data_reabastecimento) {
                        $data_mov = $data_reabastecimento;
                    } 
                    // Se não temos data
                    else {
                        $data_mov = date('Y-m-d');
                    }
                    
                    // Se a data não está no formato correto, tenta converter
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_mov)) {
                        // Tenta converter vários formatos de data
                        $timestamp = strtotime($data_mov);
                        if ($timestamp === false) {
                            throw new Exception("Formato de data inválido: $data_mov");
                        }
                        $data_mov = date('Y-m-d', $timestamp);
                    }
                    
                    // Validação final da data
                    $ano = (int)substr($data_mov, 0, 4);
                    if ($ano < 2000 || $ano > 2100) {
                        throw new Exception("Ano fora do intervalo permitido (2000-2100): $ano");
                    }
                    
                    error_log("Data processada com sucesso: $data_mov");
                } catch (Exception $e) {
                    error_log("Erro na data de movimentação: " . $e->getMessage());
                    $data_mov = date('Y-m-d');
                }

                // Log antes da inserção
                error_log("Preparando inserção de movimentação:");
                error_log("- produto_id: $produto_id");
                error_log("- quantidade: $quantidade_estoque");
                error_log("- data_mov: $data_mov");
                error_log("- usuario_id: " . ($_SESSION['usuario_id'] ?? 'null'));
                error_log("- custo: $custo_unitario");

                // Garante que a data está no formato correto
                $data_mov_formatada = date('Y-m-d', strtotime($data_mov));
                
                $stmtMov = $conn->prepare("
                    INSERT INTO movimentacoes_estoque 
                    (produto_id, tipo, quantidade, data_movimentacao, usuario_id, criado_em, custo_unitario) 
                    VALUES (?, 'entrada', ?, STR_TO_DATE(?, '%Y-%m-%d'), ?, NOW(), ?)
                ");
                
                if ($stmtMov === false) {
                    throw new Exception("Erro na preparação da query de movimentação - " . $conn->error);
                }

                $usuario_id = $_SESSION['usuario_id'] ?? null;
                
                error_log("Query preparada, tentando executar com data formatada: $data_mov_formatada");
                
                $stmtMov->bind_param("iisid", 
                    $produto_id,
                    $quantidade_estoque,
                    $data_mov_formatada,
                    $usuario_id,
                    $custo_unitario
                );
                if (!$stmtMov->execute()) {
                    throw new Exception("Erro ao registrar movimentação - " . $stmtMov->error);
                }
                $stmtMov->close();
            }

            $conn->commit();
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
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Linha {$displayLine}: " . $e->getMessage();
        }
    }

    // Log final dos resultados
    error_log("Importação finalizada: $imported produtos importados, " . count($errors) . " erros");
    
    // Garante que não há saída anterior
    if (ob_get_length()) ob_clean();
    
    $response = [
        'success' => $imported > 0,
        'imported' => $imported,
        'data' => $importedData,
        'errors' => $errors,
        'debug' => [
            'loja_id' => $lojaId,
            'session_data' => [
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'tipo_login' => $_SESSION['tipo_login'] ?? null,
                'loja_id' => $_SESSION['loja_id'] ?? null
            ]
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()]);
    exit;
}
