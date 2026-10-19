<?php

/*
 * Hoja de contactos de todas las candidatas de NÉXORA MARKET, en una sola
 * página: cada carpeta de búsqueda en una fila y cada foto con su etiqueta
 * «carpeta#n», que es como se eligen después en fotos-nexora-preparar.php.
 *
 * Uso: php storage/tmp/nexora-hoja.php [grupo]
 * Salida: storage/tmp/nexora-hoja.html
 */

$grupo = $argv[1] ?? '';

$carpetas = array_values(array_filter(
    array_map('basename', glob(__DIR__.'/candidatas-nexora*', GLOB_ONLYDIR)),
    fn (string $carpeta) => is_file(__DIR__.'/'.$carpeta.'/datos.json')
));

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Candidatas NÉXORA</title>'
    .'<style>body{font:13px/1.4 system-ui;background:#0e1116;color:#e8eaf0;margin:16px}'
    .'h2{font-size:14px;margin:28px 0 8px;color:#ffb27a;border-bottom:1px solid #2a303c;padding-bottom:6px}'
    .'.rejilla{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}'
    .'figure{margin:0;background:#161b23;border-radius:6px;overflow:hidden}'
    .'img{width:100%;height:150px;object-fit:cover;display:block}'
    .'figcaption{padding:5px 6px;font-size:11px}'
    .'b{color:#7dd3a0}'
    .'span{display:block;color:#9aa4b5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'
    .'</style></head><body><h1>Candidatas NÉXORA Market</h1>';

$total = 0;

foreach ($carpetas as $carpeta) {
    $filas = json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true) ?: [];
    $filas = array_values(array_filter($filas, fn (array $fila) => $grupo === '' || $fila['grupo'] === $grupo));

    if ($filas === []) {
        continue;
    }

    $html .= '<h2>'.$carpeta.' · '.count($filas).' fotos</h2><div class="rejilla">';

    foreach ($filas as $fila) {
        $total++;
        $html .= sprintf(
            '<figure><img src="%s/%03d.jpg" alt="%s" loading="lazy"><figcaption><b>%s#%03d</b> · %s<span>%s</span></figcaption></figure>',
            rawurlencode($carpeta),
            $fila['n'],
            htmlspecialchars($fila['busqueda'], ENT_QUOTES),
            htmlspecialchars(substr($carpeta, 19), ENT_QUOTES),
            $fila['n'],
            htmlspecialchars($fila['grupo'], ENT_QUOTES),
            htmlspecialchars(mb_substr($fila['titulo'], 0, 60), ENT_QUOTES)
        );
    }

    $html .= '</div>';
}

file_put_contents(__DIR__.'/nexora-hoja.html', $html.'</body></html>');
printf("%d fotos en la hoja de contactos\n", $total);
