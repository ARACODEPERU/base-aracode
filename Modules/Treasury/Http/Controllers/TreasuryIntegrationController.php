<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Services\TreasuryIntegrationService;

/**
 * Bandeja de movimientos de integración que no llegaron al libro.
 *
 * Antes solo se podían recuperar por consola (`treasury:integration-retry`);
 * ahora cualquier usuario con permiso ve el error y reintenta desde aquí.
 * El comando se conserva para automatizaciones.
 */
class TreasuryIntegrationController extends Controller
{
    use ValidatesTreasuryRequests;

    public function index()
    {
        $this->authorizePermission('treasury_reintentos');

        $movements = TreasurySourceMovement::query()
            ->where(function ($query) {
                $query->whereIn('status', [
                    TreasurySourceMovement::STATUS_FAILED,
                    TreasurySourceMovement::STATUS_PENDING,
                ])->orWhere(function ($stale) {
                    $stale->where('status', TreasurySourceMovement::STATUS_PROCESSING)
                        ->where('updated_at', '<', now()->subMinutes(TreasurySourceMovement::PROCESSING_TIMEOUT_MINUTES));
                });
            })
            ->with(['account', 'paymentMethod'])
            ->orderBy('movement_date')
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->map(fn (TreasurySourceMovement $movement) => [
                'id' => $movement->id,
                'module' => $movement->module,
                'source_type' => $movement->source_type,
                'source_id' => $movement->source_id,
                'operation' => $movement->operation,
                'type' => $movement->type,
                'status' => $movement->status,
                'amount' => (float) $movement->amount,
                'currency' => $movement->currency ?: 'PEN',
                'movement_date' => $movement->movement_date?->format('d/m/Y'),
                'description' => $movement->description,
                'reference' => $movement->reference,
                'account' => $movement->account?->displayName(),
                'payment_method' => $movement->paymentMethod?->description,
                'error' => $movement->error_message,
                'attempts' => $movement->updated_at?->format('d/m/Y H:i'),
            ]);

        $counts = TreasurySourceMovement::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Treasury::Integration/Index', [
            'movements' => $movements,
            'counts' => $counts,
        ]);
    }

    public function retry(Request $request, TreasuryIntegrationService $integration)
    {
        $this->authorizePermission('treasury_reintentos');

        $data = $request->validate([
            'movement_id' => ['nullable', 'integer', 'exists:treasury_source_movements,id'],
        ]);

        $result = $integration->retryPending(
            limit: empty($data['movement_id']) ? 100 : 1,
            movementId: $data['movement_id'] ?? null
        );

        $message = "Procesados: {$result['processed']}, ignorados: {$result['ignored']}, con error: {$result['failed']}.";

        return back()->with($result['failed'] > 0 ? 'error' : 'success', $message);
    }
}
