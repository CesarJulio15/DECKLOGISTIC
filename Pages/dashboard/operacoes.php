<?php
session_start();
include __DIR__ . '/../../conexao.php';

$lojaId = $_SESSION['loja_id'] ?? 0;
$usuarioId = $_SESSION['usuario_id'] ?? 0;
$tipo_login = $_SESSION['tipo_login'] ?? 'funcionario';

$msg = '';

// Pega contagem de cada tipo
$contTipos = [];
if ($lojaId) {
    $tipos = [
        'Produto Adicionado'=>'adicionado',
        'Produto Excluído'=>'excluido',
        'Tag Criada'=>'criado',
        'Tag Alterada'=>'alterado',
        'Tag Excluída'=>'excluido_tag'
    ];
    foreach ($tipos as $label => $acao) {
        switch ($acao) {
            case 'adicionado':
            case 'excluido':
                $res = $conn->query("SELECT COUNT(*) c FROM historico_produtos h INNER JOIN usuarios u ON h.usuario_id=u.id WHERE u.loja_id=$lojaId AND h.acao='$acao'");
                break;
            case 'criado':
                $res = $conn->query("SELECT COUNT(*) c FROM tags t WHERE t.loja_id=$lojaId AND t.deletado_em IS NULL AND t.criado_em IS NOT NULL");
                break;
            case 'alterado':
                $res = $conn->query("SELECT COUNT(*) c FROM tags t WHERE t.loja_id=$lojaId AND t.atualizado_em IS NOT NULL");
                break;
            case 'excluido_tag':
                $res = $conn->query("SELECT COUNT(*) c FROM tags t WHERE t.loja_id=$lojaId AND t.deletado_em IS NOT NULL");
                break;
        }
        $contTipos[$label] = $res ? $res->fetch_assoc()['c'] : 0;
    }
}

// Processa exclusão do histórico
if ($tipo_login === 'empresa' && isset($_POST['apagar_historico'])) {
    $periodo = $_POST['periodo'] ?? 'tudo';
    $tiposSelecionados = $_POST['tipos'] ?? [];

    $condPeriodo = "";
    switch ($periodo) {
        case 'hoje': $condPeriodo="AND DATE(h.criado_em)=CURDATE()"; break;
        case 'semana': $condPeriodo="AND YEARWEEK(h.criado_em,1)=YEARWEEK(CURDATE(),1)"; break;
        case 'mes': $condPeriodo="AND YEAR(h.criado_em)=YEAR(CURDATE()) AND MONTH(h.criado_em)=MONTH(CURDATE())"; break;
        case 'ano': $condPeriodo="AND YEAR(h.criado_em)=YEAR(CURDATE())"; break;
        case 'tudo': $condPeriodo=""; break;
    }

    // Excluir produtos
    if(in_array('Produto Adicionado', $tiposSelecionados)) {
        $conn->query("DELETE h FROM historico_produtos h INNER JOIN usuarios u ON h.usuario_id=u.id WHERE u.loja_id=$lojaId AND h.acao='adicionado' $condPeriodo");
    }
    if(in_array('Produto Excluído', $tiposSelecionados)) {
        $conn->query("DELETE h FROM historico_produtos h INNER JOIN usuarios u ON h.usuario_id=u.id WHERE u.loja_id=$lojaId AND h.acao='excluido' $condPeriodo");
    }
    // Excluir tags
    if(in_array('Tag Criada', $tiposSelecionados)) {
        $conn->query("DELETE FROM tags WHERE loja_id=$lojaId AND criado_em IS NOT NULL AND deletado_em IS NULL $condPeriodo");
    }
    if(in_array('Tag Alterada', $tiposSelecionados)) {
        $conn->query("UPDATE tags SET atualizado_em=NULL WHERE loja_id=$lojaId AND atualizado_em IS NOT NULL $condPeriodo");
    }
    if(in_array('Tag Excluída', $tiposSelecionados)) {
        $conn->query("DELETE FROM tags WHERE loja_id=$lojaId AND deletado_em IS NOT NULL $condPeriodo");
    }

    $msg="✅ Histórico atualizado com sucesso!";
}

// Paginação e SQL histórico
$linhasPorPagina = 13;
$paginaAtual = isset($_GET['pagina']) ? max(1,intval($_GET['pagina'])) : 1;

$whereHistorico = $whereTags = '';
if ($tipo_login === 'empresa' && $lojaId) {
    $whereHistorico = "h.usuario_id IN (SELECT id FROM usuarios WHERE loja_id = $lojaId)";
    $whereTags = "t.loja_id = $lojaId";
} elseif ($tipo_login === 'funcionario' && $usuarioId) {
    $res = $conn->query("SELECT loja_id FROM usuarios WHERE id = $usuarioId LIMIT 1");
    $lojaFuncionario = $res ? ($res->fetch_assoc()['loja_id'] ?? 0) : 0;
    $whereHistorico = "h.usuario_id IN (SELECT id FROM usuarios WHERE loja_id = $lojaFuncionario)";
    $whereTags = "t.loja_id = $lojaFuncionario";
} else {
    $whereHistorico = $whereTags = "1=0";
}
$whereHistoricoFinal = $whereHistorico ? " AND $whereHistorico" : "";

// SQL histórico completo (agora inclui todas operações relevantes)
$sql = "
SELECT h.acao AS tipo, h.nome AS item, 
       CASE WHEN h.acao='excluido' THEN 'fa-trash' ELSE '' END AS icone,
       CASE WHEN h.acao='excluido' THEN '#fcfcfcff' ELSE '' END AS cor,
       CONCAT('Qtd: ', h.quantidade, IFNULL(CONCAT(' | Lote: ', h.lote),'')) AS detalhe, h.criado_em AS data,
       u.nome AS usuario
