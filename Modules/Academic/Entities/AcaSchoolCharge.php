<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AcaSchoolCharge extends Model
{
    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_PAGADO = 'pagado';
    public const STATUS_ANULADO = 'anulado';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'fee_type_id',
        'description',
        'amount',
        'status',
        'paid_at',
        'payment_method',
        'reference',
        'payment_schedule_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolFeeType::class, 'fee_type_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolPaymentSchedule::class, 'payment_schedule_id');
    }

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SaleDocument::class, 'sale_document_id');
    }

    public static function paymentMethods(): array
    {
        return [
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'yape' => 'Yape',
            'plin' => 'Plin',
            'otro' => 'Otro',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDIENTE => 'Pendiente',
            self::STATUS_PAGADO => 'Pagado',
            self::STATUS_ANULADO => 'Anulado',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $charge) {
            if (Auth::check() && blank($charge->created_by)) {
                $charge->created_by = Auth::id();
            }

            if ($charge->status === self::STATUS_PAGADO && blank($charge->paid_at)) {
                $charge->paid_at = now();
            }
        });
    }
}
