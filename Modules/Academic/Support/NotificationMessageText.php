<?php

namespace Modules\Academic\Support;

/**
 * Texto que viaja en un SMS (y sirve de base para el canal SMSGate).
 *
 * Es la composicion historica del SMS: mensaje del administrador, mas el curso
 * y el tiempo como lineas propias. Vive aqui para que la campana en cola y los
 * canales SMS (Vonage y SMSGate) compongan exactamente el mismo texto.
 */
class NotificationMessageText
{
    /**
     * Compone el texto con las lineas no vacias.
     */
    public static function compose(?string $message, string $courseName, ?string $time): string
    {
        $courseName = trim($courseName);
        $time = trim((string) $time);

        $parts = array_filter([
            trim((string) $message),
            $courseName !== '' ? 'Curso: ' . $courseName : null,
            $time !== '' ? 'Tiempo: ' . $time : null,
        ], fn (?string $part) => $part !== null && $part !== '');

        return implode("\n", $parts);
    }
}
