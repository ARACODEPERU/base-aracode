<?php

namespace Modules\Health\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Health\Jobs\PullGoogleCalendarChanges;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Notificaciones push de Google Calendar.
 *
 * Google avisa a esta URL cada vez que cambia algo en el calendario. El
 * endpoint es publico a proposito (Google no tiene sesion ni token de Sanctum)
 * y se valida con los encabezados del canal:
 *
 *   - X-Goog-Channel-ID:    el canal que registramos (no debe estar vacio).
 *   - X-Goog-Channel-Token: el secreto del parametro SC-00015, comparado con
 *                           hash_equals para no filtrar informacion.
 *   - X-Goog-Resource-State: 'sync' es el mensaje de verificacion del canal.
 *
 * El aviso no trae el cambio: solo dice "mira el calendario". Por eso aqui no
 * se lee nada: se encola la lectura (con unos segundos de retardo para que una
 * rafaga de cambios se resuelva en una sola pasada) y se responde 200 enseguida,
 * porque Google reintenta cualquier respuesta que no sea 2xx.
 */
class GoogleCalendarWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        GoogleCalendarService $google,
        GoogleCalendarSyncService $sync
    ): JsonResponse {
        if (! $sync->ready()) {
            return response()->json(['ok' => true, 'message' => 'La sincronizacion con Google Calendar esta desactivada.'], 200);
        }

        $channelId = trim((string) $request->header('X-Goog-Channel-ID', ''));
        $token = (string) $request->header('X-Goog-Channel-Token', '');
        $expected = (string) $google->channelToken();

        if ($channelId === '' || $expected === '') {
            return response()->json(['ok' => true, 'message' => 'Notificacion sin canal registrado.'], 200);
        }

        if (! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Secreto de canal invalido.'], 403);
        }

        $state = (string) $request->header('X-Goog-Resource-State', '');

        // Mensaje de verificacion del canal: no hay nada que leer.
        if ($state === 'sync') {
            return response()->json(['ok' => true, 'message' => 'Canal verificado.'], 200);
        }

        if (! $sync->inboundEnabled()) {
            return response()->json(['ok' => true, 'message' => 'La recepcion de cambios esta apagada.'], 200);
        }

        if ($sync->markPushPending()) {
            PullGoogleCalendarChanges::dispatch()->delay(now()->addSeconds(10));
        }

        return response()->json(['ok' => true], 200);
    }
}
