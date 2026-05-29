<?php

namespace Modules\Socialevents\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumRentalAdvancePayment implements ValidationRule
{
    public function __construct(
        protected float $totalPrice
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $paidAmount = round((float) $value, 2);
        $totalPrice = round($this->totalPrice, 2);

        if ($totalPrice <= 0) {
            return;
        }

        if ($paidAmount >= $totalPrice) {
            return;
        }

        $minimumAdvance = round($totalPrice * 0.2, 2);

        if ($paidAmount < $minimumAdvance) {
            $fail("El adelanto mínimo es S/ {$minimumAdvance} (20% del total).");
        }
    }
}
