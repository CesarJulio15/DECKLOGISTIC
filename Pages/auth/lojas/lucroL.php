<?php 
include __DIR__ . '/../../../header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico Lucro Líquido</title>
    <link rel="icon" href="../../../img/logo2.svg" type="image/x-icon" />
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <link rel="stylesheet" href="../../../assets/lucroL.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
   <!-- Botão Voltar -->
   <button class="btn-voltar" onclick="window.location.href='../../dashboard/financas.php'">← Voltar</button>

    <!-- Sidebar -->
<div class="content">
  <div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../../img/logo2.svg" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li class="active"><a href="../../../Pages/dashboard/financas.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../../../Pages/dashboard/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../../../Pages/dashboard/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
          <li><a href="../../../Pages/dashboard/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
          <li><a href="../../dashboard/tabelas/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
          <li><a href="../../dashboard/tag.php"><span><img src="../../../img/tag.svg" alt="Tags"></span> Tags</a></li>
        </ul>
      </div>
      <div class="bottom-links">
        <a href="../../auth/config.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
        <a href="../../../Pages/auth/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
      </div>
    </nav>
  </div>

    <!-- Conteúdo -->
    <div class="conteudo">
        <div class="grafico-container">
            <div class="header-grafico">
                <div class="indicador">
                    <span>▲</span> <b>34%</b>
                </div>
            </div>
            <div class="titulo">Lucro Líquido</div>
            <div class="canvas-wrapper">
                <canvas id="grafico"></canvas>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script>
       async function carregarGrafico() {
            try {
                const response = await fetch("/DECKLOGISTIC/api/lucro_liquido.php");
                const data = await response.json();

                const labels = data.series.map(item => item.data);
                const valores = data.series.map(item => item.valor);

                const ctx = document.getElementById('grafico').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Lucro Líquido',
                            data: valores,
                            borderColor: 'rgba(54, 162, 235, 0.8)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 0
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } catch (error) {
                console.error("Erro ao carregar gráfico:", error);
            }
        }

        carregarGrafico();  
    </script> 
</body>
</html>
