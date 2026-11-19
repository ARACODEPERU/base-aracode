<?php

namespace Modules\Ai\Support;

/**
 * Respuesta normalizada de un proveedor de IA.
 *
 * Todos los proveedores devuelven esta misma estructura para que el consumo,
 * el costo y la pantalla de resultados no dependan del formato de cada API.
 */
final class AiResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $text,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly float $costUsd = 0.0,
    ) {
    }
}
