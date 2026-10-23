<?php

/**
 * Recrea en local la edición real de avc.pichanguero.com con los datos
 * extraídos de su landing pública. Es una copia de prueba: se elimina al
 * terminar la revisión.
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentLandingPresenter;

$datos = json_decode(file_get_contents(__DIR__ . '/datos-produccion.json'), true);

if (! is_array($datos)) {
    fwrite(STDERR, "No se pudo leer datos-produccion.json\n");
    exit(1);
}

$slug = 'edicion-2026-2026';

$previa = EventEdition::where('public_slug', $slug)->first();
if ($previa) {
    TournamentLandingCache::forget((int) $previa->id);
    EventEditionMatch::where('edition_id', $previa->id)->delete();
    EventEditionTeam::where('edition_id', $previa->id)->delete();
    $orgId = $previa->evento?->organization_id;
    if ($orgId) {
        EventTeam::where('organization_id', $orgId)->delete();
        SocialeventOrganization::where('id', $orgId)->delete();
    }
    $eventoId = $previa->event_id;
    $previa->delete();
    EvenEvent::where('id', $eventoId)->delete();
}

$org = SocialeventOrganization::create([
    'name' => 'Promocion 2003',
    'status' => 'active',
    'trial_started_at' => now(),
    'trial_days' => 30,
    'trial_ends_at' => now()->addDays(30),
    'access_until' => now()->addMonth(),
    'match_rate' => 5,
    'prepaid_balance' => 1000,
]);

$evento = EvenEvent::create([
    'title' => $datos['titulo'],
    'organization_id' => $org->id,
    'description' => $datos['descripcion'],
]);

$premios = ['first' => null, 'second' => null, 'third' => null];
$claves = ['first', 'second', 'third'];
foreach ($datos['premios'] as $i => $premio) {
    if (! isset($claves[$i])) {
        continue;
    }

    $valor = $premio['valor'];
    $partes = array_map('trim', explode('+', $valor, 2));
    $dinero = null;

    if (preg_match('/S\/\s*([\d.,]+)/', $partes[0], $m)) {
        $dinero = (float) str_replace(',', '', $m[1]);
    }

    $premios[$claves[$i]] = array_filter([
        'money' => $dinero,
        'gift' => $partes[1] ?? null,
    ]);
}

$edicion = EventEdition::create([
    'event_id' => $evento->id,
    'year' => 2026,
    'name' => $datos['edicion'],
    'start_date' => '2026-08-01',
    'landing_published' => true,
    'public_slug' => $slug,
    'mobile_enabled' => true,
    'inscription_fee' => 200,
    'contact_name' => $datos['contacto'][0] ?? 'Promocion 2003',
    'contact_phone' => $datos['contacto'][1] ?? null,
    'contact_whatsapp' => $datos['contacto'][1] ?? null,
    'prize_details' => array_filter($premios),
]);

$equipos = [];
foreach ($datos['equipos'] as $i => $equipo) {
    $team = EventTeam::create([
        'organization_id' => $org->id,
        'name' => $equipo['nombre'],
        'short_name' => mb_substr($equipo['nombre'], 0, 3),
        'status' => 1,
    ]);

    EventEditionTeam::create([
        'edition_id' => $edicion->id,
        'team_id' => $team->id,
        'matches_played' => $equipo['pj'],
        'matches_won' => $equipo['pg'],
        'matches_drawn' => $equipo['pe'],
        'matches_lost' => $equipo['pp'],
        'goals_for' => $equipo['gf'],
        'goals_against' => $equipo['gc'],
        'goal_difference' => $equipo['dg'],
        'points' => $equipo['pts'],
        'bonus_points' => $equipo['extra'],
        'rank' => $i + 1,
    ]);

    $equipos[$equipo['nombre']] = $team;
}

$omitidos = 0;
foreach ($datos['partidos'] as $i => $partido) {
    $local = $equipos[$partido['local']] ?? null;
    $visitante = $equipos[$partido['visitante']] ?? null;

    if (! $local || ! $visitante) {
        // Cruces con equipos que ya no figuran en la tabla real.
        $omitidos++;
        continue;
    }

    $marcador = $partido['marcador'];
    $estado = mb_strtolower($partido['estado']);
    $terminado = str_contains($estado, 'finalizado');
    $enVivo = str_contains($estado, 'vivo');
    $goles = [0, 0];

    if (preg_match('/(\d+)\s*-\s*(\d+)/', $marcador, $m)) {
        $goles = [(int) $m[1], (int) $m[2]];
    }

    EventEditionMatch::create([
        'edition_id' => $edicion->id,
        'team_h_id' => $local->id,
        'team_a_id' => $visitante->id,
        'phase' => str_contains((string) ($partido['fase'] ?? ''), 'Eliminaciones') ? 'playoffs' : 'everyone',
        'round_number' => $partido['fecha'] ?: (intdiv($i, 4) + 1),
        'match_date' => now()->subDays(30)->addDays($i),
        'location' => 'Cancha La Unión',
        'score_h' => $goles[0],
        'score_a' => $goles[1],
        'status' => $enVivo ? 'live' : ($terminado ? 'finished' : 'pending'),
    ]);
}

TournamentLandingCache::forget((int) $edicion->id);

echo 'Edición local con datos de producción: ID ' . $edicion->id . PHP_EOL;
echo 'Equipos: ' . count($equipos) . ' · Partidos: ' . (count($datos['partidos']) - $omitidos) . ' (omitidos: ' . $omitidos . ')' . PHP_EOL;
echo 'App visible: ' . (TournamentLandingPresenter::showAppDownload($edicion) ? 'sí' : 'no') . PHP_EOL;
echo 'URL: ' . $edicion->landingUrl() . PHP_EOL;
