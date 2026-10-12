<?php

/*
 * Prepara las capturas del catálogo de plantillas: toma los PNG de 1600 × 1000
 * (16/10, la proporción con la que se pinta la tarjeta) de storage/tmp/capturas
 * y los deja en JPEG en public/themes/plantillas/images/<slug>.jpg, que es la
 * ruta que se escribe en `imagen` de config/plantillas.php.
 *
 * Las capturas se toman con Chrome en modo sin interfaz, una por demostración:
 *
 *   chrome --headless=new --hide-scrollbars --force-device-scale-factor=1 \
 *          --window-size=1600,1000 --virtual-time-budget=8000 \
 *          --user-data-dir=<perfil temporal> \
 *          --screenshot=<salida>.png http://127.0.0.1:8123/demos/<slug>/
 *
 * Uso: php storage/tmp/capturas-preparar.php
 */

const ORIGEN = __DIR__.'/capturas';
const DESTINO = __DIR__.'/../../public/themes/plantillas/images';
const CALIDAD = 82;

// Una captura por plantilla con demostración publicada.
$slugs = [
    'clinica-dental',
    'restaurante-carta',
    'academia-cursos',
    'estudio-contable',
    'inmobiliaria-propiedades',
    'portafolio-creativo',
    'tienda-online',
];

if (! is_dir(DESTINO) && ! mkdir(DESTINO, 0775, true) && ! is_dir(DESTINO)) {
    fwrite(STDERR, "No se pudo crear ".DESTINO."\n");
    exit(1);
}

$problemas = [];

foreach ($slugs as $slug) {
    $png = ORIGEN.'/'.$slug.'.png';

    if (! is_file($png)) {
        $problemas[] = "falta {$png}";
        continue;
    }

    $medidas = getimagesize($png);

    if ($medidas === false) {
        $problemas[] = "{$slug}.png no es una imagen legible";
        continue;
    }

    // 16/10 exacto: la tarjeta recorta con object-fit: cover, así que una
    // proporción distinta se comería parte del diseño.
    if ($medidas[0] * 10 !== $medidas[1] * 16) {
        $problemas[] = sprintf('%s.png no es 16/10 (%dx%d)', $slug, $medidas[0], $medidas[1]);
        continue;
    }

    $origen = imagecreatefrompng($png);

    if ($origen === false) {
        $problemas[] = "{$slug}.png no se pudo abrir con GD";
        continue;
    }

    $jpg = DESTINO.'/'.$slug.'.jpg';

    imagejpeg($origen, $jpg, CALIDAD);
    imagedestroy($origen);

    printf(
        "%-26s %d × %d · %d KB → %d KB\n",
        $slug,
        $medidas[0],
        $medidas[1],
        filesize($png) / 1024,
        filesize($jpg) / 1024
    );
}

if ($problemas) {
    fwrite(STDERR, "No se pudo preparar todo:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}

echo "\nCapturas en ".realpath(DESTINO)."\n";
