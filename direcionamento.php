<?php
require_once 'config.php';
session_start();

// Se o usuário estiver logado → redireciona para dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: pages/dashboard/financas.php');
    exit;
}

// Se não estiver logado → redireciona direto para cadastro
header('Location: pages/auth/lojas/cadastro.php');
exit;