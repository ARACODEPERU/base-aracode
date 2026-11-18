<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Sales\Services\Sire\SireApiException;
use Modules\Sales\Services\Sire\SirePropuestaService;

/**
 * Acciones de solo lectura del SIRE por API: descargar la propuesta del
 * período. No solicita aceptación ni envía reemplazos.
 */
class SirePropuestaController extends Controller
{
    public function ventas(Request $request)
    {
        return $this->procesar($request, 'ventas');
    }

    public function compras(Request $request)
    {
        return $this->procesar($request, 'compras');
    }

    private function procesar(Request $request, string $tipo)
    {
        $period = (string) $request->get('period');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) !== 1) {
            $period = Carbon::now()->format('Y-m');
        }

        $company = Company::query()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Configura primero los datos de la empresa.',
            ], 422);
        }

        try {
            $resultado = app(SirePropuestaService::class)->descargar(
                $company,
                $tipo,
                $period,
                $request->user()?->id
            );

            return response()->json(['success' => true] + $resultado);
        } catch (SireApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'ticket' => $e->ticket(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo descargar la propuesta de SUNAT. Revisa la bitácora del SIRE.',
            ], 500);
        }
    }
}
