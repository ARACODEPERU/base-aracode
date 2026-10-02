<?php

namespace Modules\Academic\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecera de una campana de notificaciones masivas (SMS o WhatsApp).
 *
 * El avance (enviados, fallidos y el telefono en curso) vive aqui para que la
 * barra de progreso se pueda consultar por sondeo aunque el administrador
 * cierre el aviso y el proceso siga en la cola.
 */
class AcaNotificationCampaign extends Model
{
    use HasFactory;

    protected $table = 'aca_notification_campaigns';

    protected $fillable = [
        'user_id',
        'course_id',
        'channel',
        'is_test',
        'message',
        'time_label',
        'total_recipients',
        'sent_count',
        'failed_count',
        'current_phone',
        'status',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_test' => 'boolean',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AcaNotificationCampaignRecipient::class, 'campaign_id');
    }

    /**
     * Porcentaje de avance: enviados + fallidos sobre el total del padron.
     */
    public function percent(): int
    {
        $total = (int) $this->total_recipients;

        if ($total <= 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $processed = (int) $this->sent_count + (int) $this->failed_count;

        return (int) min(100, floor($processed * 100 / $total));
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
