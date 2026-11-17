<?php

namespace Modules\Treasury\Services;

use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use Modules\Treasury\Entities\TreasuryAccount;

/**
 * Crea cuentas de Tesorería 1:1 a partir de cuentas bancarias y billeteras
 * existentes. Es idempotente: si ya existe una cuenta vinculada, no duplica.
 */
class TreasuryAccountSyncService
{
    /**
     * Crea la cuenta de Tesorería de una cuenta bancaria si todavía no tiene una.
     */
    public function ensureForBankAccount(BankAccount $bankAccount): ?TreasuryAccount
    {
        $existing = TreasuryAccount::query()->where('bank_account_id', $bankAccount->id)->first();
        if ($existing) {
            return $existing;
        }

        return TreasuryAccount::create([
            'type' => 'bank',
            'bank_account_id' => $bankAccount->id,
            'currency_type_id' => $bankAccount->currency_type_id ?: 'PEN',
            'status' => (bool) $bankAccount->status,
        ]);
    }

    /**
     * Crea la cuenta de Tesorería de una billetera si todavía no tiene una.
     */
    public function ensureForWallet(CompanyBilletera $billetera): ?TreasuryAccount
    {
        $existing = TreasuryAccount::query()->where('company_billetera_id', $billetera->id)->first();
        if ($existing) {
            return $existing;
        }

        return TreasuryAccount::create([
            'type' => 'wallet',
            'company_billetera_id' => $billetera->id,
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);
    }

    /**
     * Cuenta "Efectivo/Caja": recibe el dinero cobrado en efectivo.
     * Es única; si ya existe no se duplica.
     */
    public function ensureCash(): TreasuryAccount
    {
        $existing = TreasuryAccount::query()->where('type', TreasuryAccount::TYPE_CASH)->first();
        if ($existing) {
            return $existing;
        }

        return TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Efectivo/Caja',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);
    }

    /**
     * Cuenta de MercadoPago: es una billetera del catálogo (tipo wallet), por
     * eso se crea igual que cualquier billetera registrada y no requiere número
     * de cuenta. Devuelve null si el catálogo aún no tiene MercadoPago.
     */
    public function ensureMercadoPago(): ?TreasuryAccount
    {
        $billetera = $this->ensureMercadoPagoWallet();

        return $billetera ? $this->ensureForWallet($billetera) : null;
    }

    /**
     * Registro de la billetera MercadoPago en company_billeteras, sin número de
     * cuenta (la columna es obligatoria, así que se guarda cadena vacía).
     * Si el catálogo no tiene MercadoPago, devuelve null.
     */
    private function ensureMercadoPagoWallet(): ?CompanyBilletera
    {
        $existente = CompanyBilletera::query()
            ->whereHas('billetera', fn ($query) => $query->whereRaw('LOWER(full_name) = ?', ['mercadopago']))
            ->first();

        if ($existente) {
            // MercadoPago se cobra con enlace de pago: la negociacion necesita ese campo.
            if (! $existente->has_payment_link) {
                $existente->update(['has_payment_link' => true]);
            }

            return $existente;
        }

        $catalogo = BilleteraDigital::query()
            ->whereRaw('LOWER(full_name) = ?', ['mercadopago'])
            ->first();

        if (! $catalogo) {
            return null;
        }

        return CompanyBilletera::create([
            'billetera_id' => $catalogo->id,
            'account_name' => 'MercadoPago',
            'account_number' => '',
            'has_payment_link' => true,
            'status' => true,
        ]);
    }

    /**
     * Botón "Crear cuentas según parámetros": recorre bancos y billeteras existentes
     * y crea las cuentas que falten. Devuelve la cantidad creada.
     */
    public function syncAll(): int
    {
        $created = 0;

        if (! TreasuryAccount::query()->where('type', TreasuryAccount::TYPE_CASH)->exists()) {
            $this->ensureCash();
            $created++;
        }

        BankAccount::query()->get()->each(function (BankAccount $bankAccount) use (&$created) {
            $exists = TreasuryAccount::query()->where('bank_account_id', $bankAccount->id)->exists();
            if (! $exists) {
                $this->ensureForBankAccount($bankAccount);
                $created++;
            }
        });

        // MercadoPago no lleva número de cuenta: se registra como billetera sin cuenta bancaria
        $this->ensureMercadoPagoWallet();

        CompanyBilletera::query()->get()->each(function (CompanyBilletera $billetera) use (&$created) {
            $exists = TreasuryAccount::query()->where('company_billetera_id', $billetera->id)->exists();
            if (! $exists) {
                $this->ensureForWallet($billetera);
                $created++;
            }
        });

        // Cada cuenta bancaria y billetera activa se ofrece también como método de pago.
        app(PaymentMethodCatalogService::class)->ensureForAccounts();

        return $created;
    }
}
