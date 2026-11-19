<?php

namespace Modules\Ai\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Ai\Services\AiBudgetService;

/**
 * Revisa el gasto de IA del mes y registra la alerta una sola vez por nivel.
 *
 * Se programa cada hora en AiServiceProvider. El registro queda en
 * ai_budget_alerts y se muestra en el dashboard del módulo.
 */
class CheckAiBudget extends Command
{
    protected $signature = 'ai:presupuesto';

    protected $description = 'Registra la alerta mensual de presupuesto de IA (aviso o límite excedido)';

    public function handle(AiBudgetService $budget): int
    {
        $estado = $budget->estado();

        $this->line(sprintf(
            'Gasto de IA del mes: US$ %.6f de US$ %.2f (%s)',
            $estado['spend'],
            $estado['budget'],
            $estado['level']
        ));

        $alerta = $budget->registrarAlerta();

        if ($alerta === null) {
            $this->info('Sin alerta nueva.');

            return self::SUCCESS;
        }

        Log::warning('Presupuesto de IA: ' . $alerta->level, [
            'period' => $alerta->period,
            'spend_usd' => $alerta->spend_usd,
            'budget_usd' => $alerta->budget_usd,
            'threshold_percent' => $alerta->threshold_percent,
        ]);

        $this->warn('Alerta registrada: ' . $alerta->level);

        return self::SUCCESS;
    }
}
