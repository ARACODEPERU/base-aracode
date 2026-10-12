<?php

/*
 * Buscador de fotos para la demo «Restaurante y carta digital» en Wikimedia
 * Commons.
 *
 * Commons permite pedir una miniatura del ancho que se necesite (aquí 1280 px,
 * por debajo del máximo de 1600 del banco) y devuelve la licencia y el autor en
 * los metadatos, que es justo lo que pide CREDITOS.md.
 *
 * Uso: php storage/tmp/fotos-restaurante-commons.php <temas.json> [inicio] [carpeta]
 * Salida: storage/tmp/candidatas-commons/{n}.jpg + datos.json + hoja.html
 *
 * El archivo de temas es un JSON con {"grupo": ["búsqueda", ...]} para poder
 * repetir la búsqueda con otros términos sin tocar el script.
 */

const AGENTE = 'ARACODE-BancoPlantillas/1.0 (demo de plantilla; soporte@aracodeperu.com)';
const ANCHO = 1280;

$archivoTemas = $argv[1] ?? __DIR__.'/restaurante-temas.json';
$inicio = (int) ($argv[2] ?? 0);
$carpeta = $argv[3] ?? 'candidatas-commons';

define('SALIDA', __DIR__.'/'.$carpeta);

$temas = json_decode(file_get_contents($archivoTemas), true);

function consultar(string $url): ?array
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

    return $ok && $estado === 200 && filesize($destino) > 30000;
}

if (! is_dir(SALIDA)) {
    mkdir(SALIDA, 0777, true);
}

$indice = $inicio;
$filas = [];
$vistos = [];

foreach ($temas as $grupo => $busquedas) {
    foreach ($busquedas as $busqueda) {
        $lista = consultar('https://commons.wikimedia.org/w/api.php?'.http_build_query([
            'action' => 'query',
            'format' => 'json',
            'generator' => 'search',
            'gsrsearch' => 'filetype:bitmap '.$busqueda,
            'gsrnamespace' => 6,
            'gsrlimit' => 12,
            'prop' => 'imageinfo',
            'iiprop' => 'url|size|extmetadata',
            'iiurlwidth' => ANCHO,
        ]));

        foreach ($lista['query']['pages'] ?? [] as $pagina) {
            $info = $pagina['imageinfo'][0] ?? null;

            if ($info === null || isset($vistos[$pagina['title']])) {
                continue;
            }

            $meta = $info['extmetadata'] ?? [];
            $licencia = trim(strip_tags($meta['LicenseShortName']['value'] ?? ''));
            $ancho = (int) ($info['width'] ?? 0);
            $alto = (int) ($info['height'] ?? 0);

            // Solo licencias sin obligaciones y material grande de verdad: la
            // miniatura de Commons nunca aumenta la foto original.
            if (! preg_match('/^(CC0|Public domain|PD|CC PD)/i', $licencia)) {
                continue;
            }

            if ($ancho < 1400 || $alto < 900) {
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

            $vistos[$pagina['title']] = true;
            $indice++;
            $archivo = sprintf('%s/%03d.jpg', SALIDA, $indice);

            if (! descargar($info['thumburl'] ?? $info['url'], $archivo)) {
                $indice--;

                continue;
            }

            $filas[] = [
                'n' => $indice,
                'grupo' => $grupo,
                'busqueda' => $busqueda,
                'orientacion' => $orientacion,
                'original' => $ancho.'x'.$alto,
                'titulo' => $pagina['title'],
                'autor' => trim(strip_tags($meta['Artist']['value'] ?? '')),
                'licencia' => $licencia,
                'ficha' => $info['descriptionurl'] ?? '',
                'archivo' => $archivo,
                'medidas' => implode('x', array_slice(getimagesize($archivo), 0, 2)),
                'bytes' => filesize($archivo),
            ];
        }
    }
}

file_put_contents(SALIDA.'/datos.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Candidatas Commons</title>'
    .'<style>body{font:14px system-ui;background:#111;color:#eee;margin:20px}figure{margin:0 0 24px;border-bottom:1px solid #444;padding-bottom:16px}'
    .'img{max-width:760px;display:block;margin-bottom:8px}code{color:#ffd79a}</style></head><body><h1>Candidatas Commons</h1>';

foreach ($filas as $fila) {
    $html .= sprintf(
        '<figure><img src="%1$03d.jpg" alt="%2$s" loading="lazy"><figcaption><strong>#%1$03d</strong> · %3$s · %4$s · %5$s · %6$s<br>%2$s<br>%7$s · %8$s<br><code>%9$s</code></figcaption></figure>',
        $fila['n'],
        htmlspecialchars($fila['titulo']),
        htmlspecialchars($fila['grupo']),
        htmlspecialchars($fila['orientacion']),
        htmlspecialchars($fila['original']),
        htmlspecialchars($fila['licencia']),
        htmlspecialchars($fila['autor']),
        htmlspecialchars($fila['medidas']),
        htmlspecialchars($fila['ficha'])
    );
}

file_put_contents(SALIDA.'/hoja.html', $html.'</body></html>');

echo count($filas)." candidatas de Commons\n";
