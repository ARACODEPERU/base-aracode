<?php

namespace Modules\Dental\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Health\Entities\HealAttention;

class DentAppointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'correlative',
        'patient_id',
        'patient_person_id',
        'doctor_id',
        'doctor_person_id',
        'date_appointmen',
        'time_appointmen',
        'date_end_appointmen',
        'time_end_appointmen',
        'email',
        'telephone',
        'description',
        'details',
        'message',
        'status',
        'no_show_at',
        'important',
        'created_user_id',
        'updated_user_id',
        'sick_time'
    ];

    protected $casts = [
        'no_show_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->correlative = self::generateCorrelative();
        });
    }

    public static function generateCorrelative()
    {
        $latest = self::latest('id')->first();
        $nextCorrelative = $latest ? $latest->correlative + 1 : 1;
        return str_pad($nextCorrelative, 6, '0', STR_PAD_LEFT);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Person::class, 'id', 'patient_person_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Person::class, 'id', 'doctor_person_id');
    }

    public function healthAttentions(): HasMany
    {
        return $this->hasMany(HealAttention::class, 'appointment_id');
    }

    public function healthAttention(): HasOne
    {
        return $this->hasOne(HealAttention::class, 'appointment_id')->latestOfMany();
    }
}
