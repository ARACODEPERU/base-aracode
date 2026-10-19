<?php

/*
 * Hoja de finalistas de NÉXORA MARKET: las candidatas preseleccionadas, con el
 * uso previsto al lado, para revisarlas de un vistazo en el navegador antes de
 * recortarlas. Es una revisión, no un paso del proceso: la selección definitiva
 * vive en fotos-nexora-preparar.php.
 *
 * Uso: php storage/tmp/nexora-finalistas.php
 * Salida: storage/tmp/nexora-finalistas.html
 */

const FINALISTAS = [
    ['candidatas-nexora-ov2', 1, 'PORTADA 1 · tienda de tecnología'],
    ['candidatas-nexora2', 6, 'PORTADA 2 · boutique de moda'],
    ['candidatas-nexora3', 2, 'PORTADA 3 · hogar (cocina comedor)'],
    ['candidatas-nexora4-ov', 3, 'RUBRO tecnología'],
    ['candidatas-nexora2', 5, 'RUBRO moda'],
    ['candidatas-nexora3', 17, 'RUBRO hogar'],
    ['candidatas-nexora2', 57, 'RUBRO belleza'],
    ['candidatas-nexora2', 58, 'RUBRO deportes'],
    ['candidatas-nexora2', 61, 'RUBRO juguetes'],
    ['candidatas-nexora4-ov', 1, 'FRANJA tecnología'],
    ['candidatas-nexora2', 55, 'EDITORIAL estilo (brocha)'],
    ['candidatas-nexora2', 54, 'EDITORIAL hogar (dormitorio)'],
    ['candidatas-nexora2', 63, 'BLOG accesorios tecnología'],
    ['candidatas-nexora3-ov', 7, 'BLOG renovar hogar (aspiradora)'],
    ['candidatas-nexora5', 6, 'VERTICAL confianza (consumibles)'],
    ['candidatas-nexora5-ov', 1, 'VERTICAL alternativa (caminando)'],
    ['candidatas-nexora4-ov', 2, 'PRODUCTO auriculares over-ear'],
    ['candidatas-nexora4', 3, 'PRODUCTO auriculares (alt.)'],
    ['candidatas-nexora-ov2', 19, 'PRODUCTO auriculares inalámbricos'],
    ['candidatas-nexora-ov', 5, 'PRODUCTO reloj inteligente'],
    ['candidatas-nexora-ov2', 7, 'PRODUCTO altavoz portátil'],
    ['candidatas-nexora2', 37, 'PRODUCTO altavoz inteligente'],
    ['candidatas-nexora2', 42, 'PRODUCTO cámara mirrorless'],
    ['candidatas-nexora', 24, 'PRODUCTO ratón gamer'],
    ['candidatas-nexora2', 27, 'PRODUCTO dron'],
    ['candidatas-nexora3', 11, 'PRODUCTO smartphone/tecnología'],
    ['candidatas-nexora2', 9, 'PRODUCTO zapatillas'],
    ['candidatas-nexora2', 13, 'PRODUCTO gafas de sol'],
    ['candidatas-nexora2', 14, 'PRODUCTO bolso'],
    ['candidatas-nexora2', 23, 'PRODUCTO cafetera'],
    ['candidatas-nexora2', 24, 'PRODUCTO hervidor'],
    ['candidatas-nexora3', 7, 'PRODUCTO licuadora'],
    ['candidatas-nexora3', 6, 'PRODUCTO ollas'],
    ['candidatas-nexora2', 49, 'PRODUCTO jarrón'],
    ['candidatas-nexora2', 44, 'PRODUCTO cepillo dental'],
    ['candidatas-nexora2', 15, 'PRODUCTO perfume'],
    ['candidatas-nexora', 23, 'PRODUCTO secador'],
    ['candidatas-nexora4-ov', 5, 'PRODUCTO mancuernas'],
    ['candidatas-nexora', 13, 'PRODUCTO balón de fútbol'],
    ['candidatas-nexora-ov2', 29, 'PRODUCTO esterilla de yoga'],
    ['candidatas-nexora2', 19, 'PRODUCTO bloques de madera'],
    ['candidatas-nexora2', 20, 'PRODUCTO coche teledirigido'],
    ['candidatas-nexora2', 47, 'PRODUCTO peluche'],
    ['candidatas-nexora3', 15, 'PRODUCTO tren de madera'],
    ['candidatas-nexora5', 1, 'ALTERNATIVA (bolsas)'],
    ['candidatas-nexora2', 4, 'ALTERNATIVA (tienda 1936)'],
    ['candidatas-nexora3', 18, 'ALTERNATIVA hogar (histórica)'],
];

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Finalistas NÉXORA</title>'
    .'<style>body{font:13px/1.45 system-ui;background:#0e1116;color:#e8eaf0;margin:16px}'
    .'h1{font-size:18px;margin:0 0 14px}'
    .'.rejilla{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}'
    .'figure{margin:0;background:#161b23;border-radius:6px;overflow:hidden}'
    .'img{width:100%;height:190px;object-fit:cover;display:block}'
    .'figcaption{padding:6px 7px;font-size:11px}'
    .'b{color:#7dd3a0;display:block}'
    .'span{color:#ffb27a}'
    .'em{display:block;color:#9aa4b5;font-style:normal;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
    .'</style></head><body><h1>Finalistas NÉXORA Market</h1><div class="rejilla">';

$faltan = [];

foreach (FINALISTAS as [$carpeta, $n, $uso]) {
    $ruta = sprintf('%s/%s/%03d.jpg', __DIR__, $carpeta, $n);

    if (! is_file($ruta)) {
        $faltan[] = $carpeta.'#'.$n;
    }

    $html .= sprintf(
        '<figure><img src="%s/%03d.jpg" alt="" loading="lazy"><figcaption><b>%s#%03d</b><span>%s</span>%s</figcaption></figure>',
        rawurlencode($carpeta),
        $n,
        htmlspecialchars(substr($carpeta, 19), ENT_QUOTES),
        $n,
        htmlspecialchars($uso, ENT_QUOTES),
        is_file($ruta) ? '' : '<em>FALTA</em>'
    );
}

file_put_contents(__DIR__.'/nexora-finalistas.html', $html.'</div></body></html>');

printf("%d finalistas\n", count(FINALISTAS));

if ($faltan) {
    printf("Faltan: %s\n", implode(', ', $faltan));
    exit(1);
}
