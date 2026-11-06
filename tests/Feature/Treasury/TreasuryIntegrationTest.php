<?php

namespace Tests\Feature\Treasury;

use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\FinancialMovement;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryIntegrationService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Tests\TestCase;

/**
 * Capa de integración Ventas → Tesorería.
 *
 * Cada prueba verifica el hecho de origen, la fila de la tabla puente y el
 * movimiento del libro (importe, moneda, estado y ausencia de duplicados).
 */
class TreasuryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PaymentMethod $yape;

    private PaymentMethod $efectivo;

    private PaymentMethod $plin;

    private TreasuryAccount $account;

    private TreasuryIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->yape = PaymentMethod::firstOrCreate(['description' => 'Yape'], ['number' => '999999999']);
        $this->efectivo = PaymentMethod::firstOrCreate(['description' => 'Efectivo']);
        $this->plin = PaymentMethod::firstOrCreate(['description' => 'Plin'], ['number' => '999999999']);

        $this->account = $this->makeWalletAccount('Yape');
        $this->service = app(TreasuryIntegrationService::class);
    }

    public function test_venta_al_contado_registra_un_movimiento_por_cada_pago(): void
    {
        $sale = $this->makeSale([
            'total' => 100,
            'payments' => [
                ['type' => $this->yape->id, 'amount' => 60, 'reference' => 'OP-1'],
                ['type' => $this->yape->id, 'amount' => 40, 'reference' => 'OP-2'],
            ],
        ]);
        $this->makeDocument($sale);

        $this->assertSame(2, TreasuryHooks::recordSaleIncomes($sale));

        // Dos pagos del mismo importe y fecha ya no se bloquean entre sí.
        $this->assertSame(2, TreasuryTransaction::query()->count());
        $this->assertSame(2, TreasurySourceMovement::query()->processed()->count());
        $this->assertSame(100.0, $this->balance());
    }

    public function test_registrar_dos_veces_el_mismo_pago_no_duplica(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 80]],
        ]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);
        TreasuryHooks::recordSaleIncomes($sale);

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()->count());
        $this->assertSame(80.0, $this->balance());
    }

    public function test_el_efectivo_no_se_registra_en_el_libro_bancario(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->efectivo->id, 'amount' => 50]],
        ]);
        $this->makeDocument($sale);

        $this->assertSame(0, TreasuryHooks::recordSaleIncomes($sale));

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, $bridge->status);
        $this->assertSame(0, TreasuryTransaction::query()->count());
        $this->assertSame(0.0, $this->balance());
    }

    public function test_venta_al_credito_sin_cuota_inicial_no_registra_ingreso(): void
    {
        $sale = $this->makeSale(['total' => 900, 'advancement' => 0, 'payments' => null]);
        $this->makeDocument($sale, ['forma_pago' => 'Credito']);

        $this->assertSame(0, TreasuryHooks::recordSaleIncomes($sale));
        $this->assertSame(0, TreasurySourceMovement::query()->count());
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_la_cuota_inicial_registra_solo_el_importe_recibido(): void
    {
        $sale = $this->makeSale(['total' => 1000, 'advancement' => 200, 'payments' => null]);
        $document = $this->makeDocument($sale, ['forma_pago' => 'Credito']);

        TreasuryHooks::recordDownPayment($document, [
            'type' => $this->yape->id,
            'amount' => 200,
            'reference' => 'CI-1',
        ]);

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(200.0, $this->balance());

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame('down_payment', $bridge->operation);
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
    }

    public function test_abono_de_cuota_registra_ingreso_y_su_anulacion_no_duplica(): void
    {
        [$payment, $document] = $this->makeQuotaPayment(150);

        TreasuryHooks::recordQuotaPayment($payment, $document);

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(150.0, $this->balance());

        TreasuryHooks::voidQuotaPayment($payment);
        TreasuryHooks::voidQuotaPayment($payment);

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertNotNull(TreasuryTransaction::query()->first()->voided_at);
        $this->assertSame(0.0, $this->balance());

        $original = TreasurySourceMovement::query()->whereNull('reversal_of_id')->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_REVERSED, $original->status);
        $this->assertSame(1, TreasurySourceMovement::query()->whereNotNull('reversal_of_id')->count());
    }

    public function test_el_pago_total_registra_el_dinero_recibido_una_sola_vez(): void
    {
        $sale = $this->makeSale(['total' => 500, 'advancement' => 0, 'payments' => null]);
        $document = $this->makeDocument($sale, ['forma_pago' => 'Credito']);

        TreasuryHooks::recordFullPayment($document, 500, $this->yape->id, 'SALDO-1', now()->toDateString());
        TreasuryHooks::recordFullPayment($document, 500, $this->yape->id, 'SALDO-1', now()->toDateString());

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(500.0, $this->balance());
    }

    public function test_la_anulacion_de_la_venta_revierte_sus_ingresos_una_sola_vez(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 70]],
        ]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);
        $this->assertSame(70.0, $this->balance());

        TreasuryHooks::voidSaleIncomes($sale);
        TreasuryHooks::voidSaleIncomes($sale);

        $this->assertSame(0.0, $this->balance());
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()->whereNotNull('reversal_of_id')->count());
    }

    public function test_un_comprobante_en_dolares_se_convierte_con_su_tipo_de_cambio(): void
    {
        $sale = $this->makeSale([
            'total' => 100,
            'payments' => [['type' => $this->yape->id, 'amount' => 100, 'reference' => 'USD-1']],
        ]);
        $this->makeDocument($sale, [
            'invoice_type_currency' => 'USD',
            'exchange_rate' => 3.75,
        ]);

        TreasuryHooks::recordSaleIncomes($sale);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame('USD', $bridge->currency);
        $this->assertSame('100.00', (string) $bridge->amount);
        $this->assertSame('375.00', (string) $bridge->amount_base);

        $transaction = TreasuryTransaction::query()->firstOrFail();
        $this->assertSame('375.00', (string) $transaction->amount);
        $this->assertStringContainsString('TC USD 3.75', (string) $transaction->reference);
    }

    public function test_una_cuenta_en_dolares_no_acepta_un_pago_en_soles(): void
    {
        $this->makeWalletAccount('Plin', 'USD');

        $sale = $this->makeSale([
            'payments' => [['type' => $this->plin->id, 'amount' => 120]],
        ]);
        $this->makeDocument($sale);

        $this->assertSame(0, TreasuryHooks::recordSaleIncomes($sale));

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_FAILED, $bridge->status);
        $this->assertStringContainsString('no admite la moneda', (string) $bridge->error_message);
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    public function test_un_cobro_del_cronograma_genera_su_movimiento(): void
    {
        $sale = $this->makeSale(['total' => 300, 'payments' => null]);
        $document = $this->makeDocument($sale, ['forma_pago' => 'Credito']);

        $destination = SalePaymentScheduleDestination::create([
            'method_id' => $this->yape->id,
            'date_payment' => now()->toDateString(),
            'reference' => 'CRONO-1',
            'amount' => 300,
            'document_id' => $document->id,
            'sale_id' => $sale->id,
            'status' => 'paid',
            'user_id' => $this->user->id,
        ]);

        TreasuryHooks::recordScheduleDestination($destination);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION, $bridge->source_type);
        $this->assertSame('installment_payment', $bridge->operation);
        $this->assertSame(300.0, $this->balance());
    }

    public function test_reintentar_un_movimiento_fallido_no_duplica(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 90]],
        ]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $bridge->treasuryTransaction->delete();
        $bridge->update([
            'status' => TreasurySourceMovement::STATUS_FAILED,
            'treasury_transaction_id' => null,
            'error_message' => 'fallo simulado',
        ]);

        $result = $this->service->retryPending();

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->refresh()->status);
        $this->assertSame(90.0, $this->balance());
    }

    public function test_el_backfill_es_idempotente_y_no_cobra_ventas_al_credito(): void
    {
        $contado = $this->makeSale([
            'total' => 100,
            'payments' => [['type' => $this->yape->id, 'amount' => 100, 'reference' => 'CONT-1']],
        ]);
        $this->makeDocument($contado);

        // Venta al crédito: su JSON de pagos no es dinero recibido.
        $credito = $this->makeSale([
            'total' => 500,
            'advancement' => 0,
            'payments' => [['type' => $this->yape->id, 'amount' => 500]],
        ]);
        $documentCredito = $this->makeDocument($credito, ['forma_pago' => 'Credito']);
        SaleDocumentQuota::create([
            'sale_document_id' => $documentCredito->id,
            'quota_number' => 1,
            'amount' => 500,
            'due_date' => now()->addMonth()->toDateString(),
            'balance' => 500,
            'status' => 'Pendiente',
        ]);

        $this->artisan('treasury:backfill', ['--since' => '2020-01-01'])->assertSuccessful();
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(100.0, $this->balance());

        $this->artisan('treasury:backfill', ['--since' => '2020-01-01'])->assertSuccessful();
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()->count());
    }

    public function test_el_backfill_en_dry_run_no_escribe_nada(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 45]],
        ]);
        $this->makeDocument($sale);

        $this->artisan('treasury:backfill', ['--since' => '2020-01-01', '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, TreasuryTransaction::query()->count());
        $this->assertSame(0, TreasurySourceMovement::query()->count());
    }

    public function test_un_monto_no_positivo_es_rechazado(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->register(new \Modules\Treasury\Services\FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE,
            sourceId: 1,
            operation: 'sale_income',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: 0,
            date: now(),
        ));
    }

    public function test_dos_abonos_de_la_misma_cuota_generan_dos_movimientos(): void
    {
        [$firstPayment, $document] = $this->makeQuotaPayment(100);

        $secondPayment = SalePaymentQuota::create([
            'payment_method_id' => $this->yape->id,
            'sale_document_quota_id' => $firstPayment->sale_document_quota_id,
            'reference' => 'ABONO-2',
            'payment_date' => now()->toDateString(),
            'amount_applied' => 50,
            'description' => null,
            'estado' => true,
        ]);

        TreasuryHooks::recordQuotaPayment($firstPayment, $document);
        TreasuryHooks::recordQuotaPayment($secondPayment, $document);

        $this->assertSame(2, TreasuryTransaction::query()->count());
        $this->assertSame(150.0, $this->balance());
    }

    public function test_la_clave_unica_de_la_base_impide_duplicar_el_movimiento(): void
    {
        $movement = new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE,
            sourceId: 999,
            operation: 'sale_income',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: 25,
            date: now(),
        );

        $this->service->register($movement);

        $this->expectException(QueryException::class);

        // Simula al segundo proceso que gana la carrera después de la
        // comprobación previa: la restricción única es la que decide.
        TreasurySourceMovement::create([
            'module' => $movement->module,
            'source_type' => $movement->sourceType,
            'source_id' => $movement->sourceId,
            'source_line' => null,
            'operation' => $movement->operation,
            'type' => $movement->type,
            'status' => TreasurySourceMovement::STATUS_PENDING,
            'amount' => 25,
            'currency' => 'PEN',
            'amount_base' => 25,
            'movement_date' => now()->toDateString(),
            'idempotency_key' => 'sales|sale|999|-|sale_income|-',
            'source' => 'auto',
        ]);
    }

    public function test_dos_instancias_del_servicio_no_duplican_el_mismo_pago(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 35]],
        ]);
        $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);

        app()->forgetInstance(TreasuryIntegrationService::class);
        $secondRun = app(TreasuryIntegrationService::class);

        $this->assertSame(
            TreasurySourceMovement::query()->value('id'),
            $secondRun->register($this->saleIncomeMovement($sale, 0, 35))->id
        );
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()->count());
    }

    public function test_el_fallo_del_libro_deja_el_movimiento_para_reintento(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 120]],
        ]);
        $this->makeDocument($sale);

        // Mock parcial: solo falla la escritura en el libro, el resto se comporta real.
        $this->partialMock(TreasuryLedgerService::class, function ($mock) {
            $mock->shouldReceive('registerIncome')->andReturn(null);
        });
        app()->forgetInstance(TreasuryIntegrationService::class);

        TreasuryHooks::recordSaleIncomes($sale);

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_FAILED, $bridge->status);
        $this->assertStringContainsString('no creó el movimiento', (string) $bridge->error_message);
        $this->assertSame(0, TreasuryTransaction::query()->count());

        // Con el libro restaurado, el reintento resuelve sin duplicar.
        app()->forgetInstance(TreasuryLedgerService::class);
        app()->forgetInstance(TreasuryIntegrationService::class);

        $result = app(TreasuryIntegrationService::class)->retryPending();

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(120.0, $this->balance());
    }

    public function test_una_devolucion_efectiva_se_registra_como_egreso(): void
    {
        $sale = $this->makeSale([
            'payments' => [['type' => $this->yape->id, 'amount' => 200]],
        ]);
        $document = $this->makeDocument($sale);

        TreasuryHooks::recordSaleIncomes($sale);
        $this->assertSame(200.0, $this->balance());

        $refund = $this->service->registerRefund(new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: 'sale_document',
            sourceId: $document->id,
            operation: 'refund',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: 200,
            date: now(),
            paymentMethodId: $this->yape->id,
            reference: 'DEV-1',
        ));

        $this->assertSame(TreasurySourceMovement::TYPE_EXPENSE, $refund->type);
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $refund->status);
        $this->assertSame(0.0, $this->balance());
        $this->assertSame(2, TreasuryTransaction::query()->count());
    }

    private function saleIncomeMovement(Sale $sale, int $line, float $amount): FinancialMovement
    {
        return new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE,
            sourceId: $sale->id,
            operation: 'sale_income',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: $amount,
            date: now(),
            sourceLine: $line,
            paymentMethodId: $this->yape->id,
        );
    }

    private function makeSale(array $attributes = []): Sale
    {
        return Sale::create(array_merge([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 100,
            'advancement' => 100,
            'total_discount' => 0,
            'payments' => null,
            'sale_date' => now()->toDateString(),
            'physical' => 1,
        ], $attributes));
    }

    private function makeDocument(Sale $sale, array $attributes = []): SaleDocument
    {
        return SaleDocument::create(array_merge([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => (string) (SaleDocument::query()->count() + 1),
            'invoice_type_currency' => 'PEN',
            'forma_pago' => 'Contado',
            'overall_total' => $sale->total,
        ], $attributes));
    }

    /**
     * @return array{0: SalePaymentQuota, 1: SaleDocument}
     */
    private function makeQuotaPayment(float $amount): array
    {
        $sale = $this->makeSale(['total' => $amount, 'advancement' => 0, 'payments' => null]);
        $document = $this->makeDocument($sale, ['forma_pago' => 'Credito']);

        $quota = SaleDocumentQuota::create([
            'sale_document_id' => $document->id,
            'quota_number' => 1,
            'amount' => $amount,
            'due_date' => now()->addMonth()->toDateString(),
            'balance' => $amount,
            'status' => 'Pendiente',
        ]);

        $payment = SalePaymentQuota::create([
            'payment_method_id' => $this->yape->id,
            'sale_document_quota_id' => $quota->id,
            'reference' => 'ABONO-1',
            'payment_date' => now()->toDateString(),
            'amount_applied' => $amount,
            'description' => null,
            'estado' => true,
        ]);

        return [$payment, $document];
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

    private function balance(): float
    {
        return app(TreasuryLedgerService::class)->accountBalance($this->account->refresh());
    }
}
