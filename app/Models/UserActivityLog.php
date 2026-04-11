<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'request_payload',
        'ip',
        'user_agent',
        'status_code',
        'error_message',
        'details_data',
    ];

    protected $casts = [
        'details_data' => 'array',
        'request_payload' => 'array',
    ];

    /**
     * Accessor para descomprimir request_payload automáticamente
     */
    public function getRequestPayloadAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // Si está comprimido (empieza con "gz:")
        if (str_starts_with($value, 'gz:')) {
            $compressed = base64_decode(substr($value, 3));
            $json = gzdecode($compressed);
            return json_decode($json, true);
        }

        // Si no está comprimido, intentar decodificar como JSON
        $decoded = json_decode($value, true);
        return $decoded ?? $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
