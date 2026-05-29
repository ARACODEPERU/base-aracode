<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Modules\Socialevents\Entities\EvenLocalRental;

class LocalRentalAvailabilityService
{
    public function hasOverlap(
        int $localId,
        string $eventDate,
        string $startTime,
        string $eventEndDate,
        string $endTime,
        ?int $excludeRentalId = null
    ): bool {
        $newStart = Carbon::parse($eventDate . ' ' . substr($startTime, 0, 5));
        $newEnd = Carbon::parse($eventEndDate . ' ' . substr($endTime, 0, 5));

        if ($newEnd->lte($newStart)) {
            return false;
        }

        $query = EvenLocalRental::query()
            ->where('local_id', $localId)
            ->where('reservation_status', '!=', 'cancelled');

        if ($excludeRentalId) {
            $query->where('id', '!=', $excludeRentalId);
        }

        foreach ($query->get() as $rental) {
            $existingEndDate = $rental->event_end_date
                ? $rental->event_end_date->format('Y-m-d')
                : $rental->event_date->format('Y-m-d');

            $existingStart = Carbon::parse(
                $rental->event_date->format('Y-m-d') . ' ' . substr((string) $rental->start_time, 0, 5)
            );
            $existingEnd = Carbon::parse(
                $existingEndDate . ' ' . substr((string) $rental->end_time, 0, 5)
            );

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }
}
