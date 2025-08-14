<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="stylesheet" href="../../assets/config.css">
</head>
<body>
<div class="pagina">
    
    <!-- Sidebar -->
    <div class="sidebar">
        <?php include '../../partials/sidebar.php'; ?>
    </div>

    <!-- Conteúdo -->
    <div class="conteudo">
        <h1>Minha Conta</h1>

        <div class="caixa-conta">
            <div class="perfil">
                <div class="icone-usuario"><img src="../../img/icon-user.svg" alt=""></div>
                <h2>Empresa x</h2>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-name.svg" alt=""></span>
                <strong>Nome:</strong>
                <span>Empresa X</span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-email.svg" alt=""></span>
                <strong>Email:</strong>
                <span>empresaxloja@gmail.com</span>
                <a href="#"><img src="../../img/Icon-Edit.svg" alt="Editar"></a>
            </div>

            <div class="linha-info">
                <span class="icone"><img src="../../img/icon-senha.svg" alt=""></span>
                <strong>Senha:</strong>
                <span>********</span>
                <a href="#">Alterar Senha</a>
            </div>

            <button class="btn-sair">Sair da conta</button>

            <button class="btn-excluir">
               <a> Excluir Conta </a><img src="../../img/icon-lixo.svg" alt="">
            </button>
        </div>
    </div>
</div>
</body>
</html>