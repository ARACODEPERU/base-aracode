<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Modules\Ai\Entities\AiUsage;

/**
 * Consumo de IA: quién la usa, con qué proveedor y modelo, tokens y costo.
 *
 * Los filtros se comparten entre la pantalla y las exportaciones para que el
 * archivo descargado coincida exactamente con lo que se ve en pantalla.
 */
class AiConsumoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filtros($request);

        if (! Schema::hasTable('ai_usages')) {
            return Inertia::render('Ai::Consumo/Index', [
                'usages' => ['data' => [], 'links' => [], 'total' => 0],
                'totals' => ['requests' => 0, 'tokens' => 0, 'cost' => 0.0],
                'filters' => $filters,
                'users' => [],
                'sinTabla' => true,
            ]);
        }

        $query = $this->consulta($filters);
        $totals = $this->totales(clone $query);

        $usages = $query->latest('id')->paginate(25)->withQueryString();
        $usages->through(fn (AiUsage $usage) => $this->fila($usage));

        $userIds = AiUsage::query()->whereNotNull('user_id')->distinct()->pluck('user_id');

        $users = User::query()
            ->whereIn('id', $userIds)
            ->with('person')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'nombre' => $user->person?->full_name ?: $user->name,
            ])
            ->sortBy('nombre')
            ->values()
            ->all();

        return Inertia::render('Ai::Consumo/Index', [
            'usages' => $usages,
            'totals' => $totals,
            'filters' => $filters,
            'users' => $users,
            'sinTabla' => false,
        ]);
    }

    /**
     * Exporta el consumo filtrado como CSV compatible con Excel.
     */
    public function exportarCsv(Request $request)
    {
        abort_unless(Schema::hasTable('ai_usages'), 404);

        $usages = $this->consulta($this->filtros($request))
            ->latest('id')
            ->limit(10000)
            ->get();

        $nombre = 'consumo-ia-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($usages) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8: Excel abre las tildes correctamente.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'Fecha', 'Usuario', 'Proveedor', 'Modelo', 'Pantalla',
                'Tokens entrada', 'Tokens salida', 'Tokens total', 'Costo USD', 'Estado',
            ], ';');

            foreach ($usages as $usage) {
                $fila = $this->fila($usage);

                fputcsv($salida, [
                    $fila['fecha'],
                    $fila['usuario'],
                    $fila['proveedor'],
                    $fila['modelo'],
                    $fila['pantalla'] ?: '',
                    $fila['prompt_tokens'],
                    $fila['completion_tokens'],
                    $fila['total_tokens'],
                    number_format($fila['costo'], 6, '.', ''),
                    $fila['estado'],
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Exporta el consumo filtrado a PDF (máximo 2000 filas).
     */
    public function exportarPdf(Request $request)
    {
        abort_unless(Schema::hasTable('ai_usages'), 404);

        $query = $this->consulta($this->filtros($request));
        $totales = $this->totales(clone $query);
        $usages = $query->latest('id')->limit(2000)->get();

        $pdf = Pdf::loadView('ai::consumo.pdf', [
            'filas' => $usages->map(fn (AiUsage $usage) => $this->fila($usage))->all(),
            'totales' => $totales,
            'filtros' => $this->filtros($request),
            'generado' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('consumo-ia-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * @return array{user_id: ?int, provider: ?string, desde: ?string, hasta: ?string}
     */
    private function filtros(Request $request): array
    {
        return [
            'user_id' => $request->integer('user_id') ?: null,
            'provider' => in_array($request->input('provider'), ['openai', 'gemini'], true)
                ? (string) $request->input('provider')
                : null,
            'desde' => $this->fecha($request->input('desde')),
            'hasta' => $this->fecha($request->input('hasta')),
        ];
    }

    /**
     * @param  array{user_id: ?int, provider: ?string, desde: ?string, hasta: ?string}  $filters
     */
    private function consulta(array $filters): Builder
    {
        $query = AiUsage::query()->with('user.person');

        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['provider']) {
            $query->where('provider', $filters['provider']);
        }

        if ($filters['desde']) {
            $query->where('created_at', '>=', $filters['desde'] . ' 00:00:00');
        }

        if ($filters['hasta']) {
            $query->where('created_at', '<=', $filters['hasta'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * @return array{requests: int, tokens: int, cost: float}
     */
    private function totales(Builder $query): array
    {
        return [
            'requests' => (clone $query)->count(),
            'tokens' => (int) (clone $query)->sum('total_tokens'),
            'cost' => round((float) (clone $query)->sum('cost_usd'), 6),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(AiUsage $usage): array
    {
        return [
            'id' => $usage->id,
            'fecha' => optional($usage->created_at)->format('d/m/Y H:i'),
            'usuario' => $usage->user?->person?->full_name ?: ($usage->user?->name ?: 'Sistema'),
            'proveedor' => $usage->provider,
            'modelo' => $usage->model,
            'pantalla' => $usage->screen,
            'prompt_tokens' => (int) $usage->prompt_tokens,
            'completion_tokens' => (int) $usage->completion_tokens,
            'total_tokens' => (int) $usage->total_tokens,
            'costo' => round((float) $usage->cost_usd, 6),
            'estado' => $usage->status,
        ];
    }

    private function fecha(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
