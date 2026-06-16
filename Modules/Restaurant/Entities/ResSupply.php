<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResSupply extends Model
{
    public const UNITS = ['unidad', 'kg', 'gramo', 'litro', 'ml', 'docena'];

    protected $fillable = [
        'name',
        'unit',
        'stock',
        'stock_min',
        'status',
        'notes',
    ];

    protected $casts = [
        'stock' => 'decimal:4',
        'stock_min' => 'decimal:4',
        'status' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(ResSupplyMovement::class, 'supply_id');
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ResRecipeItem::class, 'supply_id');
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock <= (float) $this->stock_min;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->stock <= 0;
    }

    public function suggestedPurchaseQty(): float
    {
        $stock = (float) $this->stock;
        $min = (float) $this->stock_min;

        return max($min * 2 - $stock, $min - $stock, 1);
    }
}
