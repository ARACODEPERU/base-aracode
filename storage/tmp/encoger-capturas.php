<?php
// Reduce las capturas del repaso para poder mirarlas: el visor no acepta
// imágenes de más de 8192 px por lado ni de más de 768 KB.
$dir = __DIR__.'/repaso-paginas-web/';

foreach (glob($dir.'*.png') as $origen) {
    $nombre = basename($origen, '.png');

    if (str_starts_with($nombre, 'vista-')) {
        continue;
    }

    $imagen = imagecreatefrompng($origen);
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    $escala = min(900 / $ancho, 7800 / $alto, 1);
    $nuevoAncho = (int) round($ancho * $escala);
    $nuevoAlto = (int) round($alto * $escala);

    $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

    $destino = $dir.'vista-'.$nombre.'.png';
    imagepng($lienzo, $destino, 9);
    imagedestroy($lienzo);
    imagedestroy($imagen);

    printf("%-34s %d × %d → %d × %d · %d KB\n", $nombre, $ancho, $alto, $nuevoAncho, $nuevoAlto, filesize($destino) / 1024);
}
