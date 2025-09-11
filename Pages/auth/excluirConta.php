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
        // 1️⃣ Excluir movimentações de estoque relacionadas aos produtos da loja
        $stmtMov = $conn->prepare("
            DELETE me 
            FROM movimentacoes_estoque me
            INNER JOIN produtos p ON me.produto_id = p.id
            WHERE p.loja_id = ?
        ");
        $stmtMov->bind_param("i", $userId);
        $stmtMov->execute();
        $stmtMov->close();

        // 2️⃣ Excluir produtos da loja
        $stmtProd = $conn->prepare("DELETE FROM produtos WHERE loja_id = ?");
        $stmtProd->bind_param("i", $userId);
        $stmtProd->execute();
        $stmtProd->close();

        // 3️⃣ Excluir transações financeiras da loja
        $stmtFin = $conn->prepare("DELETE FROM transacoes_financeiras WHERE loja_id = ?");
        $stmtFin->bind_param("i", $userId);
        $stmtFin->execute();
        $stmtFin->close();

        // 4️⃣ Excluir usuários vinculados à loja
        $stmtUsu = $conn->prepare("DELETE FROM usuarios WHERE loja_id = ?");
        $stmtUsu->bind_param("i", $userId);
        $stmtUsu->execute();
        $stmtUsu->close();

        // 5️⃣ Excluir a loja
        $stmt = $conn->prepare("DELETE FROM lojas WHERE id=?");

    } else {
        // Excluir usuário
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    }

    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->close();

        // Destrói a sessão
        $_SESSION = [];
        session_destroy();

        // Redireciona
        header("Location: /DECKLOGISTIC/Pages/auth/lojas/cadastro.php");
        exit;
    } else {
        echo "Erro ao excluir conta: " . $conn->error;
    }

} else {
    header("Location: /Pages/conta.php");
    exit;
}
?>
