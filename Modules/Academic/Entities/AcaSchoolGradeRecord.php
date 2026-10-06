<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota del colegio por alumno (matricula), area curricular y bimestre.
 * Escala dual segun MINEDU: vigesimal (score_number 0-20) para Primaria y
 * Secundaria; literal (score_letter AD/A/B/C) para Inicial.
 */
class AcaSchoolGradeRecord extends Model
{
    protected $table = 'aca_school_grade_records';

    protected $fillable = [
        'enrollment_id',
        'section_id',
        'year_id',
        'area',
        'bimester',
        'scale_type',
        'score_number',
        'score_letter',
        'observations',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bimester' => 'integer',
        'score_number' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }
}
