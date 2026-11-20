<?php

namespace Modules\Ai\Observers;

use App\Models\Parameter;
use Modules\Ai\Support\IaSettings;

/**
 * Mantiene sincronizada la caché de ajustes de IA con la tabla `parameters`.
 *
 * Cualquier guardado o borrado de un parámetro con prefijo IA- (desde la
 * pantalla de Parámetros, un seeder, una migración o tinker) invalida la
 * caché. Así el módulo de IA y la pantalla de Parámetros siempre ven el mismo
 * valor sin necesidad de reiniciar ni esperar a que expire la caché.
 */
class ParameterObserver
{
    public function saved(Parameter $parameter): void
    {
        $this->flushIfIa((string) $parameter->parameter_code);
    }

    public function deleted(Parameter $parameter): void
    {
        $this->flushIfIa((string) $parameter->parameter_code);
    }

    private function flushIfIa(string $code): void
    {
        if (str_starts_with($code, 'IA-')) {
            IaSettings::flush($code);
        }
    }
}
