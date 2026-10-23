<?php

use Modules\Socialevents\Support\TournamentDateLabels;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Fechas de la landing en español
|--------------------------------------------------------------------------
|
| El domingo (dayOfWeek 0 en Carbon) se colaba en inglés en la galería.
| Estas pruebas fijan los nombres de día para toda la semana.
|
*/

uses(TestCase::class);

it('escribe el domingo en español', function () {
    expect(TournamentDateLabels::full('2026-10-18'))
        ->toBe('Domingo, 18 de octubre 2026')
        ->and(TournamentDateLabels::shortDate('2026-10-18'))
        ->toBe('Dom 18 Oct 2026');
});

it('mantiene el resto de días en español', function () {
    expect(TournamentDateLabels::full('2026-08-10'))
        ->toBe('Lunes, 10 de agosto 2026')
        ->and(TournamentDateLabels::shortDate('2026-08-17'))
        ->toBe('Lun 17 Ago 2026');
});
