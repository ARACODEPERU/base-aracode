<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Conversacion de registro a medias en el chat del bot.
 *
 * Existe mientras el bot espera que la persona escriba su documento; se borra
 * al vincular el chat, al dar de baja o al vencer (config
 * integrationhub.telegram.registration_session_minutes).
 */
class IntegrationTelegramRegistrationSession extends Model
{
    protected $table = 'integration_telegram_registration_sessions';

    protected $fillable = [
        'chat_id',
        'telegram_username',
        'telegram_first_name',
        'step',
        'context',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Sesiones que siguen vigentes (sin fecha vencida).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('step', 'awaiting_document')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
