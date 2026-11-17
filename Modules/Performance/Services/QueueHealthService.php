<?php

namespace Modules\Performance\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Diagnóstico de la cola de trabajos.
 *
 * No ejecuta, reintenta ni borra nada: solo lee la configuración, las tablas
 * `jobs` / `failed_jobs` y el log para que un programador vea si la cola está
 * trabajando o acumulando errores.
 */
class QueueHealthService
{
    /** Minutos a partir de los cuales un trabajo sin tomar indica worker caído. */
    private const STALE_MINUTES = 15;

    /** Bytes del final del log que se leen para el resumen. */
    private const LOG_BYTES = 400_000;

    /** Máximo de entradas de error que se devuelven. */
    private const LOG_ENTRIES = 15;

    /**
     * Informe completo para la pantalla.
     *
     * @return array<string, mixed>
     */
    public function report(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget('performance:queue:jobs');
        }

        $connection = $this->connection();
        $pending = $this->pending($connection);
        $failed = $this->failed();
        $log = $this->log();

        return [
            'connection'  => $connection,
            'pending'     => $pending,
            'failed'      => $failed,
            'log'         => $log,
            'job_classes' => $this->jobClasses(),
            'status'      => $this->status($connection, $pending, $failed, $log),
            'checked_at'  => now()->format('Y-m-d H:i:s'),
            'stale_minutes' => self::STALE_MINUTES,
        ];
    }

    /**
     * Conexión por defecto y comprobaciones de su driver.
     *
     * @return array<string, mixed>
     */
    private function connection(): array
    {
        $name = (string) config('queue.default', 'sync');
        $config = (array) config("queue.connections.$name", []);
        $driver = (string) ($config['driver'] ?? $name);
        $checks = [];

        switch ($driver) {
            case 'database':
                $table = (string) ($config['table'] ?? 'jobs');
                $exists = Schema::hasTable($table);
                $checks[] = [
                    'label'  => "Tabla {$table}",
                    'ok'     => $exists,
                    'detail' => $exists ? 'Existe' : 'No existe: faltan migraciones',
                ];

                if ((string) config('queue.failed.driver') === 'database-uuids') {
                    $failedTable = (string) config('queue.failed.table', 'failed_jobs');
                    $failedExists = Schema::hasTable($failedTable);
                    $checks[] = [
                        'label'  => "Tabla {$failedTable}",
                        'ok'     => $failedExists,
                        'detail' => $failedExists ? 'Existe' : 'No existe: los fallos no se podrán registrar',
                    ];
                }
                break;

            case 'redis':
                $hasClient = extension_loaded('redis') || class_exists(\Predis\Client::class);
                $checks[] = [
                    'label'  => 'Cliente Redis',
                    'ok'     => $hasClient,
                    'detail' => $hasClient
                        ? (extension_loaded('redis') ? 'Extensión redis cargada' : 'Predis disponible')
                        : 'Sin extensión redis ni predis',
                ];
                break;

            case 'sqs':
                $hasKeys = (bool) config('queue.connections.sqs.key') && (bool) config('queue.connections.sqs.secret');
                $checks[] = [
                    'label'  => 'Credenciales AWS',
                    'ok'     => $hasKeys,
                    'detail' => $hasKeys ? 'Configuradas' : 'Faltan AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY',
                ];
                break;

            case 'sync':
                $checks[] = [
                    'label'  => 'Modo síncrono',
                    'ok'     => true,
                    'detail' => 'Los trabajos se ejecutan en la misma petición, sin worker',
                ];
                break;

            default:
                $checks[] = [
                    'label'  => "Driver {$driver}",
                    'ok'     => true,
                    'detail' => 'Sin comprobación automática para este driver',
                ];
        }

        return [
            'name'        => $name,
            'driver'      => $driver,
            'queue'       => (string) ($config['queue'] ?? 'default'),
            'retry_after' => (int) ($config['retry_after'] ?? 0),
            'checks'      => $checks,
            'ok'          => collect($checks)->every(fn (array $check): bool => $check['ok']),
            'failed_driver' => (string) config('queue.failed.driver', 'null'),
        ];
    }

    /**
     * Trabajos pendientes agrupados por cola.
     *
     * @param  array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    private function pending(array $connection): array
    {
        $empty = [
            'available'      => false,
            'total'          => 0,
            'reserved'       => 0,
            'oldest_minutes' => null,
            'queues'         => [],
        ];

        if ($connection['driver'] !== 'database') {
            return $empty + ['available' => true];
        }

        $table = (string) config('queue.connections.database.table', 'jobs');

        if (! Schema::hasTable($table)) {
            return $empty;
        }

        $rows = DB::table($table)
            ->select('queue')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when reserved_at is null then 0 else 1 end) as reserved')
            ->selectRaw('min(created_at) as oldest')
            ->selectRaw('max(attempts) as attempts')
            ->groupBy('queue')
            ->orderByDesc('total')
            ->get();

        $queues = [];
        $total = 0;
        $reserved = 0;
        $oldest = null;

        foreach ($rows as $row) {
            $rowTotal = (int) $row->total;
            $total += $rowTotal;
            $reserved += (int) $row->reserved;

            if ($row->oldest !== null && ($oldest === null || (int) $row->oldest < $oldest)) {
                $oldest = (int) $row->oldest;
            }

            $queues[] = [
                'queue'         => (string) $row->queue,
                'total'         => $rowTotal,
                'reserved'      => (int) $row->reserved,
                'waiting'       => $rowTotal - (int) $row->reserved,
                'oldest_minutes' => $row->oldest === null ? null : max(0, (int) floor((time() - (int) $row->oldest) / 60)),
                'attempts'      => (int) $row->attempts,
            ];
        }

        return [
            'available'      => true,
            'total'          => $total,
            'reserved'       => $reserved,
            'oldest_minutes' => $oldest === null ? null : max(0, (int) floor((time() - $oldest) / 60)),
            'queues'         => $queues,
        ];
    }

    /**
     * Trabajos fallidos: totales y últimos registros.
     *
     * @return array<string, mixed>
     */
    private function failed(): array
    {
        $table = (string) config('queue.failed.table', 'failed_jobs');
        $empty = [
            'available' => false,
            'total'     => 0,
            'last_24h'  => 0,
            'last_7d'   => 0,
            'items'     => [],
        ];

        if (! Schema::hasTable($table)) {
            return $empty;
        }

        $query = DB::table($table);
        $total = (clone $query)->count();
        $last24 = (clone $query)->where('failed_at', '>=', now()->subDay())->count();
        $last7 = (clone $query)->where('failed_at', '>=', now()->subDays(7))->count();

        $items = DB::table($table)
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get()
            ->map(function ($row): array {
                $payload = json_decode((string) ($row->payload ?? ''), true) ?: [];
                $exception = (string) ($row->exception ?? '');
                $firstLine = trim((string) strtok($exception, "\n"));

                return [
                    'id'         => $row->id ?? null,
                    'uuid'       => $row->uuid ?? null,
                    'connection' => (string) ($row->connection ?? ''),
                    'queue'      => (string) ($row->queue ?? ''),
                    'job'        => (string) ($payload['displayName'] ?? class_basename((string) ($payload['job'] ?? 'Desconocido'))),
                    'exception'  => mb_strimwidth($firstLine !== '' ? $firstLine : 'Sin detalle', 0, 240, '…'),
                    'failed_at'  => $row->failed_at ? (string) $row->failed_at : null,
                ];
            })
            ->all();

        return [
            'available' => true,
            'total'     => $total,
            'last_24h'  => $last24,
            'last_7d'   => $last7,
            'items'     => $items,
        ];
    }

    /**
     * Errores recientes del log de Laravel.
     *
     * @return array<string, mixed>
     */
    private function log(): array
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];

        if ($files === []) {
            return [
                'file'       => null,
                'bytes'      => 0,
                'scanned_bytes' => 0,
                'errors'     => 0,
                'warnings'   => 0,
                'criticals'  => 0,
                'queue_errors' => [],
                'entries'    => [],
            ];
        }

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
        $file = $files[0];
        $content = $this->tail($file, self::LOG_BYTES);

        $errors = 0;
        $warnings = 0;
        $criticals = 0;
        $queueErrors = [];
        $entries = [];
        $since = now()->subDay();

        preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [\w\-]+\.(\w+): (.*)$/m', $content, $matches, PREG_SET_ORDER);

        foreach (array_reverse($matches) as $match) {
            $date = $match[1];
            $level = strtoupper($match[2]);
            // El log adjunta el JSON de la excepción; para la pantalla basta el mensaje.
            $message = trim((string) preg_split('/\s*\{"exception"/', trim($match[3]))[0]);

            try {
                $at = \Illuminate\Support\Carbon::parse($date);
            } catch (Throwable) {
                continue;
            }

            if ($at->lt($since)) {
                continue;
            }

            if ($level === 'WARNING') {
                $warnings++;
            }

            if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
                $errors++;
            }

            if (in_array($level, ['CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
                $criticals++;
            }

            $related = (bool) preg_match('/queue|trabajo|job|worker|horizon|failed|attempt|timeout/i', $message);
            $isError = in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true);

            if ($isError && count($entries) < self::LOG_ENTRIES) {
                $entries[] = [
                    'at'      => $at->format('Y-m-d H:i'),
                    'level'   => $level,
                    'message' => mb_strimwidth($message, 0, 300, '…'),
                    'related' => $related,
                ];
            }

            if ($isError && $related && count($queueErrors) < self::LOG_ENTRIES) {
                $queueErrors[] = [
                    'at'      => $at->format('Y-m-d H:i'),
                    'level'   => $level,
                    'message' => mb_strimwidth($message, 0, 300, '…'),
                ];
            }
        }

        return [
            'file'        => basename($file),
            'bytes'       => (int) filesize($file),
            'scanned_bytes' => min((int) filesize($file), self::LOG_BYTES),
            'errors'      => $errors,
            'warnings'    => $warnings,
            'criticals'   => $criticals,
            'queue_errors' => $queueErrors,
            'entries'     => $entries,
        ];
    }

    /**
     * Devuelve los últimos bytes de un archivo.
     */
    private function tail(string $file, int $bytes): string
    {
        $size = (int) filesize($file);

        if ($size === 0) {
            return '';
        }

        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return '';
        }

        $read = min($size, $bytes);
        fseek($handle, -$read, SEEK_END);
        $content = (string) fread($handle, $read);
        fclose($handle);

        // La primera línea puede quedar cortada por el punto de lectura.
        if ($read < $size) {
            $pos = strpos($content, "\n");
            $content = $pos === false ? '' : substr($content, $pos + 1);
        }

        return $content;
    }

    /**
     * Clases encolables del proyecto (jobs propios de la aplicación y módulos).
     *
     * @return array<string, mixed>
     */
    private function jobClasses(): array
    {
        return Cache::remember('performance:queue:jobs', 3600, function (): array {
            $files = array_merge(
                glob(base_path('app/Jobs/*.php')) ?: [],
                glob(base_path('Modules/*/Jobs/*.php')) ?: []
            );

            $classes = array_map(fn (string $file): string => basename($file, '.php'), $files);
            sort($classes);

            return [
                'count' => count($classes),
                'names' => array_slice($classes, 0, 40),
            ];
        });
    }

    /**
     * Estado resumido y explicación para la cabecera.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $pending
     * @param  array<string, mixed>  $failed
     * @param  array<string, mixed>  $log
     * @return array<string, mixed>
     */
    private function status(array $connection, array $pending, array $failed, array $log): array
    {
        if (! $connection['ok']) {
            return [
                'level'  => 'error',
                'title'  => 'La conexión de cola no está lista',
                'detail' => 'Revisa las comprobaciones de la conexión: falta alguna tabla o credencial necesaria para encolar trabajos.',
            ];
        }

        if ($connection['driver'] === 'sync') {
            return [
                'level'  => 'info',
                'title'  => 'Cola en modo síncrono',
                'detail' => 'No hay worker: cada trabajo se ejecuta dentro de la misma petición. Si quieres procesarlos en segundo plano, cambia QUEUE_CONNECTION a database y arranca un worker.',
            ];
        }

        if ($failed['last_24h'] > 0) {
            return [
                'level'  => 'error',
                'title'  => $failed['last_24h'] . ' trabajo(s) fallaron en las últimas 24 horas',
                'detail' => 'La cola está procesando, pero hay errores. Revisa la tabla de fallidos y el log para ver la causa antes de reintentarlos.',
            ];
        }

        if ($pending['total'] > 0 && $pending['reserved'] > 0) {
            return [
                'level'  => 'ok',
                'title'  => 'La cola está trabajando',
                'detail' => 'Hay ' . $pending['reserved'] . ' trabajo(s) en proceso ahora mismo. Los pendientes se están tomando con normalidad.',
            ];
        }

        if ($pending['total'] > 0 && ($pending['oldest_minutes'] ?? 0) >= self::STALE_MINUTES) {
            return [
                'level'  => 'error',
                'title'  => 'Hay trabajos esperando y nadie los está tomando',
                'detail' => 'El trabajo más antiguo lleva ' . $pending['oldest_minutes'] . ' minutos en cola. Todo apunta a que el worker no está corriendo: arráncalo o revisa su servicio.',
            ];
        }

        if ($pending['total'] > 0) {
            return [
                'level'  => 'warning',
                'title'  => 'Hay trabajos pendientes',
                'detail' => 'Los trabajos llevan menos de ' . self::STALE_MINUTES . ' minutos en cola. Si el número no baja, comprueba que el worker siga vivo.',
            ];
        }

        if (count($log['queue_errors']) > 0) {
            return [
                'level'  => 'warning',
                'title'  => 'Sin trabajos pendientes, pero hay errores en el log',
                'detail' => 'El log reciente contiene errores relacionados con la cola. Puede que el worker esté fallando al arrancar o al procesar.',
            ];
        }

        return [
            'level'  => 'idle',
            'title'  => 'Sin actividad en la cola',
            'detail' => 'No hay trabajos pendientes ni fallidos recientes. Desde la web no se puede ver el proceso del worker: el estado se deduce de los trabajos reservados y de su antigüedad.',
        ];
    }
}
