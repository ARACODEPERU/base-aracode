<?php

namespace Modules\Sales\Entities;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturador3ImportMap extends Model
{
    protected $fillable = [
        'f3_item_id',
        'product_id',
        'interne',
        'is_product',
        'imported_at',
    ];

    protected $casts = [
        'is_product' => 'boolean',
        'imported_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
