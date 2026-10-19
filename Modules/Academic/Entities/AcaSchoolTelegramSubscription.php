<?php

namespace Modules\Academic\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suscripción de un chat de Telegram para recibir los avisos de asistencia
 * (entrada y salida) de un alumno del colegio.
 *
 * Un mismo chat (padre, madre o apoderado) puede tener una fila por cada hijo
 * estudiante registrado. Al enviar /baja, todas sus filas pasan a inactive.
 */
class AcaSchoolTelegramSubscription extends Model
{
    protected $table = 'aca_school_telegram_subscriptions';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'school_id',
        'student_id',
        'guardian_person_id',
        'chat_id',
        'telegram_username',
        'telegram_first_name',
        'status',
        'registered_at',
        'last_seen_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'guardian_person_id');
    }

    /**
     * Suscripciones activas que deben recibir avisos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->whereNotNull('chat_id');
    }
}
