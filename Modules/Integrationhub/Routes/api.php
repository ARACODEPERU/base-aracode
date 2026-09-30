<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Http\Controllers\Api\BirthdayBenefitsController;
use Modules\Integrationhub\Http\Controllers\Api\TelegramWebhookController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('integrationhub', fn (Request $request) => $request->user())->name('integrationhub');
});

Route::middleware(['localhost.only'])->prefix('integrationhub')->name('integrationhub.')->group(function () {
    Route::post('birthday_benefits', BirthdayBenefitsController::class)->name('birthday_benefits');
});

/*
    Webhook del bot de Telegram.

    Es publico a proposito (Telegram no envia sesion ni token de sanctum) y se
    valida con el header X-Telegram-Bot-Api-Secret-Token, cuyo valor se deriva
    del token del bot (parametro del sistema SC-00002). La URL que se registra
    en Telegram la arma TelegramBotService::webhookUrl().
*/
Route::post('integrationhub/telegram/webhook', TelegramWebhookController::class)
    ->name('integrationhub_telegram_webhook');
