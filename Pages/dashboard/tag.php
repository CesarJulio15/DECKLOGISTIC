<?php
// Inclui a conexão com o banco
session_start();
include __DIR__ . '/../../conexao.php';

$usuarioId = $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) {
    die("Usuário não está logado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_tag_id'], $_POST['rename_tag_nome'])) {
    $id = intval($_POST['rename_tag_id']);
    $novoNome = trim($_POST['rename_tag_nome']);
    if ($id && $novoNome) {
$stmt = $conn->prepare("
    UPDATE tags 
    SET nome_antigo = nome, 
        nome = ?, 
        atualizado_em = NOW(), 
        usuario_atualizacao_id = ? 
    WHERE id = ?
");
$stmt->bind_param("sii", $novoNome, $usuarioId, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: tag.php"); // Atualiza a página após renomear
        exit;
    }
}

// Inserir nova tag se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tag_name'], $_POST['icon'])) {
    $tagName = $_POST['tag_name'];
    $icon = $_POST['icon'];
    $color = $_POST['color'] ?? '#000000';
    if ($tagName && $icon) {
$usuarioId = $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) {
    die("Usuário não está logado.");
}
if (!$usuarioId) die("Usuário não logado");
$stmt = $conn->prepare("INSERT INTO tags (nome, nome_criado, cor, icone, usuario_id, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssi", $tagName, $tagName, $color, $icon, $usuarioId);
if (!$stmt->execute()) {
    die("Erro ao inserir tag: " . $stmt->error);
}
$stmt->close();
    }
}

// Buscar todas as tags
$tags = [];$result = $conn->query("
    SELECT * 
    FROM tags 
    WHERE deletado_em IS NULL 
    ORDER BY criado_em DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}

// Excluir tag via formulário oculto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $idToDelete = (int)$_POST['delete_id'];
    if ($idToDelete) {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $idToDelete = (int)$_POST['delete_id'];
    if ($idToDelete) {
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        $stmt = $conn->prepare("
            UPDATE tags 
            SET deletado_em = NOW(), usuario_exclusao_id = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $usuarioId, $idToDelete);
        $stmt->execute();
        $stmt->close();

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}
        $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
$stmt->bind_param("i", $idToDelete);
        $stmt->execute();
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}
// Inserir nova tag se o formulário foi enviado
// Buscar todas as tags
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
<style>
/* MENU DE CONTEXTO */
.context-menu {
    display: none;
    position: absolute;
    z-index: 1000;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    width: 150px;
}
.context-menu ul {
    list-style: none;
    margin: 0;
    padding: 5px 0;
}
.context-menu li {
    padding: 10px;
    cursor: pointer;
    transition: background 0.2s;
}
.context-menu li:hover {
    background: #f0f0f0;
}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="logo-area">
        <img src="../../img/logoDecklogistic.webp" alt="Logo">
    </div>
    <nav class="nav-section">
        <div class="nav-menus">
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
       <ul class="nav-list top-section">
    <li class="<?= $currentPage=='financas.php' ? 'active' : '' ?>">
        <a href="financas.php"><span><img src="../../img/icon-finan.svg" alt="Financeiro"></span> Financeiro</a>
    </li>
    <li class="<?= $currentPage=='estoque.php' ? 'active' : '' ?>">
        <a href="estoque.php"><span><img src="../../img/icon-estoque.svg" alt="Estoque"></span> Estoque</a>
    </li>
</ul>
            <hr>
            <ul class="nav-list middle-section">
                <li><a href="visaoGeral.php"><span><img src="../../img/icon-visao.svg" alt="Visão Geral"></span> Visão Geral</a></li>
                <li><a href="operacoes.php"><span><img src="../../img/icon-operacoes.svg" alt="Operações"></span> Operações</a></li>
               <li><a href="../dashboard/tabelas/produtos.php"><span><img src="../../img/icon-produtos.svg" alt="Produtos"></span> Produtos</a></li>
              <li class="<?= $currentPage=='tag.php' ? 'active' : '' ?>">
        <a href="tag.php"><span><img src="../../img/tag.svg" alt="Tags"></span> Tags</a>
    </li>
            </ul>
        </div>
        <div class="bottom-links">
           <a href="../auth/config.php"><span><img src="../../img/icon-config.svg" alt="Conta"></span> Conta</a>
            <a href="/Pages/dicas.php"><span><img src="../../img/icon-dicas.svg" alt="Dicas"></span> Dicas</a>
        </div>
    </nav>
</aside>

<div class="main">
    <form method="POST" style="flex: 1;">
        <h1>Nova Tag</h1>
       <div class="input-wrapper">
          <input type="text" name="tag_name" placeholder="Nome da Tag" required>
          <span class="check-icon">&#10003;</span>
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
         'fa-laptop'=>'Eletrônicos','fa-desktop'=>'Computadores','fa-tv'=>'TV e Áudio', 'fa-mobile'=>'Celulares','fa-tablet'=>'Tablets','fa-headphones'=>'Acessórios Eletrônicos', 'fa-headset'=>'Headsets','fa-camera'=>'Câmeras','fa-video'=>'Filmadoras', 'fa-microchip'=>'Hardware e Peças','fa-plug'=>'Energia e Carregadores', 'fa-microphone'=>'Áudio e Música','fa-satellite-dish'=>'Satélites e Comunicação', 'fa-server'=>'Servidores','fa-keyboard'=>'Teclados','fa-mouse'=>'Mouses','fa-burger'=>'Alimentos','fa-apple-alt'=>'Frutas','fa-carrot'=>'Legumes', 'fa-drumstick-bite'=>'Carnes','fa-fish'=>'Peixes','fa-bread-slice'=>'Padaria', 'fa-cheese'=>'Laticínios','fa-wine-glass'=>'Bebidas','fa-beer'=>'Cervejas', 'fa-cocktail'=>'Drinks','fa-wine-bottle'=>'Vinhos','fa-cookie'=>'Confeitaria', 'fa-ice-cream'=>'Sorvetes','fa-mug-hot'=>'Café e Chás','fa-seedling'=>'Orgânicos', 'fa-hotdog'=>'Lanches','fa-pizza-slice'=>'Pizzas','fa-couch'=>'Móveis','fa-bed'=>'Cama e Colchão','fa-chair'=>'Cadeiras', 'fa-bath'=>'Banheiro e Higiene','fa-lightbulb'=>'Iluminação','fa-paint-roller'=>'Decoração e Pintura', 'fa-blender'=>'Eletrodomésticos','fa-fan'=>'Climatização','fa-recycle'=>'Sustentabilidade', 'fa-box'=>'Embalagens','fa-door-open'=>'Portas','fa-sink'=>'Cozinha e Pias', 'fa-shirt'=>'Roupas','fa-tshirt'=>'Moda Casual','fa-shoe-prints'=>'Calçados', 'fa-gem'=>'Acessórios e Joias','fa-hat-cowboy'=>'Chapéus e Bonés','fa-glasses'=>'Óculos', 'fa-ring'=>'Anéis','fa-socks'=>'Meias','fa-soap'=>'Sabonetes e Limpeza', 'fa-heart'=>'Beleza e Cuidados','fa-spa'=>'Relaxamento & Spa', 'fa-stethoscope'=>'Equipamentos Médicos','fa-pills'=>'Medicamentos', 'fa-hospital'=>'Saúde','fa-syringe'=>'Vacinas','fa-dna'=>'Exames e Biotecnologia', 'fa-baby'=>'Bebês','fa-gamepad'=>'Brinquedos','fa-book'=>'Livros', 'fa-puzzle-piece'=>'Jogos Educativos','fa-school'=>'Material Escolar',  'fa-dog'=>'Pet','fa-cat'=>'Gatos','fa-bone'=>'Petiscos','fa-paw'=>'Acessórios Pets',  'fa-football'=>'Esportes','fa-basketball-ball'=>'Basquete','fa-running'=>'Fitness', 'fa-bicycle'=>'Bicicletas','fa-motorcycle'=>'Motos','fa-swimmer'=>'Natação', 'fa-dumbbell'=>'Academia','fa-futbol'=>'Futebol','fa-campground'=>'Camping', 'fa-hiking'=>'Trilhas','fa-fish'=>'Pesca','fa-golf-ball'=>'Golfe','fa-car'=>'Automotivo','fa-bus'=>'Ônibus e Passagens','fa-train'=>'Transportes', 'fa-plane'=>'Viagens','fa-ship'=>'Náutica','fa-truck'=>'Entrega e Caminhões', 'fa-gas-pump'=>'Combustível','fa-charging-station'=>'Carros Elétricos', 'fa-tools'=>'Oficinas','fa-warehouse'=>'Estoque e Garagem',  'fa-wrench'=>'Ferramentas','fa-hammer'=>'Construção','fa-screwdriver'=>'Pequenos Reparos', 'fa-hard-hat'=>'EPI e Segurança','fa-toolbox'=>'Caixa de Ferramentas','fa-ruler-combined'=>'Medidas', 'fa-ticket-alt'=>'Eventos e Ingressos','fa-theater-masks'=>'Teatro e Cultura', 'fa-film'=>'Cinema','fa-music'=>'Música','fa-guitar'=>'Instrumentos Musicais', 'fa-camera-retro'=>'Fotografia','fa-book-open'=>'Livros Abertos','fa-newspaper'=>'Jornais',  'fa-map'=>'Mapas e Turismo','fa-suitcase'=>'Mala e Bagagem','fa-hotel'=>'Hotelaria', 'fa-passport'=>'Documentação','fa-compass'=>'Exploração', 'fa-wallet'=>'Carteiras','fa-credit-card'=>'Cartões e Pagamentos', 'fa-money-bill'=>'Dinheiro','fa-coins'=>'Moedas','fa-university'=>'Banco', 'fa-percent'=>'Ofertas e Descontos','fa-star'=>'Promoções','fa-gift'=>'Presentes','fa-robot'=>'Robótica','fa-vr-cardboard'=>'Realidade Virtual', 'fa-space-shuttle'=>'Espaço e Astronomia', 'fa-brain'=>'IA & Machine Learning','fa-network-wired'=>'Redes', 'fa-cloud'=>'Nuvem','fa-code'=>'Programação' ];
                
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
                <div class="tag-item" data-id="<?= $tag['id'] ?>">
                    <i class="fa-solid <?= htmlspecialchars($tag['icone']) ?>" style="color: <?= htmlspecialchars($tag['cor']) ?>;"></i>
                    <?= htmlspecialchars($tag['nome']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Formulário oculto para deletar tag -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<!-- MENU DE CONTEXTO -->
   <div id="contextMenu" class="context-menu">
    <ul>
        <li id="renameTag">Renomear</li> <!-- Adicionei este -->
        <li id="deleteTag">Excluir</li>
    </ul>
</div>
    <form id="renameForm" method="POST" style="display:none;">
    <input type="hidden" name="rename_tag_id" id="rename_tag_id">
    <input type="text" name="rename_tag_nome" id="rename_tag_nome">
</form>
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

// ===== CONTEXT MENU =====
const contextMenu = document.getElementById("contextMenu");
let selectedTagId = null;

function showContextMenu(x, y) {
    const menu = contextMenu;
    const pad = 8;
    const vw = window.innerWidth, vh = window.innerHeight;
    menu.style.display = "block";
    const rect = menu.getBoundingClientRect();
    let left = x, top = y;
    if (left + rect.width > vw) left = vw - rect.width - pad;
    if (top + rect.height > vh) top = vh - rect.height - pad;
    menu.style.left = left + "px";
    menu.style.top  = top  + "px";
}

document.querySelectorAll(".tag-item").forEach(tag => {
    tag.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        selectedTagId = tag.dataset.id;
        showContextMenu(e.pageX, e.pageY);
    });
});

document.addEventListener("click", () => contextMenu.style.display = "none");
window.addEventListener("scroll", () => contextMenu.style.display = "none");
window.addEventListener("resize", () => contextMenu.style.display = "none");

// Deletar via formulário PHP
document.getElementById("deleteTag").addEventListener("click", () => {
    contextMenu.style.display = "none";
    if (!selectedTagId) return;
    if (!confirm("Deseja realmente excluir esta tag?")) return;
    document.getElementById("delete_id").value = selectedTagId;
    document.getElementById("deleteForm").submit();
});
document.getElementById("renameTag").addEventListener("click", () => {
    contextMenu.style.display = "none";
    if (!selectedTagId) return;
    const novoNome = prompt("Digite o novo nome da tag:");
    if (!novoNome) return;
    document.getElementById("rename_tag_id").value = selectedTagId;
    document.getElementById("rename_tag_nome").value = novoNome;
    document.getElementById("renameForm").submit();
});

</script>
</body>
</html>
