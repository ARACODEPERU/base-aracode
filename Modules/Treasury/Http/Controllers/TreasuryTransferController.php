<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryTransferProfile;
use Modules\Treasury\Services\TreasuryTransferService;

/**
 * Transferencias entre cuentas de tesorería (bancos y billeteras).
 *
 * La comisión del destino siempre se cobra; la del origen solo entre cuentas
 * propias (siempre lo son aquí) y el usuario la elige. El check de ITF libre
 * para cuentas propias se guarda por par de cuentas.
 */
class TreasuryTransferController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryTransferService $transfers)
    {
    }

    /**
     * Lista las transferencias y muestra el formulario con el perfil guardado del par elegido.
     */
    public function index(Request $request)
    {
        $this->authorizePermission('treasury_cuentas');

        $accounts = TreasuryAccount::query()
            ->active()
            ->with(['bankAccount.bank', 'companyBilletera.billetera'])
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'currency' => $account->currency_type_id,
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
                'itf_amount' => (float) $transfer->itf_amount,
                'itf_applies' => (bool) $transfer->itf_applies,
            ]);

        return Inertia::render('Treasury/Transfers/Index', [
            'accounts' => $accounts,
            'transfers' => $transfers,
            'itfThreshold' => (float) config('treasury.itf_threshold', 1000),
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
                ],
                $data['reference'] ?? null,
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        // Se queda en la misma pantalla; el mensaje lo muestra la vista con alertaSistema
        return back();
    }
}
