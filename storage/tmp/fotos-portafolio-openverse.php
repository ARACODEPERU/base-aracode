<?php

/*
 * Buscador de fotos para la demo «Portafolio Creativo» en la API de Openverse.
 *
 * Commons por palabra clave devuelve patrimonio (mapas, grabados, retratos
 * antiguos) y un taller de diseño de hoy no aparece por ahí. Openverse reúne
 * Commons, Flickr, rawpixel y museos, y deja filtrar por licencia sin
 * obligaciones (CC0 y dominio público), que es la regla de CREDITOS.md.
 *
 * Dos diferencias con el buscador de Commons del banco:
 *   - El filtro de tamaño se mide por el lado LARGO: una vertical grande tiene
 *     el alto por encima de 1400 px aunque su ancho sea menor, y es justo la
 *     proporción que necesita la sección de confianza de esta demo.
 *   - Se comprueba el tamaño real del archivo descargado: la API anuncia
 *     medidas que a veces no coinciden con lo que baja.
 *
 * Uso: php storage/tmp/fotos-portafolio-openverse.php <temas.json> [inicio] [carpeta]
 * Salida: storage/tmp/<carpeta>/{n}.jpg + datos.json + hoja.html
 */

const AGENTE = 'ARACODE-BancoPlantillas/1.0 (demo de plantilla; soporte@aracodeperu.com)';

$archivoTemas = $argv[1] ?? __DIR__.'/portafolio-temas2.json';
$inicio = (int) ($argv[2] ?? 0);
$carpeta = $argv[3] ?? 'candidatas-portafolio-ov';

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
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => AGENTE,
    ]);
    $ok = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fh);

    return $ok && $estado === 200 && filesize($destino) > 60000;
}

if (! is_dir(SALIDA)) {
    mkdir(SALIDA, 0777, true);
}

$indice = $inicio;
$filas = [];
$vistos = [];

foreach ($temas as $grupo => $busquedas) {
    foreach ($busquedas as $busqueda) {
        $respuesta = consultar('https://api.openverse.org/v1/images/?'.http_build_query([
            'q' => $busqueda,
            'license' => 'cc0,pdm',
            'size' => 'large',
            'page_size' => 30,
        ]));

        foreach ($respuesta['results'] ?? [] as $item) {
            $ancho = (int) ($item['width'] ?? 0);
            $alto = (int) ($item['height'] ?? 0);
            $clave = $item['foreign_landing_url'] ?? $item['id'] ?? '';
            $url = $item['url'] ?? '';

            if ($clave === '' || $url === '' || isset($vistos[$clave])) {
                continue;
            }

            // Solo licencias sin obligaciones, igual que el resto del banco.
            if (! in_array($item['license'] ?? '', ['cc0', 'pdm'], true)) {
                continue;
            }

            $relacion = $ancho / max($alto, 1);
            $orientacion = $relacion >= 1.2 ? 'horizontal' : ($relacion <= 0.85 ? 'vertical' : 'cuadrada');

            if ($grupo === 'vertical') {
                if ($orientacion !== 'vertical' || $alto < 1400) {
                    continue;
                }
            } elseif ($orientacion !== 'horizontal' || $ancho < 1600 || $alto < 900) {
                continue;
            }

            $vistos[$clave] = true;
            $indice++;
            $archivo = sprintf('%s/%03d.jpg', SALIDA, $indice);

            if (! descargar($url, $archivo)) {
                $indice--;

                continue;
            }

            $medidas = getimagesize($archivo);

            // La descarga puede no coincidir con lo que anuncia la API.
            if ($medidas === false || max($medidas[0], $medidas[1]) < 1400 || min($medidas[0], $medidas[1]) < 800) {
                unlink($archivo);
                $indice--;

                continue;
            }

            if ($grupo === 'vertical' && $medidas[0] >= $medidas[1]) {
                unlink($archivo);
                $indice--;

                continue;
            }

            $filas[] = [
                'n' => $indice,
                'grupo' => $grupo,
                'busqueda' => $busqueda,
                'orientacion' => $medidas[0] / max($medidas[1], 1) >= 1.2 ? 'horizontal' : ($medidas[0] / max($medidas[1], 1) <= 0.85 ? 'vertical' : 'cuadrada'),
                'original' => $medidas[0].'x'.$medidas[1],
                'titulo' => $item['title'] ?? '',
                'autor' => $item['creator'] ?? '',
                'fuente' => $item['source'] ?? '',
                'licencia' => strtoupper($item['license'] ?? ''),
                'ficha' => $item['foreign_landing_url'] ?? '',
                'archivo' => $archivo,
                'medidas' => $medidas[0].'x'.$medidas[1],
                'bytes' => filesize($archivo),
            ];
        }
    }
}

file_put_contents(SALIDA.'/datos.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Candidatas Portafolio (Openverse)</title>'
    .'<style>body{font:14px system-ui;background:#111;color:#eee;margin:20px}figure{margin:0 0 24px;border-bottom:1px solid #444;padding-bottom:16px}'
    .'img{max-width:760px;display:block;margin-bottom:8px}code{color:#ffd79a}</style></head><body><h1>Candidatas Portafolio (Openverse)</h1>';

foreach ($filas as $fila) {
    $html .= sprintf(
        '<figure id="c%1$03d"><img src="%2$s" alt="%3$s" loading="lazy"><figcaption><strong>#%1$03d</strong> · %4$s · %5$s · %6$s · %7$s<br>%3$s · %8$s<br><code>%9$s</code></figcaption></figure>',
        $fila['n'],
        basename($fila['archivo']),
        htmlspecialchars($fila['titulo']),
        htmlspecialchars($fila['grupo']),
        htmlspecialchars($fila['orientacion']),
        htmlspecialchars($fila['original']),
        htmlspecialchars($fila['fuente'].' / '.$fila['licencia']),
        htmlspecialchars($fila['autor']),
        htmlspecialchars($fila['ficha'])
    );
}

file_put_contents(SALIDA.'/hoja.html', $html.'</body></html>');

echo count($filas)." candidatas de Openverse\n";
