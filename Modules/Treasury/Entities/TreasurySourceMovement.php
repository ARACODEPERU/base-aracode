<?php

namespace Modules\Treasury\Entities;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operación financiera reportada por un módulo de origen.
 *
 * Una fila por movimiento de dinero real (un pago dividido en dos métodos son
 * dos filas). El estado refleja qué pasó con esa operación en el libro:
 *
 *  pending     recién registrada, todavía sin procesar
 *  processing  en curso (marca temporal para detectar procesos abandonados)
 *  processed   el libro ya tiene su movimiento (treasury_transaction_id)
 *  failed      no se pudo registrar; requiere reintento administrativo
 *  ignored     no aplica al libro (método excluido o sin cuenta mapeada)
 *  reversed    el movimiento del libro fue anulado; la fila se conserva
 */
class TreasurySourceMovement extends Model
{
    public const MODULE_SALES = 'sales';
    public const MODULE_PURCHASES = 'purchases';
    public const MODULE_PETTY_CASH = 'petty_cash';

    public const SOURCE_SALE = 'sale';
    public const SOURCE_SALE_DOCUMENT = 'sale_document';
    public const SOURCE_SALE_PAYMENT_QUOTA = 'sale_payment_quota';
    public const SOURCE_SALE_SCHEDULE_DESTINATION = 'sale_payment_schedule_destination';

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_REVERSED = 'reversed';

    /** Minutos tras los cuales un "processing" se considera abandonado. */
    public const PROCESSING_TIMEOUT_MINUTES = 5;

    protected $fillable = [
        'module',
        'source_type',
        'source_id',
        'source_line',
        'operation',
        'type',
        'status',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'movement_date',
        'payment_method_id',
        'treasury_account_id',
        'treasury_transaction_id',
        'reversal_of_id',
        'idempotency_key',
        'reference',
        'description',
        'error_message',
        'source',
        'meta',
        'processed_at',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_base' => 'decimal:2',
        'movement_date' => 'date',
        'processed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'treasury_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Movimientos con impacto real en el libro (procesados, sin revertir).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeForOrigin(Builder $query, string $sourceType, int $sourceId): Builder
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isReversal(): bool
    {
        return $this->reversal_of_id !== null;
    }

    /**
     * Un "processing" viejo quedó colgado (proceso interrumpido) y puede
     * reprocesarse sin riesgo: la clave única impide duplicar.
     */
    public function isStale(): bool
    {
        if ($this->status !== self::STATUS_PROCESSING) {
            return false;
        }

        return $this->updated_at === null
            || $this->updated_at->lt(now()->subMinutes(self::PROCESSING_TIMEOUT_MINUTES));
    }
}
