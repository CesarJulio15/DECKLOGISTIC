<?php
// Configuração de URL base para o servidor
define('BASE_URL', '/projetos/2025_dev/deckers/');
define('BASE_PATH', __DIR__);

// Função helper para gerar URLs
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// Função helper para gerar caminhos de API
function api_url($endpoint = '') {
    return BASE_URL . 'api/' . ltrim($endpoint, '/');
}

// Função helper para assets (css, js, images)
function asset($path = '') {
    return BASE_URL . ltrim($path, '/');
}
?>
<?php

$host = "localhost"; 
$user = "devgom44_deckers";
$pass = "deckers@1234!";
$db   = "devgom44_deckers";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
