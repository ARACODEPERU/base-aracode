<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Area curricular del colegio (catalogo editable). Cada area pertenece a un
 * nivel de la educacion basica regular y se usa como lista de opciones del
 * registro de notas del docente.
 */
class AcaSchoolArea extends Model
{
    use HasFactory;

    public const LEVEL_INICIAL = 'inicial';
    public const LEVEL_PRIMARIA = 'primaria';
    public const LEVEL_SECUNDARIA = 'secundaria';

    /** Codigos de nivel admitidos (coinciden con aca_school_levels.code). */
    public const LEVELS = [
        self::LEVEL_INICIAL,
        self::LEVEL_PRIMARIA,
        self::LEVEL_SECUNDARIA,
    ];

    protected $fillable = [
        'school_id',
        'name',
        'level',
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

    /** Etiquetas legibles por codigo de nivel. */
    public static function levelLabels(): array
    {
        return [
            self::LEVEL_INICIAL => 'Inicial',
            self::LEVEL_PRIMARIA => 'Primaria',
            self::LEVEL_SECUNDARIA => 'Secundaria',
        ];
    }

    /** Etiqueta legible de un codigo de nivel. */
    public static function levelLabel(string $code): string
    {
        return self::levelLabels()[$code] ?? ucfirst($code);
    }

    /**
     * Areas curriculares estandar por nivel segun el CNEB (MINEDU).
     * Igual que AcaSchoolLevel::standardPeruStructure(): permite precargar
     * el catalogo con un clic y sirve de fallback del registro de notas
     * mientras el colegio no haya cargado el suyo.
     */
    public static function standardCnebAreas(): array
    {
        return [
            self::LEVEL_INICIAL => [
                'Desarrollo personal, social y comunicación',
                'Ciencia y ambiente',
                'Psicomotricidad',
            ],
            self::LEVEL_PRIMARIA => [
                'Matemática',
                'Comunicación',
                'Ciencia y Tecnología',
                'Personal Social',
                'Educación Física',
                'Arte y Cultura',
                'Inglés como lengua extranjera',
                'Educación Religiosa',
            ],
            self::LEVEL_SECUNDARIA => [
                'Matemática',
                'Comunicación',
                'Ciencia y Tecnología',
                'Ciencias Sociales',
                'Desarrollo Personal, Ciudadanía y Cívica',
                'Educación Física',
                'Arte y Cultura',
                'Inglés como lengua extranjera',
                'Educación Religiosa',
                'Educación para el Trabajo',
            ],
        ];
    }
}
