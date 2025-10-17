<?php
// Inicia a sessão apenas se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cabeçalhos anti-cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");

// Se não está logado, redireciona imediatamente
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /DECKLOGISTIC/Pages/auth/lojas/cadastro.php");
    exit;
}

// Pequeno script para forçar reload caso a página seja restaurada do bfcache/back-forward cache
// (isso faz o navegador requisitar a versão atual no servidor, que vai ver a sessão inválida e redirecionar)
echo '<script>
window.addEventListener("pageshow", function(event) {
    // event.persisted indica bfcache; o check de navigation type cobre outros navegadores
    if (event.persisted || (performance.getEntriesByType && performance.getEntriesByType("navigation")[0] && performance.getEntriesByType("navigation")[0].type === "back_forward")) {
        // força recarregamento para que o servidor reavalie a sessão
        window.location.reload();
    }
});
</script>';
?>
