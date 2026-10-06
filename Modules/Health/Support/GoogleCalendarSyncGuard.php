<?php

namespace Modules\Health\Support;

/**
 * Marca de supresion de la salida hacia Google Calendar.
 *
 * La sincronizacion tiene dos direcciones y las dos escriben en la misma base:
 * el observador de la cita empuja hacia Google y el feed de Google actualiza la
 * cita. Sin esta marca, el pull actualizaria la cita, el observador lo tomaria
 * como un cambio del usuario y lo volveria a empujar: el bucle clasico
 * Laravel -> Google -> Laravel.
 *
 * Toda escritura originada por Google se envuelve en withoutSync(), y el
 * observador no encola nada cuando suppressed() es true. Es un contador (no un
 * booleano) para que los ambitos anidados no reactiven la salida antes de
 * tiempo.
 *
 * Nota: no se usa Model::withoutEvents() porque desactivaria tambien el hook
 * `creating` de DentAppointment que genera el `correlative` de la cita.
 */
class GoogleCalendarSyncGuard
{
    /** Profundidad de anidamiento de los ambitos suprimidos. */
    private static int $depth = 0;

    /**
     * Ejecuta el callback con la salida hacia Google suprimida.
     */
    public static function withoutSync(callable $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth = max(0, self::$depth - 1);
        }
    }

    /**
     * true mientras haya un ambito suprimido activo.
     */
    public static function suppressed(): bool
    {
        return self::$depth > 0;
    }
}
