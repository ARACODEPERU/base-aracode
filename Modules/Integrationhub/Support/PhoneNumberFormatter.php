<?php

namespace Modules\Integrationhub\Support;

/**
 * Normaliza los telefonos al formato E.164 que exigen los transportes
 * (Vonage, SMSGate y los flujos de WhatsApp).
 *
 * Vive en Integrationhub porque es el modulo del que dependen los canales:
 * Vonage no admite el signo "+" ni el prefijo "00" al inicio del numero y
 * SMSGate exige E.164 con el codigo de pais.
 */
class PhoneNumberFormatter
{
    /**
     * Devuelve el telefono en E.164 sin "+" (ej: 51999888777) o null cuando el
     * numero no es utilizable.
     *
     * Evita repetir el codigo de pais: si la persona ya escribio su numero con
     * codigo ("+51 999 888 777", "0051999888777" o "51999888777") se respeta el
     * numero tal cual; si lo escribio sin codigo se le antepone el del pais.
     */
    public static function toE164(
        ?string $phone,
        ?string $countryCodePhone = null,
        ?string $defaultCountryCode = null
    ): ?string {
        $cleaned = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($cleaned === '') {
            return null;
        }

        // Prefijos internacionales escritos por el usuario: 00 o 011.
        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        $countryCode = preg_replace('/\D+/', '', (string) ($countryCodePhone ?: $defaultCountryCode ?: '51')) ?? '';

        if ($countryCode === '') {
            $countryCode = '51';
        }

        if (! str_starts_with($cleaned, $countryCode)) {
            $cleaned = $countryCode . ltrim($cleaned, '0');
        }

        // E.164 admite hasta 15 digitos; por debajo de 8 no es un telefono plausible.
        $length = strlen($cleaned);

        if ($length < 8 || $length > 15) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Lee una lista de numeros internacionales escrita a mano (modo prueba).
     *
     * Los numeros deben venir completos, con su codigo de pais, separados por
     * coma (ej: "51944614034, 51943781122, 5298765432156"), asi que aqui NO se
     * antepone ningun codigo.
     *
     * @return array{numbers: array<int, string>, invalid: array<int, string>}
     */
    public static function parseInternationalList(string $raw): array
    {
        $numbers = [];
        $invalid = [];

        // Se separa por coma (tambien salto de linea o punto y coma). Los
        // espacios dentro de una entrada no la parten: "+51 944 614 034" es un
        // solo numero y se limpia mas abajo.
        $tokens = preg_split('/[,;\n\r]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            $cleaned = preg_replace('/\D+/', '', $token) ?? '';

            if (str_starts_with($cleaned, '00')) {
                $cleaned = substr($cleaned, 2);
            }

            $length = strlen($cleaned);

            if ($length < 8 || $length > 15) {
                $invalid[] = $token;
                continue;
            }

            // La clave deduplica numeros repetidos en la lista.
            $numbers[$cleaned] = $cleaned;
        }

        return [
            'numbers' => array_values($numbers),
            'invalid' => $invalid,
        ];
    }

    /**
     * Version legible para la interfaz: "51999888777".
     *
     * Sin el simbolo "+": el numero se muestra igual que se envia a Vonage y a
     * Integrationhub, solo con el codigo de pais y los digitos.
     */
    public static function toDisplay(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }
}
