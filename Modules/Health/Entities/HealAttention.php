<?php

namespace Modules\Health\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HealAttention extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_at',
        'service_type',
        'patient_id',
        'doctor_id',
        'history_id',
        'user_id',
        'appointment_id',
        'patient_story',
        'doctor_observation',
        'clinical_findings',
        'cie10_id',
        'diagnosis',
        'treatment_plan',
        'observations',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'respiratory_rate',
        'height',
        'weight',
        'body_mass_index',
        'signed_at',
        'signed_by_doctor_id',
        'signed_by_user_id',
        'signature_ip',
    ];

    protected $casts = [
        'attention_at' => 'datetime',
        'signed_at' => 'datetime',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'body_mass_index' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HealPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HealDoctor::class, 'doctor_id');
    }

    public function cie10(): BelongsTo
    {
        return $this->belongsTo(HealCie10::class, 'cie10_id');
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(HealHistory::class, 'history_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Dental\Entities\DentAppointment::class, 'appointment_id');
    }

    public function signedByDoctor(): BelongsTo
    {
        return $this->belongsTo(HealDoctor::class, 'signed_by_doctor_id');
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(HealAttentionExam::class, 'attention_id');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(HealAttentionTreatment::class, 'attention_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(HealPatientCharge::class, 'attention_id');
    }

    public function odontogram(): HasOne
    {
        return $this->hasOne(HealOdontogram::class, 'attention_id');
    }
}
