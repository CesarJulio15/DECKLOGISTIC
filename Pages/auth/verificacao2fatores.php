<?php
session_start();
include __DIR__ . '/../../conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/login.php");
    exit;
}

// Gera código de verificação aleatório
if (!isset($_SESSION['codigo2fa'])) {
    $_SESSION['codigo2fa'] = rand(100000, 999999); // 6 dígitos
    $_SESSION['codigo_expira'] = time() + 300; // 5 minutos de validade

    // Enviar por email
    $destino = $_SESSION['tipo_login'] === 'empresa' ? $_SESSION['usuario_email'] : ''; // ou pegar email do usuário
    $assunto = "Código de verificação 2FA";
    $mensagem = "Seu código para alterar a senha é: " . $_SESSION['codigo2fa'];
    $headers = "From: no-reply@decklogistic.com";

    // mail($destino, $assunto, $mensagem, $headers); // descomente para enviar
}

// Verifica código
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    if ($codigo == ($_SESSION['codigo2fa'] ?? '') && time() <= $_SESSION['codigo_expira']) {
        $_SESSION['autorizado_alterar_senha'] = true;
        header("Location: alterarSenha.php");
        exit;
    } else {
        $erro = "Código incorreto ou expirado!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação 2FA | DeckLogistic</title>
    <link rel="stylesheet" href="../../assets/config.css">
</head>
<body>
<div class="conteudo">
    <h1>Verificação em 2 fatores</h1>
    <p>Um código de 6 dígitos foi enviado para seu email cadastrado.</p>

    <?php if($erro): ?>
        <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="codigo" placeholder="Digite o código" required>
        <button type="submit" class="btn">Verificar</button>
    </form>
</div>
</body>
</html>
