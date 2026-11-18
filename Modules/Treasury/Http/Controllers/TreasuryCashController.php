<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryCashCountService;
use Modules\Treasury\Services\TreasuryCashService;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryCashController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryCashService $cash,
        private TreasuryCashCountService $counts,
        private TreasuryLedgerService $ledger,
    ) {
    }

    /**
     * Panel de efectivo: ventas y abonos cobrados en efectivo, egresos de caja
     * chica, saldo por sesión y arqueos registrados. No escribe en el libro.
     */
    public function index(Request $request)
    {
        $this->authorizePermission('treasury_efectivo');

        return Inertia::render('Treasury::Cash/Index', array_merge(
            $this->cash->build($request->get('from'), $request->get('to')),
            [
                'counts' => $this->counts->history(null, 30),
                'denominations' => TreasuryCashCountService::DENOMINATIONS,
                'canCount' => auth()->user()?->hasPermissionTo('treasury_efectivo_arqueo'),
                // Enlace con el libro: el efectivo manual vive en su cuenta de Tesorería.
                'ledger' => $this->ledgerPayload(),
            ]
        ));
    }

    /**
     * Saldo del libro de la cuenta de efectivo, para cruzar la pestaña de cajas
     * con Movimientos. No se resta de la caja: son dos registros distintos.
     *
     * @return array<string, mixed>|null
     */
    private function ledgerPayload(): ?array
    {
        $account = TreasuryAccount::query()->active()->cash()->first();

        if (! $account) {
            return null;
        }

        return [
            'account_id' => $account->id,
            'label' => $account->displayName(),
            'balance' => $this->ledger->displayBalance($account),
            'movements_count' => $account->transactions()->active()->count(),
        ];
    }

    /**
     * Registra el arqueo de una sesión y, si se pide, la cierra con el dinero
     * contado como saldo final.
     */
    public function storeCount(Request $request)
    {
        $this->authorizePermission('treasury_efectivo_arqueo');

        $data = $request->validate([
            'petty_cash_id' => ['required', 'integer', 'exists:petty_cashes,id'],
            'denominations' => ['required', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'close_session' => ['boolean'],
        ]);

        try {
            $count = $this->counts->record(
                (int) $data['petty_cash_id'],
                $data['denominations'] ?? [],
                $data['notes'] ?? null,
                $request->boolean('close_session'),
                auth()->id()
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = 'Arqueo registrado: contado '
            . number_format((float) $count->counted_amount, 2, '.', '')
            . ', diferencia ' . number_format((float) $count->difference, 2, '.', '') . '.';

        if ($count->closed_session) {
            $message .= ' La caja quedó cerrada.';
        }

        return back()->with('success', $message);
    }
}
