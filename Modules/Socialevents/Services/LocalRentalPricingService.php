<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Entities\EvenLocalRentalExtraCharge;
use Modules\Socialevents\Entities\EvenLocalRentalRate;

class LocalRentalPricingService
{
    public function calculateHours(
        string $eventDate,
        string $startTime,
        string $eventEndDate,
        string $endTime
    ): float {
        $start = Carbon::parse($eventDate . ' ' . substr($startTime, 0, 5));
        $end = Carbon::parse($eventEndDate . ' ' . substr($endTime, 0, 5));

        if ($end->lte($start)) {
            return 0;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    public function getHourlyRate(int $rentalRateId): float
    {
        $rate = EvenLocalRentalRate::query()
            ->where('id', $rentalRateId)
            ->where('is_active', true)
            ->firstOrFail();

        return (float) $rate->hourly_rate;
    }

    public function sumExtraCharges(EvenLocalRental $rental): float
    {
        return (float) $rental->extraCharges()->sum('amount');
    }

    public function sumExtraChargesFromArray(array $extras): float
    {
        return round(collect($extras)->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2);
    }

    public function calculateBaseAmount(float $totalHours, float $hourlyRate): float
    {
        return round($totalHours * $hourlyRate, 2);
    }

    public function calculateTotalPrice(float $baseAmount, float $extrasTotal): float
    {
        return round($baseAmount + $extrasTotal, 2);
    }

    public function resolvePaymentStatus(float $totalPrice, float $paidAmount): string
    {
        if ($totalPrice <= 0) {
            return 'paid_full';
        }

        if ($paidAmount >= $totalPrice) {
            return 'paid_full';
        }

        if ($paidAmount >= round($totalPrice * 0.2, 2)) {
            return 'partial_20';
        }

        return 'pending';
    }

    public function recalculateTotals(EvenLocalRental $rental): EvenLocalRental
    {
        $extrasTotal = $this->sumExtraCharges($rental);
        $rental->total_price = $this->calculateTotalPrice((float) $rental->base_amount, $extrasTotal);
        $rental->balance_amount = round($rental->total_price - (float) $rental->paid_amount, 2);
        $rental->payment_status = $this->resolvePaymentStatus(
            (float) $rental->total_price,
            (float) $rental->paid_amount
        );

        $rental->save();

        return $rental->fresh(['extraCharges', 'local', 'customer', 'rate']);
    }

    public function addExtraCharge(EvenLocalRental $rental, array $data): EvenLocalRentalExtraCharge
    {
        $charge = $rental->extraCharges()->create([
            'description' => $data['description'],
            'amount' => $data['amount'],
            'phase' => $data['phase'] ?? EvenLocalRentalExtraCharge::PHASE_DURING_EVENT,
            'reason' => $data['reason'] ?? 'other',
            'added_by' => $data['added_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->recalculateTotals($rental);

        return $charge;
    }

    public function buildPricingSnapshot(array $input): array
    {
        $eventEndDate = $input['event_end_date'] ?? $input['event_date'];

        $totalHours = $this->calculateHours(
            $input['event_date'],
            $input['start_time'],
            $eventEndDate,
            $input['end_time']
        );
        $hourlyRate = $this->getHourlyRate((int) $input['rental_rate_id']);
        $baseAmount = $this->calculateBaseAmount($totalHours, $hourlyRate);
        $extrasTotal = $this->sumExtraChargesFromArray($input['extras'] ?? []);
        $totalPrice = $this->calculateTotalPrice($baseAmount, $extrasTotal);
        $paidAmount = round((float) ($input['paid_amount'] ?? 0), 2);
        $balanceAmount = round($totalPrice - $paidAmount, 2);

        return [
            'total_hours' => $totalHours,
            'hourly_rate' => $hourlyRate,
            'base_amount' => $baseAmount,
            'extras_total' => $extrasTotal,
            'total_price' => $totalPrice,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'payment_status' => $this->resolvePaymentStatus($totalPrice, $paidAmount),
        ];
    }

    public function applyPayment(EvenLocalRental $rental, float $amount): EvenLocalRental
    {
        $rental->paid_amount = round((float) $rental->paid_amount + $amount, 2);
        $rental->balance_amount = round((float) $rental->total_price - (float) $rental->paid_amount, 2);
        $rental->payment_status = $this->resolvePaymentStatus(
            (float) $rental->total_price,
            (float) $rental->paid_amount
        );
        $rental->save();

        return $rental->fresh();
    }
}
