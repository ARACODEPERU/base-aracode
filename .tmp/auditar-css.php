<?php
/*
 * Auditoría de hojas de estilo: qué archivos .css de resources/css y public/css
 * menciona el código (vistas, JS, configuración, builds) y cuáles no.
 *
 * Solo lectura: no borra nada.
 */

$raiz = dirname(__DIR__);

// Hojas a revisar.
$hojas = [];
foreach (['resources/css', 'public/css'] as $base) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz.'/'.$base, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $archivo) {
        if ($archivo->isFile() && strtolower($archivo->getExtension()) === 'css') {
            $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz) + 1));
            $hojas[$rel] = $archivo->getPathname();
        }
    }
}
ksort($hojas);

// Dónde se busca: código del sitio y configuración (nada de dependencias).
$zonas = ['resources/views', 'resources/js', 'resources/css', 'app', 'config', 'routes', 'tests', 'database', 'public/js'];

$extensiones = ['php', 'blade.php', 'js', 'mjs', 'cjs', 'json', 'css', 'vue', 'scss', 'webmanifest'];

$omitir = ['vendor', 'node_modules', '.git', 'public/build', 'public/storage', 'public/EditorAracode'];

$archivos = [];

foreach ($zonas as $zona) {
    $rutaZona = $raiz.'/'.$zona;
    if (! is_dir($rutaZona)) {
        continue;
    }
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rutaZona, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $archivo) {
        if (! $archivo->isFile()) {
            continue;
        }
        $ruta = str_replace('\\', '/', $archivo->getPathname());
        foreach ($omitir as $tramo) {
            if (str_contains($ruta, '/'.$tramo.'/')) {
                continue 2;
            }
        }
        $nombre = $archivo->getFilename();
        $ok = false;
        foreach ($extensiones as $ext) {
            if (str_ends_with($nombre, '.'.$ext)) {
                $ok = true;
                break;
            }
        }
        if ($ok && $archivo->getSize() < 3_000_000) {
            $archivos[] = $ruta;
        }
    }
}

foreach (['vite.config.js', 'tailwind.config.js', 'tailwind.public.config.js', 'postcss.config.js', 'package.json', 'composer.json', 'webpack.mix.js'] as $suelto) {
    if (is_file($raiz.'/'.$suelto)) {
        $archivos[] = $raiz.'/'.$suelto;
    }
}

$archivos = array_values(array_unique($archivos));

$resultado = [];

foreach ($hojas as $rel => $absoluto) {
    $nombreArchivo = basename($rel);
    $subruta = str_contains($rel, 'resources/css/') ? substr($rel, strlen('resources/css/')) : $nombreArchivo;

    // Patrones que cuentan como referencia real a esta hoja.
    $patrones = [
        $rel,                          // resources/css/aracodito.css
        'css/'.$nombreArchivo,          // css/aracodito.css (asset())
        '//'.$nombreArchivo,            // raro, pero cubre URLs con host
    ];

    foreach ($patrones as $i => $p) {
        $patrones[$i] = preg_quote($p, '#');
    }

    if ($subruta !== $nombreArchivo) {
        $patrones[] = preg_quote($subruta, '#');
    }

    if (in_array($nombreArchivo, ['app.css', 'website.css', 'colegio.css', 'kapta.css', 'kirafact.css', 'pichanguero.css', 'plantillas.css'], true)
        && str_starts_with($rel, 'resources/css/')) {
        // Los nombres repetidos (app.css, website.css, plantillas.css…) solo
        // cuentan si se nombran con su carpeta: «css/app.css», «plantillas/plantillas.css».
        $patrones = array_values(array_filter($patrones, fn ($p) => $p !== preg_quote($nombreArchivo, '#')));
    }

    $usos = [];

    foreach ($archivos as $rutaArchivo) {
        if (str_replace('\\', '/', $rutaArchivo) === str_replace('\\', '/', $absoluto)) {
            continue;
        }

        $lineas = @file($rutaArchivo);
        if (! $lineas) {
            continue;
        }

        foreach ($lineas as $n => $linea) {
            foreach ($patrones as $p) {
                if (preg_match('#'.$p.'#i', $linea)) {
                    $usos[] = str_replace('\\', '/', substr($rutaArchivo, strlen($raiz) + 1)).':'.($n + 1).'  '.trim(mb_substr($linea, 0, 150));
                    continue 2;
                }
            }
        }
    }

    $resultado[$rel] = $usos;
}

foreach ($resultado as $rel => $usos) {
    echo "\n=== ".$rel." ===\n";
    if (! $usos) {
        echo "  SIN REFERENCIAS\n";
        continue;
    }
    foreach (array_slice($usos, 0, 6) as $uso) {
        echo '  '.$uso."\n";
    }
    if (count($usos) > 6) {
        echo '  … y '.(count($usos) - 6)." más\n";
    }
}
