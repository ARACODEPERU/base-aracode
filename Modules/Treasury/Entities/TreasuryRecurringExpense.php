<?php

namespace Modules\Treasury\Entities;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Gasto recurrente (planilla, servicio, insumo, alquiler). El comando
 * `treasury:run-recurring` lo encola en la cola `database`; el job crea la
 * orden de pago y, si `auto_pay` está activo, la paga.
 */
class TreasuryRecurringExpense extends Model
{
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_BIWEEKLY = 'biweekly';
    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_QUARTERLY = 'quarterly';
    public const FREQ_ANNUAL = 'annual';

    public const FREQUENCY_LABELS = [
        self::FREQ_WEEKLY => 'Semanal',
        self::FREQ_BIWEEKLY => 'Quincenal',
        self::FREQ_MONTHLY => 'Mensual',
        self::FREQ_QUARTERLY => 'Trimestral',
        self::FREQ_ANNUAL => 'Anual',
    ];

    public const TYPE_LABELS = [
        'payroll' => 'Planilla / personal',
        'service' => 'Servicio',
        'supplies' => 'Insumos',
        'rent' => 'Alquiler',
        'tax' => 'Impuesto',
        'other' => 'Otro',
    ];

    protected $fillable = [
        'concept',
        'type',
        'supplier_id',
        'treasury_category_id',
        'currency',
        'amount',
        'treasury_account_id',
        'payment_method_id',
        'frequency',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'next_run_date',
        'auto_pay',
        'status',
        'last_run_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'last_run_at' => 'datetime',
        'auto_pay' => 'boolean',
        'status' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TreasurySupplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreasuryCategory::class, 'treasury_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TreasuryRecurringRun::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeDue(Builder $query, ?string $date = null): Builder
    {
        return $query->where('status', true)->whereDate('next_run_date', '<=', $date ?: now()->toDateString());
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCY_LABELS[$this->frequency] ?? $this->frequency;
    }

    /**
     * Siguiente ocurrencia después de una fecha, respetando el día del mes
     * y ajustando a meses cortos (31 → último día de febrero).
     */
    public function advanceFrom(Carbon $from): Carbon
    {
        return match ($this->frequency) {
            self::FREQ_WEEKLY => $from->copy()->addWeek(),
            self::FREQ_BIWEEKLY => $from->copy()->addWeeks(2),
            self::FREQ_QUARTERLY => $this->addMonthsClamped($from, 3),
            self::FREQ_ANNUAL => $this->addMonthsClamped($from, 12),
            default => $this->addMonthsClamped($from, 1),
        };
    }

    /**
     * Fechas de ocurrencia dentro de un rango, para la previsión de caja.
     *
     * @return array<int, string>
     */
    public function occurrencesBetween(string $from, string $to): array
    {
        $dates = [];
        $cursor = $this->next_run_date?->copy() ?? Carbon::parse($this->start_date);
        $limit = Carbon::parse($to);
        $guard = 0;

        while ($cursor->lte($limit) && $guard < 200) {
            if ($cursor->gte(Carbon::parse($from)) && (! $this->end_date || $cursor->lte($this->end_date))) {
                $dates[] = $cursor->toDateString();
            }

            $cursor = $this->advanceFrom($cursor);
            $guard++;
        }

        return $dates;
    }

    private function addMonthsClamped(Carbon $date, int $months): Carbon
    {
        $day = $this->day_of_month ?: $date->day;
        $target = $date->copy()->startOfMonth()->addMonths($months);

        return $target->day(min($day, $target->daysInMonth));
    }
}
