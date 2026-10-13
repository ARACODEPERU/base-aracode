<?php

/*
 * Revisión sin vista previa: cuando el panel no compone fotogramas, esta
 * auditoría imprime de cada imagen su tamaño, brillo medio, contraste y un mapa
 * de luminancia en ASCII (16 × 8). Sirve para comprobar que la foto no salió en
 * blanco, en negro ni recortada al aire, y para leer su composición.
 *
 * Uso: php storage/tmp/auditar-fotos.php <carpeta>
 */

$carpeta = $argv[1] ?? __DIR__.'/../../public/demos/inmobiliaria-propiedades/img';
$rampa = ' .:-=+*#%@';

foreach (array_merge(glob(rtrim($carpeta, "/")."/*.webp"), glob(rtrim($carpeta, "/")."/*.jpg")) as $ruta) {
    $imagen = str_ends_with($ruta, ".webp") ? imagecreatefromwebp($ruta) : imagecreatefromstring(file_get_contents($ruta));
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    $reducida = imagecreatetruecolor(16, 8);
    imagecopyresampled($reducida, $imagen, 0, 0, 0, 0, 16, 8, $ancho, $alto);

    $valores = [];

    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 16; $x++) {
            $color = imagecolorat($reducida, $x, $y);
            $valores[] = (($color >> 16 & 0xFF) * 0.299) + (($color >> 8 & 0xFF) * 0.587) + (($color & 0xFF) * 0.114);
        }
    }

    $media = array_sum($valores) / count($valores);
    $varianza = sqrt(array_sum(array_map(fn (float $v) => ($v - $media) ** 2, $valores)) / count($valores));

    printf("%s  %dx%d  brillo %.0f  contraste %.0f\n", basename($ruta), $ancho, $alto, $media, $varianza);

    for ($y = 0; $y < 8; $y++) {
        $linea = '';

        for ($x = 0; $x < 16; $x++) {
            $valor = $valores[$y * 16 + $x];
            $linea .= $rampa[(int) floor($valor / 256 * strlen($rampa))] ?? '@';
        }

        echo '    '.$linea."\n";
    }

    imagedestroy($reducida);
    imagedestroy($imagen);
}
