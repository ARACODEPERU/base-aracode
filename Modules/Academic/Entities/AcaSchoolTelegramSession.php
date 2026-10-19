<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Sesión conversacional en curso dentro del bot de Telegram escolar.
 *
 * Guarda el paso actual (esperando DNI del apoderado, DNI del hijo o
 * respuesta de si tiene otro hijo en el colegio), el contexto acumulado
 * (apoderado validado, hijos ya vinculados en la conversación) y la fecha de
 * vencimiento.
 */
class AcaSchoolTelegramSession extends Model
{
    protected $table = 'aca_school_telegram_sessions';

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
}
