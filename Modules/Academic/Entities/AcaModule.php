<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcaModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'position',
        'description',
        'teacher_id',
        'allow_certificate_download',
        'certificate_description',
        'certificate_title',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function themes(): HasMany
    {
        return $this->hasMany(AcaTheme::class, 'module_id');
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcaTeacher::class, 'teacher_id');
    }

    /**
     * Relación con el examen regular del módulo (no simulacro)
     */
    public function exam(): HasOne
    {
        // El examen del módulo no cuelga de un contenido: con content_id null
        // se distingue del examen de una clase, que también lleva module_id.
        return $this->hasOne(AcaExam::class, 'module_id')
            ->where('is_mock', false)
            ->whereNull('content_id');
    }

    /**
     * Relación con el examen simulacro del módulo
     */
    public function mockExam(): HasOne
    {
        return $this->hasOne(AcaExam::class, 'module_id')
            ->where('is_mock', true)
            ->whereNull('content_id');
    }

}
