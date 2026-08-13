<?php

declare(strict_types=1);

namespace Modules\Bibliodata\Contracts;

/**
 * Interfaz de persistencia de progreso por tipo de recurso educativo.
 *
 * Implementaciones futuras: video, examen, PDF, curso, etc.
 */
interface ProgressRepository
{
    /**
     * Obtener el progreso desde la base de datos.
     *
     * @return array{book_id: int, current_page: ?int, progress: ?int, chapter: ?int, updated_at: ?string}|null
     */
    public function get(int $userId, int $resourceId): ?array;

    /**
     * Guardar el progreso en la base de datos.
     *
     * @param  array{current_page?: int|null, chapter?: int|null, progress?: int|float|null}  $data
     */
    public function save(int $userId, int $resourceId, array $data): void;

    /**
     * Eliminar el progreso en la base de datos.
     */
    public function delete(int $userId, int $resourceId): void;
}
