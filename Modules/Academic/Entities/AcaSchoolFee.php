<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaSchoolFee extends Model
{
    protected $fillable = [
        'year_id',
        'fee_type_id',
        'level_id',
        'grade_id',
        'section_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolYear::class, 'year_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolFeeType::class, 'fee_type_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolLevel::class, 'level_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolGrade::class, 'grade_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }

    /**
     * Devuelve la tarifa vigente mas especifica para el alcance dado:
     * seccion > grado > nivel > general (con nulls solo cuando no hay
     * especifica). Devuelve null si no hay tarifa configurada.
     */
    public static function resolve(int $yearId, int $feeTypeId, ?int $levelId = null, ?int $gradeId = null, ?int $sectionId = null): ?self
    {
        return self::query()
            ->where('year_id', $yearId)
            ->where('fee_type_id', $feeTypeId)
            ->where('status', true)
            ->where(function ($q) use ($levelId) {
                $q->whereNull('level_id')->orWhere('level_id', $levelId);
            })
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id')->orWhere('grade_id', $gradeId);
            })
            ->where(function ($q) use ($sectionId) {
                $q->whereNull('section_id')->orWhere('section_id', $sectionId);
            })
            ->orderByRaw('(section_id IS NOT NULL) DESC, (grade_id IS NOT NULL) DESC, (level_id IS NOT NULL) DESC')
            ->first();
    }
}
