<?php

namespace Modules\Academic\Support;

use Modules\Integrationhub\Support\PhoneNumberFormatter as IntegrationhubPhoneNumberFormatter;

/**
 * Alias historico del formateador de telefonos del Academico.
 *
 * La implementacion vive en Integrationhub (el modulo que agrupa los canales
 * de envio, que tambien usa Salud); esta subclase se conserva para no romper
 * las referencias del modulo Academico.
 */
class PhoneNumberFormatter extends IntegrationhubPhoneNumberFormatter
{
}
