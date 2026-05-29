<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Entities\EvenLocalRental;

class UpdateRentalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_status' => [
                'required',
                Rule::in(collect(EvenLocalRental::reservationStatusOptions())->pluck('value')->all()),
            ],
        ];
    }
}
