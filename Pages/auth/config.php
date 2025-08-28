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
    
    <!-- Sidebar -->
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

    <!-- Conteúdo -->
    <div class="conteudo">
        <h1>Minha Conta</h1>

        <div class="caixa-conta">
            <div class="perfil">
                <div class="icone-usuario"><img src="../../img/icon-user.svg" alt="Ícone usuário"></div>
                <!-- Se estiver usando PHP, você pode colocar o nome da loja dinamicamente -->
                <h2><?php echo $_SESSION['nome_loja'] ?? 'Empresa X'; ?></h2>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-name.svg" alt="Ícone Nome"></span>
                <strong>Nome:</strong>
                <span><?php echo $_SESSION['nome_loja'] ?? 'Empresa X'; ?></span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar Nome"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-email.svg" alt="Ícone Email"></span>
                <strong>Email:</strong>
                <span><?php echo $_SESSION['email'] ?? 'empresaxloja@gmail.com'; ?></span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar Email"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-senha.svg" alt="Ícone Senha"></span>
                <strong>Senha:</strong>
                <span>********</span>
                <a href="/Pages/alterarSenha.php">Alterar Senha</a>
            </div>

            <!-- Botão de Logout -->
            <a href="/logout.php" class="btn-sair">Sair da conta</a>

            <!-- Botão de excluir conta -->
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