FROM historico_produtos h
LEFT JOIN usuarios u ON u.id = h.usuario_id
WHERE (h.usuario_id IN (SELECT id FROM usuarios WHERE loja_id = $lojaId) OR h.usuario_id IS NULL OR h.usuario_id = 0)

UNION ALL

SELECT 
    CASE WHEN m.tipo='entrada' THEN 'Compra (Entrada)' ELSE 'Venda (Saída)' END AS tipo,
    p.nome AS item,
    '' AS icone,
    '' AS cor,
    CONCAT('Qtd: ', m.quantidade) AS detalhe,
    m.data_movimentacao AS data,
    u.nome AS usuario
FROM movimentacoes_estoque m
INNER JOIN produtos p ON m.produto_id = p.id
LEFT JOIN usuarios u ON m.usuario_id = u.id
WHERE p.loja_id = $lojaId

UNION ALL

SELECT 'Tag Criada' AS tipo, t.nome AS item, t.icone AS icone, t.cor AS cor, 
       CONCAT('Cor: ', t.cor, ' | Ícone: ', t.icone) AS detalhe, t.criado_em AS data,
       COALESCE(u.nome, l.nome) AS usuario
FROM tags t
LEFT JOIN usuarios u ON u.id = t.usuario_id
LEFT JOIN lojas l ON l.id = t.loja_id
WHERE t.deletado_em IS NULL AND t.loja_id = $lojaId

UNION ALL

SELECT 'Tag Alterada' AS tipo, CONCAT(COALESCE(t.nome_antigo,''),' → ',COALESCE(t.nome,'')) AS item, t.icone AS icone, t.cor AS cor, 
       CONCAT('Cor: ', t.cor,' | Ícone: ', t.icone) AS detalhe, t.atualizado_em AS data,
       COALESCE(u.nome, l.nome) AS usuario
FROM tags t
LEFT JOIN usuarios u ON u.id = t.usuario_atualizacao_id
LEFT JOIN lojas l ON l.id = t.loja_id
WHERE t.atualizado_em IS NOT NULL AND t.loja_id = $lojaId

UNION ALL

SELECT 'Tag Excluída' AS tipo, t.nome AS item, t.icone AS icone, t.cor AS cor, 
       CONCAT('Tag removida em ', DATE_FORMAT(t.deletado_em, '%d/%m/%Y %H:%i')) AS detalhe, t.deletado_em AS data,
       COALESCE(u.nome, l.nome) AS usuario
FROM tags t
LEFT JOIN usuarios u ON u.id = t.usuario_exclusao_id
LEFT JOIN lojas l ON l.id = t.loja_id
WHERE t.deletado_em IS NOT NULL AND t.loja_id = $lojaId

ORDER BY data DESC
";


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
#btnApagar { background:#f00; color:#fff; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:5px;}
#btnApagar i { color:#fff; }
#modalFundo { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:1000; justify-content:center; align-items:center; }
#modal { background:#fff; padding:20px; border-radius:10px; width:350px; text-align:center; max-height:80%; overflow-y:auto; }
#modal button { margin-top:10px; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; }
#filtroPeriodo { padding:5px; border-radius:4px; width:100%; margin-top:10px;}
.checkboxItem { display:flex; justify-content:space-between; align-items:center; margin-top:10px; }
.checkboxItem label { flex:1; text-align:left; }
.checkboxItem span { font-size:12px; color:#555; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo-area"><img src="../../img/logoDecklogistic.webp" alt="Logo"></div>
  <nav class="nav-section">
    <div class="nav-menus">
      <ul class="nav-list top-section">
        <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
        <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
      </ul>
      <hr>
      <ul class="nav-list middle-section">
        <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
        <li class="active"><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
        <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
        <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
      </ul>
    </div>
    <div class="bottom-links">
      <a href="../../Pages/auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
      <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
    </div>
  </nav>
</aside>

<main>
<h1>Operações Recentes</h1>

<?php if (!empty($msg)): ?>
    <div style="background:#d1ffd6; padding:10px; border-radius:6px; margin-bottom:10px; font-weight:600; color:#064e3b"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($tipo_login === 'empresa'): ?>
<button id="btnApagar"><i class="fa fa-trash"></i> Apagar Histórico</button>
<?php endif; ?>

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

<!-- Modal -->
<div id="modalFundo">
    <div id="modal">
        <h3>Excluir histórico</h3>
        <form method="POST">
            <select name="periodo" id="filtroPeriodo">
                <option value="hoje">Hoje</option>
                <option value="semana">Semana</option>
                <option value="mes">Mês</option>
                <option value="ano">Ano</option>
                <option value="tudo">Tudo</option>
            </select>

            <?php foreach($contTipos as $tipo=>$qtd): ?>
            <div class="checkboxItem">
                <label><input type="checkbox" name="tipos[]" value="<?= htmlspecialchars($tipo) ?>"> <?= htmlspecialchars($tipo) ?></label>
                <span><?= $qtd ?> registros</span>
            </div>
            <?php endforeach; ?>

            <button type="submit" name="apagar_historico">Confirmar</button>
            <button type="button" id="btnFechar">Cancelar</button>
        </form>
    </div>
</div>

<script>
const btnApagar = document.getElementById('btnApagar');
const modalFundo = document.getElementById('modalFundo');
const btnFechar = document.getElementById('btnFechar');

btnApagar?.addEventListener('click', () => modalFundo.style.display='flex');
btnFechar?.addEventListener('click', () => modalFundo.style.display='none');
window.addEventListener('click', e => { if(e.target===modalFundo) modalFundo.style.display='none'; });
</script>
</main>
</body>
</html>
</html>
