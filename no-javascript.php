<?php
$currentUrl = $_SERVER['HTTP_REFERER'] ?? '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>JavaScript Requerido</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 600px; 
            margin: 100px auto; 
            padding: 20px; 
        }
    </style>
</head>
<body>
    <h1>⚠️ JavaScript Necessário</h1>
    <p>Nosso sistema requer JavaScript para funcionar.</p>
    <p><a href="<?php echo $currentUrl; ?>">Clique aqui para tentar novamente</a></p>
</body>
</html>