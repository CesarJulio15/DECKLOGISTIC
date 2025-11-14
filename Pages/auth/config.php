<?php
session_start();
include __DIR__ . '/../../conexao.php';
include __DIR__ . '../../../session_check.php';

// ===============================
// Define se é loja ou funcionário
// ===============================
$tipo_login = $_SESSION['tipo_login'] ?? ''; // 'empresa' ou 'funcionario'

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Pages/auth/login.php");
    exit;
}

// ===============================
// Busca dados do usuário logado
// ===============================
if ($tipo_login === 'empresa') {
    // Dados da loja
    $stmt = $conn->prepare("SELECT id, nome, email FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['loja_id']);
    $stmt->execute();
    $dados_usuario = $stmt->get_result()->fetch_assoc();
} else {
    // Dados do funcionário
    $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $dados_usuario = $stmt->get_result()->fetch_assoc();
}

// ===============================
// Variável para mensagens de erro/sucesso
// ===============================
$mensagem = '';
$tipo_mensagem = '';

// ===============================
// Processa alterações (somente loja)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tipo_login === 'empresa') {
    // Alterar Nome
    if (isset($_POST['alterar_nome'])) {
        $novo_nome = trim($_POST['nome']);
        
        // Validação de tamanho
        if (strlen($novo_nome) > 255) {
            $mensagem = '❌ O nome não pode ter mais de 255 caracteres!';
            $tipo_mensagem = 'error';
        } elseif (!empty($novo_nome)) {
            $stmt = $conn->prepare("UPDATE lojas SET nome=? WHERE id=?");
            $stmt->bind_param("si", $novo_nome, $_SESSION['loja_id']);
            $stmt->execute();
            $mensagem = '✅ Nome alterado com sucesso!';
            $tipo_mensagem = 'success';
        }
    }

    // Alterar Email
    if (isset($_POST['alterar_email'])) {
        $novo_email = trim($_POST['email']);
        if (!empty($novo_email)) {
            // Verifica se o email já existe em lojas (exceto o próprio usuário)
            $stmtCheckLojas = $conn->prepare("SELECT id FROM lojas WHERE email = ? AND id != ?");
            $stmtCheckLojas->bind_param("si", $novo_email, $_SESSION['loja_id']);
            $stmtCheckLojas->execute();
            $resultLojas = $stmtCheckLojas->get_result();
            
            // Verifica se o email já existe em usuarios (funcionários)
            $stmtCheckUsuarios = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmtCheckUsuarios->bind_param("s", $novo_email);
            $stmtCheckUsuarios->execute();
            $resultUsuarios = $stmtCheckUsuarios->get_result();
            
            if ($resultLojas->num_rows > 0 || $resultUsuarios->num_rows > 0) {
                // Email já existe
                $mensagem = '❌ Este email já está em uso!';
                $tipo_mensagem = 'error';
            } else {
                // Email disponível - pode atualizar
                $stmt = $conn->prepare("UPDATE lojas SET email=? WHERE id=?");
                $stmt->bind_param("si", $novo_email, $_SESSION['loja_id']);
                $stmt->execute();
                $mensagem = '✅ Email alterado com sucesso!';
                $tipo_mensagem = 'success';
            }
            
            $stmtCheckLojas->close();
            $stmtCheckUsuarios->close();
        }
    }

    // Alterar Senha
    if (isset($_POST['alterar_senha'])) {
        $senha = $_POST['senha'] ?? '';
        $senha2 = $_POST['senha2'] ?? '';
        if ($senha === $senha2 && !empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE lojas SET senha_hash=? WHERE id=?");
            $stmt->bind_param("si", $senha_hash, $_SESSION['loja_id']);
            $stmt->execute();
            $mensagem = '✅ Senha alterada com sucesso!';
            $tipo_mensagem = 'success';
        }
    }

    // Recarrega dados atualizados
    $stmt = $conn->prepare("SELECT id, nome, email FROM lojas WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['loja_id']);
    $stmt->execute();
    $dados_usuario = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="icon" href=" " type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/config.css">
    <link rel="stylesheet" href="../../assets/sidebar.css"> 
    <style>
        .mensagem {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }
        .mensagem.success {
            background-color: #10b981;
            color: white;
        }
        .mensagem.error {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo-area">
      <img src="../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li><a href="../dashboard/financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../dashboard/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../dashboard/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../dashboard/tabelas/produtosmobile.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Histórico"></span> Histórico</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a class="active" href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

  <div class="content">
    <div class="conteudo">
        <h1>Minha Conta</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?= $tipo_mensagem ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        
        <div class="caixa-conta">
            <div class="perfil">
                <h2><?= htmlspecialchars($dados_usuario['nome'] ?? '') ?></h2>
            </div>

            <!-- Formulário Nome -->
            <form method="POST">
                <div class="linha-info">
                    <span class="icone">
                        <img src="../../img/icon-name.svg" alt="Ícone Nome" 
                             style="display:inline-block; vertical-align:middle; filter: invert(1); width:20px; height:20px;">
                    </span>
                    <strong>Nome:</strong>
                    <input type="text" name="nome" value="<?= htmlspecialchars($dados_usuario['nome'] ?? '') ?>" 
                           maxlength="255" required
                    <?= $tipo_login !== 'empresa' ? 'readonly' : '' ?>>
                    <?php if ($tipo_login === 'empresa'): ?>
                        <button type="submit" name="alterar_nome">Salvar</button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Formulário Email -->
            <form method="POST">
                <div class="linha-info">
                    <span class="icone"><img src="../../img/icon-email.svg" alt="Ícone Email"
                  style="display:inline-block; vertical-align:middle; filter: invert(1); width:20px; height:20px;">
                </span>
                    <strong>Email:</strong>
                    <input type="email" name="email" value="<?= htmlspecialchars($dados_usuario['email'] ?? '') ?>" required
                    <?= $tipo_login !== 'empresa' ? 'readonly' : '' ?>>
                    <?php if ($tipo_login === 'empresa'): ?>
                        <button type="submit" name="alterar_email">Salvar</button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($tipo_login === 'empresa'): ?>
                <a href="funcionarios/cadastrolojafuncionario.php" class="btn-cadastrar">Cadastrar Funcionário</a>
    <a href="listafuncionarios.php" class="btn-cadastrar" style="margin-top:10px;">Ver Funcionários</a>
    <a href="../../Pages/auth/2FA.php" class="btn-alterar-senha">Alterar Senha</a>

                <!-- Excluir conta -->
            <?php endif; ?>

            <!-- Logout -->
            <a href="../auth/logout.php" class="btn-sair">Sair da conta</a>

                   <form action="../auth/excluirConta.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir a conta? Essa ação não pode ser desfeita!');">
                    <button type="submit" class="btn-excluir">
                        <span>Excluir Conta</span>
                        <img src="../../img/icon-lixo.svg" alt="Excluir Conta" style="width:12px; position:relative; top:3px; margin-left:3px;">
                    </button>
                </form>

        </div>
      </div>
    </div>
</div>

<script>
    // Auto-remove mensagem após 5 segundos
    setTimeout(() => {
        const mensagem = document.querySelector('.mensagem');
        if (mensagem) {
            mensagem.style.transition = 'opacity 0.5s';
            mensagem.style.opacity = '0';
            setTimeout(() => mensagem.remove(), 500);
        }
    }, 5000);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const links = document.querySelectorAll('a[href*="produtos.php"], a[href*="produtosmobile.php"]');
  const isMobile = window.innerWidth <= 768; // ajuste o breakpoint se quiser

  links.forEach(link => {
    // se for mobile, muda o href pro mobile
    if (isMobile) {
      link.href = "../dashboard/tabelas/produtosmobile.php";
    } else {
      link.href = "../dashboard/tabelas/produtos.php";
    }
  });
});
</script>

</body>
</html>
