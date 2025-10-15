<?php
include __DIR__ . '/../../conexao.php';

$filtro = $_GET['filtro'] ?? 'dia'; // padrão: dia

switch ($filtro) {
    case 'bimestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
    case 'trimestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
    case 'semestre':
        $sql = "SELECT CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0')) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0'))
                ORDER BY periodo ASC";
        break;
    case 'mes':
        $sql = "SELECT DATE_FORMAT(data_movimentacao, '%Y-%m') AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY DATE_FORMAT(data_movimentacao, '%Y-%m')
                ORDER BY periodo ASC";
        break;
    case 'ano':
        $sql = "SELECT YEAR(data_movimentacao) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY YEAR(data_movimentacao)
                ORDER BY periodo ASC";
        break;
    default: // dia
        $sql = "SELECT DATE(data_movimentacao) AS periodo,
                       SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE 0 END) AS entradas,
                       SUM(CASE WHEN tipo = 'saida' THEN quantidade ELSE 0 END) AS saidas
                FROM movimentacoes_estoque
                GROUP BY DATE(data_movimentacao)
                ORDER BY periodo ASC";
        break;
}

$res = mysqli_query($conn, $sql);

$labels = [];
$dadosEntradas = [];
$dadosSaidas = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['periodo'];
    $dadosEntradas[] = (int)$row['entradas'];
    $dadosSaidas[] = (int)$row['saidas'];
}

// Condição de filtro para os cards
switch ($filtro) {
    case 'bimestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/2)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/2)+1, 2, '0'))";
        $tituloFiltro = "Bimestre";
        break;
    case 'trimestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/3)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/3)+1, 2, '0'))";
        $tituloFiltro = "Trimestre";
        break;
    case 'semestre':
        $condicao = "CONCAT(YEAR(data_movimentacao), '-', LPAD(FLOOR((MONTH(data_movimentacao)-1)/6)+1, 2, '0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/6)+1, 2, '0'))";
        $tituloFiltro = "Semestre";
        break;
    case 'mes':
        $condicao = "DATE_FORMAT(data_movimentacao, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $tituloFiltro = "Mês";
        break;
    case 'ano':
        $condicao = "YEAR(data_movimentacao) = YEAR(CURDATE())";
        $tituloFiltro = "Ano";
        break;
    default:
        $condicao = "DATE(data_movimentacao) = CURDATE()";
        $tituloFiltro = "Dia";
        break;
}

// Totais
$sqlEntradas = "SELECT SUM(quantidade) AS total_entradas FROM movimentacoes_estoque WHERE tipo='entrada' AND $condicao";
$entradas = mysqli_fetch_assoc(mysqli_query($conn, $sqlEntradas))['total_entradas'] ?? 0;

$sqlSaidas = "SELECT SUM(quantidade) AS total_saidas FROM movimentacoes_estoque WHERE tipo='saida' AND $condicao";
$saidas = mysqli_fetch_assoc(mysqli_query($conn, $sqlSaidas))['total_saidas'];
?>
