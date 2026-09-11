<?php

namespace Modules\Treasury\Entities;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TreasuryTransaction extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const SOURCE_AUTO = 'auto';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_BACKFILL = 'backfill';

    protected $fillable = [
        'treasury_account_id',
        'transaction_date',
        'type',
        'treasury_category_id',
        'origin_type',
        'origin_id',
        'amount',
        'reference',
        'description',
        'attachment_path',
        'payment_method_id',
        'source',
        'reconciled_at',
        'voided_at',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreasuryCategory::class, 'treasury_category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Modelo de origen (Sale, SalePaymentQuota, etc.). Se resuelve con
     * mapeo explícito en lugar de morphMap global para no tocar el resto
     * del proyecto. Devuelve null para orígenes desconocidos.
     */
    public function origin(): ?Model
    {
        if (! $this->origin_type || ! $this->origin_id) {
            return null;
        }

        $map = config('treasury.origin_models', []);

        $class = $map[$this->origin_type] ?? (class_exists($this->origin_type) ? $this->origin_type : null);

        if (! $class) {
            return null;
        }

        return $class::query()->find($this->origin_id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->whereNull('reconciled_at');
    }

    /**
     * Firma única lógica de un movimiento automático/backfill, para evitar duplicados.
     */
    public static function signatureFor(string $originType, int $originId, string $type, string $date, float $amount): string
    {
        return implode('|', [$originType, $originId, $type, $date, number_format($amount, 2, '.', '')]);
    }

    /**
     * Indica si ya existe un movimiento activo con la misma firma (idempotencia).
     */
    public static function signatureExists(string $originType, int $originId, string $type, string $date, float $amount): bool
    {
        $dateValue = Carbon::parse($date)->toDateString();

        return static::query()
            ->active()
            ->where('origin_type', $originType)
            ->where('origin_id', $originId)
            ->where('type', $type)
            ->whereDate('transaction_date', $dateValue)
            ->where('amount', round($amount, 2))
            ->exists();
    }

    /**
     * Anula el movimiento conservando el registro (trazabilidad).
     */
    public function void(?int $userId = null): bool
    {
        $this->voided_at = $this->voided_at ?? Carbon::now();
        if ($userId) {
            $this->user_id = $this->user_id ?? $userId;
        }

        return $this->save();
    }
}
