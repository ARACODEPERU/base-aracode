<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Health\Database\factories\HealAllergyPatientFactory;

class HealAllergyPatient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'allergy_id',
        'patient_id',
        'description',
        'additional',
        'additional1'
    ];

    protected static function newFactory(): HealAllergyPatientFactory
    {
        //return HealAllergyPatientFactory::new();
    }

    public function allergies()
    {
        return $this->belongsToMany(HealAllergy::class, 'heal_allergy_patients', 'id', 'allergy_id');
    }
}
