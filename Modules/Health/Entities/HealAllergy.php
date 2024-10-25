<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Health\Database\factories\HealAllergyFactory;

class HealAllergy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'icon',
        'title',
        'additional'
    ];

    protected static function newFactory(): HealAllergyFactory
    {
        //return HealAllergyFactory::new();
    }

    public function allergyPatient(): HasMany
    {
        return $this->hasMany(HealAllergyPatient::class, 'allergy_id');
    }
}
