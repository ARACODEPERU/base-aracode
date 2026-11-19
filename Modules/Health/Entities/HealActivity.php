<?php

namespace Modules\Health\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HealActivity extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_person_id',
        'actor_display',
        'activity_type',
        'category',
        'subject_type',
        'subject_id',
        'patient_id',
        'patient_display',
        'metadata',
        'request_id',
        'route',
        'http_method',
        'before_hash',
        'after_hash',
        'previous_hash',
        'entry_hash',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * El registro de auditoría es de solo lectura: una vez escrito no se
     * puede modificar ni borrar desde la aplicación. Las purgas por retención
     * se hacen con consultas masivas en el comando correspondiente.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('El registro de auditoría no se puede modificar.');
        });

        static::deleting(function () {
            throw new \RuntimeException('El registro de auditoría no se puede eliminar desde la aplicación.');
        });
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorPerson()
    {
        return $this->belongsTo(Person::class, 'actor_person_id');
    }

    public function patient()
    {
        return $this->belongsTo(HealPatient::class, 'patient_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    public function getActivityLabelAttribute(): string
    {
        return match ($this->activity_type) {
            'created' => 'Creación',
            'updated' => 'Modificación',
            'deleted' => 'Eliminación',
            'signed' => 'Firma',
            'cancelled' => 'Anulación',
            'restored' => 'Restauración',
            'anonymized' => 'Anonimización',
            'deactivated' => 'Desactivación',
            'viewed' => 'Consulta',
            'exported' => 'Exportación',
            'retention_purge' => 'Purga por retención',
            default => ucfirst($this->activity_type),
        };
    }

    public function getSubjectLabelAttribute(): string
    {
        $type = class_basename($this->subject_type);

        return match ($type) {
            'HealAttention' => 'Atención',
            'HealPatient' => 'Paciente',
            'HealDoctor' => 'Doctor',
            'HealProcedure' => 'Procedimiento',
            'HealPatientCharge' => 'Cobro',
            'HealHistory' => 'Historia Clínica',
            'HealSetting' => 'Configuración',
            default => $type,
        };
    }

    public function getSummaryAttribute(): string
    {
        $metadata = $this->metadata ?? [];
        $subjectName = $metadata['subject_name'] ?? $this->subject_label;
        $patientName = $this->patient_display ? " - Paciente: {$this->patient_display}" : '';

        return "{$this->activity_label} de {$subjectName}{$patientName}";
    }
}
