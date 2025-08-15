<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tagName = $_POST['tag_name'] ?? '';
    $icon = $_POST['icon'] ?? '';
    $color = $_POST['color'] ?? '#000000';

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
<aside class="sidebar">
    <div class="logo-area">
        <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>

    <nav class="nav-section">
        <div class="nav-menus">
            <ul class="nav-list top-section">
                <li><a href="/Pages/financeiro.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a></li>
                <li class="active"><a href="/Pages/estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a></li>
            </ul>

            <hr>

            <ul class="nav-list middle-section">
                <li><a href="/Pages/visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="/Pages/operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
                <li><a href="/Pages/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
            </ul>
        </div>

        <div class="bottom-links">
            <a href="/Pages/conta.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<div class="main">
    <h1>Nova Tag</h1>
    <form method="POST">
        <input type="text" name="tag_name" placeholder="Nome da Tag" required>

        <p>Escolha um ícone que represente a sua tag:</p>
        <input type="text" id="search" class="search" placeholder="Procurar ícone...">

        <div class="icon-grid" id="iconGrid">
            <?php
            $icons = [
                'fa-laptop' => 'Notebook',
                'fa-burger' => 'Comida',
                'fa-wine-glass' => 'Bebidas',
                'fa-dog' => 'Pet',
                'fa-football' => 'Esporte',
                'fa-shirt' => 'Roupa',
                'fa-couch' => 'Móveis',
                'fa-wrench' => 'Ferramentas',
                'fa-utensils' => 'Cozinha'
            ];
            foreach($icons as $class => $label) {
                echo "<div class='icon-item' data-icon='$class' title='$label'><i class='fa-solid $class'></i></div>";
            }
            ?>
        </div>

        <input type="hidden" name="icon" id="selectedIcon">

        <p>Escolha a cor do seu ícone</p>
        <input type="color" name="color" value="#000000">

        <br>
        <button type="submit">Pronto</button>
    </form>
</div>

<script>
const icons = document.querySelectorAll('.icon-item');
const selectedInput = document.getElementById('selectedIcon');
const searchInput = document.getElementById('search');

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