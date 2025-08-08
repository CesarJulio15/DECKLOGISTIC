<?php
$conn = mysqli_connect("localhost", "root", "", "decklog_db");
if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}
