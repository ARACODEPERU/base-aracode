<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Texto reescrito de un mensaje del bot de Telegram.
 *
 * La fila existe solo cuando alguien modifico el texto de fabrica: si no hay
 * fila (o su body esta vacio) se usa el del catalogo Support\TelegramMessages.
 */
class IntegrationTelegramMessage extends Model
{
    protected $table = 'integration_telegram_messages';

    protected $fillable = [
        'code',
        'body',
        'format',
        'updated_by',
    ];
}
