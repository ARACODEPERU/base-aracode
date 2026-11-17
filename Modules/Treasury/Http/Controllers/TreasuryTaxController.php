<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryCurrencyService;
use Modules\Treasury\Services\TreasuryTaxService;

/**
 * IGV de boletas y facturas emitidas: lo que se debe a SUNAT y el registro de
 * su pago como egreso. El IGV no se descuenta del ingreso de la venta.
 */
class TreasuryTaxController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryTaxService $taxes,
        private TreasuryCurrencyService $currency,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_impuestos');

        $month = (string) ($request->get('month') ?: now()->subMonthNoOverflow()->format('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = now()->format('Y-m');
        }

        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        $currency = strtoupper((string) ($request->get('currency') ?: 'PEN')) ?: 'PEN';

        $summary = $this->taxes->monthSummary($year, $monthNumber, $currency);

        $accounts = TreasuryAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'is_detraction' => $account->isDetraction(),
                'currency' => $this->currency->accountCurrency($account),
                'balance' => (float) app(\Modules\Treasury\Services\TreasuryLedgerService::class)->displayBalance($account),
            ]);

        // Cuenta sugerida al llegar desde la pantalla de detracción.
        $selectedAccountId = $request->integer('account_id') ?: null;

        if ($selectedAccountId && ! $accounts->contains('id', $selectedAccountId)) {
            $selectedAccountId = null;
        }

        return Inertia::render('Treasury::Taxes/Index', [
            'summary' => $summary,
            'selectedAccountId' => $selectedAccountId,
            'payments' => $this->taxes->payments(null, 40),
            'accounts' => $accounts,
            'currencies' => $this->taxesCurrencies(),
            'filters' => [
                'month' => $month,
                'currency' => $currency,
            ],
            'dueDate' => $this->taxes->dueDate($month),
            'igvRate' => 18,
            'canPay' => auth()->user()?->hasPermissionTo('treasury_impuestos'),
        ]);
    }

    /**
     * Registra el pago del IGV de un periodo en la cuenta elegida.
     */
    public function pay(Request $request)
    {
        $this->authorizePermission('treasury_impuestos');

        $data = $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $account = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);

        // La cuenta de detracción paga IGV, pero nunca más de lo que tiene.
        if ($account->isDetraction()) {
            $saldo = app(\Modules\Treasury\Services\TreasuryLedgerService::class)->accountBalance($account);

            if ((float) $data['amount'] > $saldo + 0.001) {
                return back()->with('error', 'La cuenta de detracción solo tiene ' . number_format($saldo, 2) . ' disponible: ajusta el importe del pago.');
            }
        }

        try {
            $this->taxes->registerPayment(
                $account,
                $data['period'],
                (float) $data['amount'],
                $data['date'],
                $data['reference'] ?? null,
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago de IGV registrado.');
    }

    /**
     * Monedas con documentos o cuentas: PEN siempre, más las que existan.
     *
     * @return array<int, string>
     */
    private function taxesCurrencies(): array
    {
        $currencies = TreasuryAccount::query()
            ->active()
            ->pluck('currency_type_id')
            ->map(fn ($value) => strtoupper(trim((string) $value)) ?: 'PEN')
            ->push('PEN')
            ->unique()
            ->values()
            ->all();

        return $currencies;
    }
}
