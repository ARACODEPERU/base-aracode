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
        'subject_type',
        'subject_id',
        'patient_id',
        'patient_display',
        'metadata',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

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
