<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Modules\Socialevents\Entities\EvenLocalRentalItem;

class LocalRentalAvailabilityService
{
    /**
     * Comprueba solapamiento de horario dentro de un área concreta.
     * Un salón reservado no bloquea el campo, y viceversa.
     */
    public function hasOverlapInArea(
        int $areaId,
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

        $query = EvenLocalRentalItem::query()
            ->where('area_id', $areaId)
            ->whereHas('rental', function ($rentalQuery) use ($excludeRentalId) {
                $rentalQuery->where('reservation_status', '!=', 'cancelled');

                if ($excludeRentalId) {
                    $rentalQuery->where('id', '!=', $excludeRentalId);
                }
            });

        foreach ($query->get() as $item) {
            $existingEndDate = $item->event_end_date
                ? $item->event_end_date->format('Y-m-d')
                : $item->event_date->format('Y-m-d');

            $existingStart = Carbon::parse(
                $item->event_date->format('Y-m-d') . ' ' . substr((string) $item->start_time, 0, 5)
            );
            $existingEnd = Carbon::parse(
                $existingEndDate . ' ' . substr((string) $item->end_time, 0, 5)
            );

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }
}
