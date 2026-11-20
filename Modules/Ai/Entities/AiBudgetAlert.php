<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Alerta mensual de presupuesto (aviso o límite excedido).
 */
class AiBudgetAlert extends Model
{
    protected $table = 'ai_budget_alerts';

    protected $fillable = [
        'period',
        'level',
        'spend_usd',
        'budget_usd',
        'threshold_percent',
        'notified_at',
    ];

    protected $casts = [
        'spend_usd' => 'float',
        'budget_usd' => 'float',
        'threshold_percent' => 'integer',
        'notified_at' => 'datetime',
    ];
}
