<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryTransferProfile;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryCurrencyService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransferService;

/**
 * Transferencias entre cuentas de tesorería (bancos y billeteras).
 *
 * Si las cuentas tienen monedas distintas se exige tipo de cambio SUNAT: la
 * pantalla muestra el monto realmente acreditado en el destino.
 */
class TreasuryTransferController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryTransferService $transfers,
        private TreasuryCurrencyService $currency,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_cuentas');

        $accounts = TreasuryAccount::query()
            ->active()
            ->where('type', '<>', TreasuryAccount::TYPE_DETRACTION)
            ->with(['bankAccount.bank', 'companyBilletera.billetera'])
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'currency' => $this->currency->accountCurrency($account),
                // Saldo que la regla de sobregiro usa para esta cuenta; la caja no se limita.
                'balance' => $account->type === TreasuryAccount::TYPE_CASH
                    ? null
                    : app(TreasuryLedgerService::class)->accountBalance($account),
            ]);

        $transfers = TreasuryTransfer::query()
            ->with(['fromAccount', 'toAccount'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (TreasuryTransfer $transfer) => [
                'id' => $transfer->id,
                'transfer_date' => $transfer->transfer_date?->format('Y-m-d'),
                'from' => $transfer->fromAccount?->displayName(),
                'to' => $transfer->toAccount?->displayName(),
                'amount' => (float) $transfer->amount,
                'from_currency' => $transfer->from_currency ?: 'PEN',
                'to_currency' => $transfer->to_currency ?: 'PEN',
                'exchange_rate' => $transfer->exchange_rate !== null ? (float) $transfer->exchange_rate : null,
                'amount_destination' => $transfer->amount_destination !== null
                    ? (float) $transfer->amount_destination
                    : (float) $transfer->net_received,
                'itf_amount' => (float) $transfer->itf_amount,
                'itf_applies' => (bool) $transfer->itf_applies,
                'from_fee_amount' => (float) $transfer->from_fee_amount,
                'from_fee_igv' => (float) $transfer->from_fee_igv,
                'to_fee_amount' => (float) $transfer->to_fee_amount,
                'to_fee_igv' => (float) $transfer->to_fee_igv,
                'total_fees' => round((float) $transfer->from_fee_amount + (float) $transfer->from_fee_igv
                    + (float) $transfer->to_fee_amount + (float) $transfer->to_fee_igv
                    + (float) $transfer->itf_amount, 2),
                'voided' => $transfer->isVoided(),
            ]);

        // Tipos de cambio vigentes para las monedas distintas de la base.
        $rates = [];

        foreach ($accounts->pluck('currency')->unique()->filter(fn ($code) => $code !== 'PEN') as $code) {
            $rate = $this->currency->rate($code);

            if ($rate) {
                $rates[$code] = $rate;
            }
        }

        return Inertia::render('Treasury::Transfers/Index', [
            'accounts' => $accounts,
            'transfers' => $transfers,
            'rates' => (object) $rates,
            'itfThreshold' => (float) config('treasury.itf_threshold', 1000),
            'canVoid' => auth()->user()?->hasPermissionTo('treasury_cuentas_editar'),
        ]);
    }

    /**
     * Perfil guardado para un par de cuentas (para proponer comisiones e ITF propios).
     */
    public function profile(Request $request)
    {
        $this->authorizePermission('treasury_cuentas');

        $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:treasury_accounts,id', 'different:from_account_id'],
        ]);

        $profile = TreasuryTransferProfile::query()
            ->where('from_account_id', $request->integer('from_account_id'))
            ->where('to_account_id', $request->integer('to_account_id'))
            ->first();

        return response()->json($profile);
    }

    /**
     * Registra una transferencia entre dos cuentas de tesorería.
     */
    public function store(Request $request)
    {
        $this->authorizePermission('treasury_cuentas_nuevo');

        $data = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:treasury_accounts,id', 'different:to_account_id'],
            'to_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'transfer_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'to_fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'to_percent_fee' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'charge_origin_fee' => ['nullable', 'boolean'],
            'from_fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'from_percent_fee' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'own_account_itf_free' => ['nullable', 'boolean'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $from = TreasuryAccount::query()->active()->findOrFail($data['from_account_id']);
        $to = TreasuryAccount::query()->active()->findOrFail($data['to_account_id']);

        try {
            $this->transfers->transfer(
                $from,
                $to,
                (float) $data['amount'],
                $data['transfer_date'],
                [
                    'from_fixed_fee' => (float) ($data['from_fixed_fee'] ?? 0),
                    'from_percent_fee' => (float) ($data['from_percent_fee'] ?? 0),
                    'to_fixed_fee' => (float) ($data['to_fixed_fee'] ?? 0),
                    'to_percent_fee' => (float) ($data['to_percent_fee'] ?? 0),
                    'own_account_itf_free' => (bool) ($data['own_account_itf_free'] ?? false),
                    'charge_origin_fee' => (bool) ($data['charge_origin_fee'] ?? false),
                    'exchange_rate' => isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
                ],
                $data['reference'] ?? null,
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            // La operación se rechaza completa: se deja rastro para poder explicarla.
            Log::warning('Transferencia rechazada', [
                'user_id' => auth()->id(),
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'amount' => $data['amount'],
                'transfer_date' => $data['transfer_date'],
                'motivo' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Transferencia registrada.');
    }

    /**
     * Anula la transferencia completa (sus dos movimientos).
     */
    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_cuentas_editar');

        try {
            $this->transfers->void(TreasuryTransfer::findOrFail($id), auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transferencia anulada. Los movimientos se conservan como anulados.');
    }
}
