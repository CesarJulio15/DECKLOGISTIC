

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
   <link rel="stylesheet" href="../../assets/confirmar.css">
</head>
<body>
  <div class="sidebar">
<link rel="stylesheet" href="../../assets/sidebar.css">

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
</div>

  <div class="conteudo">
<div class="confirmar"> 
<span><img src="../../img/icon-carta.svg" alt="Carta"></span> 

    <div class="loader-tres-pontinhos">           
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

<h1>Enviamos um e-mail com um código de confirmação para </h1>

<h2>Insira o código que chegou no seu e-mail</h2>

<div class="code-container">
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
  <input type="number" class="digit-input" maxlength="1" min="0" max="9" required>
</div>


<a><h3>Enviar código novamente</h3></a>
</div>
</div>

<script>
  const inputs = document.querySelectorAll('.digit-input');

  inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      // Mantém apenas o último caractere digitado
      input.value = input.value.replace(/\D/g, '').slice(0, 1);

      // Passa para o próximo campo se tiver 1 dígito
      if (input.value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && input.value === '' && index > 0) {
        inputs[index - 1].focus();
      }
    });
  });
</script>

</body>
</html>