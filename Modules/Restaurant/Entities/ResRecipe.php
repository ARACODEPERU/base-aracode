<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResRecipe extends Model
{
    protected $fillable = ['comanda_id'];

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(ResComanda::class, 'comanda_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResRecipeItem::class, 'recipe_id');
    }
}
