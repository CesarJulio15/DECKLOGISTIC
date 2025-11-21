<?php
session_start();
include __DIR__ . '/../../conexao.php';
require_once __DIR__ . '/../../session_check.php';
$lojaId = $_SESSION['loja_id'] ?? 0;
$usuarioId = $_SESSION['usuario_id'] ?? 0;
$tipo_login = $_SESSION['tipo_login'] ?? 'funcionario';

$msg = '';

// Contagem de cada tipo
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

// Exclusão do histórico
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

    if(in_array('Produto Adicionado', $tiposSelecionados)) {
        $conn->query("DELETE h FROM historico_produtos h INNER JOIN usuarios u ON h.usuario_id=u.id WHERE u.loja_id=$lojaId AND h.acao='adicionado' $condPeriodo");
    }
    if(in_array('Produto Excluído', $tiposSelecionados)) {
        $conn->query("DELETE h FROM historico_produtos h INNER JOIN usuarios u ON h.usuario_id=u.id WHERE u.loja_id=$lojaId AND h.acao='excluido' $condPeriodo");
    }
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

// Paginação
$linhasPorPagina = 13;
$paginaAtual = isset($_GET['pagina']) ? max(1,intval($_GET['pagina'])) : 1;

// SQL histórico completo
$sql = "
SELECT 
    CASE 
        WHEN h.acao = 'adicionado' THEN 'Produto Adicionado'
        WHEN h.acao = 'excluido'   THEN 'Produto Excluído'
        ELSE h.acao
    END AS tipo,
    h.nome AS item, 
    CASE 
        WHEN h.acao='excluido'   THEN 'fa-trash'
        WHEN h.acao='adicionado' THEN 'fa-plus'
        ELSE ''
    END AS icone,
    CASE 
        WHEN h.acao='excluido'   THEN '#fcfcfcff'
        WHEN h.acao='adicionado' THEN '#d1ffd6'
        ELSE ''
    END AS cor,
    CONCAT('Qtd: ', h.quantidade, IFNULL(CONCAT(' | Lote: ', h.lote),'')) AS detalhe, 
    h.criado_em AS data,
    COALESCE(u.nome, l.nome, 'Sistema') AS usuario
FROM historico_produtos h
LEFT JOIN produtos p ON p.id = h.produto_id
LEFT JOIN usuarios u ON u.id = h.usuario_id
LEFT JOIN lojas l ON l.id = u.loja_id OR l.id = p.loja_id
WHERE (p.loja_id = $lojaId OR u.loja_id = $lojaId)


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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Operações Recentes</title>
<link rel="stylesheet" href="../../assets/sidebar.css">
<link rel="stylesheet" href="../../assets/operacoes.css">
<link rel="icon" href="../../img/logoDecklogistic.webp" type="image/x-icon" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
html {
    box-sizing: border-box;
}
*, *:before, *:after {
    box-sizing: inherit;
}

body {
    font-size: 1rem;
    background: #1b1b1b;
    color: #e0e0e0;
    margin: 0;
    padding: 0;
}

main {
    margin-left: 220px;
    padding: 35px;
    background: #1b1b1b;
    min-height: 100vh;
    transition: margin-left 0.3s ease, padding 0.3s ease;
}

/* PAGINAÇÃO */
.paginacao {
    margin-top: 15px;
    display: flex;
    gap: 5px;
    justify-content: center;
}
.paginacao a {
    min-width: 2.5em;
    min-height: 2.5em;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #555;
    border-radius: 4px;
    text-decoration: none;
    color: #fff;
    background-color: transparent;
    font-size: 1rem;
}
.paginacao a.active {
    border: 2px solid #ff6600 !important;
    color: #fff !important;
    font-weight: normal;
    background-color: transparent;
}

/* MODAL E BOTÕES */
#btnApagar {
    background: #f00;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1rem;
    min-height: 48px;
}
#btnApagar i { color: #fff; }
#modalFundo {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}
#modal {
    background: #fff;
    color: #222;
    padding: 20px;
    border-radius: 10px;
    width: 350px;
    text-align: center;
    max-height: 80%;
    overflow-y: auto;
}
#modal button {
    margin-top: 10px;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    min-height: 48px;
    font-size: 1rem;
}
#filtroPeriodo {
    padding: 8px;
    border-radius: 4px;
    width: 100%;
    margin-top: 10px;
    font-size: 1rem;
}
.checkboxItem {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}
.checkboxItem label {
    flex: 1;
    text-align: left;
    font-size: 1rem;
}
.checkboxItem span {
    font-size: 0.95rem;
    color: #555;
}

