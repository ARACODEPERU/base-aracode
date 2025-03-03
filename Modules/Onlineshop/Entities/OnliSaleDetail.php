<?php

namespace Modules\Onlineshop\Entities;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaSubscriptionType;

class OnliSaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'item_id',
        'entitie',
        'price',
        'quantity',
        'onli_item_id'
    ];

    protected static function newFactory()
    {
        return \Modules\Onlineshop\Database\factories\OnliSaleDetailFactory::new();
    }
    public function course(): HasOne
    {
        return $this->hasOne(AcaCourse::class, 'id', 'item_id');
    }
    public function item(): HasOne
    {
        return $this->hasOne(OnliItem::class, 'id', 'onli_item_id');
    }
    public function subscription(): HasOne
    {
        return $this->hasOne(AcaSubscriptionType::class, 'id', 'item_id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'item_id');
    }
}
