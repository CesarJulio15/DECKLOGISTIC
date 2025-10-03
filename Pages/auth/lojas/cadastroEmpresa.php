<?php
require_once '../../../conexao.php'; // aqui o $conn deve ser mysqli_connect(...)
include __DIR__ . '/../../../header.php';


$nome  = mysqli_real_escape_string($conn, $_POST['nome']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$senha = $_POST['senha'];
$senha2 = $_POST['senha2'];

if ($senha !== $senha2) {
    die("As senhas não coincidem.");
}

// Verifica se email já existe
$sqlCheck = "SELECT 1 FROM lojas WHERE email = '$email' LIMIT 1";
$result = mysqli_query($conn, $sqlCheck);
if (mysqli_num_rows($result) > 0) {
    die("E-mail já cadastrado. Use outro.");
}

$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

// Insere loja
$sqlInsert = "INSERT INTO lojas (nome, email, senha_hash) VALUES ('$nome', '$email', '$senhaHash')";
if (mysqli_query($conn, $sqlInsert)) {
    $loja_id = mysqli_insert_id($conn);
    header("Location: cadastroEmpresaCompleto.php?id=" . $loja_id);
    exit;
} else {
    die("Erro ao criar loja: " . mysqli_error($conn));
}
?>
