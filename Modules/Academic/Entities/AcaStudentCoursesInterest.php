<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\factories\AcaStudentCoursesInterestFactory;

class AcaStudentCoursesInterest extends Model
{
    use HasFactory;

    protected $table = 'aca_student_courses_interests';
    public $incrementing = false;
    protected $primaryKey = null; // 🔹 No hay una sola columna como ID
    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
    ];

    // 🔹 Sobrescribimos para evitar errores con claves compuestas
    protected function setKeysForSaveQuery($query)
    {
        $query
            ->where('student_id', '=', $this->getAttribute('student_id'))
            ->where('course_id', '=', $this->getAttribute('course_id'));
        return $query;
    }

    // 🔹 Relaciones
    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }
}
