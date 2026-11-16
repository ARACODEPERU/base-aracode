<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Ítem manual de la previsión: impuestos, gratificaciones, CTS o cualquier
 * compromiso que todavía no tiene orden de pago.
 */
class TreasuryForecastAdjustment extends Model
{
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'date',
        'type',
        'currency',
        'amount',
        'concept',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
