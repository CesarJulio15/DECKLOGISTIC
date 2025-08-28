<?php
session_start();
include __DIR__ . '/../../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/login.php");
    exit;
}

$tipo_login = $_SESSION['tipo_login'] ?? 'funcionario'; // default funcionário

// Pega dados reais da empresa se for funcionário
$empresa = null;
if ($tipo_login === 'funcionario') {
    $stmt = $conn->prepare("SELECT nome, email FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['loja_id']);
    $stmt->execute();
    $empresa = $stmt->get_result()->fetch_assoc();
}

// Função para mostrar valor correto
function valor($tipo_login, $campo_empresa, $campo_sessao) {
    return htmlspecialchars($tipo_login === 'empresa' ? ($campo_sessao ?? '') : ($campo_empresa ?? ''));
}

// Processa alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['alterar_nome'])) {
        $novo_nome = trim($_POST['nome']);
        if ($tipo_login === 'empresa') {
            $stmt = $conn->prepare("UPDATE lojas SET nome=? WHERE id=?");
            $stmt->bind_param("si", $novo_nome, $_SESSION['usuario_id']);
            $stmt->execute();
            $_SESSION['usuario_nome'] = $novo_nome;
        }
    }

    if (isset($_POST['alterar_email'])) {
        $novo_email = trim($_POST['email']);
        if ($tipo_login === 'empresa') {
            $stmt = $conn->prepare("UPDATE lojas SET email=? WHERE id=?");
            $stmt->bind_param("si", $novo_email, $_SESSION['usuario_id']);
            $stmt->execute();
            $_SESSION['usuario_email'] = $novo_email;
        }
    }

    if (isset($_POST['alterar_senha'])) {
        $senha = $_POST['senha'] ?? '';
        $senha2 = $_POST['senha2'] ?? '';
        if ($senha === $senha2 && !empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            if ($tipo_login === 'empresa') {
                $stmt = $conn->prepare("UPDATE lojas SET senha_hash=? WHERE id=?");
                $stmt->bind_param("si", $senha_hash, $_SESSION['usuario_id']);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET senha_hash=? WHERE id=?");
                $stmt->bind_param("si", $senha_hash, $_SESSION['usuario_id']);
                $stmt->execute();
            }
        }
    }

    header("Location: conta.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="stylesheet" href="../../assets/config.css">
    <link rel="stylesheet" href="../../assets/sidebar.css">
</head>
<body>
<div class="pagina">

    <!-- Sidebar original -->
    <div class="sidebar">
        <div class="logo-area">
            <img src="../../img/logoDecklogistic.webp" alt="Logo DeckLogistic">
        </div>

        <nav class="nav-section">
            <div class="nav-menus">
                <ul class="nav-list top-section">
                    <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                    <li><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
                </ul>
                <hr>
                <ul class="nav-list middle-section">
                    <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                    <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                    <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    <li><a href="../dashboard/tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
                </ul>
            </div>
            <div class="bottom-links">
                <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
                <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
            </div>
        </nav>
    </div>

    <!-- Conteúdo principal -->
    <div class="conteudo">
        <h1>Minha Conta</h1>
        <div class="caixa-conta">
            <div class="perfil">
                <div class="icone-usuario"><img src="../../img/icon-user.svg" alt="Ícone usuário"></div>
                <h2><?= valor($tipo_login, $empresa['nome'] ?? '', $_SESSION['usuario_nome'] ?? '') ?></h2>
            </div>

            <!-- Formulário Nome -->
            <form method="POST">
                <div class="linha-info">
                    <span class="icone"><img src="../../img/icon-name.svg" alt="Ícone Nome"></span>
                    <strong>Nome:</strong>
                    <input type="text" name="nome" value="<?= valor($tipo_login, $empresa['nome'] ?? '', $_SESSION['usuario_nome'] ?? '') ?>">
                    <button type="submit" name="alterar_nome">Salvar</button>
                </div>
            </form>

            <!-- Formulário Email -->
            <form method="POST">
                <div class="linha-info">
                    <span class="icone"><img src="../../img/icon-email.svg" alt="Ícone Email"></span>
                    <strong>Email:</strong>
                    <input type="email" name="email" value="<?= valor($tipo_login, $empresa['email'] ?? '', $_SESSION['usuario_email'] ?? '') ?>">
                    <button type="submit" name="alterar_email">Salvar</button>
                </div>
            </form>

      <a href="/Pages/auth/verificacao2fatores.php" class="btn-alterar-senha">Alterar Senha</a>


            <!-- Botão de cadastrar funcionário (apenas empresa) -->
            <?php if ($tipo_login === 'empresa'): ?>
                <a href="/Pages/auth/funcionarios/cadastroFuncionario.php" class="btn-cadastrar">Cadastrar Funcionário</a>
            <?php endif; ?>

            <!-- Logout -->
            <a href="/Pages/auth/logout.php" class="btn-sair">Sair da conta</a>

            <!-- Excluir conta -->
            <form action="/logout.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir a conta? Essa ação não pode ser desfeita!');">
                <button type="submit" class="btn-excluir">
                    <span>Excluir Conta</span>
                    <img src="../../img/icon-lixo.svg" alt="Excluir Conta">
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
