<?php

namespace Modules\Integrationhub\Exceptions;

use RuntimeException;

/**
 * Rechazo permanente de SMSGate: el mensaje no se enviara por mas que se
 * reintente.
 *
 * Se lanza cuando el numero no es utilizable (vacio o sin formato E.164) o
 * cuando el servidor responde un error 4xx, como el HTTP 400
 * {"message":"invalid phone number"} que devuelve ante un telefono sin codigo
 * de pais. Quien la captura debe marcar el envio como fallido y NO reintentar:
 * el telefono hay que corregirlo en los datos, no volver a llamar al API.
 *
 * Los fallos transitorios (5xx, timeout, caida de red) siguen siendo un
 * RuntimeException normal, para que la cola los reintente.
 */
class SmsgateRejectedException extends RuntimeException
{
}
