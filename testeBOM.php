<?php
$file = 'conexao.php';
$contents = file_get_contents($file);
$bom = pack('H*','EFBBBF');
if (substr($contents, 0, 3) === $bom) {
    echo "BOM detectado em $file";
} else {
    echo "Sem BOM";
}