<?php

/*
 * Hoja de contactos con las candidatas preseleccionadas de los tres lotes de
 * Commons, para mirarlas antes de recortarlas y descartar las que no
 * correspondan al producto o a la categoría que van a ilustrar.
 *
 * Uso: php storage/tmp/verdia-revisar.php
 */

const DESTINO = __DIR__.'/verdia-revisar3';

$seleccion = [
    402, 413, 416, 417, 435, 436, 438, 445, 450, 454, 455, 446, 444, 430, 432,
];

$fotos = [];

foreach (['candidatas-verdia', 'candidatas-verdia2', 'candidatas-verdia3', 'candidatas-verdia5'] as $carpeta) {
    foreach (json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true) as $fila) {
        $fotos[$fila['n']] = $fila;
    }
}

if (! is_dir(DESTINO)) {
    mkdir(DESTINO, 0777, true);
}

$filas = '';

foreach ($seleccion as $n) {
    $fila = $fotos[$n] ?? null;

    if ($fila === null || ! is_file($fila['archivo'])) {
        $filas .= "<figure class=\"falta\"><figcaption>#{$n} — falta</figcaption></figure>";
        continue;
    }

    copy($fila['archivo'], DESTINO.'/'.$n.'.jpg');

    $filas .= sprintf(
        '<figure id="c%1$d"><img src="%1$d.jpg" alt="" loading="lazy"><figcaption><strong>#%1$d</strong> %2$s<br><span>%3$s</span></figcaption></figure>',
        $n,
        htmlspecialchars($fila['busqueda'], ENT_QUOTES),
        htmlspecialchars($fila['titulo'], ENT_QUOTES)
    );
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>VERDIA — candidatas 2</title>'
    .'<style>body{font:13px/1.4 system-ui;background:#101410;color:#e8efe6;margin:16px}'
    .'h1{font-size:18px;margin:0 0 12px}.rejilla{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}'
    .'figure{margin:0;background:#1b211b;border-radius:8px;overflow:hidden}'
    .'img{width:100%;height:190px;object-fit:cover;display:block}'
    .'figcaption{padding:6px 8px;font-size:12px}figcaption span{color:#9fb09c}'
    .'</style></head><body><h1>VERDIA Market — tercera tanda</h1><div class="rejilla">'
    .$filas.'</div></body></html>';

file_put_contents(DESTINO.'/hoja.html', $html);

echo count($seleccion)." candidatas en la hoja\n";
