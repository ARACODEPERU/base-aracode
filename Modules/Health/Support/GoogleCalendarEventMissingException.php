<?php

namespace Modules\Health\Support;

use RuntimeException;

/**
 * El evento existe en el sistema pero Google ya no lo tiene (404 o 410).
 *
 * El servicio de sincronizacion la usa para volver a crear el evento en lugar
 * de dar el intento por fallido.
 */
class GoogleCalendarEventMissingException extends RuntimeException
{
}
