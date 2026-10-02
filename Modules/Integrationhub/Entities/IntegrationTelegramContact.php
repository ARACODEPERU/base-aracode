<?php

namespace Modules\Integrationhub\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vinculo entre una persona del sistema y su chat de Telegram.
 *
 * Se crea cuando la persona escribe su documento en el chat del bot y el padron
 * la reconoce. El estado inactive corresponde a quienes escribieron /baja.
 */
class IntegrationTelegramContact extends Model
{
    use HasFactory;

    protected $table = 'integration_telegram_contacts';

    protected $fillable = [
        'person_id',
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

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Contactos que reciben campanas (ya registraron su chat y no se dieron de baja).
     */
    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNotNull('chat_id');
    }
}
