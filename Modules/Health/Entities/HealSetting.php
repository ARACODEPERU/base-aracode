<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Health\Support\LogsActivity;

class HealSetting extends Model
{
    use LogsActivity;

    protected $table = 'heal_settings';

    protected $fillable = [
        'establishment_name',
        'establishment_logo',
        'representative',
        'notification_email',
        'phone',
        'address',
        'working_hours',
        'settings',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the normal start time (earliest from working hours)
     */
    public function getNormalStartAttribute(): string
    {
        $hours = $this->working_hours ?? [];

        if (empty($hours)) {
            return '08:00';
        }

        $starts = array_column($hours, 'start');
        sort($starts);

        return $starts[0] ?? '08:00';
    }

    /**
     * Get the normal end time (latest from working hours)
     */
    public function getNormalEndAttribute(): string
    {
        $hours = $this->working_hours ?? [];

        if (empty($hours)) {
            return '20:00';
        }

        $ends = array_column($hours, 'end');
        rsort($ends);

        return $ends[0] ?? '20:00';
    }

    /**
     * Check if a given time (H:i) falls within any working hours range
     */
    public function isWithinWorkingHours(string $time): bool
    {
        $hours = $this->working_hours ?? [];

        if (empty($hours)) {
            return true;
        }

        $timeMinutes = $this->timeToMinutes($time);

        foreach ($hours as $range) {
            $startMinutes = $this->timeToMinutes($range['start']);
            $endMinutes = $this->timeToMinutes($range['end']);

            if ($timeMinutes >= $startMinutes && $timeMinutes < $endMinutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the display label for working hours
     */
    public function getWorkingHoursLabelAttribute(): string
    {
        $hours = $this->working_hours ?? [];

        if (empty($hours)) {
            return '08:00 - 20:00';
        }

        return collect($hours)
            ->map(fn ($range) => $range['start'] . ' - ' . $range['end'])
            ->implode(' y ');
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return ((int) $h * 60) + (int) $m;
    }
}
