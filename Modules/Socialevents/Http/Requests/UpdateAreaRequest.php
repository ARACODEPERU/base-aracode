<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'area_type' => ['required', Rule::in(['event_hall', 'sports_field', 'pool', 'kids_play'])],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.open_time' => ['required', 'date_format:H:i'],
            'schedules.*.close_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingrese el nombre del área.',
            'area_type.required' => 'Seleccione el tipo de área.',
            'schedules.*.close_time.after' => 'La hora de cierre debe ser posterior a la de apertura.',
        ];
    }

    /**
     * Valida que el cierre sea posterior a la apertura.
     * El cierre 00:00 significa medianoche (fin del día), por eso no se compara como texto.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('schedules', []) as $index => $schedule) {
                $open = $schedule['open_time'] ?? null;
                $close = $schedule['close_time'] ?? null;

                if (! $open || ! $close || $close === '00:00') {
                    continue;
                }

                if (substr($close, 0, 5) <= substr($open, 0, 5)) {
                    $validator->errors()->add("schedules.$index.close_time", 'La hora de cierre debe ser posterior a la de apertura.');
                }
            }
        });
    }
}
