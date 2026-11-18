<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cheque o letra de cambio, por cobrar o por pagar.
 *
 * La cartera no mueve el libro por sí sola: es el registro de los documentos
 * en cartera y alimenta la previsión de caja con lo que vence. El cobro o pago
 * efectivo se registra como ingreso/egreso en el libro cuando corresponda.
 */
class TreasuryDocument extends Model
{
    public const TYPE_CHECK = 'cheque';
    public const TYPE_BILL = 'letra';

    public const DIRECTION_RECEIVABLE = 'receivable';
    public const DIRECTION_PAYABLE = 'payable';

    public const STATUS_PORTFOLIO = 'portfolio';
    public const STATUS_DEPOSITED = 'deposited';
    public const STATUS_COLLECTED = 'collected';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROTESTED = 'protested';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_LABELS = [
        self::TYPE_CHECK => 'Cheque',
        self::TYPE_BILL => 'Letra',
    ];

    public const DIRECTION_LABELS = [
        self::DIRECTION_RECEIVABLE => 'Por cobrar',
        self::DIRECTION_PAYABLE => 'Por pagar',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PORTFOLIO => 'En cartera',
        self::STATUS_DEPOSITED => 'Depositado',
        self::STATUS_COLLECTED => 'Cobrado',
        self::STATUS_PAID => 'Pagado',
        self::STATUS_PROTESTED => 'Protestado',
        self::STATUS_CANCELLED => 'Anulado',
    ];

    /** Estados que todavía representan dinero esperado. */
    public const PENDING_STATUSES = [self::STATUS_PORTFOLIO, self::STATUS_DEPOSITED];

    protected $fillable = [
        'type',
        'direction',
        'number',
        'bank_name',
        'issuer',
        'issuer_doc_type',
        'issuer_doc_number',
        'supplier_id',
        'amount',
        'currency',
        'issue_date',
        'due_date',
        'status',
        'treasury_account_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TreasurySupplier::class, 'supplier_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeReceivable(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_RECEIVABLE);
    }

    public function scopePayable(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_PAYABLE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', self::PENDING_STATUSES);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function directionLabel(): string
    {
        return self::DIRECTION_LABELS[$this->direction] ?? $this->direction;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isReceivable(): bool
    {
        return $this->direction === self::DIRECTION_RECEIVABLE;
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }

    /** Un documento pendiente con vencimiento anterior a hoy. */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date && $this->due_date->isBefore(today());
    }
}
