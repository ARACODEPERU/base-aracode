<?php

namespace Modules\Sales\Entities;

use App\Models\SaleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleSummaryDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'summary_id',
        'model_name',
        'invoice_type_doc',
        'invoice_serie',
        'invoice_document_name',
        'invoice_correlative',
        'status',
        'total'
    ];

    public function document(): HasOne
    {
        return $this->hasOne(SaleDocument::class,'id','document_id');
    }
}
