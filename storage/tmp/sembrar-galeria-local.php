<?php

/**
 * Carga en local la galería real de avc.pichanguero.com para revisar la
 * sección de galería de la landing. Las imágenes ya se descargaron a
 * storage/app/public/socialevents/galleries/1.
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMedia;
use Modules\Socialevents\Support\TournamentLandingCache;

$edicion = EventEdition::where('public_slug', 'edicion-2026-2026')->firstOrFail();

$items = [
    ['2026-10-18', 'socialevents/galleries/1/2026-10-18/1792192584_915c70d75c2a.png', 'image/png', null],
    ['2026-10-11', 'socialevents/galleries/1/2026-10-11/1792192568_e47c6e884ebc.jpg', 'image/jpeg', 'Promo 1998 vs Promoción 2006'],
    ['2026-10-11', 'socialevents/galleries/1/2026-10-11/1792192530_633a18a51060.jpg', 'image/jpeg', 'Promocion 2008 vs Promo 2001-2002'],
    ['2026-10-11', 'socialevents/galleries/1/2026-10-11/1792192507_421e51126167.jpg', 'image/jpeg', 'Promocion 2008 vs Promo 2001-2002'],
    ['2026-09-27', 'socialevents/galleries/1/2026-09-27/1790617983_046f61deba50.jpg', 'image/jpeg', null],
    ['2026-09-27', 'socialevents/galleries/1/2026-09-27/1790301512_7ddfa28d0362.jpeg', 'image/jpeg', null],
];

EventEditionMedia::where('edition_id', $edicion->id)->delete();

$creados = 0;

foreach ($items as [$fecha, $ruta, $mime, $etiqueta]) {
    $partidoId = null;

    if ($etiqueta) {
        [$local, $visitante] = array_map('trim', explode(' vs ', $etiqueta));

        $partido = EventEditionMatch::where('edition_id', $edicion->id)
            ->whereHas('equipolocal', fn ($q) => $q->where('name', $local))
            ->whereHas('equipovisitante', fn ($q) => $q->where('name', $visitante))
            ->first();

        $partidoId = $partido?->id;
    }

    EventEditionMedia::create([
        'edition_id' => $edicion->id,
        'match_id' => $partidoId,
        'media_date' => $fecha,
        'type' => 'image',
        'file_path' => $ruta,
        'file_name' => basename($ruta),
        'mime_type' => $mime,
    ]);

    $creados++;
}

TournamentLandingCache::forget((int) $edicion->id);

echo 'Medios de galería: ' . $creados . PHP_EOL;
