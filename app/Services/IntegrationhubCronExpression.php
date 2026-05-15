<?php

namespace App\Services;

use Carbon\Carbon;

class IntegrationhubCronExpression
{
    public function isDue(?string $expression, ?Carbon $date = null): bool
    {
        $date = ($date ?? now())->copy()->startOfMinute();
        $parts = $this->parts($expression);

        if (!$parts) {
            return false;
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        return $this->matchesField($minute, $date->minute, 0, 59)
            && $this->matchesField($hour, $date->hour, 0, 23)
            && $this->matchesField($day, $date->day, 1, 31)
            && $this->matchesField($month, $date->month, 1, 12)
            && $this->matchesField($weekday, $date->dayOfWeek, 0, 7);
    }

    public function nextRunDate(?string $expression, ?Carbon $from = null): ?Carbon
    {
        $candidate = ($from ?? now())->copy()->addMinute()->startOfMinute();
        $limit = $candidate->copy()->addYears(2);

        while ($candidate->lte($limit)) {
            if ($this->isDue($expression, $candidate)) {
                return $candidate;
            }

            $candidate->addMinute();
        }

        return null;
    }

    private function parts(?string $expression): ?array
    {
        $parts = preg_split('/\s+/', trim((string) $expression));

        return count($parts) === 5 ? $parts : null;
    }

    private function matchesField(string $field, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $part) {
            if ($this->matchesPart(trim($part), $value, $min, $max)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPart(string $part, int $value, int $min, int $max): bool
    {
        if ($part === '*') {
            return true;
        }

        $step = 1;
        if (str_contains($part, '/')) {
            [$part, $stepValue] = explode('/', $part, 2);
            $step = max(1, (int) $stepValue);
        }

        if ($part === '*') {
            $start = $min;
            $end = $max;
        } elseif (str_contains($part, '-')) {
            [$start, $end] = array_map('intval', explode('-', $part, 2));
        } else {
            $start = (int) $part;
            $end = (int) $part;
        }

        if ($max === 7 && $value === 0 && $start === 7) {
            $value = 7;
        }

        return $value >= $start
            && $value <= $end
            && (($value - $start) % $step === 0);
    }
}
