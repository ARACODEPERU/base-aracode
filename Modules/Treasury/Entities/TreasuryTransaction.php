<?php

namespace Modules\Treasury\Entities;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Movimiento del libro de bancos.
 *
 * `currency` es la moneda del movimiento y `amount_base` su equivalente en
 * soles congelado al registrar. Un movimiento con `treasury_reconciliation_id`
 * pertenece a un acta cerrada: es inmutable (no se edita, anula ni borra).
 */
class TreasuryTransaction extends Model
{
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const SOURCE_AUTO = 'auto';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_BACKFILL = 'backfill';
    public const SOURCE_STATEMENT = 'statement';

    protected $fillable = [
        'treasury_account_id',
        'source_wallet_account_id',
        'transaction_date',
        'transaction_time',
        'type',
        'treasury_category_id',
        'cost_center_id',
        'origin_type',
        'origin_id',
        'transfer_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'fee_amount',
        'igv_amount',
        'itf_amount',
        'reference',
        'operation_number',
        'description',
        'attachment_path',
        'payment_method_id',
        'source',
        'treasury_reconciliation_id',
        'reconciled_at',
        'voided_at',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_base' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'igv_amount' => 'decimal:2',
        'itf_amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /**
     * Normaliza la hora del movimiento a HH:MM:SS (o null si no es válida).
     * El input del navegador envía HH:MM; los extractos suelen traer HH:MM:SS.
     */
    public static function normalizeTimeValue(mixed $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $text);

            if ($parsed && $parsed->format($format) === $text) {
                return $parsed->format('H:i:s');
            }
        }

        return null;
    }

    public function setTransactionTimeAttribute(mixed $value): void
    {
        $this->attributes['transaction_time'] = self::normalizeTimeValue($value);
    }

    protected static function booted(): void
    {
        // El movimiento conciliado no se toca: es la evidencia del acta.
        static::updating(function (TreasuryTransaction $transaction) {
            if ($transaction->getOriginal('treasury_reconciliation_id')) {
                throw TreasuryOperationException::reconciled();
            }
        });

        static::deleting(function (TreasuryTransaction $transaction) {
            if ($transaction->treasury_reconciliation_id) {
                throw TreasuryOperationException::reconciled();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreasuryCategory::class, 'treasury_category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(TreasuryCostCenter::class, 'cost_center_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(TreasuryReconciliation::class, 'treasury_reconciliation_id');
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

    public function scopeInCurrency(Builder $query, ?string $currency): Builder
    {
        return $currency ? $query->where('currency', strtoupper($currency)) : $query;
    }

    /**
     * Un movimiento conciliado no se modifica nunca más.
     */
    public function isLocked(): bool
    {
        return $this->treasury_reconciliation_id !== null;
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
        $dateValue = \Illuminate\Support\Carbon::parse($date)->toDateString();

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
     * Lanza si el movimiento está conciliado.
     */
    public function void(?int $userId = null): bool
    {
        if ($this->isLocked()) {
            throw TreasuryOperationException::reconciled();
        }

        $this->voided_at = $this->voided_at ?? now();

        if ($userId) {
            $this->user_id = $this->user_id ?? $userId;
        }

        return $this->save();
    }
}
