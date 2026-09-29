<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Destinatario de una campana de notificaciones.
 *
 * Se guarda un snapshot del padron al lanzar la campana: el job solo toma las
 * filas "pending", asi que si el worker se detiene la campana se puede retomar
 * sin reenviar a quienes ya recibieron el mensaje.
 */
class AcaNotificationCampaignRecipient extends Model
{
    use HasFactory;

    protected $table = 'aca_notification_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'student_id',
        'person_id',
        'name',
        'phone',
        'source',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AcaNotificationCampaign::class, 'campaign_id');
    }
}
