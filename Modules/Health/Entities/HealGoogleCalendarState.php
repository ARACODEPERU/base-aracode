<?php

namespace Modules\Health\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Estado de la sincronizacion con Google Calendar (una fila por calendario).
 *
 * Guarda el `sync_token` del feed incremental y el canal de notificaciones
 * push, que hay que renovar antes de que expire. Cuando Google responde 410
 * GONE el token deja de ser valido: se borra y la siguiente lectura vuelve a
 * ser completa.
 *
 * Tambien guarda la cuenta de Google que quedo conectada con el boton
 * "Conectar con Google" (correo, nombre y foto), para que la pantalla pueda
 * mostrar con que cuenta se sincroniza el consultorio.
 */
class HealGoogleCalendarState extends Model
{
    protected $table = 'heal_google_calendar_states';

    protected $fillable = [
        'calendar_id',
        'account_email',
        'account_name',
        'account_picture',
        'channel_id',
        'resource_id',
        'resource_uri',
        'channel_expires_at',
        'sync_token',
        'last_sync_at',
        'last_full_sync_at',
        'last_error',
    ];

    protected $casts = [
        'channel_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_full_sync_at' => 'datetime',
    ];

    /**
     * Estado del calendario indicado, creandolo si es la primera vez.
     */
    public static function current(string $calendarId): self
    {
        return self::firstOrCreate(['calendar_id' => $calendarId]);
    }

    /**
     * true si hay un canal de push con margen suficiente antes de expirar.
     */
    public function channelIsFresh(int $renewDays = 2): bool
    {
        if ((string) $this->channel_id === '' || $this->channel_expires_at === null) {
            return false;
        }

        return $this->channel_expires_at->greaterThan(Carbon::now()->addDays(max(1, $renewDays)));
    }

    /**
     * Descarta el token del feed incremental (lectura completa la proxima vez).
     */
    public function forgetSyncToken(): void
    {
        $this->forceFill(['sync_token' => null])->save();
    }

    /**
     * Guarda el motivo del ultimo fallo (recortado para que quepa en la columna).
     */
    public function registerError(?\Throwable $exception): void
    {
        $this->forceFill([
            'last_error' => $exception === null
                ? null
                : mb_substr($exception->getMessage(), 0, 500),
        ])->save();
    }
}
