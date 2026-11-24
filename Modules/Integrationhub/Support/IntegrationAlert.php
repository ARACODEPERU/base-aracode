<?php

namespace Modules\Integrationhub\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Integrationhub\Entities\IntegrationError;

/**
 * Registra un error de integracion en las alertas del panel (pestana
 * "Errores de Integracion", tabla `integration_errors`).
 *
 * Dos reglas, porque esto se ejecuta justo cuando algo ya salio mal:
 *
 *   1. Nunca lanza: la alerta es informativa, no puede tumbar el flujo que la
 *      registra (una campana de notificaciones, una compra, una programacion).
 *   2. Si la alerta no se puede guardar (tabla inaccesible, base caida, mensaje
 *      gigante), el fallo queda en el log de la aplicacion y el flujo sigue
 *      igual: un fallo al alertar no puede detener el sistema.
 *
 * Todo registro de error de integracion pasa por aqui en vez de llamar a
 * `IntegrationError::create()` directamente.
 */
class IntegrationAlert
{
    /**
     * Origen con el que se registran los fallos del ejecutor de endpoints.
     */
    public const SOURCE_ENDPOINT = 'Integrationhub::runEndpoint';

    /**
     * Guarda la alerta. Nunca propaga excepciones.
     */
    public static function notify(string $message, ?string $source = null): void
    {
        try {
            IntegrationError::create([
                'message' => Str::limit($message, 1000),
                // La columna source es varchar(150); sin recorte, un origen largo
                // haria fallar el insert (y con el, la alerta).
                'source' => $source === null ? null : Str::limit($source, 150, ''),
            ]);
        } catch (\Throwable $e) {
            // La alerta no puede detener el sistema: queda en los logs y el
            // proceso que la invoco continua.
            Log::error('IntegrationAlert: no se pudo registrar la alerta de integracion.', [
                'source' => $source,
                'message' => Str::limit($message, 1000),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
