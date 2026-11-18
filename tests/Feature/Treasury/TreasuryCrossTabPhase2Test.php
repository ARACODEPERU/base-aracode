<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryDocument;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Entities\TreasuryWalletTransfer;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryDocumentService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryPeriodService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Integración entre las pestañas de Tesorería (Fase 2): cobrar o pagar un
 * cheque/letra asienta el movimiento en el libro de su cuenta, y la cola de
 * traslados de billetera se ve en el Dashboard y en la Previsión.
 */
class TreasuryCrossTabPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_un_cheque_cobrado_asienta_el_ingreso_en_su_cuenta(): void
    {
        $this->actingAs($this->userWith([]));

        $account = $this->makeCashAccount(0);
        $service = app(TreasuryDocumentService::class);

        $check = $service->register([
            'type' => 'cheque',
            'direction' => 'receivable',
            'number' => 'CH-500',
            'issuer' => 'Cliente ABC',
            'amount' => 500,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'treasury_account_id' => $account->id,
        ], auth()->id());

        // En cartera el libro no se mueve.
        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($account));

        $service->changeStatus($check, TreasuryDocument::STATUS_COLLECTED, auth()->id());

        $this->assertSame(500.0, app(TreasuryLedgerService::class)->accountBalance($account));

        $movement = TreasuryTransaction::query()
            ->where('origin_type', 'treasury_document')
            ->where('origin_id', $check->id)
            ->firstOrFail();

        $this->assertSame(TreasuryTransaction::TYPE_INCOME, $movement->type);
        $this->assertSame(TreasuryTransaction::SOURCE_AUTO, $movement->source);
        $this->assertSame('PEN', $movement->currency);
        $this->assertSame('Cheque CH-500 cobrado', $movement->description);

        // Repetir el cambio no duplica el asiento.
        $service->changeStatus($check->fresh(), TreasuryDocument::STATUS_COLLECTED, auth()->id());

        $this->assertSame(1, TreasuryTransaction::query()->where('origin_type', 'treasury_document')->count());
        $this->assertSame(500.0, app(TreasuryLedgerService::class)->accountBalance($account));
    }

    public function test_una_letra_pagada_asienta_el_egreso_en_su_cuenta(): void
    {
        $this->actingAs($this->userWith([]));

        $account = $this->makeCashAccount(1000);
        $service = app(TreasuryDocumentService::class);

        $bill = $service->register([
            'type' => 'letra',
            'direction' => 'payable',
            'number' => 'LT-300',
            'issuer' => 'Proveedor XYZ',
            'amount' => 300,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'treasury_account_id' => $account->id,
        ], auth()->id());

        $service->changeStatus($bill, TreasuryDocument::STATUS_PAID, auth()->id());

        $this->assertSame(700.0, app(TreasuryLedgerService::class)->accountBalance($account));

        $movement = TreasuryTransaction::query()
            ->where('origin_type', 'treasury_document')
            ->where('origin_id', $bill->id)
            ->firstOrFail();

        $this->assertSame(TreasuryTransaction::TYPE_EXPENSE, $movement->type);
        $this->assertSame('Letra LT-300 pagado', $movement->description);
    }

    public function test_un_documento_sin_cuenta_no_mueve_el_libro(): void
    {
        $this->actingAs($this->userWith([]));

        $account = $this->makeCashAccount(0);
        $service = app(TreasuryDocumentService::class);

        $check = $service->register([
            'type' => 'cheque',
            'direction' => 'receivable',
            'number' => 'CH-SIN',
            'amount' => 250,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ], auth()->id());

        $service->changeStatus($check, TreasuryDocument::STATUS_COLLECTED, auth()->id());

        $this->assertSame(TreasuryDocument::STATUS_COLLECTED, $check->fresh()->status);
        $this->assertSame(0, TreasuryTransaction::query()->where('origin_type', 'treasury_document')->count());
        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($account));
    }

    public function test_un_periodo_cerrado_impide_asentar_el_cobro(): void
    {
        $this->actingAs($this->userWith([]));

        $account = $this->makeCashAccount(0);
        $service = app(TreasuryDocumentService::class);

        $check = $service->register([
            'type' => 'cheque',
            'direction' => 'receivable',
            'number' => 'CH-CER',
            'amount' => 400,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'treasury_account_id' => $account->id,
        ], auth()->id());

        app(TreasuryPeriodService::class)->close((int) now()->year, (int) now()->month, auth()->id());

        try {
            $service->changeStatus($check, TreasuryDocument::STATUS_COLLECTED, auth()->id());
            $this->fail('Se esperaba el bloqueo del periodo cerrado.');
        } catch (TreasuryOperationException $e) {
            $this->assertStringContainsString('cerrado', $e->getMessage());
        }

        // El estado no avanza y el libro queda igual.
        $this->assertSame(TreasuryDocument::STATUS_PORTFOLIO, $check->fresh()->status);
        $this->assertSame(0, TreasuryTransaction::query()->where('origin_type', 'treasury_document')->count());
        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($account));
    }

    public function test_la_cola_de_billeteras_se_ve_en_el_dashboard_y_en_la_prevision(): void
    {
        $user = $this->userWith(['treasury_dashboard', 'treasury_prevision']);
        $this->actingAs($user);

        [$wallet, $bank] = $this->makeWalletWithBank(2);

        app(TreasuryLedgerService::class)
            ->registerIncome($wallet, now()->toDateString(), 350, null, null, null, null, 'Cobro Yape');

        $scheduled = now()->addWeekdays(2)->toDateString();

        TreasuryWalletTransfer::create([
            'company_billetera_id' => $wallet->company_billetera_id,
            'wallet_account_id' => $wallet->id,
            'bank_account_id' => $bank->id,
            'amount' => 350,
            'currency' => 'PEN',
            'scheduled_date' => $scheduled,
            'status' => TreasuryWalletTransfer::STATUS_PENDING,
            'reference' => 'Cobro Yape',
            'user_id' => $user->id,
        ]);

        $this->get(route('treasury_dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('walletTransfers.count', 1)
                ->where('walletTransfers.total', 350)
                ->where('walletTransfers.rows.0.wallet', $wallet->displayName())
                ->where('walletTransfers.rows.0.bank', $bank->displayName())
                ->where('walletTransfers.groups.0.currency', 'PEN'));

        $this->get(route('treasury_forecast_index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('forecast.wallet_transfers.count', 1)
                ->where('forecast.wallet_transfers.total', 350)
                // El dinero ya está en el saldo inicial: no se cuenta dos veces.
                ->where('forecast.opening_balance', 350)
                ->where('forecast.totals.closing', 350));
    }

    private function makeCashAccount(float $opening): TreasuryAccount
    {
        return TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => $opening,
            'status' => true,
        ]);
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
            'number' => '193-0002-PEN',
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

        $billetera = BilleteraDigital::create([
            'short_name' => 'YAPE',
            'full_name' => 'Yape',
            'status' => true,
        ]);

        $companyBilletera = CompanyBilletera::create([
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
