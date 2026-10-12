<?php

/*
 * Trocea una captura muy alta (una ventana de Chrome con la altura de la página
 * entera) en piezas de altura fija, reescaladas al ancho que se pida, para
 * revisarlas de una en una en el panel de vista previa sin perder detalle.
 *
 * Uso: php storage/tmp/trocear-captura.php <png> [alto de trozo] [ancho de salida] [carpeta]
 * Salida: <carpeta>/pieza-1.png, pieza-2.png, ...
 */

$origen = $argv[1] ?? __DIR__.'/repaso/d-entero.png';
$altoTrozo = (int) ($argv[2] ?? 1500);
$anchoSalida = (int) ($argv[3] ?? 800);
$carpeta = rtrim($argv[4] ?? __DIR__.'/repaso/trozos', '/');

if (! is_file($origen)) {
    fwrite(STDERR, "No existe {$origen}\n");
    exit(1);
}

if (! is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$imagen = imagecreatefrompng($origen);
$ancho = imagesx($imagen);
$alto = imagesy($imagen);
$escala = $anchoSalida / $ancho;
$altoEscalado = (int) round($altoTrozo * $escala);
$piezas = (int) ceil($alto / $altoTrozo);

for ($i = 0; $i < $piezas; $i++) {
    $y = $i * $altoTrozo;
    $altoReal = min($altoTrozo, $alto - $y);

    $lienzo = imagecreatetruecolor($anchoSalida, $altoEscalado);
    imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 200, 200, 200));
    imagecopyresampled($lienzo, $imagen, 0, 0, 0, $y, $anchoSalida, (int) round($altoReal * $escala), $ancho, $altoReal);

    $destino = sprintf('%s/pieza-%d.png', $carpeta, $i + 1);
    imagepng($lienzo, $destino);
    imagedestroy($lienzo);

    printf("%s (%d→%d px de alto)\n", $destino, $altoReal, (int) round($altoReal * $escala));
}

imagedestroy($imagen);

printf("original %dx%d, %d piezas de %d px\n", $ancho, $alto, $piezas, $altoTrozo);
