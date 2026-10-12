<?php
// Mide las imágenes de los artículos del blog: sirve para saber con qué
// proporción llegan y cuánto recorta la tarjeta (16/9 con object-fit: cover).
require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$articulos = \Modules\Blog\Entities\BlogArticle::where('status', true)
    ->orderByDesc('created_at')
    ->take(10)
    ->get(['title', 'imagen']);

foreach ($articulos as $articulo) {
    $ruta = $articulo->imagen;
    $absoluta = str_starts_with((string) $ruta, 'http') ? null : public_path(ltrim((string) $ruta, '/'));

    $medidas = $absoluta && is_file($absoluta) ? @getimagesize($absoluta) : null;

    printf(
        "%-46s %-44s %s\n",
        mb_substr($articulo->title, 0, 44),
        $ruta ?: '(sin imagen)',
        $medidas ? sprintf('%d × %d · %.3f', $medidas[0], $medidas[1], $medidas[0] / $medidas[1]) : 'no se pudo leer'
    );
}
