<?php

namespace Modules\Health\Support;

use Illuminate\Http\Request;

/**
 * Telefono de los avisos de Salud.
 *
 * Salud trabaja con celulares del Peru: se guarda el numero de 9 digitos que
 * empieza en 9 (sin el +51) y el codigo de pais se antepone al enviar el SMS,
 * igual que hace el modulo Academico con PhoneNumberFormatter.
 *
 * El usuario puede escribirlo con o sin el codigo de pais, con espacios o
 * guiones ("+51 987 987 987", "0051 987 987 987", "987987987"): normalize() lo
 * deja siempre en la forma que se guarda y toE164() en la que exige SMSGate.
 */
class HealthPhoneNumber
{
    /** Codigo de pais que se antepone al enviar (Peru). */
    public const COUNTRY_CODE = '51';

    /** Cantidad de digitos del numero guardado (celular peruano). */
    public const DIGITS = 9;

    /**
     * Numero guardable (9 digitos que empiezan en 9) o null si no es utilizable.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        // Prefijo internacional escrito a mano ("0051 987 987 987").
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Se acepta el numero escrito con el codigo de pais ("+51 987 987 987").
        if (strlen($digits) === self::DIGITS + strlen(self::COUNTRY_CODE)
            && str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, strlen(self::COUNTRY_CODE));
        }

        return preg_match('/^9\d{' . (self::DIGITS - 1) . '}$/', $digits) === 1 ? $digits : null;
    }

    /**
     * true si el valor es un celular peruano utilizable.
     */
    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }

    /**
     * Numero listo para SMSGate: E.164 sin "+" (51 + los 9 digitos).
     */
    public static function toE164(?string $value): ?string
    {
        $number = self::normalize($value);

        return $number === null ? null : self::COUNTRY_CODE . $number;
    }

    /**
     * Deja el telefono del request en la forma que se guarda.
     *
     * Se usa despues de validar: si el valor no es utilizable queda null, y
     * quien decide si eso es un error es la regla de validacion del formulario.
     */
    public static function normalizeRequest(Request $request, string $field = 'telephone'): void
    {
        $value = $request->get($field);

        $request->merge([$field => self::normalize(is_string($value) ? $value : null)]);
    }
}
