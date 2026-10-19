<?php

namespace Modules\Health\Support;

use Modules\Health\Entities\HealSetting;

/**
 * Opciones de facturación de los cobros de Salud.
 *
 * Viven dentro de `heal_settings.settings`, bajo la clave `billing`, con el
 * mismo criterio que GoogleCalendarOptions: un grupo por tema para no mezclarse
 * con los colores o el RUC que ya se guardan ahí.
 *
 *   - igv_affectation: afectación IGV con la que los cobros pasan a Sales
 *     (10 gravado, 20 exonerado, 30 inafecto). Los servicios médicos y
 *     odontológicos suelen ir exonerados, así que el consultorio debe poder
 *     elegirlo: antes estaba fijo en gravado y toda boleta cobraba 18% de IGV.
 *   - unit_type: unidad de medida SUNAT del ítem (ZZ = servicio).
 *   - billing_description: texto que acompaña al documento de venta armado
 *     desde Salud.
 *   - consultation_recobro_months: meses tras los cuales se vuelve a sugerir
 *     cobrar la consulta al mismo paciente.
 *   - allow_cancel_with_document: permite anular un cobro que ya generó un
 *     comprobante vigente. Apagado por defecto porque deja la venta y la
 *     tesorería descuadradas sin anular antes el documento en Sales.
 */
final class BillingOptions
{
    /** Clave dentro de heal_settings.settings. */
    private const KEY = 'billing';

    /** Afectaciones del IGV que acepta el catálogo 07 de SUNAT. */
    public const AFFECTATIONS = ['10', '20', '30'];

    /** Valores por defecto (los que se usan mientras no se guarden otros). */
    public const DEFAULTS = [
        'igv_affectation' => '10',
        'unit_type' => 'ZZ',
        'billing_description' => 'Cobros generados desde Salud. Revisar antes de emitir.',
        'consultation_recobro_months' => 2,
        'allow_cancel_with_document' => false,
    ];

    /**
     * Opciones vigentes, completando con los valores por defecto.
     *
     * @return array{igv_affectation: string, unit_type: string, billing_description: string, consultation_recobro_months: int, allow_cancel_with_document: bool}
     */
    public static function read(): array
    {
        $settings = HealSetting::first()?->settings;
        $stored = is_array($settings) && is_array($settings[self::KEY] ?? null)
            ? $settings[self::KEY]
            : [];

        return self::normalize($stored);
    }

    /**
     * Guarda solo las claves presentes, sin tocar el resto de la configuración.
     *
     * Las claves ausentes o nulas conservan lo que ya estaba guardado, así una
     * pantalla que no manda todo el grupo no borra el resto.
     *
     * @param array<string, mixed> $values
     */
    public static function save(array $values): void
    {
        $current = self::read();

        foreach (array_keys(self::DEFAULTS) as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null) {
                $current[$key] = $values[$key];
            }
        }

        $setting = HealSetting::first() ?? new HealSetting();

        $settings = is_array($setting->settings) ? $setting->settings : [];
        $settings[self::KEY] = self::normalize($current);

        $setting->settings = $settings;
        $setting->save();
    }

    /**
     * Tipos y rangos válidos de cada opción.
     *
     * @param array<string, mixed> $values
     * @return array{igv_affectation: string, unit_type: string, billing_description: string, consultation_recobro_months: int, allow_cancel_with_document: bool}
     */
    private static function normalize(array $values): array
    {
        $affectation = (string) ($values['igv_affectation'] ?? self::DEFAULTS['igv_affectation']);
        $unitType = trim((string) ($values['unit_type'] ?? self::DEFAULTS['unit_type']));
        $description = trim((string) ($values['billing_description'] ?? self::DEFAULTS['billing_description']));

        return [
            'igv_affectation' => in_array($affectation, self::AFFECTATIONS, true)
                ? $affectation
                : self::DEFAULTS['igv_affectation'],
            'unit_type' => $unitType !== '' ? $unitType : self::DEFAULTS['unit_type'],
            'billing_description' => $description,
            'consultation_recobro_months' => self::clampMonths(
                $values['consultation_recobro_months'] ?? self::DEFAULTS['consultation_recobro_months']
            ),
            'allow_cancel_with_document' => (bool) ($values['allow_cancel_with_document']
                ?? self::DEFAULTS['allow_cancel_with_document']),
        ];
    }

    /** Meses de recobro de consulta, acotados a 1..60. */
    private static function clampMonths(mixed $value): int
    {
        return min(60, max(1, (int) $value));
    }
}
