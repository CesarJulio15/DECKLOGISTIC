<?php
require_once '../../conexao.php';

function limpar($conn, $valor) {
    return mysqli_real_escape_string($conn, trim($valor));
}

// Pega o ID da loja que foi criada na etapa 1 (deve vir na URL ou no POST)
$loja_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($loja_id <= 0) {
    die("ID da loja inválido.");
}

// Limpa e recebe os dados do formulário
$razao_social = limpar($conn, $_POST['razao']);
$nome = limpar($conn, $_POST['fantasia']);
$cep = limpar($conn, $_POST['cep']);
$endereco = limpar($conn, $_POST['endereco']);
$numero = (int) $_POST['numero'];
$complemento = limpar($conn, $_POST['complemento']);
$bairro = limpar($conn, $_POST['bairro']);
$uf = limpar($conn, $_POST['uf']);
$municipio = limpar($conn, $_POST['municipio']);
$pais = limpar($conn, $_POST['pais']);
$telefone = limpar($conn, $_POST['fone']);
$regime_federal = limpar($conn, $_POST['regime_federal']);
$cnpj = limpar($conn, $_POST['cnpj']);
$cnae = limpar($conn, $_POST['cnae_f']);
$regime_estadual = limpar($conn, $_POST['regime_estadual']);
$nir = limpar($conn, $_POST['nir']);
$centralizacao_escrituracao = limpar($conn, $_POST['escrituracao_centralizada']);
$inscricao_estadual = limpar($conn, $_POST['inscricao_estadual']);
$data_nirc = limpar($conn, $_POST['data_nir']);
$area_construida_m2 = (int) $_POST['area_construida'];
$cod_estabelecimento = limpar($conn, $_POST['cod_estabelecimento']);

// Query UPDATE — só atualiza os campos extras
$sql = "UPDATE lojas SET 
    razao_social = '$razao_social',
    nome = '$nome',
    cep = '$cep',
    endereco = '$endereco',
    numero = $numero,
    complemento = '$complemento',
    bairro = '$bairro',
    uf = '$uf',
    municipio = '$municipio',
    pais = '$pais',
    telefone = '$telefone',
    regime_federal = '$regime_federal',
    cnpj = '$cnpj',
    cnae = '$cnae',
    regime_estadual = '$regime_estadual',
    nir = '$nir',
    centralizacao_escrituracao = '$centralizacao_escrituracao',
    inscricao_estadual = '$inscricao_estadual',
    data_nirc = '$data_nirc',
    area_construida_m2 = $area_construida_m2,
    cod_estabelecimento = '$cod_estabelecimento'
WHERE id = $loja_id";

if (mysqli_query($conn, $sql)) {
    header("Location: ../../index.php");
    exit();
} else {
    echo "Erro: " . mysqli_error($conn);
}

mysqli_close($conn);
