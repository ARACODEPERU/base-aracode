<?php

namespace Modules\Treasury\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Modules\Treasury\Entities\TreasuryAccount;

/**
 * Resuelve a qué cuenta de tesorería pertenece un método de pago.
 *
 * Reglas:
 *  - Método con bank_account_id configurada -> cuenta tipo "bank" de esa cuenta.
 *  - Método cuyo nombre coincide con una billetera registrada (Yape, Plin,
 *    MercadoPago...) -> cuenta tipo "wallet" de esa billetera.
 *  - Métodos excluidos por config (efectivo) o sin cuenta -> null
 *    (no se registra movimiento, no rompe el flujo de venta).
 */
class PaymentAccountResolver
{
    /**
     * Descripciones de métodos de pago excluidos del libro (en minúsculas).
     *
     * @var array<int, string>
     */
    private array $excluded;

    public function __construct()
    {
        $this->excluded = collect(config('treasury.excluded_payment_methods', ['efectivo']))
            ->map(fn ($item) => mb_strtolower(trim((string) $item)))
            ->all();
    }

    /**
     * Cuenta de tesorería para un método de pago dado (o null si no aplica).
     */
    public function resolveForMethod(?int $paymentMethodId): ?TreasuryAccount
    {
        if (! $paymentMethodId) {
            return null;
        }

        $method = PaymentMethod::find($paymentMethodId);

        if (! $method) {
            return null;
        }

        return $this->resolveForMethodModel($method);
    }

    /**
     * Variante por modelo, útil para no reconsultar el mismo método en bucles.
     */
    public function resolveForMethodModel(PaymentMethod $method): ?TreasuryAccount
    {
        if (in_array(mb_strtolower(trim($method->description)), $this->excluded, true)) {
            return null;
        }

        // 1) Método configurado con cuenta bancaria explícita
        if (filled($method->bank_account_id)) {
            return TreasuryAccount::query()
                ->active()
                ->where('type', TreasuryAccount::TYPE_BANK)
                ->where('bank_account_id', $method->bank_account_id)
                ->first();
        }

        // 2) Método de billetera por nombre (Yape, Plin, MercadoPago...)
        return $this->walletForName($method->description);
    }

    /**
     * Cuenta tipo wallet cuya billetera coincide con el nombre dado.
     */
    public function walletForName(?string $name): ?TreasuryAccount
    {
        $needle = $this->normalizeName($name);

        if ($needle === '') {
            return null;
        }

        $wallets = $this->walletAccounts();

        return $wallets->first(function (TreasuryAccount $account) use ($needle) {
            $billetera = $account->companyBilletera?->billetera;

            if (! $billetera) {
                return false;
            }

            return $this->normalizeName($billetera->short_name) === $needle
                || $this->normalizeName($billetera->full_name) === $needle
                || str_contains($this->normalizeName($billetera->full_name), $needle)
                || str_contains($needle, $this->normalizeName($billetera->short_name));
        });
    }

    /**
     * Cache de cuentas wallet con su billetera precargada (por request).
     */
    private function walletAccounts(): Collection
    {
        static $cache = null;

        if ($cache === null) {
            $cache = TreasuryAccount::query()
                ->active()
                ->wallets()
                ->with(['companyBilletera.billetera'])
                ->get();
        }

        return $cache;
    }

    private function normalizeName(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }
}
