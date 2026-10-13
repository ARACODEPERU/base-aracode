<?php
// Recorta una zona de una captura para mirarla de cerca.
$origen = $argv[1];
$x = (int) $argv[2];
$y = (int) $argv[3];
$ancho = (int) $argv[4];
$alto = (int) $argv[5];
$destino = $argv[6];

$imagen = imagecreatefrompng($origen);
$recorte = imagecreatetruecolor($ancho, $alto);
imagecopy($recorte, $imagen, 0, 0, $x, $y, $ancho, $alto);
imagepng($recorte, $destino, 9);
printf("%s · %d × %d · %d KB\n", $destino, $ancho, $alto, filesize($destino) / 1024);
