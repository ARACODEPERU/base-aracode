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
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryLedgerService;
use Tests\TestCase;

/**
 * Vinculación de métodos de pago con cuentas bancarias de Tesorería.
 *
 * Cubre la resolución explícita (payment_methods.bank_account_id), la
 * compatibilidad con billeteras por nombre y los casos en que la vinculación
 * no es utilizable (cuenta inexistente, inactiva o de otra moneda).
 */
class PaymentMethodBankAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PaymentMethod $bcp;

    private PaymentMethod $bbva;

    private PaymentMethod $yape;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->bcp = PaymentMethod::query()->where('description', 'Transferencia BCP')->firstOrFail();
        $this->bbva = PaymentMethod::query()->where('description', 'Transferencia BBVA')->firstOrFail();
        $this->yape = PaymentMethod::query()->where('description', 'Yape')->firstOrFail();
    }

    public function test_una_transferencia_vinculada_a_cuenta_bancaria_registra_el_ingreso(): void
    {
        $bankAccount = $this->makeBankAccount('PEN');
        $treasury = $this->makeTreasuryBankAccount($bankAccount, 'PEN');
        $this->linkMethod($this->bcp, $bankAccount->id);

        $this->registerSalePayment($this->bcp->id, 350);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame(TreasurySourceMovement::TYPE_INCOME, $bridge->type);
        $this->assertSame($this->bcp->id, $bridge->payment_method_id);
        $this->assertSame($treasury->id, $bridge->treasury_account_id);
        $this->assertNotNull($bridge->treasury_transaction_id);

        $transaction = TreasuryTransaction::query()->firstOrFail();
        $this->assertSame($treasury->id, $transaction->treasury_account_id);
        $this->assertSame('350.00', (string) $transaction->amount);
        $this->assertSame(350.0, $this->balanceOf($treasury));
    }

    public function test_un_metodo_sin_cuenta_vinculada_conserva_el_comportamiento_anterior(): void
    {
        // Sin bank_account_id y sin billetera que coincida por nombre.
        $this->assertNull($this->bbva->bank_account_id);

        $this->registerSalePayment($this->bbva->id, 200);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertNull($bridge->treasury_transaction_id);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_la_billetera_digital_sigue_resolviendose_por_nombre(): void
    {
        $wallet = $this->makeWalletAccount('Yape');

        $this->registerSalePayment($this->yape->id, 80);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame($wallet->id, $bridge->treasury_account_id);
        $this->assertSame(80.0, $this->balanceOf($wallet));
    }

    public function test_una_vinculacion_a_una_cuenta_bancaria_inexistente_no_genera_ingreso(): void
    {
        $this->linkMethod($this->bcp, 999999);

        $this->registerSalePayment($this->bcp->id, 100);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_una_vinculacion_a_una_cuenta_de_tesoreria_inactiva_no_genera_ingreso(): void
    {
        $bankAccount = $this->makeBankAccount('PEN');
        $this->makeTreasuryBankAccount($bankAccount, 'PEN', false);
        $this->linkMethod($this->bcp, $bankAccount->id);

        $this->registerSalePayment($this->bcp->id, 100);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_una_vinculacion_a_una_cuenta_bancaria_inactiva_no_genera_ingreso(): void
    {
        $bankAccount = $this->makeBankAccount('PEN', false);
        $this->makeTreasuryBankAccount($bankAccount, 'PEN');
        $this->linkMethod($this->bcp, $bankAccount->id);

        $this->registerSalePayment($this->bcp->id, 100);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_una_cuenta_de_tesoreria_con_otra_moneda_que_su_cuenta_bancaria_no_se_usa(): void
    {
        $bankAccount = $this->makeBankAccount('PEN');
        $this->makeTreasuryBankAccount($bankAccount, 'USD');
        $this->linkMethod($this->bcp, $bankAccount->id);

        $this->registerSalePayment($this->bcp->id, 100);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_una_cuenta_bancaria_en_dolares_rechaza_un_cobro_en_soles(): void
    {
        $bankAccount = $this->makeBankAccount('USD');
        $treasury = $this->makeTreasuryBankAccount($bankAccount, 'USD');
        $this->linkMethod($this->bcp, $bankAccount->id);

        $this->registerSalePayment($this->bcp->id, 100);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_FAILED, $bridge->status);
        $this->assertStringContainsString('no admite la moneda', (string) $bridge->error_message);
        $this->assertSame(0, TreasuryTransaction::query()->count());
        $this->assertSame(0.0, $this->balanceOf($treasury));
    }

    public function test_registrar_dos_veces_la_misma_transferencia_no_duplica(): void
    {
        $bankAccount = $this->makeBankAccount('PEN');
        $treasury = $this->makeTreasuryBankAccount($bankAccount, 'PEN');
        $this->linkMethod($this->bcp, $bankAccount->id);

        $sale = $this->makeSale([['type' => $this->bcp->id, 'amount' => 150]]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);
        TreasuryHooks::recordSaleIncomes($sale);

        $this->assertSame(1, TreasurySourceMovement::query()->count());
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(150.0, $this->balanceOf($treasury));
    }

    private function linkMethod(PaymentMethod $method, ?int $bankAccountId): void
    {
        $method->bank_account_id = $bankAccountId;
        $method->save();
    }

    private function makeBankAccount(string $currency, bool $active = true): BankAccount
    {
        $bank = Bank::create([
            'short_name' => 'BCP',
            'full_name' => 'Banco de Crédito del Perú',
            'status' => $active,
        ]);

        return BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta corriente ' . $currency,
            'number' => '193-0001-' . $currency,
            'currency_type_id' => $currency,
            'status' => $active,
        ]);
    }

    private function makeTreasuryBankAccount(BankAccount $bankAccount, string $currency, bool $active = true): TreasuryAccount
    {
        return TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $bankAccount->id,
            'label' => 'BCP ' . $currency,
            'currency_type_id' => $currency,
            'opening_balance' => 0,
            'status' => $active,
        ]);
    }

    private function makeWalletAccount(string $walletName, string $currency = 'PEN'): TreasuryAccount
    {
        $billetera = BilleteraDigital::create([
            'short_name' => $walletName,
            'full_name' => $walletName . ' Digital',
            'status' => true,
        ]);

        $companyBilletera = CompanyBilletera::create([
            'billetera_id' => $billetera->id,
            'account_name' => 'ARACODE',
            'account_number' => '999888777',
            'status' => true,
        ]);

        return TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_WALLET,
            'company_billetera_id' => $companyBilletera->id,
            'label' => $walletName . ' caja',
            'currency_type_id' => $currency,
            'opening_balance' => 0,
            'status' => true,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     */
    private function makeSale(array $payments): Sale
    {
        return Sale::create([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => array_sum(array_column($payments, 'amount')),
            'advancement' => array_sum(array_column($payments, 'amount')),
            'total_discount' => 0,
            'payments' => $payments,
            'sale_date' => now()->toDateString(),
            'physical' => 1,
        ]);
    }

    private function makeDocument(Sale $sale): SaleDocument
    {
        return SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => (string) (SaleDocument::query()->count() + 1),
            'invoice_type_currency' => 'PEN',
            'forma_pago' => 'Contado',
            'overall_total' => $sale->total,
        ]);
    }

    private function registerSalePayment(int $paymentMethodId, float $amount): void
    {
        $sale = $this->makeSale([['type' => $paymentMethodId, 'amount' => $amount]]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);
    }

    private function balanceOf(TreasuryAccount $account): float
    {
        return app(TreasuryLedgerService::class)->accountBalance($account->refresh());
    }
}
