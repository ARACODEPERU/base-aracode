<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Periodo contable de tesorería (mes). Cerrado bloquea altas, ediciones y
 * anulaciones con fecha dentro del mes; la reapertura queda auditada.
 */
class TreasuryPeriod extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'period_year',
        'period_month',
        'status',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'notes',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(TreasuryPeriodBalance::class, 'period_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /** Etiqueta legible: «Noviembre 2026». */
    public function label(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($months[$this->period_month] ?? $this->period_month) . ' ' . $this->period_year;
    }
}
