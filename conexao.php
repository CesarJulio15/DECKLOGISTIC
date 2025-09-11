    <?php
    $conn = mysqli_connect("162.241.62.33", "devgom44_deckers", "deckers@1234!", "devgom44_deckers");
    if (!$conn) {
        die("Erro na conexão: " . mysqli_connect_error());
    }
