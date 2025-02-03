<?php

namespace Modules\Dental\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Dental\Database\factories\DentAttentionFactory;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealHistory;
use Modules\Health\Entities\HealPatient;

class DentAttention extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'date_time_attention',
        'current_illness',
        'reason_consultation',
        'age',
        'sick_time',
        'appetite',
        'thirst',
        'dream',
        'mood',
        'urine',
        'depositions',
        'weight_loss',
        'pex_tem',
        'pex_pa',
        'pex_fc',
        'pex_fr',
        'pex_peso',
        'pex_talla',
        'pex_imc',
        'treatment',
        'pex_aux_examination',
        'doctor_id',
        'user_id',
        'patient_id',
        'appointment_id',
        'next_appointment_id',
        'signed_accepted',
        'observations',
        'history_id'
    ];

    protected static function newFactory(): DentAttentionFactory
    {
        //return DentAttentionFactory::new();
    }

    public function patient(): HasOne
    {
        return $this->hasOne(HealPatient::class, 'id', 'patient_id');
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(HealHistory::class, 'history_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(HealDoctor::class, 'id', 'doctor_id');
    }

    public function nextappointment(): HasOne
    {
        return $this->hasOne(DentAppointment::class, 'id', 'next_appointment_id');
    }
}
