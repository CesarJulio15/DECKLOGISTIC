<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos</title>
<link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background-color: #121212;
    color: #fff;
    font-family: 'Inter', sans-serif;
    margin: 0;
    padding: 0;
  }

  h1 {
    font-size: 1.6rem;
    text-align: center;
    margin: 20px 0;
  }

  table {
    width: 100%;
    color: #fff;
    border-collapse: collapse;
    font-size: 14px;
  }

  thead {
    background: #1e1e1e;
  }

  th, td {
    padding: 12px;
    border-bottom: 1px solid #333;
    text-align: left;
    vertical-align: middle;
  }

  tr:hover {
    background: rgba(255, 255, 255, 0.05);
  }

  /* Responsividade total */
  @media (max-width: 768px) {
    table thead {
      display: none;
    }

    table, table tbody, table tr, table td {
      display: block;
      width: 100%;
    }

    table tr {
      background: #1b1b1b;
      margin-bottom: 15px;
      border-radius: 10px;
      overflow: hidden;
      padding: 10px 15px;
    }

    table td {
      padding: 8px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: none;
      border-bottom: 1px solid #2a2a2a;
    }

    table td:last-child {
      border-bottom: none;
    }

    table td::before {
      content: attr(data-label);
      font-weight: 600;
      color: #ff9900;
      flex-basis: 45%;
      text-align: left;
    }
  }
</style>
</head>

<body>
  <div class="container my-4">
    <h1>Produtos</h1>
    <div class="table-responsive">
      <table class="table table-dark table-hover">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Preço Unitário</th>
            <th>Quantidade</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($produto = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td data-label="Nome"><?= htmlspecialchars($produto['nome']) ?></td>
            <td data-label="Preço Unitário">R$ <?= number_format($produto['preco_unitario'], 2, ',', '.') ?></td>
            <td data-label="Quantidade"><?= intval($produto['quantidade_estoque']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <div style="margin-top:15px; display:flex; justify-content:center; gap:6px;">
      <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>" 
           class="<?= ($i == $paginaAtual) ? 'active' : '' ?>"
           style="width:30px; height:30px; display:flex; align-items:center; justify-content:center;
                  border:1px solid #555; border-radius:4px; text-decoration:none; color:#fff;">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
    <style>
      a.active {
        border: 2px solid #ff6600 !important;
        color: #fff !important;
        background-color: transparent;
      }
    </style>
    <?php endif; ?>
  </div>
</body>
</html>
