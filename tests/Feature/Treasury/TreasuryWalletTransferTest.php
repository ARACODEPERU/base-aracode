<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryWalletTransfer;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryWalletTransferService;
use Tests\TestCase;

/**
 * Billeteras digitales enlazadas a un banco con plazo de transferencia.
 *
 * Si la billetera tarda días hábiles, el cobro queda en su cuenta y se
 * programa el traslado; el saldo no se suma al banco hasta ejecutarse.
 */
class TreasuryWalletTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PaymentMethod $yape;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->yape = PaymentMethod::query()->where('description', 'Yape')->firstOrFail();
    }

    public function test_una_billetera_con_plazo_deja_el_dinero_en_ella_y_programa_el_traslado(): void
    {
        [$wallet, $bank] = $this->makeWalletWithBank(2);

        $this->registerSalePayment($this->yape->id, 350);

        $item = TreasuryWalletTransfer::query()->firstOrFail();

        $this->assertTrue($item->isPending());
        $this->assertSame($wallet->id, (int) $item->wallet_account_id);
        $this->assertSame($bank->id, (int) $item->bank_account_id);
        $this->assertSame(350.0, (float) $item->amount);
        $this->assertTrue($item->scheduled_date->isAfter(now()->startOfDay()));
        $this->assertSame(
            app(TreasuryWalletTransferService::class)
                ->addBusinessDays(now(), 2)
                ->toDateString(),
            $item->scheduled_date->toDateString()
        );

        $ledger = app(TreasuryLedgerService::class);

        // El dinero está en la billetera, no en el banco.
        $this->assertSame(350.0, (float) $ledger->accountBalance($wallet));
        $this->assertSame(0.0, (float) $ledger->accountBalance($bank));
        $this->assertSame(350.0, (float) $ledger->displayBalance($wallet));
        $this->assertSame(0.0, (float) $ledger->displayBalance($bank));
        $this->assertFalse($ledger->hidesBalance($wallet));
    }

    public function test_al_vencer_la_fecha_la_transferencia_mueve_el_dinero_al_banco(): void
    {
        [$wallet, $bank] = $this->makeWalletWithBank(1);

        $this->registerSalePayment($this->yape->id, 350);

        $item = TreasuryWalletTransfer::query()->firstOrFail();
        $item->forceFill(['scheduled_date' => now()->subDay()->toDateString()])->save();

        $result = app(TreasuryWalletTransferService::class)->runDue();

        $this->assertSame(0, $result['failed'], (string) $item->fresh()->error);
        $this->assertSame(1, $result['processed']);
        $this->assertSame(TreasuryWalletTransfer::STATUS_PROCESSED, $item->fresh()->status);
        $this->assertNotNull($item->fresh()->treasury_transfer_id);

        $ledger = app(TreasuryLedgerService::class);

        $this->assertSame(0.0, (float) $ledger->accountBalance($wallet));
        $this->assertSame(350.0, (float) $ledger->accountBalance($bank));
        // La billetera conserva su cuenta con saldo propio (ya en cero tras el traslado).
        $this->assertFalse($ledger->hidesBalance($wallet));
        $this->assertSame(0.0, (float) $ledger->displayBalance($wallet));

        // Ejecutar de nuevo no duplica el traslado.
        $again = app(TreasuryWalletTransferService::class)->runDue();
        $this->assertSame(0, $again['processed']);
        $this->assertSame(350.0, (float) $ledger->accountBalance($bank));
    }

    public function test_los_dias_habiles_saltan_el_fin_de_semana(): void
    {
        $service = app(TreasuryWalletTransferService::class);

        // Viernes 20/11/2026 + 1 día hábil = lunes 23.
        $this->assertSame('2026-11-23', $service->addBusinessDays('2026-11-20', 1)->toDateString());

        // Viernes + 3 días hábiles = miércoles 25.
        $this->assertSame('2026-11-25', $service->addBusinessDays('2026-11-20', 3)->toDateString());

        // Sin días configurados la fecha no cambia.
        $this->assertSame('2026-11-20', $service->addBusinessDays('2026-11-20', 0)->toDateString());
    }

    public function test_una_billetera_sin_plazo_sigue_registrando_el_cobro_en_el_banco(): void
    {
        [$wallet, $bank] = $this->makeWalletWithBank(0);

        $this->registerSalePayment($this->yape->id, 350);

        $ledger = app(TreasuryLedgerService::class);

        $this->assertSame(0, TreasuryWalletTransfer::query()->count());
        $this->assertSame(0.0, (float) $ledger->accountBalance($wallet));
        $this->assertSame(350.0, (float) $ledger->accountBalance($bank));
        $this->assertTrue($ledger->hidesBalance($wallet));
    }

    /**
     * Crea la cuenta bancaria, su cuenta de Tesorería y la billetera Yape
     * enlazada con el plazo indicado.
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

    private function registerSalePayment(int $paymentMethodId, float $amount): void
    {
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => $amount,
            'advancement' => $amount,
            'total_discount' => 0,
            'payments' => [['type' => $paymentMethodId, 'amount' => $amount]],
            'sale_date' => now()->toDateString(),
            'physical' => 1,
        ]);

        SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => (string) (SaleDocument::query()->count() + 1),
            'invoice_type_currency' => 'PEN',
            'forma_pago' => 'Contado',
            'overall_total' => $amount,
        ]);

        TreasuryHooks::recordSaleIncomes($sale);
    }
}
