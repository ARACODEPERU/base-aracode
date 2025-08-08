<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sales\Database\factories\SaleDocumentQuotaFactory;

class SaleDocumentQuota extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_document_id',
        'quota_number',
        'amount',
        'due_date',
        'balance',
        'status',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(SalePaymentQuota::class,'sale_document_quota_id','id');
    }
}
