<?php
session_start(); 
include __DIR__ . '/../conexao.php';
header('Content-Type: application/json');

$lojaId = $_SESSION['loja_id'] ?? 0; 

$sql = "SELECT data_ocorrencia, detalhe, score
        FROM anomalias
        WHERE loja_id = ?
        ORDER BY data_ocorrencia DESC
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lojaId);
$stmt->execute();
$result = $stmt->get_result();

$anomalias = [];
while ($row = $result->fetch_assoc()) {
    $anomalias[] = [
        'data_ocorrencia' => $row['data_ocorrencia'],
        'detalhe' => $row['detalhe'],
        'score' => floatval($row['score'])
    ];
}

echo json_encode($anomalias);
