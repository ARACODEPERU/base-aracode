<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'local_id' => ['required', 'integer', 'exists:local_sales,id'],
            'name' => ['required', 'string', 'max:255'],
            'hourly_rate' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'local_id.required' => 'Seleccione un local.',
            'name.required' => 'Ingrese el nombre de la tarifa.',
            'hourly_rate.required' => 'Ingrese el monto por hora.',
            'hourly_rate.min' => 'El monto por hora debe ser mayor a cero.',
        ];
    }
}
