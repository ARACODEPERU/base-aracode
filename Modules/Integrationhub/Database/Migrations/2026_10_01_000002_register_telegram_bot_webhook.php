<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Services\TelegramWebhookRegistrar;

return new class extends Migration
{
    /**
     * Registra el webhook del bot de Telegram al desplegar.
     *
     * Telegram entrega los mensajes en la URL publica que se registra con
     * setWebhook, asi que hasta ahora, despues de cada despliegue, habia que
     * correr a mano el comando integrationhub:telegram-set-webhook (o pulsar el
     * boton de la pantalla de Notificaciones). Esta migracion hace ese paso por
     * ti, sin comandos.
     *
     * Es a proposito una operacion de "mejor esfuerzo": si el token todavia no
     * esta cargado (instalacion nueva, donde el parametro SC-00002 nace vacio) o
     * Telegram no responde, se deja un aviso en el log y la migracion sigue; una
     * llamada de red no puede tumbar el despliegue.
     *
     * Idempotente: setWebhook y setMyCommands sobreescriben con el mismo valor,
     * de modo que volver a correrla no duplica ni cambia nada, y la URL se
     * recalcula desde config('app.url') para que cada entorno registre la suya.
     */
    public function up(): void
    {
        try {
            $registrar = app(TelegramWebhookRegistrar::class);

            if (! $registrar->isConfigured()) {
                Log::info(
                    'Telegram: el parametro ' . config('integrationhub.telegram.parameter', 'SC-00002')
                    . ' todavia no tiene token; se omite el registro del webhook.'
                );

                return;
            }

            $url = $registrar->register(true);

            Log::info('Telegram: webhook registrado desde la migracion.', ['url' => $url]);
        } catch (\Throwable $exception) {
            Log::warning(
                'Telegram: no se pudo registrar el webhook desde la migracion; '
                . 'usa integrationhub:telegram-set-webhook set o el boton de la pantalla de Notificaciones.',
                ['error' => $exception->getMessage()]
            );
        }
    }

    /**
     * No se retira el webhook al revertir: quitarlo dejaria al bot sin recibir
     * mensajes en un sistema que sigue en pie. Para retirarlo esta el comando
     * integrationhub:telegram-set-webhook delete.
     */
    public function down(): void
    {
    }
};