/* TABELA RESPONSIVA */
section table {
    margin-top: 50px;
}
table {
    width: 95%;
    max-width: 1100px;
    margin: 0 auto;
    border-collapse: collapse;
    background-color: #1b1b1b;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 16px rgba(0,0,0,0.6);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
table:hover {
    transform: translateY(-2px);
}
th, td {
    padding: 1.1em 1.5em;
    border-bottom: 1px solid #333;
    text-align: left;
    font-size: 1rem;
    transition: background 0.2s ease;
    color: #e0e0e0;
}
th {
    background-color: #222;
    font-weight: 600;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
tr:nth-child(even) {
    background-color: #1e1e1e;
}
tr:hover td {
    background-color: #333;
}
tr:last-child td {
    border-bottom: none;
}

/* MOBILE: TABELA VIRA CARDS */
@media (max-width: 768px) {
    main {
        margin-left: 0;
        padding: 1.2em 0.5em 4.5em 0.5em;
    }
    .sidebar {
        display: none !important;
    }
    table, thead, tbody, th, td, tr {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }
    thead {
        display: none;
    }
    tr {
        margin-bottom: 1.5em;
        background: #232323;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        padding: 1.2em 1em;
        border: none;
    }
    td {
        border: none;
        padding: 0.7em 0.5em;
        font-size: 1.08em;
        position: relative;
        width: 100%;
        min-width: 0;
        word-break: break-word;
    }
    td:before {
        content: attr(data-label);
        font-weight: 600;
        color: #ff6600;
        display: block;
        margin-bottom: 0.3em;
        font-size: 1em;
        letter-spacing: 0.02em;
    }
    .paginacao {
        margin-bottom: 60px;
    }
}

@media (max-width: 480px) {
    main {
        padding: 0.7em 0.1em 4.5em 0.1em;
    }
    tr {
        padding: 1em 0.3em;
    }
    td {
        font-size: 1em;
        padding: 0.5em 0.2em;
    }
}

/* BOTÃO DE CONTA FIXO NO MOBILE */
#mobileContaBtn {
    display: none;
}
@media (max-width: 768px) {
    #mobileContaBtn {
        display: flex;
        position: fixed;
        left: 0; bottom: 0;
        width: 100vw;
        height: 60px;
        background: #222;
        color: #fff;
        z-index: 9999;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 600;
        border: none;
        border-radius: 0;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.18);
        cursor: pointer;
        gap: 12px;
        transition: background 0.2s;
    }
    #mobileContaBtn:active, #mobileContaBtn:focus {
        background: #333;
        outline: 2px solid #ff6600;
    }
}

/* ACESSIBILIDADE */
button, [role="button"], input[type="checkbox"] {
    min-width: 48px;
    min-height: 48px;
}

/* Ajustes para acessibilidade e foco */
a:focus, button:focus, input:focus {
    outline: 2px solid #ff6600;
    outline-offset: 2px;
}
</style>
</head>
<noscript>
    <meta http-equiv="refresh" content="0; URL=../../no-javascript.php">
</noscript>
<body>


    <div class="sidebar">
        <link rel="stylesheet" href="../../assets/sidebar.css">
        <div class="logo-area">
            <img src="../../img/logo2.svg" alt="Logo">
        </div>
        <nav class="nav-section">
            <div class="nav-menus">
                <ul class="nav-list top-section">
                    <li><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                    <li><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
                </ul>
                <hr>
                <ul class="nav-list middle-section">
                    <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                    <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    <li class="active"><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Histórico</a></li>
                </ul>
            </div>
            <div class="bottom-links">
                <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
                <a href="../../Pages/auth/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
            </div>
        </nav>
    </div>

    <!-- Botão de Conta fixo no mobile -->
    <a id="mobileContaBtn" href="../auth/config.php" role="button" aria-label="Ir para Conta">
        <i class="fa-solid fa-user-gear" aria-hidden="true"></i> Conta
    </a>


<main>
<?php if (!empty($msg)): ?>
    <div style="background:#d1ffd6; padding:10px; border-radius:6px; margin-bottom:10px; font-weight:600; color:#064e3b"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($tipo_login === 'empresa'): ?>
<?php endif; ?>


<section>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Item</th>
            <th>Detalhes</th>
            <th>Data</th>
            <th>Funcionário</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($op = $result->fetch_assoc()): ?>
        <tr>
            <td data-label="Tipo"><?= htmlspecialchars($op['tipo']) ?></td>
            <td data-label="Item">
                <?php if ($op['icone']): ?>
                    <i class="fa-solid <?= htmlspecialchars($op['icone']) ?>" style="color: <?= htmlspecialchars($op['cor']) ?>; margin-right:5px;"></i>
                <?php endif; ?>
                <?= htmlspecialchars($op['item']) ?>
            </td>
            <td data-label="Detalhes"><?= htmlspecialchars($op['detalhe']) ?></td>
            <td data-label="Data"><?= date('d/m/Y H:i', strtotime($op['data'])) ?></td>
            <td data-label="Funcionário"><?= htmlspecialchars($op['usuario']) ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">Nenhuma operação registrada</td></tr>
    <?php endif; ?>
    </tbody>
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

function abrirModalAnimado() {
    modalFundo.classList.add('modal-ativo');
}
function fecharModalAnimado() {
    modalFundo.classList.remove('modal-ativo');
}

btnApagar?.addEventListener('click', abrirModalAnimado);
btnFechar?.addEventListener('click', fecharModalAnimado);
window.addEventListener('click', e => { if(e.target===modalFundo) fecharModalAnimado(); });
</script>
</main>
</body>
</html>
</html>
