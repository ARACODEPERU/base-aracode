<?php

use Illuminate\Http\Request;
use Modules\Health\Http\Controllers\Api\GoogleCalendarWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/health', function (Request $request) {
    return $request->user();
});

/*
    Notificaciones push de Google Calendar.

    Es publico a proposito (Google no envia sesion ni token de sanctum) y se
    valida con los encabezados X-Goog-Channel-ID y X-Goog-Channel-Token (el
    secreto vive en el parametro del sistema SC-00015). Google solo entrega si
    la URL es HTTPS publica; en local el aviso no llega y la reconciliacion
    programada (health:sync-google-calendar) es la que lee los cambios.
*/
Route::post('health/google-calendar/notifications', GoogleCalendarWebhookController::class)
    ->name('health_google_calendar_webhook');