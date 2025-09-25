<?php
// Conexão com o banco de dados
include __DIR__ . '/../conexao.php';

// Função para gerar o campo de data conforme o filtro
function getDataField($filtro) {
    switch($filtro) {
        case 'bimestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/2)+1,2,'0'))";
        case 'trimestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/3)+1,2,'0'))";
        case 'semestre':
            return "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/6)+1,2,'0'))";
        case 'mes':
            return "DATE_FORMAT(data_venda, '%Y-%m')";
        case 'ano':
            return "YEAR(data_venda)";
        default:
            return "DATE(data_venda)";
    }
}

// Obtém o filtro da query string, default para 'dia'
$filtro = $_GET['filtro'] ?? 'dia';
$dataField = getDataField($filtro);

// Consulta principal para obter receita e custo
$sql = "SELECT $dataField AS periodo,
               SUM(valor_total) AS receita,
               SUM(custo_total) AS custo
        FROM vendas
        GROUP BY $dataField
        ORDER BY periodo ASC";

// Executa a consulta
$res = mysqli_query($conn, $sql);

if (!$res) {
    echo json_encode(["error" => "Erro ao consultar os dados do banco."]);
    exit;
}

$labels = [];
$dadosReceita = [];
$dadosCusto = [];

while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['periodo'];
    $dadosReceita[] = (float)$row['receita'];
    $dadosCusto[] = (float)$row['custo'];
}

// Totais para o filtro atual
$condicao = match($filtro) {
    'bimestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/2)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/2)+1,2,'0'))",
    'trimestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/3)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/3)+1,2,'0'))",
    'semestre' => "CONCAT(YEAR(data_venda), '-', LPAD(FLOOR((MONTH(data_venda)-1)/6)+1,2,'0')) = CONCAT(YEAR(CURDATE()), '-', LPAD(FLOOR((MONTH(CURDATE())-1)/6)+1,2,'0'))",
    'mes' => "DATE_FORMAT(data_venda, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
    'ano' => "YEAR(data_venda) = YEAR(CURDATE())",
    default => "DATE(data_venda) = CURDATE()"
};

// Receita e custo para o filtro atual
$sqlReceita = "SELECT SUM(valor_total) AS total_receita FROM vendas WHERE $condicao";
$receita = mysqli_fetch_assoc(mysqli_query($conn, $sqlReceita))['total_receita'] ?? 0;

$sqlCusto = "SELECT SUM(custo_total) AS total_custo FROM vendas WHERE $condicao";
$custo = mysqli_fetch_assoc(mysqli_query($conn, $sqlCusto))['total_custo'] ?? 0;

// Lucro e variação
$lucro = $receita - $custo;
$percentual = 0;
$seta = "↑";
$classe = "positivo";
if ($receita > 0) {
    $percentual = ($lucro / $receita) * 100;
    if ($percentual < 0) {
        $seta = "↓";
        $classe = "negativo";
    }
}

// Retorna os resultados como JSON
$response = [
    "periodo" => $filtro,
    "total_receita" => number_format($receita, 2, ',', '.'),
    "total_custo" => number_format($custo, 2, ',', '.'),
    "lucro" => number_format($lucro, 2, ',', '.'),
    "percentual" => number_format($percentual, 2, ',', '.'),
    "seta" => $seta,
    "classe" => $classe,
    "labels" => $labels,
    "dados_receita" => $dadosReceita,
    "dados_custo" => $dadosCusto
];

// Retorna o JSON
echo json_encode($response);
?>
