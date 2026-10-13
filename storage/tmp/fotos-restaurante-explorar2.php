<?php

/*
 * Segunda pasada del explorador: consultas pensadas para la foto VERTICAL de la
 * sección de confianza (una persona trabajando: chef, camarero, barista) y para
 * algunas horizontales más (cocina y mesa puesta).
 *
 * Uso: php storage/tmp/fotos-restaurante-explorar2.php
 * Salida: storage/tmp/candidatas-restaurante/1xx.jpg + fila2.json + hoja2.html
 */

const AGENTE = 'ARACODE-BancoPlantillas/1.0 (demo de plantilla; contacto: soporte@aracodeperu.com)';
const SALIDA = __DIR__.'/candidatas-restaurante';
const DESDE = 100;

$busquedas = [
    'vertical' => [
        'chef portrait',
        'cook kitchen',
        'waitress restaurant',
        'barista portrait',
        'pizza chef',
        'grill cook',
    ],
    'hero' => [
        'tapas table food',
        'restaurant interior tables',
    ],
    'detalle' => [
        'coffee cup table',
        'bread basket table',
        'ceviche plate',
    ],
];

function pedir(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => AGENTE,
    ]);
    $cuerpo = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($cuerpo === false || $estado !== 200) ? null : json_decode($cuerpo, true);
}

function descargar(string $url, string $destino): bool
{
    $ch = curl_init($url);
    $fh = fopen($destino, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => AGENTE,
    ]);
    $ok = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fh);

    return $ok && $estado === 200 && filesize($destino) > 20000;
}

$indice = DESDE;
$filas = [];

foreach ($busquedas as $grupo => $consultas) {
    foreach ($consultas as $consulta) {
        $datos = pedir('https://api.openverse.org/v1/images/?'.http_build_query([
            'q' => $consulta,
            'license' => 'cc0',
            'size' => 'large',
            'page_size' => 6,
        ]));

        if ($datos === null || empty($datos['results'])) {
            fwrite(STDERR, "sin resultados: {$consulta}\n");

            continue;
        }

        foreach ($datos['results'] as $foto) {
            $ancho = (int) ($foto['width'] ?? 0);
            $alto = (int) ($foto['height'] ?? 0);

            if ($ancho < 1000 || $alto < 1000) {
                continue;
            }

            $relacion = $ancho / max($alto, 1);
            $orientacion = $relacion >= 1.2 ? 'horizontal' : ($relacion <= 0.85 ? 'vertical' : 'cuadrada');

            if ($grupo === 'vertical' && $orientacion === 'horizontal') {
                continue;
            }

            if ($grupo !== 'vertical' && $orientacion === 'vertical') {
                continue;
            }

            $indice++;
            $archivo = sprintf('%s/%03d.jpg', SALIDA, $indice);

            if (! descargar($foto['thumbnail'] ?? $foto['url'], $archivo)) {
                $indice--;

                continue;
            }

            $filas[] = [
                'n' => $indice,
                'grupo' => $grupo,
                'consulta' => $consulta,
                'orientacion' => $orientacion,
                'medidas' => $ancho.'x'.$alto,
                'titulo' => $foto['title'] ?? '',
                'autor' => $foto['creator'] ?? '',
                'fuente' => $foto['source'] ?? '',
                'licencia' => strtoupper($foto['license'] ?? '').' '.($foto['license_version'] ?? ''),
                'ficha' => $foto['foreign_landing_url'] ?? '',
                'imagen' => $foto['url'] ?? '',
                'archivo' => $archivo,
                'bytes' => filesize($archivo),
            ];
        }
    }
}

file_put_contents(SALIDA.'/datos2.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$html = "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"utf-8\"><title>Candidatas 2</title><style>body{font:14px system-ui;background:#111;color:#eee;margin:20px}figure{margin:0 0 22px;border-bottom:1px solid #444;padding-bottom:14px}img{max-width:760px;display:block;margin-bottom:8px}a{color:#7fd}code{color:#ffd79a}</style></head><body><h1>Candidatas (CC0) — segunda pasada</h1>";

foreach ($filas as $fila) {
    $html .= sprintf(
        "<figure><img src=\"%s\" alt=\"%s\"><figcaption><strong>#%03d</strong> · %s · %s · %s · %s<br>%s — %s<br><code>%s</code></figcaption></figure>",
        htmlspecialchars($fila['archivo']),
        htmlspecialchars($fila['titulo']),
        $fila['n'],
        htmlspecialchars($fila['grupo']),
        htmlspecialchars($fila['orientacion']),
        htmlspecialchars($fila['medidas']),
        htmlspecialchars($fila['licencia']),
        htmlspecialchars($fila['titulo']),
        htmlspecialchars($fila['autor'].' · '.$fila['fuente']),
        htmlspecialchars($fila['ficha'])
    );
}

file_put_contents(SALIDA.'/hoja2.html', $html.'</body></html>');

echo count($filas)." candidatas nuevas\n";
