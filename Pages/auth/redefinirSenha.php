<?php
session_start();
include __DIR__ . '/../../conexao.php';
include __DIR__ . '/../../header.php';

// Se o usuário não estiver autorizado pelo 2FA, volta para login
if (!isset($_SESSION['autorizado_alterar_senha']) || !$_SESSION['autorizado_alterar_senha']) {
    header("Location: /Pages/auth/login.php");
    exit;
}

$erro = '';
$sucesso = '';

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = trim($_POST['senha'] ?? '');
    $senha_confirm = trim($_POST['senha_confirm'] ?? '');

    if (!$senha || !$senha_confirm) {
        $erro = "Preencha todos os campos.";
    } elseif ($senha !== $senha_confirm) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        // Atualiza senha no banco (exemplo para tabela 'usuarios')
        $usuario_id = $_SESSION['usuario_id'];
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $senha_hash, $usuario_id);

        if ($stmt->execute()) {
            $sucesso = "Senha alterada com sucesso!";
            unset($_SESSION['autorizado_alterar_senha']);
        } else {
            $erro = "Erro ao atualizar a senha. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha | DeckLogistic</title>
    <link rel="stylesheet" href="../../assets/redefinirSenha.css">
</head>
<body>
<div class="conteudo">
    <h1>Redefinir Senha</h1>
    <p>Digite sua nova senha abaixo:</p>

    <?php if($erro): ?>
        <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="sucesso-msg"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if(!$sucesso): ?>
    <form method="POST">
        <input type="password" name="senha" placeholder="Nova senha" required>
        <input type="password" name="senha_confirm" placeholder="Confirme a nova senha" required>
        <button type="submit" class="btn">Alterar Senha</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
