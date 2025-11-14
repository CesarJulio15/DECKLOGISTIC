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
