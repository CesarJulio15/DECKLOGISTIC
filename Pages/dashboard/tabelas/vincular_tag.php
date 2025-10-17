<?php
include '../../../conexao.php';
session_start();

header('Content-Type: application/json');

// Verifica se está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['loja_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$lojaId = $_SESSION['loja_id'];
$produtoId = intval($_POST['produto_id'] ?? 0);
$tagId = intval($_POST['tag_id'] ?? 0);

if ($produtoId <= 0 || $tagId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Verifica se o produto pertence à loja do usuário
$stmtProduto = $conn->prepare("SELECT id FROM produtos WHERE id = ? AND loja_id = ?");
$stmtProduto->bind_param("ii", $produtoId, $lojaId);
$stmtProduto->execute();
$produto = $stmtProduto->get_result()->fetch_assoc();
$stmtProduto->close();

if (!$produto) {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
    exit;
}

// Verifica se a tag pertence à loja do usuário
$stmtTag = $conn->prepare("SELECT icone, cor FROM tags WHERE id = ? AND loja_id = ? AND deletado_em IS NULL");
$stmtTag->bind_param("ii", $tagId, $lojaId);
$stmtTag->execute();
$tag = $stmtTag->get_result()->fetch_assoc();
$stmtTag->close();

if (!$tag) {
    echo json_encode(['success' => false, 'message' => 'Tag não encontrada']);
    exit;
}

// Verifica se já existe vínculo
$stmtCheck = $conn->prepare("SELECT id FROM produto_tag WHERE produto_id = ? AND tag_id = ?");
$stmtCheck->bind_param("ii", $produtoId, $tagId);
$stmtCheck->execute();
$existe = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if ($existe) {
    echo json_encode(['success' => false, 'message' => 'Tag já vinculada a este produto']);
    exit;
}

// Insere o vínculo
$stmtInsert = $conn->prepare("INSERT INTO produto_tag (produto_id, tag_id) VALUES (?, ?)");
$stmtInsert->bind_param("ii", $produtoId, $tagId);

if ($stmtInsert->execute()) {
    $stmtInsert->close();
    echo json_encode([
        'success' => true,
        'message' => 'Tag vinculada com sucesso',
        'icone' => $tag['icone'],
        'cor' => $tag['cor']
    ]);
} else {
    $stmtInsert->close();
    echo json_encode(['success' => false, 'message' => 'Erro ao vincular tag']);
}
?>
