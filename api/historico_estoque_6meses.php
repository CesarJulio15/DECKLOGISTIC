<?php
session_start();
require_once '../conexao.php';

// Supondo que você guardou o id do usuário na sessão
$usuarioId = $_SESSION['usuario_id'] ?? 0;

if (!$usuarioId) {
    echo "Usuário não logado";
    exit;
}

// Pega a loja_id do usuário logado
$res = mysqli_query($conn, "SELECT loja_id FROM usuarios WHERE id = $usuarioId LIMIT 1");
$row = mysqli_fetch_assoc($res);

$lojaIdAtual = $row['loja_id'] ?? null;

echo "Loja ID atual: " . $lojaIdAtual;
