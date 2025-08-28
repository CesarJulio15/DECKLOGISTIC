<?php
session_start();
include __DIR__ . '/../../../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($senha, $usuario['senha_hash'])) {
            // Salva dados na sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['loja_id'] = $usuario['loja_id'];
            $_SESSION['tipo_login'] = 'funcionario';

            header("Location: ../../dashboard/financas.php");
            exit;
        }
    }

    $_SESSION['erro_login'] = "E-mail ou senha inválidos.";
    header("Location: login_funcionario.php");
    exit;
}
?>
