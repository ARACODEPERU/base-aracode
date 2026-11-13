<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\SaleDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryIntegrationService;
use Modules\Treasury\Services\TreasuryLedgerService;

/**
 * Vinculación manual de cobros: comprobantes de venta cuyo dinero no llegó al
 * libro porque el método de pago no tiene cuenta (típicamente efectivo o
 * ventas creadas sin medio de pago).
 *
 * Dos caminos:
 *  - Existe fila puente ignorada/fallida  -> se fija la cuenta explícita y se
 *    reprocesa (nunca se duplica: la clave única ya existe).
 *  - No existe ninguna fila (venta sin `payments`) -> se crea el movimiento
 *    manual por el total del comprobante.
 *
 * Nunca modifica `sales.payments`: la cuenta explícita manda en el puente.
 */
class TreasuryCollectionLinkController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_cobros_vincular');

        $from = $request->get('from') ?: Carbon::now()->subDays(30)->toDateString();
        $to = $request->get('to') ?: Carbon::now()->toDateString();

        $documents = SaleDocument::query()
            ->with('sale')
            ->whereIn('invoice_type_doc', ['01', '03'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', '!=', 3)
            ->whereNotIn('invoice_status', ['Anulada', 'Por anular', 'Rechazada'])
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $documentBridges = TreasurySourceMovement::query()
            ->where('source_type', TreasurySourceMovement::SOURCE_SALE_DOCUMENT)
            ->whereIn('source_id', $documents->pluck('id'))
            ->whereNull('reversal_of_id')
            ->get()
            ->groupBy('source_id');

        $saleBridges = TreasurySourceMovement::query()
            ->where('source_type', TreasurySourceMovement::SOURCE_SALE)
            ->whereIn('source_id', $documents->pluck('sale_id')->filter()->unique())
            ->whereNull('reversal_of_id')
            ->get()
            ->groupBy('source_id');

        $rows = [];

        foreach ($documents as $document) {
            $bridges = collect($documentBridges->get($document->id, []))
                ->merge($saleBridges->get($document->sale_id, []));

            // Ya registrado en el libro: no se vuelve a vincular.
            if ($bridges->contains(fn (TreasurySourceMovement $bridge) => $bridge->status === TreasurySourceMovement::STATUS_PROCESSED)) {
                continue;
            }

            $pending = $bridges->filter(fn (TreasurySourceMovement $bridge) => in_array($bridge->status, [
                TreasurySourceMovement::STATUS_IGNORED,
                TreasurySourceMovement::STATUS_FAILED,
                TreasurySourceMovement::STATUS_PENDING,
            ], true))->values();

            $bridge = $pending->first();
            $total = (float) ($document->overall_total ?? $document->invoice_mto_imp_sale ?? 0);

            $rows[] = [
                'id' => $document->id,
                'number' => trim(($document->invoice_serie ?? '') . '-' . ($document->invoice_correlative ?? '')),
                'date' => $document->invoice_broadcast_date
                    ? Carbon::parse($document->invoice_broadcast_date)->toDateString()
                    : $document->created_at?->toDateString(),
                'client' => $document->client_rzn_social,
                'total' => round($total, 2),
                'currency' => strtoupper((string) ($document->invoice_type_currency ?: 'PEN')),
                'state' => $bridge === null
                    ? 'sin_registro'
                    : ($bridge->status === TreasurySourceMovement::STATUS_FAILED ? 'fallido' : 'ignorado'),
                'reason' => $bridge?->error_message,
                'method' => $bridge?->paymentMethod?->description,
                'pending_amount' => round((float) ($pending->sum('amount') ?: $total), 2),
            ];
        }

        return Inertia::render('Treasury::Collections/Index', [
            'documents' => $rows,
            'accounts' => TreasuryAccount::query()
                ->active()
                ->with(['bankAccount.bank', 'companyBilletera.billetera'])
                ->get()
                ->map(fn (TreasuryAccount $account) => [
                    'id' => $account->id,
                    'label' => $account->displayName(),
                    'currency' => $account->currency_type_id,
                ])
                ->all(),
            'paymentMethods' => PaymentMethod::query()->orderBy('description')->get(['id', 'description'])->all(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function store(Request $request, TreasuryIntegrationService $integration)
    {
        $this->authorizePermission('treasury_cobros_vincular');

        $data = $request->validate([
            'sale_document_id' => ['required', 'integer', 'exists:sale_documents,id'],
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $document = SaleDocument::with('sale')->findOrFail($data['sale_document_id']);

        if ((string) $document->status === '3'
            || in_array($document->invoice_status, ['Anulada', 'Por anular', 'Rechazada'], true)) {
            return back()->withErrors([
                'sale_document_id' => 'El comprobante está anulado o rechazado: no se puede vincular un cobro.',
            ]);
        }

        $account = TreasuryAccount::query()->active()->find($data['treasury_account_id']);

        if (! $account) {
            return back()->withErrors([
                'treasury_account_id' => 'La cuenta seleccionada está inactiva.',
            ]);
        }

        $bridges = $this->originBridges($document);

        if ($bridges->contains(fn (TreasurySourceMovement $bridge) => $bridge->status === TreasurySourceMovement::STATUS_PROCESSED)) {
            return back()->withErrors([
                'sale_document_id' => 'Este comprobante ya tiene un cobro registrado en tesorería.',
            ]);
        }

        $accountCurrency = strtoupper((string) $account->currency_type_id);

        if ($bridges->isNotEmpty()) {
            foreach ($bridges as $bridge) {
                if (strtoupper((string) $bridge->currency) !== $accountCurrency && $accountCurrency !== 'PEN') {
                    return back()->withErrors([
                        'treasury_account_id' => 'La moneda de la cuenta no coincide con la del comprobante.',
                    ]);
                }
            }

            foreach ($bridges as $bridge) {
                $integration->assignAccount($bridge, $account, auth()->id());
            }

            return back()->with('success', 'Cobro vinculado a la cuenta seleccionada.');
        }

        // Comprobante sin ningún movimiento de origen (venta creada sin pagos).
        [$documentCurrency, $rate] = $this->ledger->resolveDocumentCurrency($document);

        if ($documentCurrency !== $accountCurrency && $accountCurrency !== 'PEN') {
            return back()->withErrors([
                'treasury_account_id' => 'La moneda de la cuenta no coincide con la del comprobante.',
            ]);
        }

        if ($documentCurrency !== $accountCurrency && $accountCurrency === 'PEN' && empty($rate)) {
            return back()->withErrors([
                'treasury_account_id' => 'El comprobante está en moneda extranjera y no tiene tipo de cambio registrado.',
            ]);
        }

        $movement = TreasuryHooks::recordManualDocumentIncome(
            $document,
            $account,
            $data['payment_method_id'] ?? null,
            isset($data['amount']) ? (float) $data['amount'] : null,
            $data['date'] ?? null,
            $data['reference'] ?? null,
        );

        if (! $movement || $movement->status !== TreasurySourceMovement::STATUS_PROCESSED) {
            return back()->withErrors([
                'sale_document_id' => 'No se pudo registrar el cobro: revisa el monto y el estado de la cuenta.',
            ]);
        }

        return back()->with('success', 'Cobro registrado y vinculado a la cuenta.');
    }

    /**
     * Filas puente vigentes del comprobante y de su venta.
     *
     * @return Collection<int, TreasurySourceMovement>
     */
    private function originBridges(SaleDocument $document): Collection
    {
        $bridges = TreasurySourceMovement::query()
            ->where('source_type', TreasurySourceMovement::SOURCE_SALE_DOCUMENT)
            ->where('source_id', (int) $document->id)
            ->whereNull('reversal_of_id')
            ->get();

        if ($document->sale_id) {
            $bridges = $bridges->merge(
                TreasurySourceMovement::query()
                    ->where('source_type', TreasurySourceMovement::SOURCE_SALE)
                    ->where('source_id', (int) $document->sale_id)
                    ->whereNull('reversal_of_id')
                    ->get()
            );
        }

        return $bridges->values();
    }
}
