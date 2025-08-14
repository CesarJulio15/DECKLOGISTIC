<?php
$conn = mysqli_connect("localhost", "root", "Home@spSENAI2025!", "decklog_db");
if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}
