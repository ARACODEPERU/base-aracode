<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Sales\Services\Sire\SireApiException;
use Modules\Sales\Services\Sire\SirePresentacionService;

/**
 * Presentación del SIRE por API: comparar la propuesta con el ERP, reemplazarla
 * y aceptarla.
 *
 * El SIRE no tiene ambiente de pruebas: las tres acciones trabajan contra el
 * registro real de SUNAT. Por eso el reemplazo exige la comparación previa y,
 * si hay diferencias, una confirmación explícita.
 */
class SirePresentacionController extends Controller
{
    public function comparar(Request $request, string $tipo)
    {
        return $this->procesar($request, $tipo, 'comparar');
    }

    public function reemplazar(Request $request, string $tipo)
    {
        return $this->procesar($request, $tipo, 'reemplazar');
    }

    public function aceptar(Request $request, string $tipo)
    {
        return $this->procesar($request, $tipo, 'aceptar');
    }

    public function estado(Request $request)
    {
        $company = Company::query()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Configura primero los datos de la empresa.',
            ], 422);
        }

        $id = (int) $request->get('submission_id');

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Indica el proceso cuyo estado quieres consultar.',
            ], 422);
        }

        try {
            return response()->json(['success' => true] + app(SirePresentacionService::class)
                ->estado($company, $id));
        } catch (SireApiException $e) {
            return $this->errorControlado($e);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo consultar el estado del proceso. Revisa la bitácora del SIRE.',
            ], 500);
        }
    }

    private function procesar(Request $request, string $tipo, string $accion)
    {
        $tipo = $tipo === 'compras' ? 'compras' : 'ventas';
        $period = $this->period($request);

        $company = Company::query()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Configura primero los datos de la empresa.',
            ], 422);
        }

        $service = app(SirePresentacionService::class);

        try {
            $resultado = match ($accion) {
                'comparar' => $service->comparar($company, $tipo, $period, $request->user()?->id),
                'reemplazar' => $service->reemplazar(
                    $company,
                    $tipo,
                    $period,
                    $request->boolean('aceptar_diferencias'),
                    $request->user()?->id
                ),
                default => $service->aceptar($company, $tipo, $period, $request->user()?->id),
            };

            return response()->json(['success' => true] + $resultado);
        } catch (SireApiException $e) {
            return $this->errorControlado($e);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la acción del SIRE. Revisa la bitácora.',
            ], 500);
        }
    }

    private function errorControlado(SireApiException $e)
    {
        $payload = $e->payload();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'ticket' => $e->ticket(),
            // La comparación completa se devuelve para que la pantalla muestre
            // las diferencias y el usuario decida si envía igual.
            'comparacion' => isset($payload['resumen']) ? $payload : null,
        ], 422);
    }

    private function period(Request $request): string
    {
        $period = (string) $request->get('period');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1
            ? $period
            : Carbon::now()->format('Y-m');
    }
}
