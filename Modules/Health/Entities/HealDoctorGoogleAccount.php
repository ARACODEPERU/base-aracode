<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta de Google Calendar enlazada por un doctor.
 *
 * El refresh token va cifrado con la clave de la aplicacion (cast `encrypted`)
 * y oculto: nunca se serializa hacia el navegador.
 */
class HealDoctorGoogleAccount extends Model
{
    protected $table = 'heal_doctor_google_accounts';

    protected $fillable = [
        'doctor_id',
        'calendar_id',
        'refresh_token',
        'account_email',
        'account_name',
        'account_picture',
        'connected_by_user_id',
        'connected_at',
        'is_active',
        'last_sync_at',
        'last_error',
    ];

    protected $hidden = [
        'refresh_token',
    ];

    protected $casts = [
        'refresh_token' => 'encrypted',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HealDoctor::class, 'doctor_id');
    }
}
