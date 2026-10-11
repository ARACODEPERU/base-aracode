<?php

/*
|--------------------------------------------------------------------------
| Preparación temporal de las fotos de la academia
|--------------------------------------------------------------------------
|
| Reescala las cinco fotos elegidas al tamaño en que se usan, las guarda como
| WebP optimizado y borra el resto. Reescalar también elimina el EXIF de las
| originales.
|
*/

$origen = __DIR__.'/../../public/demos/academia-cursos/_tmp-fotos/';
$destino = __DIR__.'/../../public/demos/academia-cursos/img/';
@mkdir($destino, 0777, true);

$plan = [
    ['04-laptops', 'portada', 1600],
    ['10-laboratorio', 'aula', 1200],
    ['11-grupo', 'grupo', 1000],
    ['13-egresados', 'egresados', 1200],
    ['19-biblioteca', 'instalaciones', 1400],
];

$abrir = function (string $ruta) {
    $cabecera = file_get_contents($ruta, false, null, 0, 12);

    if (str_starts_with($cabecera, 'RIFF')) {
        return imagecreatefromwebp($ruta);
    }

    if (str_starts_with($cabecera, "\x89PNG")) {
        return imagecreatefrompng($ruta);
    }

    return imagecreatefromjpeg($ruta);
};

foreach ($plan as [$base, $salida, $ancho]) {
    $ruta = $origen.$base.'.img';

    if (! is_file($ruta)) {
        echo "FALTA $base\n";

        continue;
    }

    $img = $abrir($ruta);
    $w = imagesx($img);
    $h = imagesy($img);
    $nuevoW = min($ancho, $w);
    $nuevoH = (int) round($h * ($nuevoW / $w));

    $lienzo = imagecreatetruecolor($nuevoW, $nuevoH);
    imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nuevoW, $nuevoH, $w, $h);
    imagewebp($lienzo, $destino.$salida.'.webp', 82);
    imagedestroy($img);
    imagedestroy($lienzo);

    echo str_pad($salida.'.webp', 22).str_pad($nuevoW.'x'.$nuevoH, 12).round(filesize($destino.$salida.'.webp') / 1024)." KB\n";
}
