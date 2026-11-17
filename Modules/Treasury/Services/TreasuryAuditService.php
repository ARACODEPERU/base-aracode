<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Entities\TreasuryAuditLog;

/**
 * Registro de auditoría del módulo.
 *
 * Nunca lanza: si el log falla, la operación de tesorería sigue y queda el
 * error en el log de Laravel. La tabla es de solo inserción.
 */
class TreasuryAuditService
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $meta
     */
    public function log(
        string $entity,
        int|string|null $entityId,
        string $action,
        array $old = [],
        array $new = [],
        array $meta = [],
    ): void {
        try {
            TreasuryAuditLog::create([
                'entity' => $entity,
                'entity_id' => $entityId !== null ? (int) $entityId : null,
                'action' => $action,
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'meta' => $meta ?: null,
                'user_id' => Auth::id(),
                'ip' => request()?->ip(),
                'user_agent' => mb_substr((string) request()?->userAgent(), 0, 255),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se pudo escribir la auditoría', [
                'entity' => $entity,
                'entity_id' => $entityId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
