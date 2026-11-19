<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modules\Ai\Entities\AiUsage;
use Modules\Ai\Services\AiBudgetService;
use Modules\Ai\Services\AiUsageRecorder;
use Modules\Ai\Support\IaSettings;

/**
 * Panel del módulo de IA: proveedor activo, consumo del mes y últimos usos.
 */
class AiDashboardController extends Controller
{
    public function index(AiUsageRecorder $recorder, AiBudgetService $budgetService)
    {
        $hasTable = Schema::hasTable('ai_usages');

        $base = fn () => AiUsage::query()->where('created_at', '>=', now()->startOfMonth());

        $stats = [
            'spend_month' => $hasTable ? round((float) $base()->where('status', 'ok')->sum('cost_usd'), 6) : 0.0,
            'requests_month' => $hasTable ? $base()->count() : 0,
            'tokens_month' => $hasTable ? (int) $base()->sum('total_tokens') : 0,
            'requests_today' => $hasTable ? AiUsage::query()->where('created_at', '>=', now()->startOfDay())->count() : 0,
            'errors_month' => $hasTable ? $base()->where('status', 'error')->count() : 0,
        ];

        $byProvider = $hasTable
            ? $base()
                ->selectRaw('provider, count(*) as requests, coalesce(sum(total_tokens), 0) as tokens, coalesce(sum(cost_usd), 0) as cost')
                ->groupBy('provider')
                ->orderByDesc('requests')
                ->get()
                ->map(fn ($row) => [
                    'provider' => $row->provider,
                    'requests' => (int) $row->requests,
                    'tokens' => (int) $row->tokens,
                    'cost' => round((float) $row->cost, 6),
                ])
                ->all()
            : [];

        $recent = $hasTable
            ? AiUsage::query()
                ->with('user.person')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (AiUsage $usage) => $this->formatUsage($usage))
                ->all()
            : [];

        return Inertia::render('Ai::Dashboard/Index', [
            'stats' => $stats,
            'byProvider' => $byProvider,
            'recent' => $recent,
            'budget' => IaSettings::monthlyBudgetUsd(),
            'dailyLimit' => IaSettings::dailyLimit(),
            'provider' => IaSettings::provider(),
            'openAiModel' => IaSettings::openAiModel(),
            'geminiModel' => IaSettings::geminiModel(),
            'openAiConfigured' => IaSettings::openAiApiKey() !== null,
            'geminiConfigured' => IaSettings::geminiApiKey() !== null,
            'hasTable' => $hasTable,
            'monthSpend' => $recorder->monthlySpendUsd(),
            'budgetState' => $budgetService->estado(),
            'budgetAlerts' => $budgetService->alertasRecientes(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUsage(AiUsage $usage): array
    {
        $persona = $usage->user?->person;

        return [
            'id' => $usage->id,
            'fecha' => optional($usage->created_at)->format('d/m/Y H:i'),
            'usuario' => $persona?->full_name ?: ($usage->user?->name ?: 'Sistema'),
            'proveedor' => $usage->provider,
            'modelo' => $usage->model,
            'pantalla' => $usage->screen,
            'tokens' => (int) $usage->total_tokens,
            'costo' => round((float) $usage->cost_usd, 6),
            'estado' => $usage->status,
        ];
    }
}
