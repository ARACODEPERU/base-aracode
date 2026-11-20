<?php

namespace Modules\Ai\Services;

use App\Models\User;
use Modules\Ai\Support\AiResult;
use Modules\Ai\Support\IaSettings;
use RuntimeException;
use Throwable;

/**
 * Punto de entrada único del módulo de IA.
 *
 * Elige el proveedor configurado en IA-00002, aplica el presupuesto mensual y
 * el límite diario por usuario, y registra el consumo de cada consulta.
 */
class AiManager
{
    public function __construct(
        private readonly AiUsageRecorder $recorder,
        private readonly AiBudgetService $budget
    ) {
    }

    /**
     * Proveedor activo o el indicado ('openai'|'gemini'|'1'|'2').
     */
    public function provider(?string $name = null): AiProvider
    {
        $name = $name !== null && $name !== '' ? $name : IaSettings::provider();

        return match ($name) {
            'openai', '1' => app(OpenAiProvider::class),
            'gemini', '2' => app(GeminiProvider::class),
            default => throw new RuntimeException('Proveedor de IA no reconocido: ' . $name),
        };
    }

    /**
     * Envía una consulta con el proveedor activo y registra el consumo.
     *
     * Opciones: provider, model, instructions, temperature,
     * max_output_tokens, previous_response_id y screen.
     *
     * @param  array<string, mixed>  $options
     */
    public function chat(int|string $userId, string $message, array $options = []): AiResult
    {
        $provider = $this->provider(isset($options['provider']) ? (string) $options['provider'] : null);
        $screen = isset($options['screen']) ? (string) $options['screen'] : null;

        $this->assertWithinLimits($userId);

        try {
            $result = $provider->chat($message, $options);
        } catch (Throwable $exception) {
            $this->recorder->recordTokens(
                $userId,
                $provider->name(),
                $provider->model(),
                0,
                0,
                $screen,
                'error',
                $exception->getMessage()
            );

            throw $exception;
        }

        $this->recorder->record($userId, $result, $screen, $message);

        return $result;
    }

    /**
     * Lanza una excepción cuando se alcanzó el presupuesto del mes o el límite
     * diario del usuario. Un valor 0 en el parámetro significa sin límite.
     */
    public function assertWithinLimits(int|string $userId): void
    {
        $estado = $this->budget->estado();

        // El corte al 100% del presupuesto es configurable: si está apagado,
        // solo se registra la alerta y las consultas siguen funcionando.
        if ($estado['level'] === 'exceeded' && $estado['hard_stop']) {
            throw new RuntimeException(
                'Se alcanzó el presupuesto mensual de IA configurado en el parámetro '
                . IaSettings::MONTHLY_BUDGET . ' del sistema. Un administrador puede ampliarlo.'
            );
        }

        $usuario = is_numeric($userId) ? User::find((int) $userId) : null;

        $limitesDiarios = [];
        $limiteGlobal = IaSettings::dailyLimit();

        if ($limiteGlobal > 0) {
            $limitesDiarios[] = $limiteGlobal;
        }

        $limiteRol = 0;

        if ($usuario !== null) {
            $limiteRol = $this->budget->limiteDiarioPorRol($usuario);

            if ($limiteRol > 0) {
                $limitesDiarios[] = $limiteRol;
            }
        }

        if ($usuario !== null && $limitesDiarios !== []) {
            $limite = min($limitesDiarios);

            if ($this->recorder->todayCount($usuario->id) >= $limite) {
                throw new RuntimeException(
                    'Alcanzaste el límite diario de consultas de IA (' . $limite . ').'
                    . ($limiteRol > 0 && $limiteRol <= $limiteGlobal ? ' Lo define el límite de tu rol.' : '')
                );
            }
        }

        if ($usuario !== null) {
            $presupuestoRol = $this->budget->presupuestoMensualPorRol($usuario);

            if ($presupuestoRol > 0 && $this->recorder->monthlySpendUsd($usuario->id) >= $presupuestoRol) {
                throw new RuntimeException(
                    'Tu rol alcanzó su presupuesto mensual de IA. Un administrador puede ampliarlo en IA → Configuración.'
                );
            }
        }
    }
}
