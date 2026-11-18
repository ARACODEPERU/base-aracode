<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora del modo prueba de suscripciones.
 *
 * Cada botón del panel (crear, renovar, cancelar, limpiar) y cada webhook que
 * afecta una suscripción de prueba deja una fila, para que el administrador
 * vea qué pasó sin revisar logs del servidor.
 */
class AcaSubscriptionTestLog extends Model
{
    protected $table = 'aca_subscription_test_logs';

    protected $fillable = [
        'subscription_id',
        'preapproval_id',
        'action',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Registra un evento del modo prueba.
     *
     * @param  array<string, mixed>  $context
     */
    public static function record(
        int $subscriptionId,
        string $action,
        string $message,
        array $context = [],
        string $level = 'info',
        ?string $preapprovalId = null,
    ): self {
        return self::create([
            'subscription_id' => $subscriptionId,
            'preapproval_id' => $preapprovalId,
            'action' => $action,
            'level' => $level,
            'message' => mb_substr($message, 0, 2000),
            'context' => $context ?: null,
        ]);
    }
}
