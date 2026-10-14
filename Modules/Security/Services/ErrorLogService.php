<?php

namespace Modules\Security\Services;

use Illuminate\Support\Facades\DB;
use Modules\Security\Entities\SecurityErrorLog;
use Throwable;

/**
 * Guarda y limpia el contexto de los errores avisados por Telegram.
 *
 * Es el otro extremo del ErrorAlertService: allí se decide a quién se avisa,
 * aquí se conserva el detalle que el mensaje de Telegram no cabe (traza
 * completa, contexto del log, petición, navegador) para poder abrirlo desde el
 * enlace del aviso.
 *
 * Reglas:
 *   - NUNCA lanza: si la base falla (que puede ser justo el error que se está
 *     registrando), devuelve null y la alerta sigue su camino.
 *   - No duplica: las repeticiones del mismo fallo suman `occurrences` sobre la
 *     fila existente en lugar de crear otra.
 *   - Limpia: los errores vencidos se borran por retención (comando programado)
 *     o a mano desde la pantalla.
 */
class ErrorLogService
{
    /** Ruta de la pantalla que muestra el contexto completo de un error. */
    public const SHOW_PATH = '/security/errors/';

    /**
     * Guarda un error y devuelve la fila creada (null si no se pudo guardar).
     *
     * @param array<string, mixed> $payload datos ya armados por ErrorAlertService
     * @param string               $fingerprint huella de la repetición
     */
    public function record(array $payload, string $fingerprint): ?SecurityErrorLog
    {
        try {
            return SecurityErrorLog::create([
                'fingerprint' => $fingerprint,
                'level' => (string) ($payload['level'] ?? 'ERROR'),
                'message' => (string) ($payload['message'] ?? ''),
                'exception_class' => $this->str($payload['class'] ?? null, 255),
                'file' => $this->str($payload['file'] ?? null, 500),
                'line' => $this->int($payload['line'] ?? null),
                'trace' => $this->str($payload['trace_full'] ?? null),
                'context' => $this->str($payload['context'] ?? null),
                'environment' => $this->str($payload['environment'] ?? null, 60),
                'url' => $this->str($payload['url'] ?? null, 1000),
                'method' => $this->str($payload['method'] ?? null, 12),
                'user_id' => $this->int($payload['user_id'] ?? null),
                'user_label' => $this->str($payload['user'] ?? null, 255),
                'ip' => $this->str($payload['ip'] ?? null, 60),
                'user_agent' => $this->str($payload['agent'] ?? null, 500),
                'occurrences' => 1,
                'last_seen_at' => now(),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Registra una repetición del mismo error: suma y actualiza la última vez.
     *
     * No guarda una fila nueva a propósito: un error en ráfaga llenaría el disco
     * con la misma información repetida.
     *
     * @param array<string, mixed> $payload
     */
    public function touch(SecurityErrorLog $log, array $payload = []): void
    {
        try {
            $updates = [
                'occurrences' => max(1, (int) $log->occurrences) + 1,
                'last_seen_at' => now(),
            ];

            // Si la primera vez no hubo traza (log sin excepción) y ahora sí,
            // conviene conservarla.
            if (trim((string) $log->trace) === '' && trim((string) ($payload['trace_full'] ?? '')) !== '') {
                $updates['trace'] = $this->str($payload['trace_full'] ?? null);
            }

            $log->forceFill($updates)->save();
        } catch (Throwable) {
            // Sumar una repetición no puede tumbar la petición.
        }
    }

    /**
     * Enlace absoluto a la pantalla de detalle del error.
     */
    public function linkFor(?int $errorLogId): string
    {
        if (! $errorLogId) {
            return '';
        }

        try {
            return url(self::SHOW_PATH . $errorLogId);
        } catch (Throwable) {
            try {
                return rtrim((string) config('app.url'), '/') . self::SHOW_PATH . $errorLogId;
            } catch (Throwable) {
                return '';
            }
        }
    }

    /**
     * Borra los errores más viejos que los días indicados.
     *
     * @return int filas eliminadas (0 si los días son 0: no se borra nada)
     */
    public function prune(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        try {
            return SecurityErrorLog::query()
                ->where('created_at', '<', now()->subDays($days))
                ->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Borra TODOS los errores guardados.
     *
     * @return int filas eliminadas
     */
    public function clear(): int
    {
        try {
            return SecurityErrorLog::query()->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Borra un error concreto.
     */
    public function delete(int $id): bool
    {
        try {
            return (bool) SecurityErrorLog::query()->whereKey($id)->delete();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Resumen de lo guardado, para la parte alta de la pantalla.
     *
     * @return array{total: int, size_bytes: int, oldest_at: ?string, newest_at: ?string, levels: array<string, int>}
     */
    public function stats(): array
    {
        try {
            $query = SecurityErrorLog::query();

            $levels = (clone $query)
                ->select('level', DB::raw('COUNT(*) as total'))
                ->groupBy('level')
                ->pluck('total', 'level')
                ->map(fn ($total) => (int) $total)
                ->all();

            $row = (clone $query)->selectRaw(
                "COALESCE(SUM(LENGTH(message) + LENGTH(COALESCE(trace, '')) + LENGTH(COALESCE(context, ''))), 0) AS bytes"
            )->first();

            return [
                'total' => (int) (clone $query)->count(),
                'size_bytes' => (int) ($row->bytes ?? 0),
                'oldest_at' => (clone $query)->min('created_at'),
                'newest_at' => (clone $query)->max('created_at'),
                'levels' => $levels,
            ];
        } catch (Throwable) {
            return [
                'total' => 0,
                'size_bytes' => 0,
                'oldest_at' => null,
                'newest_at' => null,
                'levels' => [],
            ];
        }
    }

    private function str(mixed $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = is_scalar($value) ? (string) $value : json_encode($value);

        if ($text === false) {
            return null;
        }

        return $limit === null ? $text : mb_substr($text, 0, $limit);
    }

    private function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
