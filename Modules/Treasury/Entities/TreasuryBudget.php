<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Presupuesto mensual de egresos para un centro de costo.
 *
 * Si tiene categoría, el presupuesto aplica solo a esa categoría; sin
 * categoría, aplica a todos los egresos del centro. La ejecución no se guarda:
 * se calcula contra los movimientos reales del mes.
 */
class TreasuryBudget extends Model
{
    protected $fillable = [
        'period_year',
        'period_month',
        'cost_center_id',
        'treasury_category_id',
        'currency',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(TreasuryCostCenter::class, 'cost_center_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreasuryCategory::class, 'treasury_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }
}
