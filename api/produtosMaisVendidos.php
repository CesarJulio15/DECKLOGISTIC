<?php
// api/produtosMaisVendidos.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/DECKLOGISTIC/conexao.php';

// -- Permissão mínima: somente empresa/funcionario (opcional, ativo para a API)
if (!isset($_SESSION['tipo_login']) || !in_array($_SESSION['tipo_login'], ['empresa','funcionario'])) {
    // Se for chamada AJAX, retorna 403; se for include, mostra mensagem simples
    if (isset($_GET['loja_id']) || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Acesso não autorizado.']);
        exit;
    } else {
        echo "<p>Acesso não autorizado.</p>";
        exit;
    }
}

// Período padrão (ajuste se quiser)
$default_inicio = '2025-01-01';
$default_fim    = '2025-12-31';

// Pega datas por GET (valida YYYY-MM-DD)
function valid_date($d) {
    $t = explode('-', $d);
    return count($t) === 3 && checkdate((int)$t[1], (int)$t[2], (int)$t[0]);
}
$data_inicio = $default_inicio;
$data_fim = $default_fim;
if (!empty($_GET['data_inicio']) && valid_date($_GET['data_inicio'])) {
    $data_inicio = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim']) && valid_date($_GET['data_fim'])) {
    $data_fim = $_GET['data_fim'];
}

// Obtém loja_id: prioridade GET (AJAX), senão sessão (include)
$loja_id = null;
if (isset($_GET['loja_id']) && is_numeric($_GET['loja_id'])) {
    $loja_id = (int)$_GET['loja_id'];
} elseif (isset($_SESSION['loja_id']) && is_numeric($_SESSION['loja_id'])) {
    $loja_id = (int)$_SESSION['loja_id'];
}

// Verifica conexão
if (!isset($conn) || !$conn) {
    http_response_code(500);
    if (isset($_GET['loja_id']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Erro na conexão com o banco.']);
        exit;
    } else {
        echo "<p>Erro na conexão com o banco de dados.</p>";
        exit;
    }
}

// Monta SQL com filtro por loja se tiver loja_id
$sql = "
    SELECT p.id AS produto_id,
           p.nome AS produto,
           SUM(iv.quantidade) AS total_vendido
    FROM itens_venda iv
    JOIN produtos p ON p.id = iv.produto_id
    JOIN vendas v ON v.id = iv.venda_id
    WHERE iv.data_venda BETWEEN ? AND ?
";

if (!is_null($loja_id)) {
    $sql .= " AND v.loja_id = ? ";
}

$sql .= "
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 10
";

// Prepara statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    $err = $conn->error;
    if (isset($_GET['loja_id']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Erro ao preparar consulta.', 'details' => $err]);
        exit;
    } else {
        echo "<p>Erro ao preparar consulta: " . htmlspecialchars($err) . "</p>";
        exit;
    }
}

// Faz bind conforme presença de loja_id
if (!is_null($loja_id)) {
    $stmt->bind_param('ssi', $data_inicio, $data_fim, $loja_id);
} else {
    $stmt->bind_param('ss', $data_inicio, $data_fim);
}

$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $r['total_vendido'] = (int)$r['total_vendido'];
    $rows[] = $r;
}
$stmt->close();

// Retorna JSON se for chamada AJAX (loja_id via GET) ou se Accept pedir JSON
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (isset($_GET['loja_id']) || strpos($accept, 'application/json') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

// Caso inclusion server-side -> renderiza tabela HTML
if (count($rows) === 0) {
    echo '<p>Nenhum produto vendido encontrado para o período especificado.</p>';
    exit;
}

echo '<table class="produtos-mais-vendidos" border="0" cellpadding="8" cellspacing="0" style="width:100%;">';
echo '<thead><tr><th style="text-align:left">Produto</th><th style="text-align:right">Total Vendido</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $nome = htmlspecialchars($r['produto'], ENT_QUOTES, 'UTF-8');
    $total = number_format($r['total_vendido'], 0, ',', '.');
    echo "<tr><td>{$nome}</td><td style='text-align:right'>{$total}</td></tr>";
}
echo '</tbody></table>';


