<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Socialevents\Entities\EvenLocalRental;

class StoreRentalPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'emit_note' => ['nullable', 'boolean'],
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
                $validator->errors()->add('amount', 'No se pueden registrar pagos en una reserva cancelada.');
            }

            $amount = round((float) $this->input('amount'), 2);
            $balance = round((float) $rental->balance_amount, 2);

            if ($amount > $balance + 0.01) {
                $validator->errors()->add('amount', "El monto no puede superar el saldo pendiente (S/ {$balance}).");
            }

            $paymentsTotal = round(collect($this->input('payments', []))->sum(fn ($p) => (float) ($p['amount'] ?? 0)), 2);

            if (abs($paymentsTotal - $amount) > 0.01) {
                $validator->errors()->add('payments', 'La suma de métodos de pago debe coincidir con el monto del abono.');
            }
        });
    }
}
