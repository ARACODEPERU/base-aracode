<?php

namespace Modules\Commercial\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialContractPayment extends Model
{
    use HasFactory;

    protected $table = 'commercial_contract_payments';

    protected $fillable = [
        'contract_id',
        'payment_number',
        'description',
        'due_date',
        'payment_type',
        'amount',
        'interest_amount',
        'total_amount',
        'balance_amount',
        'currency',
        'status',
        'paid_at',
        'document_payments',
        'notes',
    ];

    protected $casts = [
        'document_payments' => 'array',
        'amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(CommercialContract::class, 'contract_id');
    }
}
