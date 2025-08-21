<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico Lucro Líquido</title>
    <link rel="stylesheet" href="../../assets/sidebar.css">
    <link rel="stylesheet" href="../../assets/lucroL.css">
</head>
<body>
    <div class="top-bar">Gráfico lucro líquido por mês</div>
    <div class="pagina">
        <!-- Sidebar -->
        <div class="sidebar">
            <link rel="stylesheet" href="../../../assets/sidebar.css">
            <div class="logo-area">
                <img src="../../../img/logoDecklogistic.webp" alt="Logo">
            </div>
            <nav class="nav-section">
                <div class="nav-menus">
                    <ul class="nav-list top-section">
                        <li><a href="/Pages/financeiro.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                        <li class="active"><a href="/Pages/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
                    </ul>
                    <hr>
                    <ul class="nav-list middle-section">
                        <li><a href="/Pages/visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                        <li><a href="/Pages/operacoes.php"><span><img src="../../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                        <li><a href="/Pages/produtos.php"><span><img src="../../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
                    </ul>
                </div>
                <div class="bottom-links">
                    <a href="/Pages/conta.php"><span><img src="../../../img/icon-config.svg" alt="Conta"></span> Conta</a>
                    <a href="/Pages/dicas.php"><span><img src="../../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
                </div>
            </nav>
        </div>

        <!-- Conteúdo -->
        <div class="conteudo">
            <div class="grafico-container">
                <div class="header-grafico">
                    <div>&lt; 12 Dias &gt;</div>
                    <div class="indicador">
                        <span>▲</span> <b>34%</b>
                    </div>
                </div>
                <div class="titulo">Lucro Líquido</div>
                <canvas id="grafico"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        async function carregarGrafico() {
            try {
                const response = await fetch("/DECKLOGISTIC/api/lucro_liquidoMap.php");
                const data = await response.json();

                // Cria arrays de labels e valores
                const labels = data.map(item => item.mes);
                const valores = data.map(item => item.lucro_liquido);

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
