<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Entities\EvenLocalRental;

class StoreRentalExtraChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', Rule::in(['damage', 'additional_service', 'other'])],
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

            if ($rental && $rental->reservation_status === 'cancelled') {
                $validator->errors()->add('description', 'No se pueden agregar cargos a una reserva cancelada.');
            }
        });
    }
}
