<?php

namespace Modules\Socialevents\Support;

use Illuminate\Support\Carbon;

final class TournamentDateLabels
{
    private const DAYS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    private const MONTHS = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    /**
     * Formatea una fecha con nombres de día y mes en español.
     * Ejemplo: "Domingo, 17 de agosto 2026"
     */
    public static function full(string|Carbon $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        $dayName = self::DAYS[(int) $carbon->dayOfWeek] ?? $carbon->format('l');
        $monthName = self::MONTHS[(int) $carbon->month] ?? $carbon->format('F');

        return sprintf(
            '%s, %d de %s %d',
            $dayName,
            $carbon->day,
            $monthName,
            $carbon->year
        );
    }
}
