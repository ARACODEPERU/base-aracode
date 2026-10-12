<?php

/*
 * Prepara las fotos de la demo «Tienda en línea»: toma las candidatas elegidas
 * de los lotes de Commons, las recorta a las proporciones de la plantilla y las
 * guarda en WebP dentro de public/demos/tienda-online/img/.
 *
 * Proporciones:
 *   - portada: 16/9 (tres fotos distintas para el slider)
 *   - bandas: 3/2 (las franjas de sección)
 *   - taller: 3/4 (la vertical del bloque de oficio)
 *   - piezas: 4/3 (las nueve del catálogo y las galerías de sus fichas)
 *
 * Uso: php storage/tmp/fotos-tienda-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/tienda-online/img';
const CALIDAD = 82;

// n (de la búsqueda) => [destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo), calidad]
$recetas = [
    // Portada: slider de tres fotos
    3 => ['portada-ceramica.webp', 1280, 720, 0.5, 82],   // piezas de terracota en el escaparate
    8 => ['portada-torno.webp', 1280, 720, 0.5, 82],      // el torno del taller
    39 => ['portada-envio.webp', 1280, 720, 0.5, 82],     // los pedidos salen hoy

    // Bandas de sección
    28 => ['banda-bazar.webp', 1200, 800, 0.5, 82],       // faroles de un bazar
    52 => ['banda-hilos.webp', 1200, 800, 0.5, 82],       // madejas de hilo

    // Vertical del bloque de oficio
    401 => ['taller-envio.webp', 900, 1200, 0.4, 82],     // envolviendo un pedido

    // Las nueve piezas del catálogo
    204 => ['pieza-taza.webp', 900, 675, 0.5, 82],
    222 => ['pieza-jarron.webp', 900, 675, 0.5, 82],
    233 => ['pieza-chal.webp', 900, 675, 0.5, 82],
    232 => ['pieza-ovillos.webp', 900, 675, 0.5, 82],
    251 => ['pieza-tabla.webp', 900, 675, 0.5, 82],
    261 => ['pieza-cucharones.webp', 900, 675, 0.5, 82],
    266 => ['pieza-vela.webp', 900, 675, 0.5, 82],
    318 => ['pieza-jabon.webp', 900, 675, 0.5, 82],
    308 => ['pieza-canasta.webp', 900, 675, 0.5, 82],
];

$busqueda = [];

foreach (['candidatas-tienda', 'candidatas-tienda2', 'candidatas-tienda3', 'candidatas-tienda-p'] as $carpeta) {
    $ruta = __DIR__.'/'.$carpeta.'/datos.json';

    if (is_file($ruta)) {
        foreach (json_decode(file_get_contents($ruta), true) as $fila) {
            $busqueda[$fila['n']] = $fila;
        }
    }
}

if (! is_dir(DESTINO)) {
    mkdir(DESTINO, 0777, true);
}

$informe = [];
$problemas = [];

foreach ($recetas as $n => [$destino, $ancho, $alto, $sesgo, $calidad]) {
    $fila = $busqueda[$n] ?? null;

    if ($fila === null || ! is_file($fila['archivo'])) {
        $problemas[] = "#{$n}: falta la candidata";
        continue;
    }

    $origen = imagecreatefromstring(file_get_contents($fila['archivo']));
    $anchoOrigen = imagesx($origen);
    $altoOrigen = imagesy($origen);

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

    imagewebp($lienzo, DESTINO.'/'.$destino, $calidad ?? CALIDAD);
    imagedestroy($lienzo);

    $kb = (int) round(filesize(DESTINO.'/'.$destino) / 1024);

    // La regla del banco es que ninguna imagen pase de unos 250 KB.
    if ($kb > 250) {
        $problemas[] = sprintf('%s pesa %d KB', $destino, $kb);
    }

    $informe[] = [
        'archivo' => $destino,
        'medidas' => $ancho.'x'.$alto,
        'kb' => $kb,
        'candidata' => $n,
        'titulo' => $fila['titulo'],
        'autor' => $fila['autor'],
        'licencia' => $fila['licencia'],
        'ficha' => $fila['ficha'],
        'original' => $fila['original'],
    ];
}

foreach ($informe as $fila) {
    printf(
        "%-24s %-9s %4d KB  ← #%03d %s (%s, %s)\n",
        $fila['archivo'],
        $fila['medidas'],
        $fila['kb'],
        $fila['candidata'],
        $fila['titulo'],
        $fila['autor'] ?: 'autor sin firma',
        $fila['licencia']
    );
}

file_put_contents(__DIR__.'/tienda-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if ($problemas) {
    fwrite(STDERR, "\nNo se pudo preparar todo:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}

printf("\n%d imágenes en %s\n", count($informe), realpath(DESTINO));
