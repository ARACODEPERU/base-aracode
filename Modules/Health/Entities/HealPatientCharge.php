<?php

namespace Modules\Health\Entities;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealPatientCharge extends Model
{
    protected $fillable = [
        'patient_id',
        'attention_id',
        'doctor_id',
        'procedure_id',
        'sale_id',
        'sale_document_id',
        'name_snapshot',
        'description_snapshot',
        'default_price',
        'price',
        'quantity',
        'total',
        'currency_type_id',
        'status',
        'charged_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total' => 'decimal:2',
        'charged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HealPatient::class, 'patient_id');
    }

    public function attention(): BelongsTo
    {
        return $this->belongsTo(HealAttention::class, 'attention_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HealDoctor::class, 'doctor_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(HealProcedure::class, 'procedure_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
