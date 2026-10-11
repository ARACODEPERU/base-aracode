<?php

/*
|--------------------------------------------------------------------------
| Buscador temporal de fotos para el hero de la academia
|--------------------------------------------------------------------------
|
| Script de un solo uso. Las cinco fotos que ya tiene la demo se usan en las
| secciones («cómo se enseña» y certificación), así que el slider del hero
| necesita fotos propias: repetir la misma imagen arriba y abajo se nota.
|
| Consulta Openverse filtrando por licencias sin obligaciones (CC0 y dominio
| público), descarga candidatas horizontales y guarda su ficha de origen. Se
| borra al terminar.
|
*/

$destino = __DIR__.'/candidatas-academia';
@mkdir($destino, 0777, true);

$consultas = [
    'estudiantes' => 'students',
    'aula' => 'classroom',
    'clase' => 'lecture hall students',
    'biblioteca' => 'library study',
    'laptops' => 'students laptop',
    'profesor' => 'teacher class',
    'universidad' => 'college students',
    'cuaderno' => 'notebook study desk',
    'trabajo' => 'teamwork office people',
    'idiomas' => 'language class adults',
];

$bajadas = [];
$n = 0;

foreach ($consultas as $etiqueta => $q) {
    // La coma del filtro de licencia no se codifica: con %2C la API devuelve 0.
    $url = 'https://api.openverse.org/v1/images/?q='.urlencode($q)
        .'&license=cc0,pdm&size=large&page_size=20';

    $json = shell_exec('curl -s --max-time 25 -H "Accept: application/json" '.escapeshellarg($url));
    $datos = json_decode((string) $json, true);

    $encontradas = is_array($datos['results'] ?? null) ? count($datos['results']) : 0;
    echo str_pad($etiqueta, 14).$encontradas.' resultados'.PHP_EOL;

    foreach (($datos['results'] ?? []) as $r) {
        $ancho = (int) ($r['width'] ?? 0);
        $alto = (int) ($r['height'] ?? 0);

        // Solo horizontales con margen para recortar a 16/9 sin comerse la foto.
        if ($ancho < 1400 || $alto < 900 || $ancho < $alto * 1.25) {
            continue;
        }

        $n++;
        $archivo = sprintf('%02d-%s.img', $n, $etiqueta);
        $ruta = $destino.'/'.$archivo;

        shell_exec('curl -sL --max-time 30 -o '.escapeshellarg($ruta).' '.escapeshellarg($r['url']));

        if (! is_file($ruta) || filesize($ruta) < 40000) {
            @unlink($ruta);
            $n--;

            continue;
        }

        $bajadas[] = [
            'archivo' => $archivo,
            'consulta' => $q,
            'titulo' => mb_substr((string) ($r['title'] ?? ''), 0, 60),
            'autor' => mb_substr((string) ($r['creator'] ?? ''), 0, 28),
            'licencia' => (string) ($r['license'] ?? ''),
            'origen' => (string) ($r['foreign_landing_url'] ?? ''),
            'ancho' => $ancho,
            'alto' => $alto,
            'kb' => round(filesize($ruta) / 1024),
        ];

        if (count($bajadas) >= 18) {
            break 2;
        }
    }
}

file_put_contents($destino.'/fichas.json', json_encode($bajadas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo 'candidatas: '.count($bajadas).PHP_EOL;

foreach ($bajadas as $b) {
    echo str_pad($b['archivo'], 20).str_pad($b['ancho'].'x'.$b['alto'], 12).str_pad($b['kb'].' KB', 9).$b['titulo'].PHP_EOL;
}
