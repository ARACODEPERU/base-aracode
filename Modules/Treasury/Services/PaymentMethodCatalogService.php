<?php

namespace Modules\Treasury\Services;

use App\Models\BankAccount;
use App\Models\CompanyBilletera;
use App\Models\PaymentMethod;

/**
 * Mantiene el catálogo de métodos de pago alineado con la tesorería:
 * cada cuenta bancaria y cada billetera digital activa se ofrece como método
 * de pago, además de Efectivo y MercadoPago.
 *
 * Es idempotente: no duplica métodos existentes ni pisa lo que el usuario
 * ya configuró (números, CCI, asignación de cuenta).
 */
class PaymentMethodCatalogService
{
    public function ensureForAccounts(): int
    {
        $changes = 0;

        $changes += $this->ensure('Efectivo');
        $changes += $this->ensure('Mercadopago');

        BankAccount::query()
            ->where('status', 1)
            ->with('bank')
            ->get()
            ->each(function (BankAccount $account) use (&$changes) {
                $changes += $this->ensureForBankAccount($account);
            });

        CompanyBilletera::query()
            ->where('status', true)
            ->with('billetera')
            ->get()
            ->each(function (CompanyBilletera $wallet) use (&$changes) {
                $changes += $this->ensureForWallet($wallet);
            });

        return $changes;
    }

    private function ensure(string $description): int
    {
        if ($this->findByDescription($description)) {
            return 0;
        }

        $this->createMethod($description);

        return 1;
    }

    private function ensureForBankAccount(BankAccount $account): int
    {
        // Ya hay un método asociado a esta cuenta bancaria.
        if (PaymentMethod::query()->where('bank_account_id', $account->id)->exists()) {
            return 0;
        }

        // Reutiliza el método que ya menciona al banco (por ejemplo "Transferencia BCP").
        $bankName = mb_strtolower(trim((string) $account->bank?->short_name));

        if ($bankName !== '') {
            $existing = PaymentMethod::query()
                ->whereNull('bank_account_id')
                ->whereRaw('LOWER(description) LIKE ?', ['%' . $bankName . '%'])
                ->orderBy('id')
                ->first();

            if ($existing) {
                // Asignación directa: PaymentMethod no declara $fillable.
                $existing->bank_account_id = $account->id;
                $existing->number = $existing->number ?: $account->number;
                $existing->number_cci = $existing->number_cci ?: $account->cci;
                $existing->save();

                return 1;
            }
        }

        $description = trim(((string) $account->bank?->short_name) . ' ' . ((string) ($account->description ?: $account->number)));

        if ($description === '' || $this->findByDescription($description)) {
            return 0;
        }

        $this->createMethod($description, $account->number, $account->cci, $account->id);

        return 1;
    }

    private function ensureForWallet(CompanyBilletera $wallet): int
    {
        $names = array_filter([$wallet->billetera?->full_name, $wallet->billetera?->short_name]);

        foreach ($names as $name) {
            if ($this->findByDescription($name)) {
                return 0;
            }
        }

        $description = trim((string) ($wallet->billetera?->full_name ?: $wallet->billetera?->short_name));

        if ($description === '') {
            return 0;
        }

        // Las billeteras se resuelven por nombre, sin cuenta bancaria propia:
        // su dinero se refleja en el banco vinculado a la billetera.
        $this->createMethod($description);

        return 1;
    }

    private function createMethod(string $description, ?string $number = null, ?string $cci = null, ?int $bankAccountId = null): PaymentMethod
    {
        // Asignación directa: PaymentMethod no declara $fillable.
        $method = new PaymentMethod();
        $method->description = $description;
        $method->number = $number;
        $method->number_cci = $cci;
        $method->bank_account_id = $bankAccountId;
        $method->save();

        return $method;
    }

    private function findByDescription(string $description): ?PaymentMethod
    {
        return PaymentMethod::query()
            ->whereRaw('LOWER(description) = ?', [mb_strtolower(trim($description))])
            ->first();
    }
}
