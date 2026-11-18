<?php

namespace Modules\Integrationhub\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\Integration;

/**
 * Resuelve credenciales administradas desde IntegrationHub.
 *
 * Permite configurar un secreto en el panel de integraciones (por ejemplo el
 * webhook de MercadoPago) en lugar del .env, manteniendo el .env como respaldo
 * para instalaciones que todavía no lo registran ahí.
 */
class IntegrationCredentialResolver
{
    /**
     * Valores ya resueltos durante la petición, para no repetir consultas.
     *
     * @var array<string, string|null>
     */
    private array $resolved = [];

    /**
     * Valor del campo solicitado; null si no está configurado en ningún lado.
     */
    public function value(string $integration, string $field, ?string $fallback = null): ?string
    {
        $key = mb_strtolower(trim($integration)) . '|' . mb_strtolower(trim($field));

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        $value = $this->fromHub($integration, $field);

        if ($value === null) {
            $fallback = trim((string) $fallback);
            $value = $fallback !== '' ? $fallback : null;
        }

        return $this->resolved[$key] = $value;
    }

    public function has(string $integration, string $field): bool
    {
        return $this->value($integration, $field) !== null;
    }

    private function fromHub(string $integration, string $field): ?string
    {
        try {
            if (! Schema::hasTable('integrations') || ! Schema::hasTable('integration_auth')) {
                return null;
            }

            $record = Integration::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($integration))])
                ->first();

            if (! $record) {
                return null;
            }

            $auth = $record->auths()
                ->whereRaw('LOWER(field_name) = ?', [mb_strtolower(trim($field))])
                ->where('is_enabled', true)
                ->first();

            $value = trim((string) ($auth?->field_value ?? ''));

            return $value !== '' ? $value : null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo leer una credencial de IntegrationHub.', [
                'integration' => $integration,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
