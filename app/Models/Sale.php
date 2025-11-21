<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Sales\Entities\SalePhysicalDocument;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'local_id',
        'total',
        'advancement',
        'total_discount',
        'payments',
        'petty_cash_id',
        'status',
        'sale_date',
        'physical',
        'invoice_razon_social',
        'invoice_ruc',
        'invoice_direccion',
        'invoice_ubigeo',
        'invoice_type',
        'payment_installments'
    ];

    protected $casts = [
        'payments' => 'array',
    ];

    public function saleProduct(): HasMany
    {
        return $this->hasMany(SaleProduct::class, 'sale_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SaleDocument::class, 'sale_id', 'id');
    }

    public function physicalDocument(): HasOne
    {
        return $this->hasOne(SalePhysicalDocument::class, 'sale_id', 'id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(Person::class, 'id', 'client_id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(SaleDocument::class, 'sale_id', 'id');
    }

    public function establishment(): HasOne
    {
        return $this->hasOne(LocalSale::class, 'id', 'local_id');
    }
}
