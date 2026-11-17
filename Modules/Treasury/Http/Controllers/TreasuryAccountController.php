<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\FeeCalculator;
use Modules\Treasury\Services\PaymentMethodCatalogService;
use Modules\Treasury\Services\TreasuryAccountSyncService;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryAccountController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index(TreasuryAccountSyncService $sync, PaymentMethodCatalogService $catalog)
    {
        $this->authorizePermission('treasury_cuentas');

        // MercadoPago es la pasarela con la que trabaja el sistema: su cuenta tiene
        // que existir siempre en el libro, aunque nadie la haya creado a mano.
        $sync->ensureMercadoPago();

        // Las cuentas bancarias y billeteras activas también se ofrecen como
        // método de pago en ventas, cuotas y tesorería.
        $catalog->ensureForAccounts();

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
                // Comisiones del medio de pago: se descuentan como egreso por cada ingreso.
                'fixed_fee' => (float) $account->fixed_fee,
                'percent_fee' => (float) $account->percent_fee,
                'igv_applies' => (bool) $account->igv_applies,
                'commission_formula' => $account->commission_formula,
                'status' => $account->status,
                'bank_account_id' => $account->bank_account_id,
                'company_billetera_id' => $account->company_billetera_id,
                // Billetera con banco vinculado: su dinero vive en el banco, así que
                // no muestra total; solo los movimientos del periodo. El banco, en
                // cambio, suma lo que llegó por sus billeteras.
                'hides_balance' => $this->ledger->hidesBalance($account),
                'balance' => $this->ledger->hidesBalance($account)
                    ? null
                    : $this->ledger->displayBalance($account),
                'period_income' => $this->walletPeriodIncome($account),
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

    /**
     * Ingresos del mes en curso que llegaron por una billetera (incluye los que
     * se registraron en su banco vinculado mediante source_wallet_account_id).
     */
    private function walletPeriodIncome(TreasuryAccount $account): float
    {
        if ($account->type !== TreasuryAccount::TYPE_WALLET) {
            return 0.0;
        }

        return round((float) TreasuryTransaction::query()
            ->active()
            ->where('type', TreasuryTransaction::TYPE_INCOME)
            ->where(function ($query) use ($account) {
                $query->where('treasury_account_id', $account->id)
                    ->orWhere('source_wallet_account_id', $account->id);
            })
            ->whereDate('transaction_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('transaction_date', '<=', now()->endOfMonth()->toDateString())
            ->sum('amount'), 2);
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
        $data = $this->validated($request, $account->id);

        // El saldo de detracción solo se mueve desde su pantalla crítica.
        if ($account->isDetraction()) {
            unset($data['opening_balance'], $data['opening_balance_date']);
        }

        $account->fill($data)->save();

        return back()->with('success', 'Cuenta de tesorería actualizada.');
    }

    /**
     * Botón "Crear cuentas según parámetros": crea las cuentas de Tesorería
     * que falten a partir de los bancos y billeteras existentes.
     */
    public function sync(TreasuryAccountSyncService $sync)
    {
        $this->authorizePermission('treasury_cuentas_nuevo');

        $created = $sync->syncAll();

        return back()->with('success', $created > 0
            ? "Se crearon {$created} cuenta(s) de tesorería."
            : 'Todas las cuentas ya existen en Tesorería.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_cuentas_eliminar');

        $account = TreasuryAccount::findOrFail($id);

        if ($account->isDetraction()) {
            return back()->with('error', 'La cuenta de detracción es del sistema: no se puede eliminar ni desactivar.');
        }

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
        $data = $request->validate([
            'type' => ['required', 'in:bank,wallet'],
            'bank_account_id' => ['nullable', 'required_if:type,bank', 'integer', 'exists:bank_accounts,id'],
            'company_billetera_id' => ['nullable', 'required_if:type,wallet', 'integer', 'exists:company_billeteras,id'],
            'label' => ['nullable', 'string', 'max:150'],
            'currency_type_id' => ['nullable', 'string', 'max:3'],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999'],
            'opening_balance_date' => ['nullable', 'date'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'percent_fee' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'igv_applies' => ['boolean'],
            'commission_formula' => ['nullable', 'string', 'max:255'],
            'status' => ['boolean'],
        ]);

        $data['fixed_fee'] = round((float) ($data['fixed_fee'] ?? 0), 2);
        $data['percent_fee'] = round((float) ($data['percent_fee'] ?? 0), 4);
        $data['igv_applies'] = $request->boolean('igv_applies');
        $data['commission_formula'] = filled($data['commission_formula'] ?? null)
            ? trim($data['commission_formula'])
            : null;
        $data['status'] = $request->boolean('status', true);

        // La fórmula se valida con el mismo parser que calcula la comisión.
        if ($data['commission_formula']) {
            app(FeeCalculator::class)->validateFormula($data['commission_formula']);
        }

        return $data;
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
