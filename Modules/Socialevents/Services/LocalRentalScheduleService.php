<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Modules\Socialevents\Entities\EvenLocalArea;

class LocalRentalScheduleService
{
    /**
     * Devuelve un mensaje de error si el rango no cae dentro del horario del área, o null si es válido.
     * Solo aplica a áreas de campo deportivo; las demás no tienen horario definido.
     */
    public function validateWithinSchedule(EvenLocalArea $area, string $date, string $startTime, string $endTime): ?string
    {
        if ($area->area_type !== EvenLocalArea::TYPE_SPORTS_FIELD) {
            return null;
        }

        $day = Carbon::parse($date)->dayOfWeek;
        $schedule = $area->schedules()->where('day_of_week', $day)->first();

        if (! $schedule) {
            return 'El campo no atiende ese día según su horario.';
        }

        $open = substr((string) $schedule->open_time, 0, 5);
        $close = substr((string) $schedule->close_time, 0, 5);
        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);

        // Un cierre 00:00 significa medianoche: el campo llega hasta el final del día.
        $closeMinutes = $close === '00:00' ? 1440 : $this->toMinutes($close);

        if ($this->toMinutes($start) < $this->toMinutes($open) || $this->toMinutes($end) > $closeMinutes) {
            return "El horario del campo ese día es de {$open} a {$close}.";
        }

        return null;
    }

    /**
     * Convierte una hora HH:MM en minutos desde medianoche.
     */
    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }

    /**
     * Genera las fechas de la serie de un torneo: todas las fechas entre inicio y fin
     * que caen en el día de la semana indicado (0 = domingo ... 6 = sábado).
     *
     * @return array<int, string> fechas en formato Y-m-d
     */
    public function buildTournamentDates(string $startDate, string $endDate, int $dayOfWeek): array
    {
        $dates = [];
        $cursor = Carbon::parse($startDate)->startOfDay();
        $last = Carbon::parse($endDate)->startOfDay();

        // Avanza hasta el primer día de la semana pedido dentro del rango.
        while ($cursor->dayOfWeek !== $dayOfWeek && $cursor->lte($last)) {
            $cursor->addDay();
        }

        while ($cursor->lte($last)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addWeek();
        }

        return $dates;
    }
}
