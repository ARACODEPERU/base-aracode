<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolLevel extends Model
{
    use HasFactory;

    public const INICIAL = 'inicial';
    public const PRIMARIA = 'primaria';
    public const SECUNDARIA = 'secundaria';

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(AcaSchoolGrade::class, 'level_id')->orderBy('sort_order');
    }

    /**
     * Estructura estandar de la educacion basica regular en Peru:
     * Inicial (3, 4, 5 anios), Primaria (1°-6°) y Secundaria (1°-5°).
     * Se usa al precargar la estructura de un colegio.
     */
    public static function standardPeruStructure(): array
    {
        return [
            [
                'code' => self::INICIAL,
                'name' => 'Inicial',
                'grades' => ['3 años', '4 años', '5 años'],
            ],
            [
                'code' => self::PRIMARIA,
                'name' => 'Primaria',
                'grades' => ['1°', '2°', '3°', '4°', '5°', '6°'],
            ],
            [
                'code' => self::SECUNDARIA,
                'name' => 'Secundaria',
                'grades' => ['1°', '2°', '3°', '4°', '5°'],
            ],
        ];
    }
}
