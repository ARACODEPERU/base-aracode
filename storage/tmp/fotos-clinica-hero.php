<?php

/*
|--------------------------------------------------------------------------
| Buscador temporal de una foto para el slider de la clínica
|--------------------------------------------------------------------------
|
| Script de un solo uso. `consulta.webp` resultó ser la misma imagen que
| `portada.webp` (hash de luminancia idéntico), así que el slider repetía foto:
| hay que sustituirla por una distinta. Además identifica de qué ficha de
| Openverse salió `diagnostico.webp`, que tampoco quedó documentada.
|
| Consulta Openverse filtrando por licencias sin obligaciones (CC0 y dominio
| público), descarga candidatas horizontales y guarda su ficha de origen. Se
| borra al terminar.
|
*/

$destino = __DIR__.'/candidatas-clinica';
@mkdir($destino, 0777, true);

$hoja = __DIR__.'/../../public/demos/_candidatas-clinica';
@mkdir($hoja, 0777, true);

$consultas = [
    'consultorio' => 'dental clinic',
    'dentista' => 'dentist patient',
    'sillon' => 'dentist chair',
    'tratamiento' => 'dental treatment',
    'radiografia' => 'dental x-ray',
    'control' => 'dental checkup',
    'instrumental' => 'dental instruments',
    'limpieza' => 'dental hygiene',
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
            'titulo' => mb_substr((string) ($r['title'] ?? ''), 0, 70),
            'autor' => mb_substr((string) ($r['creator'] ?? ''), 0, 30),
            'licencia' => (string) ($r['license'] ?? '').' '.($r['license_version'] ?? ''),
            'origen' => (string) ($r['foreign_landing_url'] ?? ''),
            'proveedor' => (string) ($r['source'] ?? ''),
            'ancho' => $ancho,
            'alto' => $alto,
            'kb' => round(filesize($ruta) / 1024),
        ];

        if (count($bajadas) >= 20) {
            break 2;
        }
    }
}

file_put_contents($destino.'/fichas.json', json_encode($bajadas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo PHP_EOL.'candidatas: '.count($bajadas).PHP_EOL;

// --- Comparación con las fotos publicadas -----------------------------------
function hashImagen(string $ruta): ?array
{
    $img = @imagecreatefromstring(file_get_contents($ruta));

    if (! $img) {
        return null;
    }

    $m = imagecreatetruecolor(8, 8);
    imagecopyresampled($m, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));

    $valores = [];

    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $c = imagecolorat($m, $x, $y);
            $valores[] = (($c >> 16 & 0xFF) * 0.299) + (($c >> 8 & 0xFF) * 0.587) + (($c & 0xFF) * 0.114);
        }
    }

    $media = array_sum($valores) / count($valores);

    return array_map(fn ($v) => $v >= $media ? 1 : 0, $valores);
}

$publicadas = [
    'portada' => __DIR__.'/../../public/demos/clinica-dental/img/portada.webp',
    'diagnostico' => __DIR__.'/../../public/demos/clinica-dental/img/diagnostico.webp',
];

$hashesPublicadas = array_map(fn ($r) => hashImagen($r), $publicadas);

// --- Hoja de contacto -------------------------------------------------------
$minis = [];

foreach ($bajadas as $b) {
    $ruta = $destino.'/'.$b['archivo'];
    $img = @imagecreatefromstring(file_get_contents($ruta));

    if (! $img) {
        continue;
    }

    $ancho = imagesx($img);
    $alto = imagesy($img);
    $miniatura = imagecreatetruecolor(480, (int) round(480 * $alto / $ancho));
    imagecopyresampled($miniatura, $img, 0, 0, 0, 0, 480, imagesy($miniatura), $ancho, $alto);
    imagewebp($miniatura, $hoja.'/'.$b['archivo'].'.webp', 82);

    $hash = hashImagen($ruta);
    $notas = [];

    foreach ($hashesPublicadas as $nombre => $objetivo) {
        if (! $objetivo || ! $hash) {
            continue;
        }

        $d = 0;
        foreach ($hash as $i => $bit) {
            $d += $bit === $objetivo[$i] ? 0 : 1;
        }

        if ($d <= 10) {
            $notas[] = "≈ $nombre (d=$d)";
        }
    }

    if ($notas) {
        $b['nota'] = implode(', ', $notas);
        echo '  ⚠ '.$b['archivo'].' '.$b['nota'].' — '.$b['titulo'].PHP_EOL;
    }

    $minis[$b['archivo']] = $b;
}

// La ficha completa con la nota de duplicado.
file_put_contents($destino.'/fichas.json', json_encode(array_values($minis), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$html = '<!doctype html><meta charset="utf-8"><title>Candidatas clínica</title>
<style>body{font-family:system-ui;margin:1.5rem;background:#f4f6f8;color:#111}
ul{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:1rem;padding:0;list-style:none;margin:0}
li{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.12)}
img{display:block;width:100%}
p{margin:0;padding:.5rem .75rem;font-size:.78rem;font-weight:700}
span{display:block;padding:0 .75rem .6rem;font-size:.72rem;color:#555;font-weight:400}</style>
<ul>';

foreach ($minis as $archivo => $b) {
    $html .= '<li><img src="'.basename($archivo).'.webp" alt=""><p>'.$archivo.' '.($b['nota'] ?? '').'</p>'
        .'<span>'.$b['ancho'].'×'.$b['alto'].' · '.$b['kb'].' KB · '.$b['proveedor'].' · '.htmlspecialchars($b['titulo'], ENT_QUOTES).'</span></li>';
}

$html .= '</ul>';
file_put_contents($hoja.'/index.html', $html);

echo PHP_EOL.'Hoja de contacto: public/demos/_candidatas-clinica/index.html'.PHP_EOL;

foreach ($minis as $b) {
    echo '  '.str_pad($b['archivo'], 22).str_pad($b['ancho'].'x'.$b['alto'], 12).str_pad($b['kb'].' KB', 9).$b['titulo'].PHP_EOL;
}
