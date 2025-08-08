<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sales\Entities\SaleDocumentQuota;

class StorePaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sale_document_quota_id' => ['required', 'exists:sale_document_quotas,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'], // Asumiendo una tabla payment_methods
            'amount_applied' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount_applied' => (float) $this->input('amount_applied'),
            'payment_date' => $this->input('payment_date') ?? now()->toDateString(),
        ]);
    }

     // --- ¡AÑADE ESTO PARA VALIDACIÓN ADICIONAL! ---
    public function after(): array
    {
        return [
            function ($validator) {
                $quotaId = $this->input('sale_document_quota_id');
                $amountApplied = $this->input('amount_applied');

                if ($quotaId && $amountApplied) {
                    $quota = SaleDocumentQuota::find($quotaId);

                    if ($quota && $amountApplied > $quota->balance) {
                        $validator->errors()->add(
                            'amount_applied',
                            'El monto aplicado (' . number_format($amountApplied, 2) . ') excede el saldo pendiente de la cuota (' . number_format($quota->balance, 2) . ').'
                        );
                    }
                }
            }
        ];
    }
}
