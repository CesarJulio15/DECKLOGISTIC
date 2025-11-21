<?php
require_once __DIR__ . '/config.php';

// Suprime warnings de conexão em produção
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$conn = @mysqli_connect("162.241.62.33", "devgom44_deckers", "deckers@1234!", "devgom44_deckers");

if (!$conn) {
    // Em requisições AJAX, retorna JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
        exit;
    }
    die("Erro na conexão: " . mysqli_connect_error());
}

// Define charset
mysqli_set_charset($conn, 'utf8mb4');
?>

