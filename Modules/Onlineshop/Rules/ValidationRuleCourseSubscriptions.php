<?php

namespace Modules\Onlineshop\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidationRuleCourseSubscriptions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Obtener ambos arrays de la request
        $courses = request()->courses ?? [];
        $subscriptions = request()->subscriptions ?? [];

        // Validación: al menos uno debe tener elementos
        if (empty($courses) && empty($subscriptions)) {
            $fail('Debe seleccionar al menos un curso o una suscripción.');
        }
    }
}
