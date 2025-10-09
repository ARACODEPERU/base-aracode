<?php

namespace Modules\Academic\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcaStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'person_id',
        'new_student',
        'arrival_source_id',
        'arrival_source_information'
    ];

    public function person(): HasOne
    {
        return $this->hasOne(Person::class, 'id', 'person_id');
    }
    public function registrations(): HasMany
    {
        return $this->hasMany(AcaCapRegistration::class, 'student_id');
    }
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AcaStudentSubscription::class, 'student_id', 'id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(AcaCertificate::class, 'student_id', 'id');
    }

    public function arrivalSource(): BelongsTo
    {
        return $this->belongsTo(AcaArrivalSource::class, 'arrival_source_id');
    }
    public function coursesInterest(): HasMany
    {
        return $this->hasMany(AcaStudentCoursesInterest::class, 'student_id');
    }
}
