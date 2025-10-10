<?php
session_start();
include __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/login.php");
    exit;
}

$tipo_login = $_SESSION['tipo_login'] ?? 'funcionario';
$userId = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tipo_login === 'empresa') {

        // 0️⃣ Excluir itens de venda relacionados aos produtos da loja
        $stmtItens = $conn->prepare("
            DELETE iv 
            FROM itens_venda iv
            INNER JOIN vendas v ON iv.venda_id = v.id
            WHERE v.loja_id = ?
        ");
        $stmtItens->bind_param("i", $userId);
        $stmtItens->execute();
        $stmtItens->close();

        // 1️⃣ Excluir vendas da loja
        $stmtVendas = $conn->prepare("DELETE FROM vendas WHERE loja_id = ?");
        $stmtVendas->bind_param("i", $userId);
        $stmtVendas->execute();
        $stmtVendas->close();

        // 2️⃣ Excluir movimentações de estoque relacionadas aos produtos da loja
        $stmtMov = $conn->prepare("
            DELETE me 
            FROM movimentacoes_estoque me
            INNER JOIN produtos p ON me.produto_id = p.id
            WHERE p.loja_id = ?
        ");
        $stmtMov->bind_param("i", $userId);
        $stmtMov->execute();
        $stmtMov->close();

        // 3️⃣ Excluir tags dos produtos (produto_tag)
        $stmtTagProd = $conn->prepare("
            DELETE pt 
            FROM produto_tag pt
            INNER JOIN produtos p ON pt.produto_id = p.id
            WHERE p.loja_id = ?
        ");
        $stmtTagProd->bind_param("i", $userId);
        $stmtTagProd->execute();
        $stmtTagProd->close();

        // 4️⃣ Excluir histórico de produtos
        $stmtHist = $conn->prepare("
            DELETE hp 
            FROM historico_produtos hp
            INNER JOIN produtos p ON hp.produto_id = p.id
            WHERE p.loja_id = ?
        ");
        $stmtHist->bind_param("i", $userId);
        $stmtHist->execute();
        $stmtHist->close();

        // 5️⃣ Excluir produtos da loja
        $stmtProd = $conn->prepare("DELETE FROM produtos WHERE loja_id = ?");
        $stmtProd->bind_param("i", $userId);
        $stmtProd->execute();
        $stmtProd->close();

        // 6️⃣ Excluir tags da loja
        $stmtTags = $conn->prepare("DELETE FROM tags WHERE loja_id = ?");
        $stmtTags->bind_param("i", $userId);
        $stmtTags->execute();
        $stmtTags->close();

        // 7️⃣ Excluir transações financeiras da loja
        $stmtFin = $conn->prepare("DELETE FROM transacoes_financeiras WHERE loja_id = ?");
        $stmtFin->bind_param("i", $userId);
        $stmtFin->execute();
        $stmtFin->close();

        // 8️⃣ Excluir usuários vinculados à loja
        $stmtUsu = $conn->prepare("DELETE FROM usuarios WHERE loja_id = ?");
        $stmtUsu->bind_param("i", $userId);
        $stmtUsu->execute();
        $stmtUsu->close();

        // 9️⃣ Excluir a loja
        $stmt = $conn->prepare("DELETE FROM lojas WHERE id=?");

    } else {
        // Excluir usuário comum
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    }

    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->close();

        $_SESSION = [];
        session_destroy();

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
