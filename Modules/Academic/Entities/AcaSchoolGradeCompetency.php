<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota por competencia del registro de evaluacion CNEB (formato SIAGIE):
 * nivel de logro (AD/A/B/C) o nota vigesimal 0-20 segun la escala del
 * nivel, mas la conclusion descriptiva de la competencia por bimestre.
 */
class AcaSchoolGradeCompetency extends Model
{
    use HasFactory;

    public const BIMESTERS = [1, 2, 3, 4];

    public const SCALE_LITERAL = 'literal';
    public const SCALE_VIGESIMAL = 'vigesimal';

    // Niveles de logro de la escala literal (MINEDU)
    public const LETTER_AD = 'AD';
    public const LETTER_A = 'A';
    public const LETTER_B = 'B';
    public const LETTER_C = 'C';

    public const LETTERS = [
        self::LETTER_AD,
        self::LETTER_A,
        self::LETTER_B,
        self::LETTER_C,
    ];

    /** Rango vigesimal equivalente a cada nivel de logro (para sugerencias). */
    public const VIGESIMAL_BANDS = [
        self::LETTER_AD => [18, 20],
        self::LETTER_A => [14, 17],
        self::LETTER_B => [11, 13],
        self::LETTER_C => [0, 10],
    ];

    protected $fillable = [
        'school_id',
        'year_id',
        'section_id',
        'enrollment_id',
        'competency_id',
        'bimester',
        'scale_type',
        'score_letter',
        'score_number',
        'conclusion',
        'user_id_registers',
    ];

    protected $casts = [
        'bimester' => 'integer',
        'score_number' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolCompetency::class, 'competency_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }

    /** Nota vigesimal equivalente del nivel de logro (banda inferior). */
    public static function vigesimalOfLetter(string $letter): int
    {
        return self::VIGESIMAL_BANDS[$letter][0] ?? 0;
    }

    /** Nivel de logro equivalente de una nota vigesimal. */
    public static function letterOfVigesimal(int $score): string
    {
        foreach (self::VIGESIMAL_BANDS as $letter => [$min, $max]) {
            if ($score >= $min && $score <= $max) {
                return $letter;
            }
        }

        return self::LETTER_C;
    }
}
