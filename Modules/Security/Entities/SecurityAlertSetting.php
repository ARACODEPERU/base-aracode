<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes globales de las alertas de error (fila única).
 *
 * El nivel mínimo se compara por severidad, no alfabéticamente: 'error' incluye
 * también critical, alert y emergency. La ventana de anti-duplicados evita que
 * un mismo fallo repetido en ráfaga llene el chat.
 */
class SecurityAlertSetting extends Model
{
    protected $table = 'security_alert_settings';

    protected $fillable = [
        'enabled',
        'min_level',
        'cooldown_minutes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'cooldown_minutes' => 'integer',
    ];

    /** Niveles de PSR-3 ordenados de menor a mayor severidad. */
    public const LEVELS = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ];

    /** Nivel por defecto cuando todavía no hay fila guardada. */
    public const DEFAULT_LEVEL = 'error';

    /** Ventana de anti-duplicados por defecto (minutos). */
    public const DEFAULT_COOLDOWN = 5;

    /**
     * Ajustes vigentes. Si la tabla todavía no tiene fila (o no existe) se
     * devuelve un modelo sin guardar con los valores por defecto, de modo que
     * el listener nunca falle por leer la configuración.
     */
    public static function current(): self
    {
        try {
            $setting = static::query()->first();

            if ($setting) {
                return $setting;
            }
        } catch (\Throwable) {
            // Migración pendiente: se trabaja con los valores por defecto.
        }

        return new self([
            'enabled' => true,
            'min_level' => self::DEFAULT_LEVEL,
            'cooldown_minutes' => self::DEFAULT_COOLDOWN,
        ]);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->enabled ?? true);
    }

    public function minLevel(): string
    {
        $level = strtolower(trim((string) ($this->min_level ?? self::DEFAULT_LEVEL)));

        return in_array($level, self::LEVELS, true) ? $level : self::DEFAULT_LEVEL;
    }

    public function cooldownMinutes(): int
    {
        return max(0, (int) ($this->cooldown_minutes ?? self::DEFAULT_COOLDOWN));
    }

    /**
     * true si el nivel indicado alcanza el umbral configurado.
     *
     * Un nivel desconocido (por ejemplo 'error ' normalizado a 'error' o un
     * nivel raro) se trata como 'error' para no dejar pasar avisos por accidente.
     */
    public function levelReaches(string $level): bool
    {
        $rank = array_search($this->minLevel(), self::LEVELS, true);
        $rank = $rank === false ? 0 : $rank;

        $candidate = strtolower(trim($level));
        $candidateRank = array_search($candidate, self::LEVELS, true);
        $candidateRank = $candidateRank === false ? array_search(self::DEFAULT_LEVEL, self::LEVELS, true) : $candidateRank;

        return $candidateRank >= $rank;
    }

    /**
     * Niveles que la pantalla ofrece como opción.
     *
     * @return array<int, string>
     */
    public static function selectableLevels(): array
    {
        return array_slice(self::LEVELS, array_search('error', self::LEVELS, true) ?: 4);
    }
}
