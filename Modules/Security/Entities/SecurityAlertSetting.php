<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes globales de las alertas de error (fila única).
 *
 * El nivel mínimo se compara por severidad, no alfabéticamente: 'error' incluye
 * también critical, alert y emergency. La ventana de anti-duplicados evita que
 * un mismo fallo repetido en ráfaga llene el chat.
 *
 * store_context guarda el detalle completo del error (para verlo por enlace),
 * retention_days indica cuántos días se conserva (0 = para siempre) y
 * purge_enabled activa el borrado automático de los errores vencidos.
 *
 * `enabled` es el interruptor general y cada tipo de aviso tiene el suyo
 * ({topic}_enabled): así se puede silenciar los avisos de errores sin perder
 * los mensajes de contacto, o al revés, sin borrar destinatarios.
 */
class SecurityAlertSetting extends Model
{
    protected $table = 'security_alert_settings';

    /** Tipo de aviso: fallo registrado en los logs. */
    public const TOPIC_ERRORS = 'errors';

    /** Tipo de aviso: mensaje del formulario de contacto del sitio. */
    public const TOPIC_CONTACTS = 'contacts';

    protected $fillable = [
        'enabled',
        'errors_enabled',
        'contacts_enabled',
        'min_level',
        'cooldown_minutes',
        'store_context',
        'retention_days',
        'purge_enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'errors_enabled' => 'boolean',
        'contacts_enabled' => 'boolean',
        'cooldown_minutes' => 'integer',
        'store_context' => 'boolean',
        'retention_days' => 'integer',
        'purge_enabled' => 'boolean',
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

    /** Días de retención por defecto del contexto de errores. */
    public const DEFAULT_RETENTION_DAYS = 30;

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
            'errors_enabled' => true,
            'contacts_enabled' => true,
            'min_level' => self::DEFAULT_LEVEL,
            'cooldown_minutes' => self::DEFAULT_COOLDOWN,
            'store_context' => true,
            'retention_days' => (int) config('security.alerts.retention_days', self::DEFAULT_RETENTION_DAYS),
            'purge_enabled' => true,
        ]);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->enabled ?? true);
    }

    /**
     * Tipos de aviso que la pantalla ofrece, con el texto que los explica.
     *
     * Agregar un tipo nuevo es: la columna `{clave}_enabled` en una migración y
     * una entrada aquí. El resto (interruptor en pantalla, guardado y
     * gating) sale de este registro.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public static function topics(): array
    {
        return [
            self::TOPIC_ERRORS => [
                'label' => 'Alertas de errores',
                'description' => 'Cuando un error se registra en los logs se envía su detalle, con el enlace al contexto guardado.',
            ],
            self::TOPIC_CONTACTS => [
                'label' => 'Mensajes de contacto',
                'description' => 'Cuando alguien envía el formulario de contacto del sitio.',
            ],
        ];
    }

    /**
     * true si ese tipo de aviso está activo.
     *
     * Un tipo desconocido (sin entrada en topics()) devuelve false: se prefiere
     * no enviar un aviso que ningún interruptor controla. Si la columna todavía
     * no existe porque la migración está pendiente se asume activo, para no
     * silenciar avisos que nadie apagó.
     */
    public function topicEnabled(string $topic): bool
    {
        if (! array_key_exists($topic, self::topics())) {
            return false;
        }

        return (bool) ($this->getAttribute($topic . '_enabled') ?? true);
    }

    /**
     * true si el aviso de ese tipo debe enviarse: interruptor general y del tipo.
     */
    public function allowsTopic(string $topic): bool
    {
        return $this->isEnabled() && $this->topicEnabled($topic);
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
     * true si se guarda el contexto completo del error para verlo por enlace.
     */
    public function storesContext(): bool
    {
        return (bool) ($this->store_context ?? true);
    }

    /**
     * Días que se conservan los errores guardados (0 = no borrar nunca).
     */
    public function retentionDays(): int
    {
        return max(0, (int) ($this->retention_days ?? self::DEFAULT_RETENTION_DAYS));
    }

    /**
     * true si el borrado automático diario de errores vencidos está activo.
     */
    public function autoPurge(): bool
    {
        return $this->retentionDays() > 0 && (bool) ($this->purge_enabled ?? true);
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
