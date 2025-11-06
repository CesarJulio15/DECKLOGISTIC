<?php
session_start();
include '../../../conexao.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['cadastro'])) {

    // Dados da sessão
    $nome_empresa = $_SESSION['cadastro']['nome'];
    $email = $_SESSION['cadastro']['email'];
    $senha = $_SESSION['cadastro']['senha'];

    
  // Já vem criptografada da primeira etapa
$senha_hash = $_SESSION['cadastro']['senha'];


    // Dados do formulário completo
    $razao_social = trim($_POST['razao'] ?? '');
    $fantasia = trim($_POST['fantasia'] ?? '');
    $cep = $_POST['cep'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $uf = $_POST['uf'] ?? '';
    $municipio = $_POST['municipio'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $telefone = $_POST['fone'] ?? '';
    $regime_federal = $_POST['regime_federal'] ?? '';
    $cnae = $_POST['cnae_f'] ?? '';
    $regime_estadual = $_POST['regime_estadual'] ?? '';
    $escrituracao_centralizada = $_POST['escrituracao_centralizada'] ?? '';
    $data_nirc = $_POST['data_nir'] ?? null;
    $area_construida_m2 = $_POST['area_construida'] ?? null;
    $cod_estabelecimento = $_POST['cod_estabelecimento'] ?? '';

    // Campos sigilosos decodificados
    $cnpj = !empty($_POST['cnpj']) ? base64_decode($_POST['cnpj']) : null;
    $nir  = !empty($_POST['nir']) ? base64_decode($_POST['nir']) : null;
    $inscricao_estadual = !empty($_POST['inscricao_estadual']) ? base64_decode($_POST['inscricao_estadual']) : null;

    if ($razao_social && $fantasia && $nome_empresa && $email) {
        // Verificações de duplicidade
        $duplicados = [];
        // CNPJ
        if (!empty($cnpj)) {
            $sql = "SELECT id FROM lojas WHERE REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '') = ?";
            $check = $conn->prepare($sql);
            $check->bind_param("s", $cnpj);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows > 0) {
                $duplicados[] = 'CNPJ';
            }
        }
        // CNAE
        if (!empty($cnae)) {
            $sql = "SELECT id FROM lojas WHERE cnae = ?";
            $check = $conn->prepare($sql);
            $check->bind_param("s", $cnae);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows > 0) {
                $duplicados[] = 'CNAE-Fiscal (Principal)';
            }
        }
        // NIRC
        if (!empty($nir)) {
            $sql = "SELECT id FROM lojas WHERE nir = ?";
            $check = $conn->prepare($sql);
            $check->bind_param("s", $nir);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows > 0) {
                $duplicados[] = 'NIRC';
            }
        }
        // Inscrição Estadual
        if (!empty($inscricao_estadual)) {
            $sql = "SELECT id FROM lojas WHERE inscricao_estadual = ?";
            $check = $conn->prepare($sql);
            $check->bind_param("s", $inscricao_estadual);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows > 0) {
                $duplicados[] = 'Inscrição Estadual';
            }
        }
        // Código do Estabelecimento
        if (!empty($cod_estabelecimento)) {
            $sql = "SELECT id FROM lojas WHERE cod_estabelecimento = ?";
            $check = $conn->prepare($sql);
            $check->bind_param("s", $cod_estabelecimento);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows > 0) {
                $duplicados[] = 'Código do Estabelecimento';
            }
        }

        if (!empty($duplicados)) {
            $msg = '❌ Os seguintes campos já estão cadastrados em outra loja: ' . implode(', ', $duplicados);
            echo "<script>alert('$msg'); window.location.href='cadastro.php';</script>";
            exit;
        }

        // Inserção no banco
        $sql = "INSERT INTO lojas (
                    nome, email, senha_hash, razao_social, cod_estabelecimento, cep, endereco, numero, complemento,
                    bairro, municipio, uf, pais, telefone, regime_federal, cnae, regime_estadual, nir,
                    centralizacao_escrituracao, inscricao_estadual, data_nirc, area_construida_m2, cnpj
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssssssssssssss",
            $nome_empresa, $email, $senha_hash, $razao_social, $cod_estabelecimento, $cep, $endereco, $numero, $complemento,
            $bairro, $municipio, $uf, $pais, $telefone, $regime_federal, $cnae, $regime_estadual, $nir,
            $escrituracao_centralizada, $inscricao_estadual, $data_nirc, $area_construida_m2, $cnpj
        );

        if($stmt->execute()){
            unset($_SESSION['cadastro']); // Limpa sessão
            echo "<script>alert('Cadastro concluído com sucesso!'); window.location.href='loginLoja.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar: {$stmt->error}'); window.location.href='cadastroEmpresaCompleto.php';</script>";
        }
    } else {
        echo "<script>alert('Preencha todos os campos obrigatórios!'); window.location.href='cadastroEmpresaCompleto.php';</script>";
    }
}
?>
