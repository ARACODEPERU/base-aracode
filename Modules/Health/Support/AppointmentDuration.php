<?php

namespace Modules\Health\Support;

/**
 * Catalogo unico de duraciones de cita.
 *
 * Regla del negocio: de 15 a 60 minutos en pasos de 15, y de 90 a 360
 * minutos en pasos de 30 (maximo 6 horas). La misma lista viaja a las vistas
 * como prop, para que el frontend no pueda validar distinto que el servidor.
 */
final class AppointmentDuration
{
    /** Duracion minima aceptada, en minutos. */
    public const MIN = 15;

    /** Duracion maxima aceptada, en minutos (6 horas). */
    public const MAX = 360;

    /** Paso de la primera parte del catalogo. */
    public const SHORT_STEP = 15;

    /** Ultima duracion que usa el paso corto. */
    public const SHORT_LIMIT = 60;

    /** Paso de la segunda parte del catalogo. */
    public const LONG_STEP = 30;

    /**
     * Duraciones permitidas: 15, 30, 45, 60 y luego 90 a 360 en pasos de 30.
     *
     * @return int[]
     */
    public static function values(): array
    {
        return array_merge(
            range(self::MIN, self::SHORT_LIMIT, self::SHORT_STEP),
            range(self::SHORT_LIMIT + self::LONG_STEP, self::MAX, self::LONG_STEP)
        );
    }

    /** Regla `in:` de Laravel con las duraciones permitidas. */
    public static function rule(): string
    {
        return 'in:' . implode(',', self::values());
    }

    /** Texto que se muestra cuando la duracion no pertenece al catalogo. */
    public static function message(): string
    {
        return 'La duración debe ser de 15 a 60 minutos en pasos de 15, o de 90 a 360 minutos en pasos de 30.';
    }

    /**
     * Etiqueta legible en palabras: 15 min, 1 hora, 2 horas y media, 6 horas.
     */
    public static function label(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $label = $hours . ($hours === 1 ? ' hora' : ' horas');

        return $minutes % 60 === 30 ? $label . ' y media' : $label;
    }

    /**
     * Opciones con el formato que consumen los selectores del frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (int $minutes) => ['value' => (string) $minutes, 'label' => self::label($minutes)],
            self::values()
        );
    }

    /**
     * Ajusta una duracion a la permitida inmediata inferior (nunca por debajo
     * del minimo). Se usa al reagendar arrastrando, donde el espacio libre
     * disponible no tiene por que coincidir con el catalogo.
     */
    public static function snap(int $minutes): int
    {
        $allowed = self::MIN;

        foreach (self::values() as $value) {
            if ($value <= $minutes) {
                $allowed = $value;
            }
        }

        return $allowed;
    }
}
