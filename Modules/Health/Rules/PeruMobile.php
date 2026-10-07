<?php

namespace Modules\Health\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Health\Support\HealthPhoneNumber;

/**
 * Celular del Peru: 9 digitos que empiezan en 9.
 *
 * Se acepta el numero escrito con el codigo de pais, espacios o guiones; lo
 * que se guarda es siempre la forma de 9 digitos que devuelve
 * HealthPhoneNumber::normalize().
 */
class PeruMobile implements ValidationRule
{
    /** Mensaje del error: el numero no es un celular peruano utilizable. */
    public const MESSAGE = 'El teléfono debe ser un celular del Perú: 9 dígitos que empiezan con 9 (se guarda sin el +51).';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! HealthPhoneNumber::isValid(is_string($value) ? $value : (string) $value)) {
            $fail(self::MESSAGE);
        }
    }
}
