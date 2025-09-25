<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico Margem de Lucro</title>
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <link rel="stylesheet" href="../../../assets/margem.css">
</head>
<body>
    <body>
   <!-- Botão Voltar -->
<button class="btn-voltar" onclick="window.location.href='../../dashboard/financas.php'">← Voltar</button>


  

        <!-- Sidebar -->
<div class="content">
  <div class="sidebar">
    <link rel="stylesheet" href="../../../assets/sidebar.css">
    <div class="logo-area">
      <img src="../../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
      <div class="nav-menus">
       <ul class="nav-list top-section">
    <li class="active"><a href="../../../Pages/dashboard/financas.php"><span><img src="../../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
    <li><a href="../../../Pages/dashboard/estoque.php"><span><img src="../../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
</ul>
        <hr>
        <ul class="nav-list middle-section">
          <li><a href="../visaoGeral.php"><span><img src="../../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
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
                <div class="titulo">Margem de Lucro</div>
                <canvas id="grafico"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    async function carregarGrafico() {
        try {
            // Chamadas para as duas APIs
            const [brutoResp, liquidoResp] = await Promise.all([
                fetch("/DECKLOGISTIC/api/lucro_brutoMap.php"),
                fetch("/DECKLOGISTIC/api/lucro_liquidoMap.php")
            ]);

            const brutoData = await brutoResp.json();
            const liquidoData = await liquidoResp.json();

            // Assumindo que as duas APIs retornam no mesmo formato [{ mes, valor }]
            const labels = brutoData.map(item => item.mes);

            // Cálculo da margem: (lucro líquido / lucro bruto) * 100
            const valores = brutoData.map((item, i) => {
                const bruto = item.lucro_bruto || 0;
                const liquido = liquidoData[i]?.lucro_liquido || 0;
                return bruto > 0 ? ((liquido / bruto) * 100).toFixed(2) : 0;
            });

            // Atualiza o indicador (último mês)
            const ultimoValor = valores[valores.length - 1];
            const indicador = document.querySelector(".indicador b");
            const seta = document.querySelector(".indicador span");

            if (indicador) indicador.textContent = ultimoValor + "%";
            if (seta) seta.textContent = ultimoValor >= 0 ? "▲" : "▼";

            // Gráfico
            const ctx = document.getElementById('grafico').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Margem de Lucro (%)',
                        data: valores,
                        borderColor: 'rgba(54, 162, 235, 0.9)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '%'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw + "%";
                                }
                            }
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
