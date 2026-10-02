<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AcaSchoolEnrollment extends Model
{
    use HasFactory;

    // Tipos de matricula (terminologia SIAGIE / MINEDU)
    public const TYPE_NUEVA = 'nueva';
    public const TYPE_PROMOVIDA = 'promovida';
    public const TYPE_REPITENTE = 'repitente';
    public const TYPE_TRASLADO = 'traslado';
    public const TYPE_REINGRESO = 'reingreso';

    // Estados de la matricula
    public const STATUS_ACTIVO = 'activo';
    public const STATUS_RETIRADO = 'retirado';
    public const STATUS_TRASLADO_SALIDA = 'traslado_salida';
    public const STATUS_ANULADO = 'anulado';

    protected $fillable = [
        'school_id',
        'year_id',
        'student_id',
        'section_id',
        'type',
        'status',
        'enrollment_date',
        'guardian_person_id',
        'guardian_relationship',
        'guardian_phone',
        'observations',
        'sale_note_id',
        'document_id',
        'user_id_registers',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolYear::class, 'year_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'guardian_person_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id_registers');
    }

    protected static function booted(): void
    {
        static::creating(function (self $enrollment) {
            if (Auth::check() && blank($enrollment->user_id_registers)) {
                $enrollment->user_id_registers = Auth::id();
            }
        });
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_NUEVA => 'Nueva',
            self::TYPE_PROMOVIDA => 'Promovida',
            self::TYPE_REPITENTE => 'Repitente',
            self::TYPE_TRASLADO => 'Traslado',
            self::TYPE_REINGRESO => 'Reingreso',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVO => 'Activo',
            self::STATUS_RETIRADO => 'Retirado',
            self::STATUS_TRASLADO_SALIDA => 'Traslado de salida',
            self::STATUS_ANULADO => 'Anulado',
        ];
    }

    /**
     * Vacantes libres de la seccion para un año escolar dado.
     */
    public static function availableSeats(int $sectionId, int $yearId): int
    {
        $section = AcaSchoolSection::findOrFail($sectionId);

        $taken = self::where('section_id', $sectionId)
            ->where('year_id', $yearId)
            ->where('status', self::STATUS_ACTIVO)
            ->count();

        return max(0, $section->capacity - $taken);
    }
}
