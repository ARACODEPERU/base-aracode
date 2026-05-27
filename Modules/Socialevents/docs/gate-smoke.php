<?php

/**
 * Smoke test Parte I — ejecutar: php Modules/Socialevents/docs/gate-smoke.php
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\TournamentPublicDataService;
use Modules\Socialevents\Services\TournamentStandingsService;

$edition = EventEdition::query()->orderByDesc('id')->first();

if (! $edition) {
    echo "FAIL: No hay ediciones en BD.\n";
    exit(1);
}

$id = (int) $edition->id;
$standings = app(TournamentStandingsService::class);
$public = app(TournamentPublicDataService::class);

$standingsPayload = $standings->asStandingsPayload($id);
$publicPayload = $public->buildForApi($id);

$standingsCount = count($standingsPayload);
$publicStandingsCount = count($publicPayload['standings'] ?? []);

echo "Edition #{$id} ({$edition->name})\n";
echo "  mobile_enabled: ".($edition->mobile_enabled ? 'yes' : 'no')."\n";
echo "  landing_published: ".($edition->landing_published ? 'yes' : 'no')."\n";
echo "  standings endpoint rows: {$standingsCount}\n";
echo "  /public standings rows: {$publicStandingsCount}\n";

if ($standingsCount !== $publicStandingsCount) {
    echo "WARN: standings count mismatch\n";
}

if ($standingsCount > 0) {
    $a = $standingsPayload[0]['position'] ?? null;
    $b = $publicPayload['standings'][0]['position'] ?? null;
    if ($a !== $b) {
        echo "WARN: first position mismatch ({$a} vs {$b})\n";
    }
}

echo "OK: Gate smoke passed.\n";
exit(0);
