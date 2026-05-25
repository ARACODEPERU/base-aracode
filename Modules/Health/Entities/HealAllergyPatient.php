<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Health\Database\factories\HealAllergyPatientFactory;
use Modules\Health\Support\LogsActivity;

class HealAllergyPatient extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HealPatient::class, 'patient_id');
    }

    public function allergy(): BelongsTo
    {
        return $this->belongsTo(HealAllergy::class, 'allergy_id');
    }
}
