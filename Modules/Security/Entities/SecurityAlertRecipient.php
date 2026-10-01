<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Persona (o cuenta) que recibe las alertas de error por Telegram.
 *
 * El chat_id es el destinatario real para la integración Telegram_bot; el
 * nombre es solo una etiqueta para reconocerlo en pantalla.
 */
class SecurityAlertRecipient extends Model
{
    protected $table = 'security_alert_recipients';

    protected $fillable = [
        'name',
        'chat_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Solo los destinatarios activos, que son los que reciben avisos.
     *
     * @return array<int, string>
     */
    public static function activeChatIds(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('chat_id')
            ->map(fn ($chatId) => trim((string) $chatId))
            ->filter()
            ->values()
            ->all();
    }
}
