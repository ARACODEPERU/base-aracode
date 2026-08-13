<?php

declare(strict_types=1);

namespace Modules\Bibliodata\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Bibliodata\Contracts\ProgressRepository;

/**
 * Fuente principal del estado temporal de lectura del estudiante.
 *
 * Utiliza Laravel Cache (File, Redis o Memcached) como fuente principal
 * mientras el estudiante lee. La base de datos solo se actualiza cuando es
 * realmente necesario (intervalo configurable, cambio significativo de
 * progreso, cierre de libro o cierre de sesión).
 *
 * Arquitectura preparada para otros recursos educativos: video, examen,
 * documento PDF, curso, etc. Basta con registrar un nuevo ProgressRepository.
 */
final class ReadingCacheService
{
    /**
     * @param  array<string, ProgressRepository>  $repositories
     */
    public function __construct(
        private readonly array $repositories,
        private readonly int $ttl,
        private readonly int $persistIntervalSeconds,
        private readonly float $persistProgressDelta,
    ) {}

    /**
     * Clave del progreso: student:{userId}:{type}:{resourceId}
     * Ej.: student:12:book:15
     */
    public function resourceKey(int $userId, string $resourceType, int $resourceId): string
    {
        return "student:{$userId}:{$resourceType}:{$resourceId}";
    }

    /**
     * Clave de recursos abiertos: student:{userId}:opened_{type}s
     * Ej.: student:12:opened_books
     */
    public function openedKey(int $userId, string $resourceType): string
    {
        return "student:{$userId}:opened_{$resourceType}s";
    }

    /**
     * Clave con metadatos de sincronización (evita contaminar el payload de progreso).
     */
    public function syncMetaKey(int $userId, string $resourceType, int $resourceId): string
    {
        return "student:{$userId}:{$resourceType}:{$resourceId}:sync";
    }

    /**
     * Obtener el progreso. Cache primero; si no existe, consulta BD, lo
     * guarda en cache y responde desde cache.
     *
     * @return array{book_id: int, current_page: ?int, progress: ?int, chapter: ?int, updated_at: ?string}|null
     */
    public function getProgress(int $userId, string $resourceType, int $resourceId): ?array
    {
        $key = $this->resourceKey($userId, $resourceType, $resourceId);

        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $repository = $this->repositoryFor($resourceType);

        if ($repository) {
            $fromDb = $repository->get($userId, $resourceId);

            if ($fromDb !== null) {
                Cache::put($key, $fromDb, $this->ttl);

                return $fromDb;
            }
        }

        return null;
    }

    /**
     * Registrar progreso durante la lectura. Solo actualiza cache.
     *
     * La persistencia a BD ocurre automáticamente cuando:
     *  - no hay sincronización previa,
     *  - pasó el intervalo configurado,
     *  - el progreso cambió significativamente.
     *
     * @param  array{current_page?: int|null, chapter?: int|null, progress?: int|float|null}  $data
     * @return array{updated: bool, synced: bool}
     */
    public function saveProgress(int $userId, string $resourceType, int $resourceId, array $data): array
    {
        $key = $this->resourceKey($userId, $resourceType, $resourceId);

        $payload = array_merge($data, [
            $this->resourceIdKey($resourceType) => $resourceId,
            'updated_at' => now()->toIso8601String(),
        ]);

        Cache::put($key, $payload, $this->ttl);

        $this->addOpenedResource($userId, $resourceType, $resourceId);

        return [
            'updated' => true,
            'synced' => $this->shouldSync($userId, $resourceType, $resourceId, $data) && $this->syncProgressToDatabase($userId, $resourceType, $resourceId),
        ];
    }

    /**
     * Refrescar la expiración de un progreso ya existente.
     */
    public function touchProgress(int $userId, string $resourceType, int $resourceId): void
    {
        $key = $this->resourceKey($userId, $resourceType, $resourceId);

        $payload = Cache::get($key);

        if (is_array($payload)) {
            Cache::put($key, $payload, $this->ttl);
        }
    }

    /**
     * Eliminar el progreso de cache (y metadatos de sync).
     */
    public function forgetProgress(int $userId, string $resourceType, int $resourceId): void
    {
        Cache::forget($this->resourceKey($userId, $resourceType, $resourceId));
        Cache::forget($this->syncMetaKey($userId, $resourceType, $resourceId));
        $this->removeOpenedResource($userId, $resourceType, $resourceId);
    }

