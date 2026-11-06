<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentType;
use App\Models\Serie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentSchedule;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Flujos completos de Sales que deben terminar en un único ingreso en
 * treasury_transactions, partiendo del controlador/HTTP y no del hook.
 */
class SalesTreasuryFlowsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PaymentMethod $method;

    private TreasuryAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->local_id = 1;
        $this->user->save();
        $this->actingAs($this->user);

        [$this->method, $this->account] = $this->linkBankTransfer();
    }

    public function test_flujo_venta_contado_por_transferencia(): void
    {
        $this->grantPermissions(['invo_documento_nuevo']);

        $response = $this->postJson(route('saledocuments_store'), $this->documentPayload([
            'total' => 118,
            'forma_pago' => 'Contado',
            'payments' => [['type' => $this->method->id, 'amount' => 118, 'reference' => 'TRF-1']],
        ]));

        $response->assertOk();

        $this->assertSame(
            1,
            TreasuryTransaction::query()->count(),
            (string) json_encode($response->json())
        );
        $this->assertSame(118.0, $this->balance());
        $this->assertOnlyOneProcessedIncomeMovement('sale_income');
    }

    public function test_flujo_venta_al_credito_con_cuota_inicial_por_transferencia(): void
    {
        $this->grantPermissions(['invo_documento_nuevo']);

        $response = $this->postJson(route('saledocuments_store'), $this->documentPayload([
            'total' => 600,
            'forma_pago' => 'Credito',
            'payments' => [],
            'initial_payment' => [
                ['type' => $this->method->id, 'amount' => 100, 'reference' => 'CI-1'],
            ],
            'quotas' => [
                'amounts' => [['amount' => 500, 'dueDate' => now()->addMonth()->toDateString()]],
            ],
        ]));

        $response->assertOk();

        // Solo el dinero recibido: la cuota inicial. El saldo pendiente no es ingreso.
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(100.0, $this->balance());
        $this->assertOnlyOneProcessedIncomeMovement('down_payment');
    }

    public function test_flujo_abono_de_cuota_por_transferencia(): void
    {
        $this->grantPermissions(['acco_dashboard']);

        [, $document, $quota] = $this->makeCreditFixture(200, 200);

        $response = $this->postJson(route('acco_table_document_payment_store'), [
            'sale_document_quota_id' => $quota->id,
            'payment_method_id' => $this->method->id,
            'amount_applied' => 120,
            'reference' => 'ABONO-1',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(120.0, $this->balance());
        $this->assertSame(80.0, (float) $quota->refresh()->balance);
        $this->assertOnlyOneProcessedIncomeMovement('quota_payment');
    }

    public function test_flujo_cancelacion_total_de_la_deuda_por_transferencia(): void
    {
        $this->grantPermissions(['acco_dashboard']);

        [, $document] = $this->makeCreditFixture(300, 300);

        $response = $this->post(route('acco_table_document_payment_full_store'), [
            'document_id' => $document->id,
            'payment_method_id' => $this->method->id,
            'amount_applied' => 300,
            'reference' => 'SALDO-1',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('acco_document_list'));

        // Un solo ingreso por el dinero cobrado, no uno por cada cuota de relleno.
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(300.0, $this->balance());
        $this->assertOnlyOneProcessedIncomeMovement('full_payment');
    }

    public function test_flujo_cobro_de_cronograma_por_transferencia(): void
    {
        $this->grantPermissions(['acco_pagos_cuotas_especiales_nuevo']);

        $sale = Sale::create([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 300,
            'advancement' => 0,
            'total_discount' => 0,
            'payments' => null,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
        ]);

        $schedule = SalePaymentSchedule::create([
            'sale_id' => $sale->id,
            'installment_number' => 1,
            'payment_date' => now()->toDateString(),
            'amount_to_pay' => 300,
            'amount_paid' => 0,
            'remaining_amount' => 300,
            'is_paid' => false,
        ]);

        $payload = $this->documentPayload([
            'total' => 300,
            'forma_pago' => 'Credito',
            'payments' => [['type' => $this->method->id, 'amount' => 300, 'date_payment' => now()->toDateString()]],
            'items' => [[
                'id' => $schedule->id,
                'description' => 'Cuota 1',
                'quantity' => 1,
                'amount' => 300,
                'total' => 300,
                'actualPrice' => 300,
                'discount' => 0,
                'afe_igv' => '10',
            ]],
            'sale_id' => $sale->id,
            'next_payment_date' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->putJson(
            route('acco_sales_special_rates_quota_store', ['id' => $schedule->id]),
            $payload
        );

        $response->assertOk();

        $this->assertSame(
            1,
            TreasuryTransaction::query()->count(),
            (string) json_encode($response->json())
        );
        $this->assertSame(300.0, $this->balance());

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame('installment_payment', $bridge->operation);
        $this->assertSame(TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION, $bridge->source_type);
    }

    /**
     * Vínculo método de pago → cuenta bancaria de tesorería.
     *
     * @return array{0: PaymentMethod, 1: TreasuryAccount}
     */
    private function linkBankTransfer(): array
    {
        $bank = Bank::create([
            'short_name' => 'BCP',
            'full_name' => 'Banco de Crédito del Perú',
            'status' => true,
        ]);

        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta corriente PEN',
            'number' => '193-0001',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        $account = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $bankAccount->id,
            'label' => 'BCP PEN',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);

        $method = PaymentMethod::query()->where('description', 'Transferencia BCP')->firstOrFail();
        $method->bank_account_id = $bankAccount->id;
        $method->save();

        return [$method, $account];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function grantPermissions(array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $this->user->givePermissionTo($permissions);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function documentPayload(array $overrides = []): array
    {
        $serie = Serie::create([
            'document_type_id' => 5,
            'description' => 'B001',
            'number' => 1,
            'user_id' => $this->user->id,
            'local_id' => 1,
        ]);

        $docType = SaleDocumentType::query()->first();

        if (! $docType) {
            $id = DB::table('sale_document_types')->insertGetId([
                'sunat_id' => '03',
                'description' => 'Boleta',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $docType = SaleDocumentType::query()->findOrFail($id);
        }

        return array_merge([
            'serie' => $serie->id,
            'date_issue' => now()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
            'sale_documenttype_id' => $docType->id,
            'total' => 118,
            'client_id' => 1,
            'client_dti' => '1',
            'client_number' => '12345678',
            'client_rzn_social' => 'Cliente Prueba',
            'client_direction' => '-',
            'client_ubigeo' => '150101',
            'client_ubigeo_description' => 'LIMA',
            'client_phone' => '999999999',
            'client_email' => 'cliente@example.com',
            'forma_pago' => 'Contado',
            'payments' => [['type' => $this->method->id, 'amount' => 118, 'reference' => 'TRF-1']],
            'items' => [[
                'id' => 999,
                'interne' => 'SRV001',
                'description' => 'Servicio de prueba',
                'unit_type' => 'ZZ',
                'quantity' => 1,
                'unit_price' => 118,
                'total' => 118,
                'afe_igv' => '10',
                'is_product' => false,
                'discount' => 0,
            ]],
        ], $overrides);
    }

    /**
     * @return array{0: Sale, 1: SaleDocument, 2: SaleDocumentQuota}
     */
    private function makeCreditFixture(float $amount, float $balance): array
    {
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => $amount,
            'advancement' => 0,
            'total_discount' => 0,
            'payments' => null,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
        ]);

        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_currency' => 'PEN',
            'forma_pago' => 'Credito',
            'status_pay' => false,
            'overall_total' => $amount,
        ]);

        $quota = SaleDocumentQuota::create([
            'sale_document_id' => $document->id,
            'quota_number' => 1,
            'amount' => $amount,
            'due_date' => now()->addMonth()->toDateString(),
            'balance' => $balance,
            'status' => 'Pendiente',
        ]);

        return [$sale, $document, $quota];
    }

    private function assertOnlyOneProcessedIncomeMovement(string $operation): void
    {
        $movements = TreasurySourceMovement::query()->get();

        $this->assertCount(1, $movements);
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $movements->first()->status);
        $this->assertSame($operation, $movements->first()->operation);
        $this->assertSame($this->account->id, $movements->first()->treasury_account_id);
        $this->assertSame($this->method->id, $movements->first()->payment_method_id);
        $this->assertNotNull($movements->first()->treasury_transaction_id);
    }

    private function balance(): float
    {
        return app(TreasuryLedgerService::class)->accountBalance($this->account->refresh());
    }
}
