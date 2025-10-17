<?php
// Configurações seguras de cookie de sessão — execute ANTES do session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'); // true com HTTPS
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../../../conexao.php';

// Helper para redirecionar com headers anti-cache
function redirect_no_cache($url) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: $url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || $senha === '') {
        $_SESSION['erro_login'] = "Preencha todos os campos corretamente.";
        redirect_no_cache("login.php");
    }

    $sql = "SELECT id, nome, email, senha_hash, loja_id FROM usuarios WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['erro_login'] = "Erro interno. Tente novamente.";
        redirect_no_cache("login.php");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($senha, $usuario['senha_hash'])) {
            // Evita session fixation
            session_regenerate_id(true);

            // Padronização de sessão
            $_SESSION['usuario_id'] = (int)$usuario['id'];
            $_SESSION['nome']       = $usuario['nome'];
            $_SESSION['email']      = $usuario['email'];
            $_SESSION['loja_id']    = (int)$usuario['loja_id'];
            $_SESSION['tipo_login'] = 'funcionario';

            // Anti-cache e redirecionamento
            redirect_no_cache("../../dashboard/estoque.php");
        }
    }

    $_SESSION['erro_login'] = "E-mail ou senha inválidos.";
    redirect_no_cache("login.php");
}
