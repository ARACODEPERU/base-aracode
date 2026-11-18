<?php

namespace Tests\Feature\Treasury;

use App\Http\Controllers\ApisnetPeController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryBudget;
use Modules\Treasury\Entities\TreasuryCashCount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasuryDocument;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryAlertService;
use Modules\Treasury\Services\TreasuryBudgetService;
use Modules\Treasury\Services\TreasuryBulkPaymentService;
use Modules\Treasury\Services\TreasuryCashCountService;
use Modules\Treasury\Services\TreasuryDocumentService;
use Modules\Treasury\Services\TreasuryForecastService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryPaymentOrderService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Operación diaria: arqueo de caja, cartera de cheques y letras, pago masivo,
 * presupuesto por centro de costo y alertas automáticas.
 */
class TreasuryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWith([
            'treasury_dashboard',
            'treasury_efectivo',
            'treasury_efectivo_arqueo',
            'treasury_documentos',
            'treasury_documentos_gestionar',
            'treasury_pagos',
            'treasury_pagos_nuevo',
            'treasury_pagos_masivos',
            'treasury_reportes',
            'treasury_presupuesto',
            'treasury_prevision',
        ]);

        $this->actingAs($this->user);
    }

    public function test_el_arqueo_cuenta_denominaciones_y_cierra_la_caja(): void
    {
        $cashId = $this->makePettyCash(100);

        $count = app(TreasuryCashCountService::class)->record(
            $cashId,
            ['100' => 1, '20' => 2, '1' => 1],
            'Sobrante por una venta que no se registró',
            true,
            $this->user->id
        );

        $this->assertSame(141.0, (float) $count->counted_amount);
        $this->assertSame(100.0, (float) $count->expected_amount);
        $this->assertSame(41.0, (float) $count->difference);
        $this->assertTrue((bool) $count->closed_session);

        $session = DB::table('petty_cashes')->where('id', $cashId)->first();

        $this->assertSame('0', (string) $session->state, 'La caja queda cerrada.');
        $this->assertSame(141.0, (float) $session->final_balance, 'El saldo final es el dinero contado.');
        $this->assertNotNull($session->date_closed);
    }

    public function test_el_arqueo_exige_nota_cuando_hay_diferencia(): void
    {
        $cashId = $this->makePettyCash(0);

        $this->expectException(TreasuryOperationException::class);

        app(TreasuryCashCountService::class)->record(
            $cashId,
            ['10' => 1],
            null,
            false,
            $this->user->id
        );
    }

    public function test_registra_el_arqueo_por_http(): void
    {
        $cashId = $this->makePettyCash(50);

        $this->post(route('treasury_cash_count'), [
            'petty_cash_id' => $cashId,
            'denominations' => ['50' => 1],
            'notes' => '',
            'close_session' => true,
        ])->assertRedirect();

        $this->assertSame(1, TreasuryCashCount::query()->count());
        $this->assertSame('0', (string) DB::table('petty_cashes')->where('id', $cashId)->value('state'));
    }

    public function test_los_documentos_cumplen_su_ciclo_y_alimentan_la_prevision(): void
    {
        $service = app(TreasuryDocumentService::class);

        $check = $service->register([
            'type' => 'cheque',
            'direction' => 'receivable',
            'number' => 'CH-001',
            'issuer' => 'Cliente ABC',
            'amount' => 500,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
        ], $this->user->id);

        $this->assertSame(TreasuryDocument::STATUS_PORTFOLIO, $check->status);

        $collected = $service->changeStatus($check, TreasuryDocument::STATUS_COLLECTED, $this->user->id, 'Depositado y cobrado');

        $this->assertSame(TreasuryDocument::STATUS_COLLECTED, $collected->status);

        // Un documento cobrado ya no vuelve a cartera.
        try {
            $service->changeStatus($collected, TreasuryDocument::STATUS_DEPOSITED, $this->user->id);
            $this->fail('Se esperaba una transición inválida.');
        } catch (TreasuryOperationException $e) {
            $this->assertStringContainsString('No se puede pasar', $e->getMessage());
        }

        $service->register([
            'type' => 'letra',
            'direction' => 'payable',
            'number' => 'LT-009',
            'issuer' => 'Proveedor XYZ',
            'amount' => 300,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
        ], $this->user->id);

        $projection = app(TreasuryForecastService::class)->build('PEN', 30);

        $this->assertSame(300.0, (float) $projection['totals']['outflows'], 'La letra por pagar entra como egreso previsto.');
    }

    public function test_el_documento_guarda_el_dni_o_ruc_del_emisor(): void
    {
        $document = app(TreasuryDocumentService::class)->register([
            'type' => 'cheque',
            'direction' => 'receivable',
            'number' => 'CH-777',
            'issuer' => 'EMPRESA EMISORA SAC',
            'issuer_doc_type' => '6',
            'issuer_doc_number' => '20-12345678-9',
            'amount' => 250,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ], $this->user->id);

        $this->assertSame('6', $document->issuer_doc_type);
        $this->assertSame('20123456789', $document->issuer_doc_number, 'El número se guarda sin guiones.');
    }

    public function test_consulta_el_dni_del_emisor_en_reniec(): void
    {
        $this->mock(ApisnetPeController::class, function ($mock) {
            $mock->shouldReceive('consultaDNI')->once()->with('12345678')->andReturn([
                'success' => true,
                'person' => [
                    'razon_social' => 'JUAN PEREZ GONZALES',
                    'document_number' => '12345678',
                ],
            ]);
        });

        $this->postJson(route('treasury_documents_consult_issuer'), [
            'issuer_doc_type' => '1',
            'issuer_doc_number' => '12345678',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'reniec')
            ->assertJsonPath('issuer.name', 'JUAN PEREZ GONZALES');
    }

    public function test_consulta_el_ruc_del_emisor_en_sunat_y_valida_la_longitud(): void
    {
        $this->mock(ApisnetPeController::class, function ($mock) {
            $mock->shouldReceive('consultaRUC')->once()->with('20123456789')->andReturn([
                'success' => true,
                'person' => [
                    'razon_social' => 'EMPRESA EMISORA SAC',
                    'numero_documento' => '20123456789',
                    'direccion' => 'AV. SIEMPRE VIVA 123',
                ],
            ]);
        });

        $this->postJson(route('treasury_documents_consult_issuer'), [
            'issuer_doc_type' => '6',
            'issuer_doc_number' => '20123456789',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'sunat')
            ->assertJsonPath('issuer.name', 'EMPRESA EMISORA SAC');

        $this->postJson(route('treasury_documents_consult_issuer'), [
            'issuer_doc_type' => '6',
            'issuer_doc_number' => '123',
        ])->assertStatus(422);
    }

    public function test_usa_el_respaldo_de_migo_cuando_decolecta_no_devuelve_datos(): void
    {
        $this->mock(ApisnetPeController::class, function ($mock) {
            $mock->shouldReceive('consultaDNI')->once()->with('87654321')->andReturn([
                'success' => false,
                'error' => 'Sin datos',
            ]);
            $mock->shouldReceive('consultaDNImigo')->once()->with('87654321')->andReturn([
                'success' => true,
                'person' => [
                    'razon_social' => 'MARIA LOPEZ DIAZ',
                    'document_number' => '87654321',
                ],
            ]);
        });

        $this->postJson(route('treasury_documents_consult_issuer'), [
            'issuer_doc_type' => '1',
            'issuer_doc_number' => '87654321',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('issuer.name', 'MARIA LOPEZ DIAZ');
    }

    public function test_explica_en_español_cuando_el_documento_no_existe(): void
    {
        $this->mock(ApisnetPeController::class, function ($mock) {
            $mock->shouldReceive('consultaDNI')->once()->with('00000000')->andReturn([
                'success' => false,
                'error' => 'not found',
            ]);
            $mock->shouldReceive('consultaDNImigo')->once()->with('00000000')->andReturn([
                'success' => false,
                'error' => 'Sin suscripción',
            ]);
        });

        $this->postJson(route('treasury_documents_consult_issuer'), [
            'issuer_doc_type' => '1',
            'issuer_doc_number' => '00000000',
        ])->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'No se encontraron datos para el documento consultado.');
    }

    public function test_el_presupuesto_calcula_la_ejecucion_real_y_no_se_duplica(): void
    {
        $center = TreasuryCostCenter::create(['code' => 'ADM', 'name' => 'Administración', 'status' => true]);
        $category = TreasuryCategory::create(['name' => 'Servicios', 'applies_to' => 'expense', 'color' => '#0ea5e9']);
        $account = $this->makeAccount(TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryLedgerService::class)->registerExpense(
            $account,
            now()->toDateString(),
            400,
            $category->id,
            null,
            null,
            null,
            'Servicio del mes',
            null,
            'manual',
            $this->user->id,
            true,
            null,
            'PEN',
            null,
            $center->id
        );

        $service = app(TreasuryBudgetService::class);
        $budget = $service->save([
            'month' => now()->format('Y-m'),
            'cost_center_id' => $center->id,
            'treasury_category_id' => null,
            'currency' => 'PEN',
            'amount' => 1000,
        ], $this->user->id);

        $data = $service->build(now()->format('Y-m'));
        $row = $data['rows'][0];

        $this->assertSame(400.0, (float) $row['executed']);
        $this->assertSame(40.0, (float) $row['usage']);
        $this->assertSame('ok', $row['status']);

        // Misma clave: actualiza el monto en lugar de crear otro presupuesto.
        $service->save([
            'month' => now()->format('Y-m'),
            'cost_center_id' => $center->id,
            'treasury_category_id' => null,
            'currency' => 'PEN',
            'amount' => 300,
        ], $this->user->id);

        $this->assertSame(1, TreasuryBudget::query()->count());
        $this->assertSame(300.0, (float) $budget->fresh()->amount);

        $this->assertSame('over', $service->build(now()->format('Y-m'))['rows'][0]['status']);
    }

    public function test_genera_el_archivo_del_lote_y_marca_las_ordenes(): void
    {
        $account = $this->makeAccount(TreasuryAccount::TYPE_CASH, 0);
        $orders = app(TreasuryPaymentOrderService::class);

        $first = $orders->create([
            'type' => 'supplier_payment',
            'description' => 'Compra de insumos',
            'amount' => 100,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'treasury_account_id' => $account->id,
        ], $this->user->id);

        $second = $orders->create([
            'type' => 'service',
            'description' => 'Mantenimiento',
            'amount' => 200,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'treasury_account_id' => $account->id,
        ], $this->user->id);

        $file = app(TreasuryBulkPaymentService::class)->generate(
            [$first->id, $second->id],
            'csv',
            now()->toDateString(),
            $this->user->id
        );

        $this->assertSame(2, $file['count']);
        $this->assertSame(300.0, (float) $file['total']);
        $this->assertStringContainsString($first->code, $file['content']);
        $this->assertStringContainsString($second->code, $file['content']);
        $this->assertStringContainsString('300.00', $file['content']);
        $this->assertNotEmpty($file['batch']);

        $this->assertSame($file['batch'], $first->fresh()->bulk_batch);
        $this->assertSame($file['batch'], $second->fresh()->bulk_batch);
        $this->assertNotNull($first->fresh()->bulk_exported_at);
    }

    public function test_el_archivo_del_lote_se_descarga_por_http(): void
    {
        $account = $this->makeAccount(TreasuryAccount::TYPE_CASH, 0);

        $order = app(TreasuryPaymentOrderService::class)->create([
            'type' => 'supplier_payment',
            'description' => 'Compra de insumos',
            'amount' => 150,
            'currency' => 'PEN',
            'issue_date' => now()->toDateString(),
            'treasury_account_id' => $account->id,
        ], $this->user->id);

        $response = $this->post(route('treasury_payments_bulk_file'), [
            'orders' => [$order->id],
            'format' => 'csv',
            'date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $this->assertSame('1', $response->headers->get('X-Treasury-Count'));
        $this->assertSame('150.00', $response->headers->get('X-Treasury-Total'));
        $response->assertDownload();
    }

    public function test_las_alertas_detectan_ordenes_vencidas_y_presupuesto_excedido(): void
    {
        $account = $this->makeAccount(TreasuryAccount::TYPE_CASH, 0);

        app(TreasuryPaymentOrderService::class)->create([
            'type' => 'supplier_payment',
            'description' => 'Factura vencida',
            'amount' => 100,
            'currency' => 'PEN',
            'issue_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'treasury_account_id' => $account->id,
        ], $this->user->id);

        $center = TreasuryCostCenter::create(['code' => 'OPE', 'name' => 'Operaciones', 'status' => true]);
        $category = TreasuryCategory::create(['name' => 'Insumos', 'applies_to' => 'expense']);

        app(TreasuryLedgerService::class)->registerExpense(
            $account,
            now()->toDateString(),
            150,
            $category->id,
            null,
            null,
            null,
            'Compra del mes',
            null,
            'manual',
            $this->user->id,
            true,
            null,
            'PEN',
            null,
            $center->id
        );

        $budget = app(TreasuryBudgetService::class)->save([
            'month' => now()->format('Y-m'),
            'cost_center_id' => $center->id,
            'currency' => 'PEN',
            'amount' => 100,
        ], $this->user->id);

        $codes = collect(app(TreasuryAlertService::class)->build())->pluck('code')->all();

        $this->assertContains('overdue_orders', $codes);
        $this->assertContains('budget_over_' . $budget->id, $codes);
    }

    private function makePettyCash(float $beginning): int
    {
        return DB::table('petty_cashes')->insertGetId([
            'user_id' => $this->user->id,
            'date_opening' => now()->toDateString(),
            'time_opening' => '08:00:00',
            'beginning_balance' => $beginning,
            'state' => '1',
            'seller_name' => 'Cajero de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAccount(string $type, float $opening): TreasuryAccount
    {
        return TreasuryAccount::create([
            'type' => $type,
            'label' => 'Cuenta ' . $type . ' ' . uniqid(),
            'currency_type_id' => 'PEN',
            'opening_balance' => $opening,
            'status' => true,
        ]);
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
