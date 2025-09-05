<?php
session_start();

include __DIR__ . '/../../conexao.php';


// Paginação
$linhasPorPagina = 13;
$paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Query completa de operações
$sql = "
    SELECT 
        'Produto Adicionado' AS tipo,
        p.nome AS item,
        '' AS icone,
        '' AS cor,
        CONCAT('Qtd Inicial: ', COALESCE(p.quantidade_inicial,0)) AS detalhe,
        p.criado_em AS data,
        COALESCE(u.nome, 'Usuário Desconhecido') AS usuario
    FROM produtos p
    LEFT JOIN usuarios u ON u.id = p.usuario_id

    UNION ALL

     SELECT 
    'Produto Excluído' AS tipo,
    h.nome AS item,
    '' AS icone,
    '' AS cor,
    CONCAT('Qtd: ', h.quantidade, ' | Lote: ', h.lote) AS detalhe,
    h.criado_em AS data,
    COALESCE(u.nome, 'Usuário Desconhecido') AS usuario
FROM historico_produtos h
LEFT JOIN usuarios u ON u.id = h.usuario_id
WHERE h.acao = 'excluido'


    UNION ALL

    SELECT 
        'Tag Criada' AS tipo, 
        t.nome AS item,
        t.icone AS icone,
        t.cor AS cor,
        CONCAT('Cor: ', t.cor, ' | Ícone: ', t.icone) AS detalhe, 
        t.criado_em AS data, 
        COALESCE(u.nome, 'Usuário Desconhecido') AS usuario
    FROM tags t
    LEFT JOIN usuarios u ON u.id = t.usuario_id

    UNION ALL

    SELECT 
        'Tag Alterada' AS tipo,
        CONCAT(COALESCE(t.nome_antigo,''),' → ',COALESCE(t.nome,'')) AS item,
        t.icone AS icone,
        t.cor AS cor,
        CONCAT('Cor: ', t.cor, ' | Ícone: ', t.icone) AS detalhe,
        t.atualizado_em AS data,
        COALESCE(u.nome, 'Usuário Desconhecido') AS usuario
    FROM tags t
    LEFT JOIN usuarios u ON u.id = t.usuario_atualizacao_id
    WHERE t.atualizado_em IS NOT NULL

    UNION ALL

    SELECT 
        'Tag Excluída' AS tipo,
        t.nome AS item,
        t.icone AS icone,
        t.cor AS cor,
        CONCAT('Tag removida em ', DATE_FORMAT(t.deletado_em, '%d/%m/%Y %H:%i')) AS detalhe,
        t.deletado_em AS data,
        COALESCE(u.nome, 'Usuário Desconhecido') AS usuario
    FROM tags t
    LEFT JOIN usuarios u ON u.id = t.usuario_exclusao_id
    WHERE t.deletado_em IS NOT NULL

    ORDER BY data DESC
";

// Paginação
$resultTotal = $conn->query($sql);
$totalOperacoes = $resultTotal ? $resultTotal->num_rows : 0;
$totalPaginas = ceil($totalOperacoes / $linhasPorPagina);
$inicio = ($paginaAtual - 1) * $linhasPorPagina;

$sqlComLimit = $sql . " LIMIT $inicio, $linhasPorPagina";
$result = $conn->query($sqlComLimit);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Operações Recentes</title>
<link rel="stylesheet" href="../../assets/sidebar.css">
<link rel="stylesheet" href="../../assets/operacoes.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.paginacao a { display:inline-block; width:30px; height:30px; text-align:center; line-height:30px; border:1px solid #ccc; border-radius:4px; text-decoration:none; color:#000; }
.paginacao a.active { background:#333; color:#fff; border-color:#333; }
.paginacao { margin-top:15px; display:flex; gap:5px; }
</style>
</head>
<body>
 
  <aside class="sidebar"> <div class="logo-area"> <img src="../../img/logoDecklogistic.webp" alt="Logo"> </div> <nav class="nav-section"> <div class="nav-menus"> <ul class="nav-list top-section"> <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li> <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li> </ul> <hr> <ul class="nav-list middle-section"> <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li> <li class="active"><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li> <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li> <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li> </ul> </div> <div class="bottom-links"> <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a> <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a> </div> </nav> </aside>
<main>
<h1>Operações Recentes</h1>
<section>
<table>
<tr>
<th>Tipo</th>
<th>Item</th>
<th>Detalhes</th>
<th>Data</th>
<th>Funcionário</th>
</tr>
<?php if ($result && $result->num_rows > 0): ?>
    <?php while($op = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($op['tipo']) ?></td>
        <td>
            <?php if ($op['icone']): ?>
                <i class="fa-solid <?= htmlspecialchars($op['icone']) ?>" style="color: <?= htmlspecialchars($op['cor']) ?>; margin-right:5px;"></i>
            <?php endif; ?>
            <?= htmlspecialchars($op['item']) ?>
        </td>
        <td><?= htmlspecialchars($op['detalhe']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($op['data'])) ?></td>
        <td><?= htmlspecialchars($op['usuario']) ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5">Nenhuma operação registrada</td></tr>
<?php endif; ?>
</table>

<?php if ($totalPaginas > 1): ?>
<div class="paginacao">
    <?php for ($i=1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>" class="<?= ($i == $paginaAtual) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
</section>
</main>
</body>
</html>
