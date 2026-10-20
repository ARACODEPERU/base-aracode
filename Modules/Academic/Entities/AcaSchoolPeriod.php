<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaSchoolPeriod extends Model
{
    use HasFactory;

    public const TYPE_BIMESTRAL = 'bimestral';
    public const TYPE_TRIMESTRAL = 'trimestral';
    public const TYPE_CUATRIMESTRAL = 'cuatrimestral';
    public const TYPE_SEMESTRAL = 'semestral';
    public const TYPE_PERSONALIZADA = 'personalizada';

    protected $fillable = [
        'school_id',
        'year_id',
        'organization_type',
        'name',
        'number',
        'start_date',
        'end_date',
        'status',
        'observations',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolYear::class, 'year_id');
    }
}
