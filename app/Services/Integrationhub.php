<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use RuntimeException;

class Integrationhub
{
    private Integration $integration;

    public static function __callStatic(string $integration, array $arguments): self
    {
        return new self($integration);
    }

    private function __construct(string $integration)
    {
        $this->integration = Integration::query()
            ->get()
            ->first(fn (Integration $model) => self::alias($model->name) === $integration);

        if (!$this->integration) {
            throw new InvalidArgumentException("La integracion {$integration} no existe.");
        }
    }

    public function __call(string $endpoint, array $arguments): array
    {
        $endpointModel = $this->integration->endpoints()
            ->get()
            ->first(fn (IntegrationEndpoint $model) => self::alias($model->name) === $endpoint);

        if (!$endpointModel) {
            throw new InvalidArgumentException("El endpoint {$endpoint} no existe.");
        }

        $variables = $arguments[0] ?? [];

        if (!is_array($variables)) {
            throw new InvalidArgumentException('Las variables de Integrationhub deben enviarse como array.');
        }

        $request = Request::create('', 'POST', [
            'endpoint_id' => $endpointModel->id,
            'variables' => $variables,
        ]);

        $response = app(IntegrationhubController::class)->execute($request, $this->integration->id);
        $data = $response->getData(true);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException($data['message'] ?? 'Error al ejecutar la integracion.');
        }

        return $data;
    }

    private static function alias(string $value): string
    {
        return trim(preg_replace('/_+/', '_', Str::snake(Str::ascii($value))), '_');
    }
}
