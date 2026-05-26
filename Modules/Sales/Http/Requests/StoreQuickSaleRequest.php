<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuickSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:people,id'],
            'document_type_id' => ['required', 'string', 'in:80,01,03'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.entity_name_product' => ['required', 'string', 'max:255'],
            'items.*.product' => ['required', 'array'],
            'items.*.product.id' => ['required', 'integer'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'quick_method' => ['nullable', 'string', 'max:50'],
            'cash' => ['nullable', 'array'],
            'cash.tendered' => ['nullable', 'numeric', 'min:0'],
            'cash.change' => ['nullable', 'numeric', 'min:0'],
            'cash.denominations' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Seleccione un cliente.',
            'document_type_id.in' => 'Tipo de comprobante no válido para venta rápida.',
            'items.required' => 'El carrito está vacío.',
            'payments.required' => 'Indique al menos una forma de pago.',
            'payments.*.payment_method_id.required' => 'Seleccione un método de pago.',
            'payments.*.amount.required' => 'Ingrese el monto del pago.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $cartTotal = $this->calculateCartTotal();
                $paymentsTotal = collect($this->input('payments', []))->sum(fn ($p) => (float) ($p['amount'] ?? 0));
                $items = $this->input('items', []);

                foreach ($items as $index => $item) {
                    $price = (float) ($item['price'] ?? 0);
                    $discount = (float) ($item['discount'] ?? 0);

                    if ($discount > $price) {
                        $validator->errors()->add(
                            "items.$index.discount",
                            'El descuento por unidad no puede ser mayor al precio del producto.'
                        );
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (abs($cartTotal - $paymentsTotal) > 0.02) {
                    $validator->errors()->add(
                        'payments',
                        'La suma de pagos (S/ '.number_format($paymentsTotal, 2).') no coincide con el total (S/ '.number_format($cartTotal, 2).').'
                    );
                }
            },
        ];
    }

    public function calculateCartTotal(): float
    {
        return round(collect($this->input('items', []))->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discount = min((float) ($item['discount'] ?? 0), $price);

            return max(0, ($price - $discount) * $qty);
        }), 2);
    }

    /**
     * validated() solo incluye claves con reglas explícitas; items.*.product quedaría en {id}.
     *
     * @param  string|int|array<string>|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        $rawItems = $this->input('items', []);

        foreach ($validated['items'] as $index => &$item) {
            if (isset($rawItems[$index]['product']) && is_array($rawItems[$index]['product'])) {
                $item['product'] = $rawItems[$index]['product'];
            }
        }
        unset($item);

        return $validated;
    }
}
