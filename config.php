<?php
$host = 'localhost';
$db   = 'decklog_db';
$user = 'root';
$pass = 'Home@spSENAI2025!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lança exceções em erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // retorna arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // usa prepared statements reais
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
