<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Serie extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'description',
        'number',
        'user_id',
        'local_id'
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(SaleDocumentType::class,'document_type_id','id');
    }
}
