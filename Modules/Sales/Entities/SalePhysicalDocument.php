<?php

namespace Modules\Sales\Entities;

use App\Models\SaleDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sales\Database\factories\SalePhysicalDocumentFactory;

class SalePhysicalDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'document_type',
        'serie',
        'corelative',
        'document_date',
        'document_expiration',
        'client_id',
        'client_type_doc',
        'client_number',
        'client_rzn_social',
        'client_address',
        'client_ubigeo_code',
        'client_ubigeo_description',
        'client_phone',
        'client_email',
        'products',
        'payments',
        'total',
        'status',
        'sale_id'
    ];

    public function saleDocumentType(): BelongsTo
    {
        return $this->belongsTo(SaleDocumentType::class,'document_type','id');
    }
}