    /**
     * Forzar la sincronización del progreso en cache hacia la base de datos.
     */
    public function syncProgressToDatabase(int $userId, string $resourceType, int $resourceId): bool
    {
        $payload = $this->getProgress($userId, $resourceType, $resourceId);

        if ($payload === null) {
            return false;
        }

        $repository = $this->repositoryFor($resourceType);

        if (! $repository) {
            return false;
        }

        $repository->save($userId, $resourceId, $payload);

        Cache::put($this->syncMetaKey($userId, $resourceType, $resourceId), [
            'synced_at' => now()->getTimestamp(),
            'progress' => (float) ($payload['progress'] ?? 0),
        ], $this->ttl);

        return true;
    }

    /**
     * Sincronizar a BD todos los recursos abiertos del tipo dado.
     *
     * @return array<int, bool> resourceId => resultado
     */
    public function syncOpenedToDatabase(int $userId, string $resourceType): array
    {
        $results = [];

        foreach ($this->getOpenedResources($userId, $resourceType) as $resourceId) {
            $results[$resourceId] = $this->syncProgressToDatabase($userId, $resourceType, $resourceId);
        }

        return $results;
    }

    /**
     * IDs de los recursos abiertos por el estudiante.
     *
     * @return array<int, int>
     */
    public function getOpenedResources(int $userId, string $resourceType): array
    {
        $ids = Cache::get($this->openedKey($userId, $resourceType), []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }

    /**
     * Registrar un recurso como abierto (sin duplicados, el más reciente primero).
     */
    public function addOpenedResource(int $userId, string $resourceType, int $resourceId): void
    {
        $current = $this->getOpenedResources($userId, $resourceType);
        $current = array_values(array_unique(array_merge([$resourceId], $current)));
        $current = array_slice($current, 0, 24);

        Cache::put($this->openedKey($userId, $resourceType), $current, $this->ttl);
    }

    /**
     * Quitar un recurso de la lista de abiertos.
     */
    public function removeOpenedResource(int $userId, string $resourceType, int $resourceId): void
    {
        $current = array_values(array_filter(
            $this->getOpenedResources($userId, $resourceType),
            fn (int $id): bool => $id !== $resourceId,
        ));

        Cache::put($this->openedKey($userId, $resourceType), $current, $this->ttl);
    }

    /**
     * Limpiar la lista de abiertos de un tipo.
     */
    public function flushOpenedResources(int $userId, string $resourceType): void
    {
        Cache::forget($this->openedKey($userId, $resourceType));
    }

    /**
     * Persistir y limpiar todo el estado temporal del estudiante.
     * Ideal al cerrar sesión o finalizar la lectura.
     */
    public function flushUser(int $userId): void
    {
        foreach (array_keys($this->repositories) as $resourceType) {
            $this->syncOpenedToDatabase($userId, $resourceType);

            foreach ($this->getOpenedResources($userId, $resourceType) as $resourceId) {
                $this->forgetProgress($userId, $resourceType, $resourceId);
            }

            $this->flushOpenedResources($userId, $resourceType);
        }
    }

    /**
     * Determina si el progreso debe persistirse en este momento.
     */
    private function shouldSync(int $userId, string $resourceType, int $resourceId, array $data): bool
    {
        $meta = Cache::get($this->syncMetaKey($userId, $resourceType, $resourceId));

        if (! is_array($meta)) {
            return true;
        }

        $syncedAt = (int) ($meta['synced_at'] ?? 0);
        $lastProgress = (float) ($meta['progress'] ?? -1);
        $currentProgress = (float) ($data['progress'] ?? 0);

        if ((now()->getTimestamp() - $syncedAt) >= $this->persistIntervalSeconds) {
            return true;
        }

        return $lastProgress >= 0 && abs($currentProgress - $lastProgress) >= $this->persistProgressDelta;
    }

    private function repositoryFor(string $resourceType): ?ProgressRepository
    {
        return $this->repositories[$resourceType] ?? null;
    }

    private function resourceIdKey(string $resourceType): string
    {
        return $resourceType.'_id';
    }
}
