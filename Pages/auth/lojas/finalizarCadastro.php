<?php
session_start();
include '../../../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loja_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Campos do formulário
    $razao_social = trim($_POST['razao'] ?? '');
    $nome         = trim($_POST['fantasia'] ?? '');
    $cep          = $_POST['cep'] ?? '';
    $endereco     = $_POST['endereco'] ?? '';
    $numero       = $_POST['numero'] ?? '';
    $complemento  = $_POST['complemento'] ?? '';
    $bairro       = $_POST['bairro'] ?? '';
    $uf           = $_POST['uf'] ?? '';
    $municipio    = $_POST['municipio'] ?? '';
    $pais         = $_POST['pais'] ?? '';
    $telefone     = $_POST['fone'] ?? '';
    $regime_federal = $_POST['regime_federal'] ?? '';
    $cnae         = $_POST['cnae_f'] ?? '';
    $regime_estadual = $_POST['regime_estadual'] ?? '';
    $escrituracao_centralizada = $_POST['escrituracao_centralizada'] ?? '';
    $data_nirc    = $_POST['data_nir'] ?? null;
    $area_construida_m2 = $_POST['area_construida'] ?? null;
    $cod_estabelecimento = $_POST['cod_estabelecimento'] ?? '';

    // Campos sigilosos decodificados
    $cnpj = !empty($_POST['cnpj']) ? base64_decode($_POST['cnpj']) : null;
    $nir  = !empty($_POST['nir']) ? base64_decode($_POST['nir']) : null;
    $inscricao_estadual = !empty($_POST['inscricao_estadual']) ? base64_decode($_POST['inscricao_estadual']) : null;

    if ($loja_id > 0 && $razao_social !== '' && $nome !== '') {
        $sql = "
            UPDATE lojas
            SET razao_social = ?, nome = ?, cep = ?, endereco = ?, numero = ?, complemento = ?,
                bairro = ?, uf = ?, municipio = ?, pais = ?, telefone = ?, regime_federal = ?,
                cnpj = ?, cnae = ?, regime_estadual = ?, nir = ?,
                centralizacao_escrituracao = ?, inscricao_estadual = ?, data_nirc = ?,
                area_construida_m2 = ?, cod_estabelecimento = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sssssssssssssssssssdsi",
    $razao_social, $nome, $cep, $endereco, $numero, $complemento,
    $bairro, $uf, $municipio, $pais, $telefone, $regime_federal,
    $cnpj, $cnae, $regime_estadual, $nir,
    $escrituracao_centralizada, $inscricao_estadual, $data_nirc,
    $area_construida_m2, $cod_estabelecimento,
    $loja_id
);


        if ($stmt->execute()) {
            $_SESSION['msg'] = "✅ Loja atualizada com sucesso!";
        } else {
            $_SESSION['msg'] = "❌ Erro ao atualizar: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['msg'] = "⚠️ Preencha os campos obrigatórios!";
    }

    header("Location: ../../../index.php");
    exit;
}
?>
