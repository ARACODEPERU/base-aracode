<?php

namespace Modules\Health\Support;

use Modules\Health\Entities\HealSetting;

/**
 * Opciones de configuracion de Google Calendar del modulo Salud.
 *
 * Viven dentro de `heal_settings.settings`, bajo la clave `google_calendar`,
 * para no mezclarse con los colores o el RUC que ya se guardan ahi.
 *
 *   - allow_doctor_connection: los doctores pueden enlazar SU agenda de Google.
 *     Si se apaga, solo el administrador conecta calendarios.
 *   - principal_fallback: las citas de un doctor SIN agenda propia se envian a
 *     la agenda principal del consultorio. Si se apaga, esas citas no salen a
 *     Google (la agenda principal deja de ver a los doctores sin calendario).
 *   - confirm_minutes: cuanto dura la confirmacion de contrasena antes de
 *     poder enlazar una cuenta (1 a 30 minutos).
 */
final class GoogleCalendarOptions
{
    /** Clave dentro de heal_settings.settings. */
    private const KEY = 'google_calendar';

    /** Valores por defecto (los que se usan mientras no se guarden otros). */
    public const DEFAULTS = [
        'allow_doctor_connection' => true,
        'principal_fallback' => true,
        'confirm_minutes' => 5,
    ];

    /**
     * Opciones vigentes, completando con los valores por defecto.
     *
     * @return array{allow_doctor_connection: bool, principal_fallback: bool, confirm_minutes: int}
     */
    public static function read(): array
    {
        $settings = HealSetting::first()?->settings;
        $stored = is_array($settings) && is_array($settings[self::KEY] ?? null)
            ? $settings[self::KEY]
            : [];

        return [
            'allow_doctor_connection' => array_key_exists('allow_doctor_connection', $stored)
                ? (bool) $stored['allow_doctor_connection']
                : self::DEFAULTS['allow_doctor_connection'],
            'principal_fallback' => array_key_exists('principal_fallback', $stored)
                ? (bool) $stored['principal_fallback']
                : self::DEFAULTS['principal_fallback'],
            'confirm_minutes' => self::clampMinutes($stored['confirm_minutes'] ?? self::DEFAULTS['confirm_minutes']),
        ];
    }

    /**
     * Guarda las opciones sin tocar el resto de la configuracion de Salud.
     *
     * @param array{allow_doctor_connection: bool, principal_fallback: bool, confirm_minutes: int} $values
     */
    public static function save(array $values): void
    {
        $setting = HealSetting::first() ?? new HealSetting();

        $settings = is_array($setting->settings) ? $setting->settings : [];

        $settings[self::KEY] = [
            'allow_doctor_connection' => (bool) $values['allow_doctor_connection'],
            'principal_fallback' => (bool) $values['principal_fallback'],
            'confirm_minutes' => self::clampMinutes($values['confirm_minutes']),
        ];

        $setting->settings = $settings;
        $setting->save();
    }

    /**
     * Minutos de vigencia de la confirmacion de contrasena, acotados a 1..30.
     */
    private static function clampMinutes(mixed $value): int
    {
        return min(30, max(1, (int) $value));
    }
}
