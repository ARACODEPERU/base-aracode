<?php

/*
 * Regenera una sola hoja de contacto con las candidatas de las dos pasadas,
 * usando rutas relativas para poder abrirla por HTTP (storage/tmp no es público
 * y no debe serlo: la hoja se sirve con un php -S temporal).
 */

$raiz = __DIR__.'/candidatas-restaurante';
$filas = [];

foreach (['datos.json', 'datos2.json'] as $json) {
    if (is_file($raiz.'/'.$json)) {
        $filas = array_merge($filas, json_decode(file_get_contents($raiz.'/'.$json), true));
    }
}

usort($filas, fn ($a, $b) => $a['n'] <=> $b['n']);

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Candidatas restaurante</title>'
    .'<style>body{font:14px system-ui;background:#111;color:#eee;margin:20px}figure{margin:0 0 24px;border-bottom:1px solid #444;padding-bottom:16px}'
    .'img{max-width:760px;display:block;margin-bottom:8px}code{color:#ffd79a}.v{color:#7fe3d2}</style></head><body>'
    .'<h1>Candidatas CC0 — restaurante</h1>';

foreach ($filas as $fila) {
    $html .= sprintf(
        '<figure><img src="candidatas-restaurante/%1$03d.jpg" alt="%2$s" loading="lazy">'
        .'<figcaption><strong>#%1$03d</strong> · %3$s · <span class="v">%4$s</span> · %5$s · %6$s<br>%2$s — %7$s<br><code>%8$s</code></figcaption></figure>',
        $fila['n'],
        htmlspecialchars($fila['titulo']),
        htmlspecialchars($fila['grupo']),
        htmlspecialchars($fila['orientacion']),
        htmlspecialchars($fila['medidas']),
        htmlspecialchars($fila['licencia']),
        htmlspecialchars($fila['autor'].' · '.$fila['fuente']),
        htmlspecialchars($fila['ficha'])
    );
}

file_put_contents($raiz.'/hoja.html', $html.'</body></html>');

echo count($filas)." candidatas en la hoja\n";
