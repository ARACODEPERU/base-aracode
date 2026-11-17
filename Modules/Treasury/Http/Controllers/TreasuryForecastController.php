<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryForecastAdjustment;
use Modules\Treasury\Services\TreasuryForecastService;

/**
 * Previsión de flujo de caja por moneda. Es una estimación construida con
 * cobranza pendiente, órdenes aprobadas, recurrentes y ajustes manuales.
 */
class TreasuryForecastController extends Controller
{
    use ValidatesTreasuryRequests;

    public function index(Request $request, TreasuryForecastService $forecast)
    {
        $this->authorizePermission('treasury_prevision');

        $currency = strtoupper((string) $request->get('currency', 'PEN')) ?: 'PEN';
        $days = (int) ($request->get('days') ?: 30);
        $ratio = $request->filled('ratio') ? (float) $request->get('ratio') : null;

        $data = $forecast->build($currency, $days, $ratio);

        $adjustments = TreasuryForecastAdjustment::query()
            ->where('currency', $currency)
            ->orderBy('date')
            ->get()
            ->map(fn (TreasuryForecastAdjustment $row) => [
                'id' => $row->id,
                'date' => $row->date->format('Y-m-d'),
                'type' => $row->type,
                'amount' => (float) $row->amount,
                'concept' => $row->concept,
            ]);

        return Inertia::render('Treasury::Forecast/Index', [
            'forecast' => $data,
            'currencies' => $forecast->currencies(),
            'adjustments' => $adjustments,
            'filters' => [
                'currency' => $currency,
                'days' => $data['days'],
                'ratio' => $data['collection_ratio'],
            ],
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $this->authorizePermission('treasury_prevision');

        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'concept' => ['required', 'string', 'max:200'],
        ]);

        TreasuryForecastAdjustment::create([
            'date' => $data['date'],
            'type' => $data['type'],
            'currency' => strtoupper($data['currency'] ?? 'PEN') ?: 'PEN',
            'amount' => round((float) $data['amount'], 2),
            'concept' => $data['concept'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ítem agregado a la previsión.');
    }

    public function destroyAdjustment(int $id)
    {
        $this->authorizePermission('treasury_prevision');

        TreasuryForecastAdjustment::findOrFail($id)->delete();

        return back()->with('success', 'Ítem quitado de la previsión.');
    }
}
