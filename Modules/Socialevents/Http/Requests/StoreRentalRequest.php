<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Socialevents\Rules\MinimumRentalAdvancePayment;
use Modules\Socialevents\Services\LocalRentalAvailabilityService;
use Modules\Socialevents\Services\LocalRentalPricingService;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'local_id' => ['required', 'integer', 'exists:local_sales,id'],
            'customer_id' => ['required', 'integer', 'exists:people,id'],
            'rental_rate_id' => ['required', 'integer', 'exists:even_local_rental_rates,id'],
            'event_type' => ['required', Rule::in(['birthday', 'wedding', 'quinceanera', 'party', 'meeting', 'other'])],
            'event_date' => ['required', 'date'],
            'event_end_date' => ['required', 'date', 'after_or_equal:event_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'includes_tables_chairs' => ['nullable', 'boolean'],
            'includes_food' => ['nullable', 'boolean'],
            'beer_provided_by' => ['required', Rule::in(['none', 'company', 'client'])],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'total_hours' => ['required', 'numeric', 'min:0'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'extras' => ['nullable', 'array'],
            'extras.*.description' => ['required_with:extras', 'string', 'max:255'],
            'extras.*.amount' => ['required_with:extras', 'numeric', 'min:0'],
            'extras.*.reason' => ['nullable', Rule::in(['planned', 'damage', 'additional_service', 'other'])],
        ];
    }

    public function messages(): array
    {
        return [
            'local_id.required' => 'Seleccione un local.',
            'customer_id.required' => 'Seleccione un cliente.',
            'rental_rate_id.required' => 'Seleccione una tarifa.',
            'event_end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

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

                $advanceRule = new MinimumRentalAdvancePayment($snapshot['total_price']);
                $advanceRule->validate('paid_amount', $this->input('paid_amount'), function ($message) use ($validator) {
                    $validator->errors()->add('paid_amount', $message);
                });

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $availabilityService = app(LocalRentalAvailabilityService::class);

                if ($availabilityService->hasOverlap(
                    (int) $this->input('local_id'),
                    $this->input('event_date'),
                    $this->input('start_time'),
                    $this->input('event_end_date'),
                    $this->input('end_time')
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

    public function pricingSnapshot(): array
    {
        return app(LocalRentalPricingService::class)->buildPricingSnapshot($this->all());
    }
}
