<?php
session_start();
include __DIR__ . '/../../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/login.php");
    exit;
}

$tipo_login = $_SESSION['tipo_login'] ?? 'funcionario'; // padrão funcionário
$userId = $_SESSION['usuario_id'];

// Processa exclusão da conta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tipo_login === 'empresa') {
        // Exclui a empresa
        $stmt = $conn->prepare("DELETE FROM lojas WHERE id=?");
    } else {
        // Exclui o usuário
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    }

    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Destrói a sessão igual ao logout
        $_SESSION = [];
        session_destroy();

        // Redireciona para a página de cadastro da loja
        header("Location: /DECKLOGISTIC/Pages/auth/lojas/cadastro.php");
        exit;
    } else {
        echo "Erro ao excluir conta.";
    }
} else {
    // Se não for POST, redireciona para a conta
    header("Location: /Pages/conta.php");
    exit;
}
?>
