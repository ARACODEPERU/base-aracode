<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaPerson extends Model
{
    use HasFactory;

    protected $table = 'people';

    public function teacher()
    {
        return $this->hasOne(AcaTeacher::class, 'person_id');
    }

    public function student()
    {
        return $this->hasOne(AcaStudent::class, 'person_id');
    }
    public function resumes(): HasMany
    {
        return $this->hasMany(AcaTeachingResume::class, 'person_id');
    }
}
