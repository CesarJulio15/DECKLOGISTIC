<?php
include __DIR__ . '/../../conexao.php';

// Produtos adicionados recentemente
$produtosAdicionados = $conn->query("
    SELECT id, nome, quantidade_estoque, criado_em 
    FROM produtos 
    ORDER BY criado_em DESC 
    LIMIT 10
");

// Produtos vendidos recentemente
$produtosVendidos = $conn->query("
    SELECT iv.id, p.nome, iv.quantidade, iv.data_venda, v.id AS venda_id
    FROM itens_venda iv
    JOIN produtos p ON iv.produto_id = p.id
    JOIN vendas v ON iv.venda_id = v.id
    ORDER BY iv.data_venda DESC 
    LIMIT 10
");

// Tags criadas
$tagsCriadas = $conn->query("
    SELECT id, nome, cor, icone, criado_em 
    FROM tags 
    ORDER BY criado_em DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Operações Recentes</title>
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <link rel="stylesheet" href="../../assets/operacoes.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-area">
            <img src="../../img/logoDecklogistic.webp" alt="Logo">
        </div>
        <nav class="nav-section">
            <div class="nav-menus">
                <ul class="nav-list top-section">
                    <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                    <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
                </ul>
                <hr>
                <ul class="nav-list middle-section">
                    <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                    <li><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                    <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
                </ul>
            </div>
            <div class="bottom-links">
                <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
                <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
            </div>
        </nav>
    </aside>

    <!-- Conteúdo principal -->
    <main>
        <h1>Operações Recentes</h1>

        <section>
            <h2>Produtos adicionados recentemente</h2>
            <table>
                <tr><th>ID</th><th>Nome</th><th>Estoque</th><th>Data</th></tr>
                <?php while($p = $produtosAdicionados->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= $p['nome'] ?></td>
                        <td><?= $p['quantidade_estoque'] ?></td>
                        <td><?= $p['criado_em'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <section>
            <h2>Produtos vendidos recentemente</h2>
            <table>
                <tr><th>ID</th><th>Produto</th><th>Qtd</th><th>Venda</th><th>Data</th></tr>
                <?php while($v = $produtosVendidos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $v['id'] ?></td>
                        <td><?= $v['nome'] ?></td>
                        <td><?= $v['quantidade'] ?></td>
                        <td>#<?= $v['venda_id'] ?></td>
                        <td><?= $v['data_venda'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <section>
            <h2>Tags criadas</h2>
            <table>
                <tr><th>ID</th><th>Nome</th><th>Cor</th><th>Ícone</th><th>Data</th></tr>
                <?php while($t = $tagsCriadas->fetch_assoc()): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= $t['nome'] ?></td>
                        <td><?= $t['cor'] ?></td>
                        <td><?= $t['icone'] ?></td>
                        <td><?= $t['criado_em'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
    </main>
</body>
</html>
