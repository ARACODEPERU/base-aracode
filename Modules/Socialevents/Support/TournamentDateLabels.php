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

    private const DAYS_SHORT = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mié',
        4 => 'Jue',
        5 => 'Vie',
        6 => 'Sáb',
        7 => 'Dom',
    ];

    private const MONTHS_SHORT = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',
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

        // dayOfWeekIso va de 1 (lunes) a 7 (domingo); dayOfWeek usa 0 para domingo
        // y dejaba ese día en inglés.
        $dayName = self::DAYS[(int) $carbon->dayOfWeekIso] ?? $carbon->format('l');
        $monthName = self::MONTHS[(int) $carbon->month] ?? $carbon->format('F');

        return sprintf(
            '%s, %d de %s %d',
            $dayName,
            $carbon->day,
            $monthName,
            $carbon->year
        );
    }

    /**
     * Fecha compacta para las filas de cruces: "Dom 10 Ago 2026".
     */
    public static function shortDate(string|Carbon $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        $dayName = self::DAYS_SHORT[(int) $carbon->dayOfWeekIso] ?? $carbon->format('D');
        $monthName = self::MONTHS_SHORT[(int) $carbon->month] ?? $carbon->format('M');

        return sprintf('%s %d %s %d', $dayName, $carbon->day, $monthName, $carbon->year);
    }

    /**
     * Hora en formato de 12 horas: "10:00 a. m.".
     */
    public static function time(string|Carbon $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return sprintf(
            '%s %s',
            $carbon->format('g:i'),
            (int) $carbon->format('G') < 12 ? 'a. m.' : 'p. m.'
        );
    }
}
