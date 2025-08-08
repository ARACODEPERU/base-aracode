<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Sales\Database\factories\SalePaymentQuotaFactory;

class SalePaymentQuota extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'payment_method_id',
        'sale_document_quota_id',
        'reference',
        'payment_date',
        'amount_applied',
        'description',
        'estado'
    ];

    protected static function newFactory(): SalePaymentQuotaFactory
    {
        //return SalePaymentQuotaFactory::new();
    }
}
