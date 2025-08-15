<?php
// Inclui a conexão com o banco
include __DIR__ . '/../../conexao.php';

// Inserir nova tag se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tagName = $_POST['tag_name'] ?? '';
    $icon = $_POST['icon'] ?? '';
    $color = $_POST['color'] ?? '#000000';

    if ($tagName && $icon) {
        $stmt = $conn->prepare("INSERT INTO tags (nome, cor, icone, criado_em) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $tagName, $color, $icon);
        $stmt->execute();
        $stmt->close();
    }
}

// Buscar todas as tags
$tags = [];
$result = $conn->query("SELECT * FROM tags ORDER BY criado_em DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Tag</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/tag.css">
<link rel="stylesheet" href="../../assets/sidebar.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo-area">
        <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
            <ul class="nav-list top-section">
               <li class="active"><a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                <li class="active"><a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
            </ul>
            <hr>
            <ul class="nav-list middle-section">
                <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                <li><a href="/Pages/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
             <li><a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a></li>
            </ul>
        </div>
        <div class="bottom-links">
            <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<div class="main">
    <form method="POST" style="flex: 1;">
        <h1>Nova Tag</h1>
       <div class="input-wrapper">
  <input type="text" name="tag_name" placeholder="Nome da Tag" required>
  <span class="check-icon">&#10003;</span> <!-- ✔ símbolo de correto -->
</div>

        <p class="rep">Escolha um ícone que represente a sua tag:</p>
        <div class="search-wrapper">
            <input type="text" id="search" class="search" placeholder="Procurar ícone...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="icon-grid-wrapper">
            <div class="icon-grid" id="iconGrid">
                <?php
                $icons = [
                    'fa-laptop'=>'Eletrônicos','fa-tv'=>'TV e Áudio','fa-mobile'=>'Celulares','fa-headphones'=>'Acessórios Eletrônicos',
                    'fa-burger'=>'Alimentos','fa-apple-alt'=>'Frutas','fa-carrot'=>'Legumes','fa-drumstick-bite'=>'Carnes',
                    'fa-fish'=>'Peixes','fa-bread-slice'=>'Padaria','fa-cheese'=>'Laticínios','fa-wine-glass'=>'Bebidas',
                    'fa-beer'=>'Cervejas','fa-cocktail'=>'Drinks','fa-dog'=>'Pet','fa-cat'=>'Gatos','fa-football'=>'Esportes',
                    'fa-basketball-ball'=>'Basquete','fa-running'=>'Fitness','fa-shirt'=>'Roupas','fa-tshirt'=>'Moda Casual',
                    'fa-shoe-prints'=>'Calçados','fa-couch'=>'Móveis','fa-bed'=>'Cama e Colchão','fa-chair'=>'Cadeiras',
                    'fa-wrench'=>'Ferramentas','fa-hammer'=>'Construção','fa-screwdriver'=>'Pequenos Reparos','fa-utensils'=>'Cozinha',
                    'fa-blender'=>'Eletrodomésticos','fa-toothbrush'=>'Higiene Pessoal','fa-soap'=>'Limpeza','fa-baby'=>'Bebês',
                    'fa-book'=>'Livros','fa-gamepad'=>'Brinquedos','fa-seedling'=>'Jardinagem','fa-car'=>'Automotivo',
                    'fa-gem'=>'Acessórios e Joias','fa-broom'=>'Limpeza Doméstica','fa-mug-hot'=>'Café e Chás','fa-wine-bottle'=>'Vinhos',
                    'fa-cookie'=>'Confeitaria','fa-fan'=>'Climatização','fa-spray-can'=>'Produtos de Limpeza','fa-bath'=>'Banheiro e Higiene',
                    'fa-lightbulb'=>'Iluminação','fa-paint-roller'=>'Decoração e Pintura'
                ];
                foreach($icons as $class => $label) {
                    echo "<div class='icon-item' data-icon='$class' title='$label'><i class='fa-solid $class'></i></div>";
                }
                ?>
            </div>
        </div>

        <input type="hidden" name="icon" id="selectedIcon">

        <p>Escolha a cor do seu ícone</p>
        <div class="color-wrapper">
            <input type="color" name="color" id="colorPicker" value="#000000">
            <span id="colorValue">#000000</span>
        </div>

        <br>
        <button type="submit" class="pronto">Pronto</button>
    </form>

    <div class="tags-sidebar">
        <h2>Tags Criadas</h2>
        <div class="tags-list">
            <?php foreach ($tags as $tag): ?>
                <div class="tag-item">
                    <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i>
                    <?= htmlspecialchars($tag['nome']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
const icons = document.querySelectorAll('.icon-item');
const selectedInput = document.getElementById('selectedIcon');
const searchInput = document.getElementById('search');
const colorInput = document.getElementById('colorPicker');
const colorValue = document.getElementById('colorValue');

colorInput.addEventListener('input', () => {
    colorValue.textContent = colorInput.value;
    icons.forEach(icon => icon.querySelector('i').style.color = colorInput.value);
});

icons.forEach(icon => {
    icon.addEventListener('click', () => {
        icons.forEach(i => i.classList.remove('selected'));
        icon.classList.add('selected');
        selectedInput.value = icon.getAttribute('data-icon');
    });
});

searchInput.addEventListener('input', () => {
    const term = searchInput.value.toLowerCase();
    icons.forEach(icon => {
        const title = icon.getAttribute('title').toLowerCase();
        icon.style.display = title.includes(term) ? 'block' : 'none';
    });
});
</script>

</body>
</html>
