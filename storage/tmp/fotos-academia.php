<?php

/*
|--------------------------------------------------------------------------
| Buscador temporal de fotos para la demo de la academia
|--------------------------------------------------------------------------
|
| Script de un solo uso, igual que el de la clínica: consulta la API de
| Openverse filtrando por licencias sin obligaciones (CC0 y dominio público),
| descarga las candidatas con resolución suficiente y arma una hoja de contacto
| para elegirlas mirándolas. Se borra al terminar.
|
*/

$destino = __DIR__.'/../../public/demos/academia-cursos/_tmp-fotos';
@mkdir($destino, 0777, true);

$consultas = [
    'aula' => 'students classroom',
    'laptops' => 'students laptop computers class',
    'profesora' => 'teacher helping student',
    'laboratorio' => 'computer lab university',
    'grupo' => 'study group students table',
    'egresados' => 'graduation students ceremony',
    'biblioteca' => 'university library students',
    'taller' => 'workshop training class adults',
    'online' => 'online class laptop home',
    'campus' => 'university campus building',
];

$bajadas = [];
$n = 0;

foreach ($consultas as $etiqueta => $q) {
    // La coma del filtro de licencia no se codifica: con %2C la API devuelve 0.
    $url = 'https://api.openverse.org/v1/images/?q='.urlencode($q)
        .'&license=cc0,pdm&size=large&page_size=5';

    $json = shell_exec('curl -s --max-time 25 -H "Accept: application/json" '.escapeshellarg($url));
    $datos = json_decode((string) $json, true);

    foreach (array_slice($datos['results'] ?? [], 0, 5) as $r) {
        $ancho = (int) ($r['width'] ?? 0);
        $alto = (int) ($r['height'] ?? 0);

        if ($ancho < 1200 || $alto < 800) {
            continue;
        }

        $n++;
        $archivo = sprintf('%02d-%s.img', $n, $etiqueta);
        $ruta = $destino.'/'.$archivo;

        shell_exec('curl -sL --max-time 30 -o '.escapeshellarg($ruta).' '.escapeshellarg($r['url']));

        if (! is_file($ruta) || filesize($ruta) < 25000) {
            @unlink($ruta);
            $n--;

            continue;
        }

        $bajadas[] = [
            'archivo' => $archivo,
            'titulo' => mb_substr((string) ($r['title'] ?? ''), 0, 55),
            'autor' => mb_substr((string) ($r['creator'] ?? ''), 0, 26),
            'licencia' => (string) ($r['license'] ?? ''),
            'ancho' => $ancho,
            'alto' => $alto,
            'kb' => round(filesize($ruta) / 1024),
        ];

        if (count($bajadas) >= 20) {
            break 2;
        }
    }
}

$filas = '';

foreach ($bajadas as $b) {
    $filas .= '<figure><img src="'.htmlspecialchars($b['archivo']).'" loading="lazy">'
        .'<figcaption><strong>'.htmlspecialchars($b['archivo']).'</strong> ('.$b['ancho'].'×'.$b['alto'].', '.$b['kb'].' KB)<br>'
        .htmlspecialchars($b['titulo']).'<br>'.htmlspecialchars($b['autor']).' · '.$b['licencia'].'</figcaption></figure>';
}

file_put_contents($destino.'/index.html', '<!doctype html><html lang="es"><head><meta charset="utf-8">'
    .'<title>Candidatas para la academia</title><style>body{font:13px/1.4 system-ui;margin:0;background:#0b1220;color:#e8eefc}'
    .'h1{font-size:16px;padding:16px 20px;margin:0;background:#122040}main{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding:20px}'
    .'figure{margin:0;background:#16233f;border-radius:10px;overflow:hidden}img{display:block;width:100%;height:190px;object-fit:cover}'
    .'figcaption{padding:8px 10px;font-size:12px;color:#b9c8e4}</style></head><body>'
    .'<h1>'.count($bajadas).' candidatas — elige por número</h1><main>'.$filas.'</main></body></html>');

echo 'candidatas: '.count($bajadas).PHP_EOL;

foreach ($bajadas as $b) {
    echo str_pad($b['archivo'], 18).str_pad($b['ancho'].'x'.$b['alto'], 12).str_pad($b['kb'].' KB', 9).$b['titulo'].PHP_EOL;
}
