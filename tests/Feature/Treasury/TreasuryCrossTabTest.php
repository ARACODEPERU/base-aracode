<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTaxService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;
use Modules\Treasury\Services\TreasuryTransferService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Integración entre las pestañas de Tesorería (Fase 1): el saldo que muestra
 * Movimientos es el mismo de Cuentas y el detalle del origen explica también
 * transferencias, comisiones e impuestos, con enlace a su pantalla.
 */
class TreasuryCrossTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_movimientos_muestra_el_mismo_saldo_visible_que_cuentas(): void
    {
        $user = $this->userWith(['treasury_movimientos']);
        [$wallet, $bank] = $this->makeWalletWithBank(2);

        $ledger = app(TreasuryLedgerService::class);

        // Cobro que quedó en la billetera porque su plazo aún no vence.
        $ledger->registerIncome($wallet, now()->toDateString(), 350, null, null, null, null, 'Cobro Yape');

        $this->actingAs($user)
            ->get(route('treasury_transactions_index', ['account_id' => $wallet->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('calculatedBalance', 350)
                ->where('hidesBalance', false)
                ->where('walletTransferDays', 2)
                ->where('walletBank', $bank->displayName()));

        // El banco todavía no tiene ese dinero: su saldo visible sigue en cero.
        $this->actingAs($user)
            ->get(route('treasury_transactions_index', ['account_id' => $bank->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('calculatedBalance', 0)
                ->where('hidesBalance', false)
                ->where('walletTransferDays', 0));
    }

    public function test_una_billetera_sin_plazo_muestra_su_dinero_en_el_banco(): void
    {
        $user = $this->userWith(['treasury_movimientos']);
        [$wallet, $bank] = $this->makeWalletWithBank(0);

        app(TreasuryLedgerService::class)
            ->registerIncome($wallet, now()->toDateString(), 120, null, null, null, null, 'Cobro inmediato');

        // Sin plazo el saldo propio es cero: el dinero se lee en el banco.
        $this->actingAs($user)
            ->get(route('treasury_transactions_index', ['account_id' => $wallet->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('calculatedBalance', 0)
                ->where('hidesBalance', true)
                ->where('walletBank', $bank->displayName()));

        $this->actingAs($user)
            ->get(route('treasury_transactions_index', ['account_id' => $bank->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // El banco suma lo que llegó por la billetera vinculada.
                ->where('calculatedBalance', 120));
    }

    public function test_el_detalle_de_una_transferencia_muestra_cuentas_y_enlace(): void
    {
        $this->actingAs($this->userWith([]));

        [$wallet, $bank] = $this->makeWalletWithBank(0);

        $ledger = app(TreasuryLedgerService::class);
        $ledger->registerIncome($wallet, now()->toDateString(), 500, null, null, null, null, 'Fondeo');

        $transfer = app(TreasuryTransferService::class)->transfer(
            $wallet->fresh(),
            $bank->fresh(),
            200,
            now()->toDateString(),
            [
                'from_fixed_fee' => 0,
                'from_percent_fee' => 0,
                'to_fixed_fee' => 0,
                'to_percent_fee' => 0,
                'own_account_itf_free' => true,
                'charge_origin_fee' => false,
            ],
        );

        $income = TreasuryTransaction::query()
            ->where('transfer_id', $transfer->id)
            ->where('treasury_account_id', $bank->id)
            ->where('type', TreasuryTransaction::TYPE_INCOME)
            ->firstOrFail();

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$income->id])[$income->id];

        $this->assertSame('Transferencia entre cuentas', $detail['tipo']);
        $this->assertSame(route('treasury_transfers_index'), $detail['url']);
        $this->assertSame('Ver transferencias', $detail['url_label']);
        $this->assertFalse($detail['conciliado']);

        $labels = collect($detail['meta'])->pluck('value', 'label');

        $this->assertSame($wallet->displayName(), $labels['Cuenta origen']);
        $this->assertSame($bank->displayName(), $labels['Cuenta destino']);
        $this->assertSame('200.00 PEN', $labels['Monto transferido']);
    }

    public function test_el_detalle_de_un_pago_de_igv_muestra_el_periodo_y_enlaza_impuestos(): void
    {
        $user = $this->userWith(['treasury_impuestos']);
        $this->actingAs($user);

        $account = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        app(TreasuryLedgerService::class)
            ->registerIncome($account, now()->toDateString(), 1000, null, null, null, null, 'Fondeo');

        $payment = app(TreasuryTaxService::class)->registerPayment(
            $account,
            '2026-11',
            180,
            now()->toDateString(),
            'Constancia 001',
            $user->id,
        );

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$payment->id])[$payment->id];

        $this->assertSame('Pago de IGV', $detail['tipo']);
        $this->assertSame(route('treasury_taxes_index'), $detail['url']);
        $this->assertSame('2026-11', collect($detail['meta'])->firstWhere('label', 'Periodo tributario')['value']);
    }

    /**
     * Cuenta bancaria, su cuenta de Tesorería y una billetera vinculada.
     *
     * @return array{0: TreasuryAccount, 1: TreasuryAccount}
     */
    private function makeWalletWithBank(int $days): array
    {
        $bank = Bank::create([
            'short_name' => 'BCP',
            'full_name' => 'Banco de Crédito del Perú',
            'status' => true,
        ]);

        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta corriente PEN',
            'number' => '193-0001-PEN',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        $treasuryBank = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $bankAccount->id,
            'label' => 'BCP PEN',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        $billetera = \App\Models\BilleteraDigital::create([
            'short_name' => 'YAPE',
            'full_name' => 'Yape',
            'status' => true,
        ]);

        $companyBilletera = \App\Models\CompanyBilletera::create([
            'billetera_id' => $billetera->id,
            'account_name' => 'ARACODE',
            'account_number' => '999888777',
            'bank_account_id' => $bankAccount->id,
            'bank_transfer_days' => $days,
            'status' => true,
        ]);

        $wallet = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_WALLET,
            'company_billetera_id' => $companyBilletera->id,
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        return [$wallet, $treasuryBank];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
