<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Notificación de MercadoPago recibida por el webhook.
 *
 * Permite auditar qué llegó y reprocesar una notificación fallida.
 */
class MercadoPagoEvent extends Model
{
    protected $table = 'mercadopago_events';

    protected $fillable = [
        'topic',
        'action',
        'data_id',
        'payload',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
