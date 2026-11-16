<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryPeriodService;

/**
 * Cierre de periodos de Tesorería. Un mes cerrado no admite movimientos con
 * esa fecha; la reapertura exige motivo y queda auditada.
 */
class TreasuryPeriodController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryPeriodService $periods)
    {
    }

    public function index()
    {
        $this->authorizePermission('treasury_periodos');

        return Inertia::render('Treasury::Periods/Index', [
            'periods' => $this->periods->recent(),
            'canClose' => auth()->user()?->hasPermissionTo('treasury_periodos_cerrar'),
            'canReopen' => auth()->user()?->hasPermissionTo('treasury_periodos_reabrir'),
        ]);
    }

    public function close(Request $request)
    {
        $this->authorizePermission('treasury_periodos_cerrar');

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $period = $this->periods->close(
                (int) $data['year'],
                (int) $data['month'],
                auth()->id(),
                $data['notes'] ?? null
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Periodo {$period->label()} cerrado. No se podrán registrar movimientos con esa fecha.");
    }

    public function reopen(Request $request)
    {
        $this->authorizePermission('treasury_periodos_reabrir');

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $period = $this->periods->reopen(
                (int) $data['year'],
                (int) $data['month'],
                $data['reason'],
                auth()->id()
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Periodo {$period->label()} reabierto. La reapertura quedó registrada en auditoría.");
    }
}
