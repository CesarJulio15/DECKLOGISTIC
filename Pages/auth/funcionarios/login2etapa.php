<?php
session_start();
include '../../../conexao.php'; // caminho do arquivo de conexão
include __DIR__ . '/../../../header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['empresa'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($senha2)) {
        $_SESSION['erro_cadastro'] = "Preencha todos os campos.";
        header("Location: cadastrofuncionario.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['erro_cadastro'] = "E-mail inválido.";
        header("Location: cadastrofuncionario.php");
        exit;
    }

    if ($senha !== $senha2) {
        $_SESSION['erro_cadastro'] = "As senhas não coincidem.";
        header("Location: cadastrofuncionario.php");
        exit;
    }

    // Verifica se o e-mail já existe
    $email = mysqli_real_escape_string($conn, $email);
    $sql = "SELECT id FROM usuarios WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['erro_cadastro'] = "Este e-mail já está cadastrado.";
        header("Location: cadastrofuncionario.php");
        exit;
    }

    // Cria o hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere no banco
    $nome = mysqli_real_escape_string($conn, $nome);
    $sql = "INSERT INTO usuarios (nome, email, senha_hash) VALUES ('$nome', '$email', '$senha_hash')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['sucesso_cadastro'] = "Funcionário cadastrado com sucesso!";
        header("Location: ../dashboard/dashboard.php");
        exit;
    } else {
        $_SESSION['erro_cadastro'] = "Erro ao cadastrar funcionário: " . mysqli_error($conn);
        header("Location: cadastrofuncionario.php");
        exit;
    }

} else {
    $_SESSION['erro_cadastro'] = "Método inválido.";
    header("Location: cadastrofuncionario.php");
    exit;
}
?>
