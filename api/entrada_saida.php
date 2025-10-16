<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../conexao.php';

// 1) verificar se usuário está logado
$usuarioId = $_SESSION['usuario_id'] ?? 0;
if (!$usuarioId) {
    echo json_encode(['error' => 'Usuário não autenticado (usuario_id ausente na sessão)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2) obter loja_id do usuário
$stmt = $conn->prepare("SELECT loja_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result();
$userRow = $res->fetch_assoc();
$stmt->close();

$lojaId = isset($userRow['loja_id']) ? (int)$userRow['loja_id'] : 0;
if (!$lojaId) {
    echo json_encode(['error' => 'Loja não associada ao usuário. Verifique usuarios.loja_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$debug = (isset($_GET['debug']) && $_GET['debug'] == '1');

// 3) query principal - soma entradas e saídas agrupadas por data
$sql = "
    SELECT me.data_movimentacao,
           SUM(CASE WHEN me.tipo = 'entrada' THEN COALESCE(me.quantidade,0) ELSE 0 END) AS entrada,
           SUM(CASE WHEN me.tipo = 'saida' THEN COALESCE(me.quantidade,0) ELSE 0 END) AS saida
    FROM movimentacoes_estoque AS me
    LEFT JOIN produtos AS p ON me.produto_id = p.id
    WHERE p.loja_id = ?
    GROUP BY me.data_movimentacao
    ORDER BY me.data_movimentacao ASC
";

try {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception("Erro ao preparar SQL principal: " . $conn->error);
    $stmt->bind_param("i", $lojaId);
    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while ($r = $result->fetch_assoc()) {
        $dados[] = [
            'data' => $r['data_movimentacao'],
            'entrada' => (int)$r['entrada'],
            'saida' => (int)$r['saida']
        ];
    }
    $stmt->close();

    $output = ['data' => $dados];

    // Debug detalhado
    if ($debug) {
        $output['debug_sql'] = $sql;

        // tipos distintos em movimentacoes
        $qTipos = "SELECT DISTINCT me.tipo AS tipo_raw FROM movimentacoes_estoque me LIMIT 50";
        $resTipos = $conn->query($qTipos);
        $tipos = [];
        while ($tr = $resTipos->fetch_assoc()) $tipos[] = $tr['tipo_raw'];
        $output['debug_tipos_distintos'] = $tipos;

        // amostra das últimas movimentações
        $qSample = "SELECT me.id, me.data_movimentacao, me.tipo, me.quantidade, me.produto_id FROM movimentacoes_estoque me ORDER BY me.data_movimentacao DESC LIMIT 10";
        $resSample = $conn->query($qSample);
        $sample = [];
        while ($sr = $resSample->fetch_assoc()) $sample[] = $sr;
        $output['debug_amostra_movimentacoes'] = $sample;
    }

    echo json_encode($output, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
