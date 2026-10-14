<?php

/*
 * Prepara las fotos de la demo «NÉXORA MARKET»: toma las candidatas elegidas en
 * los lotes de búsqueda (storage/tmp/nexora-seleccion.json), las recorta a las
 * proporciones de la plantilla y las guarda en WebP dentro de
 * public/demos/nexora-market/img/. Es el mismo procedimiento del resto del banco
 * (storage/tmp/fotos-verdia-preparar.php), con la receta en un JSON para poder
 * revisarla antes de escribir nada.
 *
 * Proporciones:
 *   - portada:   16/10  (el hero comercial)
 *   - rubro:      4/3   (las seis tarjetas de categoría)
 *   - franja:     3/2   (la franja promocional)
 *   - editorial: 11/8   (los dos bloques editoriales)
 *   - guía:       3/2   (las tres tarjetas del blog)
 *   - confianza:  3/4   (la vertical del bloque de confianza)
 *   - producto:   1/1   (las tarjetas del catálogo)
 *
 * Uso: php storage/tmp/fotos-nexora-preparar.php [seleccion.json]
 * Salida: public/demos/nexora-market/img/*.webp + storage/tmp/nexora-fotos.json
 */

const DESTINO = __DIR__.'/../../public/demos/nexora-market/img';
const CALIDAD = 82;

$archivo = $argv[1] ?? __DIR__.'/nexora-seleccion.json';
$seleccion = json_decode(file_get_contents($archivo), true);

if (! is_dir(DESTINO)) {
    mkdir(DESTINO, 0777, true);
}

$informe = [];
$problemas = [];

foreach ($seleccion as $receta) {
    $origen_ruta = sprintf('%s/%s/%03d.jpg', __DIR__, $receta['carpeta'], $receta['n']);

    if (! is_file($origen_ruta)) {
        $problemas[] = $receta['destino'].': falta la candidata '.$receta['carpeta'].'#'.$receta['n'];

        continue;
    }

    $origen = imagecreatefromstring(file_get_contents($origen_ruta));

    if ($origen === false) {
        $problemas[] = $receta['destino'].': la candidata no se pudo leer';

        continue;
    }

    $anchoOrigen = imagesx($origen);
    $altoOrigen = imagesy($origen);

    $ancho = (int) $receta['ancho'];
    $alto = (int) $receta['alto'];
    $sesgo = (float) ($receta['sesgo'] ?? 0.5);

    // Recorte «cover»: se toma la mayor ventana con la proporción pedida y se
    // deja el resto fuera. `sesgo` decide de dónde se corta en vertical.
    $proporcion = $ancho / $alto;
    $anchoRecorte = $anchoOrigen;
    $altoRecorte = (int) round($anchoOrigen / $proporcion);

    if ($altoRecorte > $altoOrigen) {
        $altoRecorte = $altoOrigen;
        $anchoRecorte = (int) round($altoOrigen * $proporcion);
    }

    $x = (int) round(($anchoOrigen - $anchoRecorte) / 2);
    $y = (int) round(($altoOrigen - $altoRecorte) * $sesgo);

    $recorte = imagecrop($origen, ['x' => $x, 'y' => $y, 'width' => $anchoRecorte, 'height' => $altoRecorte]);
    imagedestroy($origen);

    $lienzo = imagecreatetruecolor($ancho, $alto);
    imagecopyresampled($lienzo, $recorte, 0, 0, 0, 0, $ancho, $alto, $anchoRecorte, $altoRecorte);
    imagedestroy($recorte);

    imagewebp($lienzo, DESTINO.'/'.$receta['destino'], CALIDAD);
    imagedestroy($lienzo);

    $kb = (int) round(filesize(DESTINO.'/'.$receta['destino']) / 1024);

    // La regla del banco es que ninguna imagen pase de unos 250 KB.
    if ($kb > 250) {
        $problemas[] = sprintf('%s pesa %d KB', $receta['destino'], $kb);
    }

    $informe[] = [
        'archivo' => $receta['destino'],
        'medidas' => $ancho.'x'.$alto,
        'kb' => $kb,
        'uso' => $receta['uso'],
        'candidata' => $receta['carpeta'].'#'.sprintf('%03d', $receta['n']),
        'origen' => $origen_ruta,
    ];
}

foreach ($informe as $fila) {
    printf("%-28s %-9s %4d KB  %s\n", $fila['archivo'], $fila['medidas'], $fila['kb'], $fila['uso']);
}

file_put_contents(__DIR__.'/nexora-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if ($problemas) {
    fwrite(STDERR, "\nNo se pudo preparar todo:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}

printf("\n%d imágenes en %s\n", count($informe), realpath(DESTINO));
