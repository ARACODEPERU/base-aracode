<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryAccountController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index()
    {
        $this->authorizePermission('treasury_cuentas');

        $accounts = TreasuryAccount::query()
            ->with(['bankAccount.bank', 'companyBilletera.billetera'])
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'type' => $account->type,
                'label' => $account->displayName(),
                'custom_label' => $account->label,
                'currency' => $account->currency_type_id,
                'opening_balance' => (float) $account->opening_balance,
                'opening_balance_date' => $account->opening_balance_date?->format('Y-m-d'),
                'status' => $account->status,
                'bank_account_id' => $account->bank_account_id,
                'company_billetera_id' => $account->company_billetera_id,
                'balance' => $this->ledger->accountBalance($account),
            ]);

        return Inertia::render('Treasury::Accounts/Index', [
            'accounts' => $accounts,
            'bankAccounts' => BankAccount::query()->with('bank')->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => trim(($item->bank?->short_name ? $item->bank->short_name . ' ' : '') . ($item->description ?? $item->number)),
                    'currency' => $item->currency_type_id,
                ]),
            'wallets' => $this->availableWallets(),
            'paymentMethods' => $this->paymentMethodsPayload(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_cuentas_nuevo');

        $data = $this->validated($request);

        TreasuryAccount::create($data);

        return back()->with('success', 'Cuenta de tesorería creada.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('treasury_cuentas_editar');

        $account = TreasuryAccount::findOrFail($id);
        $account->fill($this->validated($request, $account->id))->save();

        return back()->with('success', 'Cuenta de tesorería actualizada.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_cuentas_eliminar');

        $account = TreasuryAccount::findOrFail($id);

        if ($account->transactions()->exists()) {
            // Con movimientos no se borra: se desactiva para preservar el historial
            $account->status = false;
            $account->save();

            return back()->with('success', 'La cuenta tiene movimientos: se desactivó en lugar de eliminarse.');
        }

        $account->delete();

        return back()->with('success', 'Cuenta eliminada.');
    }

    /**
     * Asigna una cuenta de tesorería a un método de pago (por bank_account_id).
     * Es lo que permite mapear automáticamente los cobros de ventas.
     */
    public function assignPaymentMethod(Request $request)
    {
        $this->authorizePermission('treasury_cuentas_editar');

        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
        ]);

        $method = PaymentMethod::findOrFail($data['payment_method_id']);

        // Métodos de billetera (Yape/Plin/MercadoPago) se resuelven por nombre;
        // solo se permite asignar cuenta bancaria directa a los demás.
        $data['bank_account_id'] = $data['bank_account_id'] ?: null;
        $method->bank_account_id = $data['bank_account_id'];
        $method->save();

        return back()->with('success', 'Método de pago asignado.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'type' => ['required', 'in:bank,wallet'],
            'bank_account_id' => ['nullable', 'required_if:type,bank', 'integer', 'exists:bank_accounts,id'],
            'company_billetera_id' => ['nullable', 'required_if:type,wallet', 'integer', 'exists:company_billeteras,id'],
            'label' => ['nullable', 'string', 'max:150'],
            'currency_type_id' => ['nullable', 'string', 'max:3'],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999'],
            'opening_balance_date' => ['nullable', 'date'],
            'status' => ['boolean'],
        ]) + ['status' => $request->boolean('status', true)];
    }

    private function availableWallets()
    {
        return BilleteraDigital::query()->with('companyBilleteras')->get()
            ->flatMap(fn ($billetera) => $billetera->companyBilleteras->map(fn ($cb) => [
                'id' => $cb->id,
                'label' => trim(($billetera->short_name ?? $billetera->full_name) . ' ' . ($cb->account_name ?? $cb->account_number)),
            ]));
    }

    private function paymentMethodsPayload()
    {
        return PaymentMethod::query()->get()->map(fn (PaymentMethod $method) => [
            'id' => $method->id,
            'description' => $method->description,
            'bank_account_id' => $method->bank_account_id,
        ]);
    }
}
