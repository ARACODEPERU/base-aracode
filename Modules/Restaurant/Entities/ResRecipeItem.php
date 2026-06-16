<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResRecipeItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'supply_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(ResRecipe::class, 'recipe_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(ResSupply::class, 'supply_id');
    }
}
