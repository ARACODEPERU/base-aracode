<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Socialevents\Database\factories\EvenCategoryFactory;

class EvenCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'icon',
        'description',
        'status',
        'main_category_id',
        'informations'
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(EvenCategory::class, 'main_category_id', 'id');
    }
}
