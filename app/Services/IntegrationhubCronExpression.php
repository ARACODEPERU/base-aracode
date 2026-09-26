<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Expresiones cron de 5 campos para las programaciones de Integrationhub.
 *
 * Formato: minuto hora día mes día_semana, con soporte de asterisco, listas
 * (a,b), rangos (a-b) y pasos (a-b/n, asterisco/n, a/n). El día de la semana es
 * 0-7, donde 0 y 7 son domingo (como en cron).
 *
 * Dos contratos importantes:
 *
 *   - `isDue` combina los cinco campos con AND (día del mes Y día de la
 *     semana), que es lo que siempre hizo esta clase; no se cambia a la regla
 *     OR del cron de Unix para no reordenar las programaciones existentes.
 *   - `nextRunDate` salta de mes en mes / día en día / hora en hora en vez de
 *     recorrer minuto a minuto re-parseando la expresión: con la variante
 *     anterior, una expresión anual como `0 0 1 1 *` tardaba minutos en
 *     calcular la próxima ejecución y eso colgaba el guardado de la
 *     programación.
 */
class IntegrationhubCronExpression
{
    /**
     * Expresiones ya parseadas, por su texto. El bucle de `nextRunDate` y el
     * scheduler consultan la misma expresión muchas veces.
     *
     * @var array<string, array{minutes: array<int>, hours: array<int>, days: array<int>, months: array<int>, weekdays: array<int>}|null>
     */
    private array $parsed = [];

    /**
     * ¿La expresión se dispara en este minuto?
     */
    public function isDue(?string $expression, ?Carbon $date = null): bool
    {
        $fields = $this->fields($expression);

        if (!$fields) {
            return false;
        }

        $date = ($date ?? now())->copy()->startOfMinute();

        return in_array($date->minute, $fields['minutes'], true)
            && in_array($date->hour, $fields['hours'], true)
            && in_array($date->day, $fields['days'], true)
            && in_array($date->month, $fields['months'], true)
            && in_array($date->dayOfWeek, $fields['weekdays'], true);
    }

    /**
     * Primer minuto futuro en el que la expresión se dispara, o null si la
     * expresión es inválida o no entra en el horizonte de búsqueda.
     */
    public function nextRunDate(?string $expression, ?Carbon $from = null): ?Carbon
    {
        $fields = $this->fields($expression);

        if (!$fields) {
            return null;
        }

        $candidate = ($from ?? now())->copy()->addMinute()->startOfMinute();
        $limit = $candidate->copy()->addYears(5);

        while ($candidate->lte($limit)) {
            if (!in_array($candidate->month, $fields['months'], true)) {
                // Al primer día del mes siguiente, a medianoche.
                $candidate->addMonthNoOverflow()->startOfMonth()->startOfDay();
                continue;
            }

            if (!in_array($candidate->day, $fields['days'], true)
                || !in_array($candidate->dayOfWeek, $fields['weekdays'], true)) {
                $candidate->addDay()->startOfDay();
                continue;
            }

            if (!in_array($candidate->hour, $fields['hours'], true)) {
                $candidate->addHour()->startOfHour();
                continue;
            }

            if (!in_array($candidate->minute, $fields['minutes'], true)) {
                $candidate->addMinute();
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * ¿La expresión es una expresión cron válida de 5 campos?
     */
    public function isValid(?string $expression): bool
    {
        return $this->fields($expression) !== null;
    }

    /**
     * @return array{minutes: array<int>, hours: array<int>, days: array<int>, months: array<int>, weekdays: array<int>}|null
     */
    private function fields(?string $expression): ?array
    {
        $expression = trim((string) $expression);

        if (!array_key_exists($expression, $this->parsed)) {
            $this->parsed[$expression] = $this->parse($expression);
        }

        return $this->parsed[$expression];
    }

    /**
     * @return array{minutes: array<int>, hours: array<int>, days: array<int>, months: array<int>, weekdays: array<int>}|null
     */
    private function parse(string $expression): ?array
    {
        $parts = preg_split('/\s+/', $expression);

        if (count($parts) !== 5) {
            return null;
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        $minutes = $this->expand($minute, 0, 59);
        $hours = $this->expand($hour, 0, 23);
        $days = $this->expand($day, 1, 31);
        $months = $this->expand($month, 1, 12);
        $weekdays = $this->expand($weekday, 0, 7);

        if (!$minutes || !$hours || !$days || !$months || !$weekdays) {
            return null;
        }

        // 0 y 7 son domingo: se normaliza a 0 para comparar con dayOfWeek.
        $weekdays = array_values(array_unique(array_map(
            fn (int $value) => $value === 7 ? 0 : $value,
            $weekdays
        )));

        return [
            'minutes' => $minutes,
            'hours' => $hours,
            'days' => $days,
            'months' => $months,
            'weekdays' => $weekdays,
        ];
    }

    /**
     * Valores permitidos de un campo (listas, rangos y pasos), o null si el
     * campo es inválido.
     *
     * @return array<int>|null
     */
    private function expand(string $field, int $min, int $max): ?array
    {
        $values = [];

        foreach (explode(',', $field) as $part) {
            $expanded = $this->expandPart(trim($part), $min, $max);

            if ($expanded === null) {
                return null;
            }

            $values = array_merge($values, $expanded);
        }

        return $values ? array_values(array_unique($values)) : null;
    }

    /**
     * @return array<int>|null
     */
    private function expandPart(string $part, int $min, int $max): ?array
    {
        if ($part === '') {
            return null;
        }

        $step = 1;

        if (str_contains($part, '/')) {
            [$part, $stepPart] = explode('/', $part, 2);

            if (!ctype_digit($stepPart)) {
                return null;
            }

            $step = max(1, (int) $stepPart);
        }

        if ($part === '' || $part === '*') {
            $start = $min;
            $end = $max;
        } elseif (str_contains($part, '-')) {
            [$startPart, $endPart] = explode('-', $part, 2);

            if (!ctype_digit(trim($startPart)) || !ctype_digit(trim($endPart))) {
                return null;
            }

            $start = (int) $startPart;
            $end = (int) $endPart;
        } elseif (ctype_digit($part)) {
            $start = (int) $part;
            $end = (int) $part;
        } else {
            return null;
        }

        if ($start < $min || $end > $max || $start > $end) {
            return null;
        }

        $values = [];

        for ($value = $start; $value <= $end; $value += $step) {
            $values[] = $value;
        }

        return $values ?: null;
    }
}
