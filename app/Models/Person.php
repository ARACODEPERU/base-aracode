<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeachingResume;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name',
        'full_name',
        'description',
        'document_type_id',
        'number',
        'telephone',
        'email',
        'image',
        'address',
        'is_provider',
        'is_client',
        'contact_telephone',
        'contact_name',
        'contact_email',
        'ubigeo',
        'birthdate',
        'names',
        'father_lastname',
        'mother_lastname',
        'ocupacion',
        'presentacion',
        'gender',
        'status',
        'social_networks',
        'ubigeo_description',
        'company_person_id', /// se registra la empresa a la que pertenese
        'industry',
        'profession',
        'company',
        'industry_id',
        'profession_id',
        'occupation_id',
        'country_id'
    ];

    protected $appends = ['formatted_name'];

    // se esta guardando la ruta completa por eso se comento
    // public function getImageAttribute($value)
    // {
    //     return $value ? asset('storage/' . $value) : null;
    // }

    public function district(): HasOne
    {
        return $this->hasOne(District::class, 'id', 'ubigeo');
    }
    public function teacher(): HasOne
    {
        return $this->hasOne(AcaTeacher::class, 'id', 'person_id');
    }
    public function resumes(): HasMany
    {
        return $this->hasMany(AcaTeachingResume::class, 'person_id');
    }

    public function country(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function getFormattedNameAttribute()
    {
        // buscamos el parámetro una sola vez
        static $format = null;

        if ($format === null) {
            $format = Parameter::where('parameter_code', 'P000020')->value('value_default') ?? 1;
        }

        switch ($format) {
            case 1: // Apellidos + Nombres
                return trim("{$this->father_lastname} {$this->mother_lastname} {$this->names}");
            case 2: // Nombres + Apellidos
                return trim("{$this->names} {$this->father_lastname} {$this->mother_lastname}");
            case 3: // Nombres + Apellido paterno solamente
                return trim("{$this->names} {$this->father_lastname}");
            default: // fallback
                return $this->full_name;
        }
    }
}
