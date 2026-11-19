<?php

namespace Modules\Ai\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Modules\Ai\Entities\AiBudgetAlert;
use Modules\Ai\Entities\AiRoleLimit;
use Modules\Ai\Support\IaSettings;

/**
 * Estado del presupuesto de IA, alertas mensuales y límites por rol.
 */
class AiBudgetService
{
    public function __construct(private readonly AiUsageRecorder $recorder)
    {
    }

    /**
     * @return array{level: string, budget: float, spend: float, percent: ?float, remaining: float, hard_stop: bool}
     */
    public function estado(): array
    {
        $budget = IaSettings::monthlyBudgetUsd();
        $spend = $this->recorder->monthlySpendUsd();
        $threshold = IaSettings::budgetWarningPercent();

        $percent = $budget > 0 ? round($spend / $budget * 100, 1) : null;
        $level = 'ok';

        if ($budget > 0) {
            if ($spend >= $budget) {
                $level = 'exceeded';
            } elseif ($threshold > 0 && $percent !== null && $percent >= $threshold) {
                $level = 'warning';
            }
        }

        return [
            'level' => $level,
            'budget' => $budget,
            'spend' => $spend,
            'percent' => $percent,
            'remaining' => round(max(0, $budget - $spend), 6),
            'hard_stop' => IaSettings::budgetHardStop(),
        ];
    }

    /**
     * Registra una sola alerta por mes y nivel. Devuelve la alerta nueva o
     * null si todavía no corresponde o ya estaba registrada.
     */
    public function registrarAlerta(): ?AiBudgetAlert
    {
        if (! Schema::hasTable('ai_budget_alerts')) {
            return null;
        }

        $estado = $this->estado();

        if ($estado['level'] === 'ok' || $estado['budget'] <= 0) {
            return null;
        }

        $existente = AiBudgetAlert::query()
            ->where('period', now()->format('Y-m'))
            ->where('level', $estado['level'])
            ->exists();

        if ($existente) {
            return null;
        }

        return AiBudgetAlert::create([
            'period' => now()->format('Y-m'),
            'level' => $estado['level'],
            'spend_usd' => $estado['spend'],
            'budget_usd' => $estado['budget'],
            'threshold_percent' => IaSettings::budgetWarningPercent(),
            'notified_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{period: string, level: string, spend: float, budget: float, notified_at: ?string}>
     */
    public function alertasRecientes(int $limit = 6): array
    {
        if (! Schema::hasTable('ai_budget_alerts')) {
            return [];
        }

        return AiBudgetAlert::query()
            ->latest('period')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AiBudgetAlert $alert) => [
                'period' => $alert->period,
                'level' => $alert->level,
                'spend' => round((float) $alert->spend_usd, 6),
                'budget' => round((float) $alert->budget_usd, 6),
                'notified_at' => optional($alert->notified_at)->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * Límite diario más restrictivo entre los roles del usuario (0 = sin tope).
     */
    public function limiteDiarioPorRol(Authenticatable $usuario): int
    {
        $limites = $this->limitesDe($usuario, 'daily_limit');

        return $limites === [] ? 0 : (int) min($limites);
    }

    /**
     * Presupuesto mensual más restrictivo entre los roles del usuario, aplicado
     * al gasto de ese usuario (0 = sin tope propio).
     */
    public function presupuestoMensualPorRol(Authenticatable $usuario): float
    {
        $limites = $this->limitesDe($usuario, 'monthly_budget_usd');

        return $limites === [] ? 0.0 : (float) min($limites);
    }

    /**
     * @return array<int, float>
     */
    private function limitesDe(Authenticatable $usuario, string $columna): array
    {
        if (! Schema::hasTable('ai_role_limits') || ! method_exists($usuario, 'getRoleNames')) {
            return [];
        }

        $roles = $usuario->getRoleNames()->all();

        if ($roles === []) {
            return [];
        }

        return AiRoleLimit::query()
            ->whereIn('role_name', $roles)
            ->where($columna, '>', 0)
            ->pluck($columna)
            ->map(fn ($valor) => (float) $valor)
            ->all();
    }
}
