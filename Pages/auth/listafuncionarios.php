<?php
session_start();
include __DIR__ . '/../../conexao.php';
include __DIR__ . '../../../session_check.php';

// Apenas loja pode acessar
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_login'] ?? '') !== 'empresa') {
    header("Location: login.php");
    exit;
}

$lojaId = $_SESSION['loja_id'];

// Alterar nome do funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['funcionario_id'], $_POST['novo_nome'])) {
    $id = intval($_POST['funcionario_id']);
    $novoNome = trim($_POST['novo_nome']);
    if ($id && $novoNome) {
        $stmt = $conn->prepare("UPDATE usuarios SET nome=? WHERE id=? AND loja_id=?");
        $stmt->bind_param("sii", $novoNome, $id, $lojaId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: listafuncionarios.php");
    exit;
}

// Excluir funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $id = intval($_POST['excluir_id']);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=? AND loja_id=?");
        $stmt->bind_param("ii", $id, $lojaId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: listafuncionarios.php");
    exit;
}

// Buscar funcionários da loja
$funcionarios = [];
$stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE loja_id=? ORDER BY nome ASC");
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $funcionarios[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Funcionários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <link rel="stylesheet" href="../../assets/listafuncionarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="sidebar">
        <link rel="stylesheet" href="../../assets/sidebar.css">
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
                    <li><a href="../dashboard/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                    <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    <li><a href="../dashboard/tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
                </ul>
            </div>
            <div class="bottom-links">
                <a href="config.php" class="active"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
                <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
            </div>
        </nav>
</div>

<!-- Botão de Conta fixo no mobile -->
<a id="mobileContaBtn" href="config.php" role="button" aria-label="Ir para Conta">
        <i class="fa-solid fa-user-gear" aria-hidden="true"></i> Conta
</a>
<div class="content">
    <h1>Funcionários</h1>
    <div class="funcionarios-card">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($funcionarios as $func): ?>
                <tr>
                    <td data-label="Nome">
                        <form method="POST" class="form-inline" style="margin:0;">
                            <input type="hidden" name="funcionario_id" value="<?= $func['id'] ?>">
                            <input type="text" name="novo_nome" class="input-nome" value="<?= htmlspecialchars($func['nome']) ?>">
                            <button type="submit" class="btn-acao" title="Salvar novo nome">
                                <i class="fa-solid fa-save"></i>
                            </button>
                        </form>
                    </td>
                    <td data-label="Email"><?= htmlspecialchars($func['email']) ?></td>
                    <td data-label="Ações">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este funcionário?');">
                            <input type="hidden" name="excluir_id" value="<?= $func['id'] ?>">
                            <button type="submit" class="btn-acao" style="background:#ff2d2d;" title="Excluir funcionário">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($funcionarios)): ?>
                <tr>
                    <td colspan="3" style="text-align:center; color:#ff9900;">Nenhum funcionário cadastrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
