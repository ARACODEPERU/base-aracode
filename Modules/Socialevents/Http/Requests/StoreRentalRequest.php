<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Socialevents\Entities\EvenLocalArea;
use Modules\Socialevents\Entities\EvenLocalRentalRate;
use Modules\Socialevents\Rules\MinimumRentalAdvancePayment;
use Modules\Socialevents\Services\LocalRentalAvailabilityService;
use Modules\Socialevents\Services\LocalRentalPricingService;
use Modules\Socialevents\Services\LocalRentalScheduleService;

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
            'event_type' => ['required', Rule::in(['birthday', 'wedding', 'quinceanera', 'party', 'meeting', 'other'])],
            'includes_tables_chairs' => ['nullable', 'boolean'],
            'includes_food' => ['nullable', 'boolean'],
            'beer_provided_by' => ['required', Rule::in(['none', 'company', 'client'])],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'tournament' => ['nullable', 'array'],
            'tournament.area_id' => ['required_with:tournament', 'integer', 'exists:even_local_areas,id'],
            // Sede del torneo: nombre de un complejo deportivo activo (sin áreas ni horarios).
            'tournament.venue_name' => ['required_with:tournament', 'string', Rule::exists('even_locals', 'names')->where('status', true)],
            'tournament.start_date' => ['required_with:tournament', 'date'],
            'tournament.end_date' => ['required_with:tournament', 'date', 'after_or_equal:tournament.start_date'],
            'tournament.day_of_week' => ['required_with:tournament', 'integer', 'between:0,6'],
            'tournament.start_time' => ['required_with:tournament', 'date_format:H:i'],
            'tournament.end_time' => ['required_with:tournament', 'date_format:H:i'],
            'tournament.total_amount' => ['required_with:tournament', 'numeric', 'min:0'],
            // Con torneo no se envían líneas por hora: las fechas salen de 'tournament'.
            'areas' => ['required_without:tournament', 'array'],
            'areas.*.area_id' => ['required', 'integer', 'exists:even_local_areas,id'],
            'areas.*.rental_rate_id' => ['required', 'integer', 'exists:even_local_rental_rates,id'],
            'areas.*.event_date' => ['required', 'date'],
            'areas.*.event_end_date' => ['required', 'date', 'after_or_equal:areas.*.event_date'],
            'areas.*.start_time' => ['required', 'date_format:H:i'],
            'areas.*.end_time' => ['required', 'date_format:H:i'],
            'areas.*.total_hours' => ['required', 'numeric', 'min:0'],
            'areas.*.hourly_rate' => ['required', 'numeric', 'min:0'],
            'areas.*.base_amount' => ['required', 'numeric', 'min:0'],
            'areas.*.subtotal' => ['required', 'numeric', 'min:0'],
            'areas.*.extras' => ['nullable', 'array'],
            'areas.*.extras.*.description' => ['required_with:areas.*.extras', 'string', 'max:255'],
            'areas.*.extras.*.amount' => ['required_with:areas.*.extras', 'numeric', 'min:0'],
            'areas.*.extras.*.reason' => ['nullable', Rule::in(['planned', 'damage', 'additional_service', 'other'])],
        ];
    }

    public function messages(): array
    {
        return [
            'local_id.required' => 'Seleccione un local.',
            'customer_id.required' => 'Seleccione un cliente.',
            'areas.required' => 'Seleccione al menos un área.',
            'areas.*.rental_rate_id.required' => 'Seleccione una tarifa para cada área.',
            'areas.*.event_end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->isTournament()) {
                    $this->validateTournament($validator);
                } elseif (count($this->input('areas', [])) === 0) {
                    // Sin torneo debe llegar al menos una línea por hora.
                    $validator->errors()->add('areas', 'Seleccione al menos un área.');
                } else {
                    $this->validateAreaLines($validator, $this->input('areas', []));
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $snapshot = $this->isTournament() ? $this->tournamentTotals() : $this->lineSnapshotTotals();

                $tolerance = 0.01;
                $submittedTotal = round((float) $this->input('total_price'), 2);

                if (abs($submittedTotal - $snapshot['total_price']) > $tolerance) {
                    $validator->errors()->add('total_price', 'El monto calculado no coincide con el enviado. Actualice el formulario.');
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $advanceRule = new MinimumRentalAdvancePayment($snapshot['total_price']);
                $advanceRule->validate('paid_amount', $this->input('paid_amount'), function ($message) use ($validator) {
                    $validator->errors()->add('paid_amount', $message);
                });
            },
        ];
    }

    /**
     * Valida cada línea: tarifa del área, duración, cálculo del servidor, horario del campo
     * y solapamiento por área.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function validateAreaLines(Validator $validator, array $lines): void
    {
        $pricingService = app(LocalRentalPricingService::class);
        $availabilityService = app(LocalRentalAvailabilityService::class);
        $localId = (int) $this->input('local_id');

        foreach ($lines as $index => $line) {
            $prefix = "areas.$index";

            $area = EvenLocalArea::find($line['area_id'] ?? null);
            if ($area && (int) $area->local_id !== $localId) {
                $validator->errors()->add("$prefix.area_id", 'El área seleccionada no corresponde al local.');
                continue;
            }

            $rate = EvenLocalRentalRate::find($line['rental_rate_id'] ?? null);
            if ($rate && (int) $rate->area_id !== (int) ($line['area_id'] ?? 0)) {
                $validator->errors()->add("$prefix.rental_rate_id", 'La tarifa seleccionada no corresponde al área.');
                continue;
            }

            $start = Carbon::parse($line['event_date'] . ' ' . $line['start_time']);
            $end = Carbon::parse(($line['event_end_date'] ?? $line['event_date']) . ' ' . $line['end_time']);

            if ($end->lte($start)) {
                $validator->errors()->add("$prefix.end_time", 'La fecha y hora de fin deben ser posteriores al inicio.');
                continue;
            }

            // En campos deportivos, el rango debe caer dentro del horario del día.
            if ($area) {
                $scheduleError = app(LocalRentalScheduleService::class)->validateWithinSchedule(
                    $area,
                    $line['event_date'],
                    $line['start_time'],
                    $line['end_time']
                );

                if ($scheduleError) {
                    $validator->errors()->add("$prefix.start_time", $scheduleError);
                    continue;
                }
            }

            if ($start->diffInHours($end) > 48) {
                $validator->errors()->add("$prefix.event_end_date", 'Cada área no puede reservarse más de 48 horas.');
                continue;
            }

            $calculated = $pricingService->buildItemSnapshot($line);
            foreach (['total_hours', 'hourly_rate', 'base_amount', 'subtotal'] as $field) {
                if (abs(round((float) $line[$field], 2) - round((float) $calculated[$field], 2)) > 0.01) {
                    $validator->errors()->add("$prefix.$field", 'El monto calculado no coincide con el enviado. Actualice el formulario.');
                }
            }

            if ($availabilityService->hasOverlapInArea(
                (int) $line['area_id'],
                $line['event_date'],
                $line['start_time'],
                $line['event_end_date'] ?? $line['event_date'],
                $line['end_time'],
                $this->excludeRentalId()
            )) {
                $validator->errors()->add("$prefix.start_time", 'Esta área ya tiene una reserva en ese horario.');
            }
        }
    }

    /**
     * Id de la reserva que no debe contar al revisar solapamientos. Al crear no hay ninguna.
     */
    protected function excludeRentalId(): ?int
    {
        return null;
    }

    protected function isTournament(): bool
    {
        return (bool) $this->input('tournament');
    }

    /**
     * Valida un torneo: solo para campos deportivos, con fechas que caigan en el día elegido
     * y dentro del horario del campo. Cada fecha se guarda como una línea para el control de solapamientos.
     */
    protected function validateTournament(Validator $validator): void
    {
        $tournament = $this->input('tournament', []);
        $area = EvenLocalArea::find($tournament['area_id'] ?? null);

        if (! $area || $area->area_type !== 'sports_field') {
            $validator->errors()->add('tournament.area_id', 'El torneo solo puede reservarse en un campo deportivo.');
            return;
        }

        if ((int) $area->local_id !== (int) $this->input('local_id')) {
            $validator->errors()->add('tournament.area_id', 'El área seleccionada no corresponde al local.');
            return;
        }

        $dates = app(LocalRentalScheduleService::class)->buildTournamentDates(
            $tournament['start_date'],
            $tournament['end_date'],
            (int) $tournament['day_of_week']
        );

        if (count($dates) === 0) {
            $validator->errors()->add('tournament.day_of_week', 'No hay fechas del día elegido dentro del rango del torneo.');
            return;
        }

        foreach ($dates as $date) {
            $scheduleError = app(LocalRentalScheduleService::class)->validateWithinSchedule(
                $area,
                $date,
                $tournament['start_time'],
                $tournament['end_time']
            );

            if ($scheduleError) {
                $validator->errors()->add('tournament.start_time', $scheduleError);
                return;
            }
        }

        $availability = app(LocalRentalAvailabilityService::class);
        foreach ($dates as $date) {
            if ($availability->hasOverlapInArea(
                (int) $area->id,
                $date,
                $tournament['start_time'],
                $date,
                $tournament['end_time'],
                $this->excludeRentalId()
            )) {
                $validator->errors()->add('tournament.start_time', "El campo ya tiene una reserva el $date en ese horario.");
                return;
            }
        }
    }

    /**
     * Total fijo del torneo. Se usa el monto enviado validado, no horas × tarifa.
     *
     * @return array{total_price: float}
     */
    public function tournamentTotals(): array
    {
        return ['total_price' => round((float) $this->input('tournament.total_amount'), 2)];
    }

    /**
     * Suma las líneas recalculadas por el servidor. Nunca usa los montos enviados como fuente.
     * Lo usa el controlador al guardar.
     *
     * @return array{total_price: float}
     */
    public function lineSnapshotTotals(): array
    {
        $pricing = app(LocalRentalPricingService::class);
        $total = 0.0;

        foreach ($this->input('areas', []) as $line) {
            $total += $pricing->buildItemSnapshot($line)['subtotal'];
        }

        return ['total_price' => round($total, 2)];
    }
}
