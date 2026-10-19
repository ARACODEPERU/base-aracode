<?php

/*
 * Hoja de repaso: pone en una sola página las capturas que deja
 * storage/tmp/revisar-demo-verdia.mjs para revisarlas de un vistazo.
 *
 * Los PNG se convierten a JPEG porque el panel de vista previa de esta máquina
 * no compone los PNG grandes (se quedan en blanco) y sí los JPEG, que además
 * pesan una fracción.
 *
 * Uso: php storage/tmp/verdia-hoja-repaso.php
 */

const CARPETA = __DIR__.'/repaso-verdia';
const CALIDAD = 78;

$titulos = [
    '01-portada' => 'Escritorio · portada (hero con slider)',
    '02-pasillos' => 'Escritorio · pasillos',
    '03-populares' => 'Escritorio · productos populares',
    '04-ofertas' => 'Escritorio · ofertas del día',
    '05-panel-canasta' => 'Escritorio · panel de la canasta',
    '06-oscuro' => 'Escritorio · portada en modo oscuro',
    '07-catalogo-vacio' => 'Escritorio · catálogo sin resultados',
    '08-catalogo-lacteos' => 'Escritorio · catálogo filtrado',
    '09-ficha-cafe' => 'Escritorio · ficha de producto',
    '10-canasta-vacia' => 'Escritorio · canasta vacía',
    '11-canasta-llena' => 'Escritorio · canasta con productos',
    '12-recibo' => 'Escritorio · recibo del pedido de prueba',
    '13-movil-portada' => 'Móvil 390 px · portada',
    '14-movil-pasillos' => 'Móvil 390 px · pasillos',
    '15-movil-menu' => 'Móvil 390 px · menú de pasillos',
];

$figuras = '';

foreach ($titulos as $archivo => $titulo) {
    $png = CARPETA.'/'.$archivo.'.png';

    if (! is_file($png)) {
        continue;
    }

    $medidas = getimagesize($png);
    $jpg = CARPETA.'/'.$archivo.'.jpg';

    // Se rehace siempre: así una captura nueva no se queda con el JPEG viejo.
    $imagen = imagecreatefrompng($png);

    if ($imagen !== false) {
        imagejpeg($imagen, $jpg, CALIDAD);
        imagedestroy($imagen);
    }

    $figuras .= sprintf(
        '<figure><figcaption>%s <span>%d × %d</span></figcaption><img src="repaso-verdia/%s.jpg" alt="" loading="lazy"></figure>',
        htmlspecialchars($titulo),
        $medidas[0],
        $medidas[1],
        $archivo
    );
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Repaso VERDIA Market</title>'
    .'<style>body{font:13px/1.4 system-ui;background:#101410;color:#e8efe6;margin:16px}'
    .'h1{font-size:17px;margin:0 0 14px}.rejilla{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}'
    .'figure{margin:0;background:#1b211b;border-radius:8px;padding:8px}'
    .'img{width:100%;height:auto;display:block;border-radius:4px;background:#fff}'
    .'figcaption{padding:6px 2px;font-weight:600}figcaption span{color:#9fb09c;font-weight:400}'
    .'</style></head><body><h1>Repaso de la demostración «Supermercado Online — VERDIA Market»</h1>'
    .'<div class="rejilla">'.$figuras.'</div></body></html>';

file_put_contents(CARPETA.'/hoja.html', $html);

echo 'hoja.html con '.substr_count($figuras, '<figure>')." capturas\n";

// Copia para verla en el panel (que sirve public/ y no storage/).
@mkdir(__DIR__.'/../../public/repaso-verdia-tmp');
foreach (glob(CARPETA.'/*.jpg') as $jpg) {
    copy($jpg, __DIR__.'/../../public/repaso-verdia-tmp/'.basename($jpg));
}

file_put_contents(
    __DIR__.'/../../public/repaso-verdia-tmp/hoja.html',
    str_replace('repaso-verdia/', 'repaso-verdia-tmp/', $html)
);
