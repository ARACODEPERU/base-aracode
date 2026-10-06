<?php

namespace Modules\Health\Support;

use RuntimeException;

/**
 * Google respondio 410 GONE: el `sync_token` del feed incremental caduco.
 *
 * El token deja de ser valido cuando pasa demasiado tiempo sin usarse, cuando
 * se cambia de calendario o cuando Google rota el historial. La unica salida es
 * descartarlo y volver a leer el calendario completo.
 */
class GoogleCalendarSyncTokenExpiredException extends RuntimeException
{
}
