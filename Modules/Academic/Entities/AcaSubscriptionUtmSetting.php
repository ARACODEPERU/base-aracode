<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

class AcaSubscriptionUtmSetting extends Model
{
    protected $fillable = [
        'utm_config',
    ];

    protected $casts = [
        'utm_config' => 'array',
    ];

    /**
     * Configuración guardada (siempre devuelve un arreglo con campaña y canales).
     */
    public static function config(): array
    {
        $saved = static::query()->first()?->utm_config ?? [];

        return [
            'campaign' => $saved['campaign'] ?? '',
            'channels' => $saved['channels'] ?? [],
        ];
    }

    /**
     * Guarda la configuración en la única fila de la tabla.
     */
    public static function store(array $config): array
    {
        $setting = static::query()->first() ?? new static();
        $setting->utm_config = $config;
        $setting->save();

        return static::config();
    }
}
