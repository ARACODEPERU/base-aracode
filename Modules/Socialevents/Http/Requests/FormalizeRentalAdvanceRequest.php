<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Services\LocalRentalSaleService;

class FormalizeRentalAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rental = EvenLocalRental::find($this->route('id'));

            if (! $rental) {
                return;
            }

            if ($rental->reservation_status === 'cancelled') {
                $validator->errors()->add('payment_method_id', 'No se puede formalizar el adelanto en una reserva cancelada.');
            }

            $saleService = app(LocalRentalSaleService::class);
            $unformalized = $saleService->unformalizedAdvanceAmount($rental);

            if ($unformalized <= 0) {
                $validator->errors()->add('payment_method_id', 'No hay adelanto pendiente de formalizar con nota de venta.');
            }
        });
    }
}
