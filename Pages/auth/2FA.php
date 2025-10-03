<?php
session_start();
include __DIR__ . '/../../conexao.php';
include __DIR__ . '/../../header.php';

// Se não estiver logado, volta para login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/loginLoja.php");
    exit;
}

// Regenera ID da sessão (segurança contra fixation)
session_regenerate_id(true);

// Função para gerar código 2FA
function gerarCodigo($destino) {
    $_SESSION['codigo2fa'] = rand(100000, 999999); // 6 dígitos
    $_SESSION['codigo_expira'] = time() + 300;     // expira em 5 min
    $_SESSION['tentativas_2fa'] = 0;               // reset tentativas
    
    // TESTE: exibe o código na tela
    echo "<p style='color: lime; font-weight:bold;'>Código de teste: {$_SESSION['codigo2fa']}</p>";
}

$destino = $_SESSION['email'] ?? '';
$email_parts = explode("@", $destino);
$email_mascarado = substr($email_parts[0], 0, 1) . str_repeat("*", max(strlen($email_parts[0]) - 2, 1)) . substr($email_parts[0], -1) . "@" . $email_parts[1];

// Se ainda não gerou código, gera
if (!isset($_SESSION['codigo2fa'])) {
    gerarCodigo($destino);
}

$erro = '';
$sucesso = '';
$mostrar_form_codigo = true;

// Processa envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reenviar'])) {
        gerarCodigo($destino);
        $erro = "Um novo código foi enviado para o seu e-mail.";
    } elseif (isset($_POST['codigo'])) {
        $codigo = trim($_POST['codigo']);

        // Verifica tentativas e expiração
        if ($_SESSION['tentativas_2fa'] >= 5) {
            $erro = "Muitas tentativas inválidas. Tente novamente mais tarde.";
        } elseif (time() > $_SESSION['codigo_expira']) {
            $erro = "O código expirou. Clique em 'Reenviar código'.";
            unset($_SESSION['codigo2fa'], $_SESSION['codigo_expira'], $_SESSION['tentativas_2fa']);
        } elseif ($codigo == ($_SESSION['codigo2fa'] ?? '')) {
            $_SESSION['autorizado_alterar_senha'] = true;
            unset($_SESSION['codigo2fa'], $_SESSION['codigo_expira'], $_SESSION['tentativas_2fa']);
            $mostrar_form_codigo = false; // mostra o formulário para nova senha
        } else {
            $_SESSION['tentativas_2fa']++;
            $erro = "Código incorreto! Tentativa {$_SESSION['tentativas_2fa']} de 5.";
        }
    } elseif (isset($_POST['senha'])) {
        // Atualiza senha no banco (tabela lojas)
        $senha = trim($_POST['senha']);
        $senha_confirm = trim($_POST['senha_confirm']);

        if (!$senha || !$senha_confirm) {
            $erro = "Preencha todos os campos.";
        } elseif ($senha !== $senha_confirm) {
            $erro = "As senhas não coincidem.";
        } elseif (strlen($senha) < 6) {
            $erro = "A senha deve ter no mínimo 6 caracteres.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE lojas SET senha_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $senha_hash, $_SESSION['usuario_id']);

            if ($stmt->execute()) {
                $sucesso = "Senha alterada com sucesso!";
                unset($_SESSION['autorizado_alterar_senha']);
                $mostrar_form_codigo = false;
            } else {
                $erro = "Erro ao atualizar a senha. Tente novamente.";
            }
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
<link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
<link rel="stylesheet" href="../../assets/2FA.css">
<script>
    // Contador regressivo
    let expiraEm = <?= ($_SESSION['codigo_expira'] ?? time()) - time() ?>;
    function atualizarContador() {
        if (expiraEm <= 0) {
            document.getElementById("contador").innerText = "Expirado";
            return;
        }
        let min = Math.floor(expiraEm / 60);
        let seg = expiraEm % 60;
        document.getElementById("contador").innerText = `${min}:${seg.toString().padStart(2,"0")}`;
        expiraEm--;
        setTimeout(atualizarContador, 1000);
    }
    window.onload = atualizarContador;
</script>
</head>
<body>
<div class="conteudo">
    <h1>Redefinir Senha - Loja</h1>

    <?php if($erro): ?>
        <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="sucesso-msg"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if($mostrar_form_codigo): ?>
        <p>Um código de 6 dígitos foi enviado para o e-mail: <b><?= htmlspecialchars($email_mascarado) ?></b></p>
        <p>O código expira em: <span id="contador"></span></p>

        <form method="POST">
            <input type="text" name="codigo" placeholder="Digite o código" maxlength="6" required>
            <button type="submit" class="btn">Verificar</button>
        </form>

        <form method="POST" style="margin-top:10px;">
            <button type="submit" name="reenviar" class="btn secundario">Reenviar código</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <input type="password" name="senha" placeholder="Nova senha" required>
            <input type="password" name="senha_confirm" placeholder="Confirme a nova senha" required>
            <button type="submit" class="btn">Alterar Senha</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
