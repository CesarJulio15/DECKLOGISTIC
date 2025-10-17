<?php
// Inicia sessão apenas se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro | DeckLogistic</title>
  <link rel="stylesheet" href="../../../assets/login.css">
  <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
  <!-- Meta redundante para reforçar não-cache (opcional) -->
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
</head>
<body>
<div class="container">
  <div class="left-side">
  </div>
  <div class="right-side">
    <img src="../../../img/logoDecklogistic.webp" alt="Logo" class="logo">
    <div class="form-container">
      <h1>Olá Novamente!</h1>

      <!-- Mostra erro, se existir -->
      <?php
      if (isset($_SESSION['erro_login'])) {
          echo '<div class="erro-msg">' . htmlspecialchars($_SESSION['erro_login']) . '</div>';
          unset($_SESSION['erro_login']); // Limpa a mensagem após mostrar
      }
      ?>

      <!--
        Observações:
        - autocomplete="off" reduz autofill.
        - campo oculto "fakeusernameremembered" ajuda a confundir alguns salvadores de senha que preenchem o primeiro campo visível.
      -->
      <form id="loginForm" action="processa_login_loja.php" method="POST" autocomplete="off">
        <input type="text" name="fakeusernameremembered" id="fakeusernameremembered"
               style="display:none" autocomplete="off" value="">

        <input type="email" name="email" maxlength="100" placeholder="Endereço de e-mail" required
               autocomplete="username" value="" title="Digite um e-mail válido">
        <input type="password" name="senha" minlength="6" maxlength="50" placeholder="Insira sua Senha" required
               autocomplete="new-password" value="" title="Mínimo 6 caracteres">

        <div class="login-link">
          Ainda não tem uma conta para sua empresa?
          <a href="cadastro.php">Cadastrar</a>
        </div>
        <button type="submit" class="btn">Continuar</button>
        <button type="button" class="btn" onclick="window.history.back()">Fechar</button>
      </form>
    </div>
  </div>
</div>

<script>
/*
 Ao restaurar a página do bfcache, alguns navegadores reapresentam valores.
 Aqui: limpamos os inputs e evitamos autofill persistente.
*/
window.addEventListener("pageshow", function(event) {
    const isNavBack = event.persisted || (performance.getEntriesByType && performance.getEntriesByType("navigation")[0] && performance.getEntriesByType("navigation")[0].type === "back_forward");
    if (isNavBack) {
        const form = document.getElementById('loginForm');
        if (form) {
            // reset() limpa valores preenchidos
            form.reset();
            // além disso, limpa programaticamente qualquer value residual
            form.querySelectorAll('input').forEach(i => {
                i.value = '';
                // tenta desativar autocomplete caso o navegador insista
                try { i.setAttribute('autocomplete','off'); } catch(e){}
            });
        }
    }
});
</script>
</body>
</html>
