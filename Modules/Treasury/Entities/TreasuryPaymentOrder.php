<?php

namespace Modules\Treasury\Entities;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden de pago: agrupa pagos, compras, pagos a proveedores, gastos
 * imprevistos y gastos recurrentes. El dinero sale del libro recién al
 * ejecutar la orden (`status = paid`), nunca al crearla.
 */
class TreasuryPaymentOrder extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_VOIDED = 'voided';

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SUPPLIER_PAYMENT = 'supplier_payment';
    public const TYPE_UNEXPECTED = 'unexpected_expense';
    public const TYPE_RECURRING = 'recurring_expense';
    public const TYPE_PAYROLL = 'payroll';
    public const TYPE_SERVICE = 'service';
    public const TYPE_OTHER = 'other';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PENDING => 'Pendiente de aprobación',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_SCHEDULED => 'Programada',
        self::STATUS_PAID => 'Pagada',
        self::STATUS_REJECTED => 'Rechazada',
        self::STATUS_VOIDED => 'Anulada',
    ];

    public const TYPE_LABELS = [
        self::TYPE_PURCHASE => 'Compra',
        self::TYPE_SUPPLIER_PAYMENT => 'Pago a proveedor',
        self::TYPE_UNEXPECTED => 'Gasto imprevisto',
        self::TYPE_RECURRING => 'Gasto recurrente',
        self::TYPE_PAYROLL => 'Pago de personal',
        self::TYPE_SERVICE => 'Servicio',
        self::TYPE_OTHER => 'Otro',
    ];

    protected $fillable = [
        'code',
        'idempotency_key',
        'type',
        'supplier_id',
        'cost_center_id',
        'description',
        'treasury_category_id',
        'currency',
        'amount',
        'issue_date',
        'due_date',
        'scheduled_date',
        'treasury_account_id',
        'payment_method_id',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'paid_by',
        'paid_at',
        'treasury_transaction_id',
        'recurring_id',
        'attachment_path',
        'notes',
        'created_by',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'scheduled_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TreasurySupplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreasuryCategory::class, 'treasury_category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(TreasuryCostCenter::class, 'cost_center_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'treasury_transaction_id');
    }

    public function recurring(): BelongsTo
    {
        return $this->belongsTo(TreasuryRecurringExpense::class, 'recurring_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_SCHEDULED]);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_SCHEDULED], true)
            && $this->voided_at === null;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->voided_at === null;
    }

    /** Fecha con la que entra a la previsión de caja. */
    public function forecastDate(): ?string
    {
        $date = $this->scheduled_date ?: $this->due_date ?: $this->issue_date;

        return $date?->format('Y-m-d');
    }
}
