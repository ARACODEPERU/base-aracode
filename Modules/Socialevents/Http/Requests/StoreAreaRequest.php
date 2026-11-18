<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'local_id' => ['required', 'integer', 'exists:local_sales,id'],
            'name' => ['required', 'string', 'max:100'],
            'area_type' => ['required', Rule::in(['event_hall', 'sports_field', 'pool', 'kids_play'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingrese el nombre del área.',
            'area_type.required' => 'Seleccione el tipo de área.',
        ];
    }
}
