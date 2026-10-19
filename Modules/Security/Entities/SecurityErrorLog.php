<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Un error avisado por Telegram, con todo el contexto para corregirlo.
 *
 * El mensaje que llega al chat es un resumen (Telegram corta en 4096
 * caracteres); esta fila es el detalle completo al que apunta el enlace del
 * aviso: traza, contexto del log, petición, usuario y navegador.
 *
 * Las repeticiones del mismo fallo no crean filas nuevas: se reconoce por
 * `fingerprint` (nivel + mensaje + archivo + línea) y solo se suma
 * `occurrences` y se actualiza `last_seen_at`.
 */
class SecurityErrorLog extends Model
{
    protected $table = 'security_error_logs';

    protected $fillable = [
        'fingerprint',
        'level',
        'message',
        'exception_class',
        'file',
        'line',
        'trace',
        'context',
        'environment',
        'url',
        'method',
        'user_id',
        'user_label',
        'ip',
        'user_agent',
        'occurrences',
        'last_seen_at',
    ];

    protected $casts = [
        'line' => 'integer',
        'user_id' => 'integer',
        'occurrences' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Huella con la que se reconoce un error repetido.
     *
     * @param array<string, mixed> $payload
     */
    public static function fingerprint(array $payload): string
    {
        return sha1(implode('|', [
            (string) ($payload['level'] ?? ''),
            (string) ($payload['message'] ?? ''),
            (string) ($payload['file'] ?? ''),
            (string) ($payload['line'] ?? ''),
        ]));
    }

    /**
     * Lugar del código donde ocurrió (archivo:línea), o el mensaje si no hay.
     */
    public function origin(): string
    {
        $file = trim((string) $this->file);

        if ($file === '') {
            return '';
        }

        return $file . ($this->line ? ':' . $this->line : '');
    }

    /**
     * Etiqueta corta para la lista (#N).
     */
    public function reference(): string
    {
        return '#' . $this->id;
    }

    /**
     * Contexto listo para mostrar: el JSON decodificado y, si no se puede
     * decodificar (dato cortado por el límite), el texto tal cual para no
     * perder información.
     *
     * @return array<string, mixed>|string|null
     */
    public function contextArray(): array|string|null
    {
        $raw = (string) $this->context;

        if (trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true, 32);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }
}
