<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use Modules\Socialevents\Rules\MinimumRentalAdvancePayment;
use Modules\Socialevents\Services\LocalRentalAvailabilityService;
use Modules\Socialevents\Services\LocalRentalPricingService;

class UpdateRentalRequest extends StoreRentalRequest
{
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $start = Carbon::parse($this->input('event_date') . ' ' . $this->input('start_time'));
                $end = Carbon::parse($this->input('event_end_date') . ' ' . $this->input('end_time'));

                if ($end->lte($start)) {
                    $validator->errors()->add(
                        'end_time',
                        'La fecha y hora de fin deben ser posteriores al inicio del evento.'
                    );
                }

                if ($start->diffInHours($end) > 48) {
                    $validator->errors()->add(
                        'event_end_date',
                        'El evento no puede durar más de 48 horas.'
                    );
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $pricingService = app(LocalRentalPricingService::class);
                $snapshot = $pricingService->buildPricingSnapshot($this->all());

                $tolerance = 0.01;
                $fields = ['total_hours', 'hourly_rate', 'base_amount', 'total_price'];

                foreach ($fields as $field) {
                    $submitted = round((float) $this->input($field), 2);
                    $calculated = round((float) $snapshot[$field], 2);

                    if (abs($submitted - $calculated) > $tolerance) {
                        $validator->errors()->add($field, 'El monto calculado no coincide con el enviado. Actualice el formulario.');
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $rentalId = (int) $this->route('id');
                $availabilityService = app(LocalRentalAvailabilityService::class);

                if ($availabilityService->hasOverlap(
                    (int) $this->input('local_id'),
                    $this->input('event_date'),
                    $this->input('start_time'),
                    $this->input('event_end_date'),
                    $this->input('end_time'),
                    $rentalId
                )) {
                    $validator->errors()->add('start_time', 'El local ya tiene una reserva en ese horario.');
                }

                $rate = \Modules\Socialevents\Entities\EvenLocalRentalRate::find($this->input('rental_rate_id'));
                if ($rate && (int) $rate->local_id !== (int) $this->input('local_id')) {
                    $validator->errors()->add('rental_rate_id', 'La tarifa seleccionada no corresponde al local.');
                }
            },
        ];
    }
}
