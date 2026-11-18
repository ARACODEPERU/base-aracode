<?php

namespace Tests\Feature\Treasury;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryDetractionService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTaxService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;
use Modules\Treasury\Services\TreasuryTransferService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Detracción del IGV: solo facturas desde S/ 700, 12 % por defecto, se
 * registra al cobrar, rebaja el IGV y su cuenta solo se mueve pagando IGV,
 * liberando saldo o con un ajuste crítico de contraseña.
 */
class TreasuryDetractionTest extends TestCase
{
    use RefreshDatabase;

    private TreasuryDetractionService $service;

    private TreasuryAccount $cuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->service = app(TreasuryDetractionService::class);
        $this->cuenta = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_CASH,
            'label' => 'Caja de prueba',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
        ]);
    }

    public function test_solo_las_facturas_desde_700_llevan_detraccion(): void
    {
        $factura = $this->document(['overall_total' => 1000, 'invoice_mto_igv' => 152.54]);
        $boleta = $this->document(['invoice_type_doc' => '03', 'overall_total' => 1000]);
        $pequena = $this->document(['overall_total' => 699.99]);

        $this->assertTrue($this->service->applies($factura));
        $this->assertFalse($this->service->applies($boleta), 'Las boletas no llevan detracción.');
        $this->assertFalse($this->service->applies($pequena), 'Bajo S/ 700 no hay detracción.');

        $split = $this->service->splitForPayment($factura, 1000);

        $this->assertSame(880.0, $split['net']);
        $this->assertSame(120.0, $split['detraction']);
    }

    public function test_el_cobro_con_detraccion_deja_120_en_la_cuenta_y_880_en_caja(): void
    {
        $factura = $this->document(['overall_total' => 1000]);
        $split = $this->service->splitForPayment($factura, 1000);

        $ingreso = app(TreasuryLedgerService::class)->registerIncome(
            $this->cuenta,
            now()->toDateString(),
            $split['net'],
            null,
            null,
            null,
            null,
            'Cobro de factura',
        );

        $this->service->afterPayment($factura, $ingreso->account, now()->toDateString());

        $this->assertSame(880.0, app(TreasuryLedgerService::class)->accountBalance($this->cuenta));
        $this->assertSame(120.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
        $this->assertDatabaseHas('treasury_transactions', [
            'origin_type' => TreasuryDetractionService::ORIGIN_DETRACTION,
            'origin_id' => $factura->id,
            'amount' => 120,
        ]);
    }

    public function test_la_auto_detraccion_se_ejecuta_despues_desde_movimientos(): void
    {
        $factura = $this->document(['overall_total' => 1000, 'detraccion_auto' => true]);

        $split = $this->service->splitForPayment($factura, 1000);

        $this->assertSame(1000.0, $split['net'], 'Con auto-detracción el cliente paga el total.');
        $this->assertTrue($split['auto']);

        $ingreso = app(TreasuryLedgerService::class)->registerIncome(
            $this->cuenta,
            now()->toDateString(),
            1000,
            null,
            null,
            null,
            null,
            'Cobro de factura',
        );

        $this->service->afterPayment($factura, $ingreso->account, now()->toDateString());

        // Al cobrar no se traslada nada: queda pendiente hasta hacerla.
        $this->assertSame(1000.0, app(TreasuryLedgerService::class)->accountBalance($this->cuenta));
        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));

        $this->service->registerAutoDetraction($factura, $this->cuenta, now()->toDateString());

        // La empresa traslada 120: caja queda en 880 y detracción en 120.
        $this->assertSame(880.0, app(TreasuryLedgerService::class)->accountBalance($this->cuenta));
        $this->assertSame(120.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
        $this->assertDatabaseHas('treasury_transactions', [
            'origin_type' => TreasuryDetractionService::ORIGIN_AUTO_MOVE,
            'origin_id' => $factura->id,
            'type' => TreasuryTransaction::TYPE_EXPENSE,
            'amount' => 120,
        ]);
    }

    public function test_el_cobro_avisa_la_detraccion_pendiente_y_la_ejecuta_desde_movimientos(): void
    {
        $user = $this->userWith(['treasury_movimientos_nuevo']);
        $factura = $this->document(['overall_total' => 1500, 'detraccion_auto' => true]);
        $this->service->syncDocument($factura);

        $ingreso = app(TreasuryLedgerService::class)->registerIncome(
            $this->cuenta,
            now()->toDateString(),
            1500,
            null,
            null,
            null,
            'F001-1',
            'Cobro con auto-detracción',
        );

        // Puente de la venta: es lo que permite ver el comprobante en Movimientos.
        TreasurySourceMovement::create([
            'module' => TreasurySourceMovement::MODULE_SALES,
            'source_type' => TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
            'source_id' => $factura->id,
            'operation' => 'full_payment',
            'type' => TreasurySourceMovement::TYPE_INCOME,
            'status' => TreasurySourceMovement::STATUS_PROCESSED,
            'amount' => 1500,
            'currency' => 'PEN',
            'amount_base' => 1500,
            'movement_date' => now()->toDateString(),
            'treasury_account_id' => $this->cuenta->id,
            'treasury_transaction_id' => $ingreso->id,
            'idempotency_key' => 'auto-detraccion-' . $ingreso->id,
        ]);

        $this->service->afterPayment($factura, $ingreso->account, now()->toDateString());

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$ingreso->id])[$ingreso->id];

        $this->assertTrue($detail['detraccion_pendiente']);
        $this->assertSame(180.0, $detail['detraccion']);

        $this->actingAs($user)
            ->post(route('treasury_transactions_auto_detraction', $ingreso->id))
            ->assertSessionHas('success');

        $this->assertSame(1320.0, app(TreasuryLedgerService::class)->accountBalance($this->cuenta));
        $this->assertSame(180.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
        $this->assertTrue((bool) $factura->fresh()->detraccion_auto);

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$ingreso->id])[$ingreso->id];
        $this->assertFalse($detail['detraccion_pendiente']);

        // Repetirla se rechaza: la auto-detracción es una sola vez.
        $this->actingAs($user)
            ->post(route('treasury_transactions_auto_detraction', $ingreso->id))
            ->assertSessionHas('error');
    }

    public function test_la_detraccion_rebaja_el_igv_del_periodo(): void
    {
        $factura = $this->document(['overall_total' => 1000, 'invoice_mto_igv' => 180]);
        $this->service->syncDocument($factura);
        $this->service->registerIncome($factura, now()->toDateString());

        $data = app(TreasuryTaxService::class)->monthSummary(now()->year, now()->month);

        $this->assertSame(180.0, $data['sales_igv']);
        $this->assertSame(120.0, $data['detractions']);
        $this->assertSame(60.0, $data['net_igv'], 'Queda por pagar solo el 6 % restante.');
    }

    public function test_los_abonos_parciales_no_descuentan_detraccion_hasta_completar(): void
    {
        $factura = $this->document(['overall_total' => 1000]);

        $parcial = $this->service->splitForPayment($factura, 500);

        $this->assertSame(500.0, $parcial['net']);
        $this->assertSame(0.0, $parcial['detraction']);
        $this->assertTrue($parcial['partial']);

        // Al cubrir el total, recién se separa la detracción.
        $total = $this->service->splitForPayment($factura, 1000);

        $this->assertSame(880.0, $total['net']);
        $this->assertFalse($total['partial']);
    }

    public function test_la_cuenta_de_detraccion_no_admite_movimientos_manuales_ni_transferencias(): void
    {
        $user = $this->userWith(['treasury_movimientos', 'treasury_movimientos_nuevo', 'treasury_cuentas']);
        $detraccion = $this->service->account();

        $this->actingAs($user)
            ->post(route('treasury_transactions_store'), [
                'treasury_account_id' => $detraccion->id,
                'transaction_date' => now()->toDateString(),
                'type' => TreasuryTransaction::TYPE_INCOME,
                'amount' => 50,
                'description' => 'Intento manual',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('treasury_transactions', [
            'treasury_account_id' => $detraccion->id,
            'description' => 'Intento manual',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(TreasuryTransferService::class)->transfer($detraccion, $this->cuenta, 10, now()->toDateString(), []);
    }

    public function test_el_ajuste_manual_exige_la_contrasena_del_usuario(): void
    {
        $user = $this->userWith(['treasury_detraccion']);
        $user->password = Hash::make('secreto-123');
        $user->save();

        $this->actingAs($user)
            ->post(route('treasury_detraction_adjust'), [
                'password' => 'incorrecta',
                'type' => 'income',
                'amount' => 100,
                'date' => now()->toDateString(),
                'reason' => 'Corrección autorizada por contabilidad',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));

        $this->actingAs($user)
            ->post(route('treasury_detraction_adjust'), [
                'password' => 'secreto-123',
                'type' => 'income',
                'amount' => 100,
                'date' => now()->toDateString(),
                'reason' => 'Corrección autorizada por contabilidad',
            ])
            ->assertSessionHas('success');

        $this->assertSame(100.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
    }

    public function test_liberar_el_saldo_exige_contrasena_y_lo_mueve_a_la_cuenta_elegida(): void
    {
        $user = $this->userWith(['treasury_detraccion']);
        $user->password = Hash::make('secreto-123');
        $user->save();

        $this->service->adjust('income', 500, now()->toDateString(), 'Fondeo de la cuenta de detracción');

        $this->actingAs($user)
            ->post(route('treasury_detraction_release'), [
                'password' => 'otra',
                'treasury_account_id' => $this->cuenta->id,
                'amount' => 200,
                'date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(500.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));

        $this->actingAs($user)
            ->post(route('treasury_detraction_release'), [
                'password' => 'secreto-123',
                'treasury_account_id' => $this->cuenta->id,
                'amount' => 200,
                'date' => now()->toDateString(),
                'reference' => 'Constancia SUNAT 001',
            ])
            ->assertSessionHas('success');

        $this->assertSame(300.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
        $this->assertSame(200.0, app(TreasuryLedgerService::class)->accountBalance($this->cuenta));
    }

    public function test_anular_la_factura_devuelve_la_detraccion_y_la_quita_del_igv(): void
    {
        $factura = $this->document(['overall_total' => 1000, 'invoice_mto_igv' => 180]);
        $this->service->syncDocument($factura);
        $this->service->registerIncome($factura, now()->toDateString());

        $this->assertSame(120.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));

        $factura->update(['status' => 3, 'invoice_status' => 'Anulada']);
        $factura->refresh();

        // Aunque el movimiento siga en la cuenta, el IGV ya no lo descuenta.
        $this->assertSame(0.0, app(TreasuryTaxService::class)->monthSummary(now()->year, now()->month)['detractions']);

        \Modules\Treasury\Services\TreasuryHooks::voidDocumentIncomes(
            $factura,
            'Anulación total del comprobante',
            'Anulación del comprobante',
        );

        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($this->service->account()));
        $this->assertFalse((bool) $factura->fresh()->detraccion);
    }

    public function test_la_cuenta_de_detraccion_no_puede_pagar_mas_igv_que_su_saldo(): void
    {
        $user = $this->userWith(['treasury_impuestos']);

        $this->service->adjust('income', 50, now()->toDateString(), 'Saldo inicial para la prueba');

        $detraccion = $this->service->account();
        $periodo = now()->subMonthNoOverflow()->format('Y-m');

        $this->actingAs($user)
            ->post(route('treasury_taxes_pay'), [
                'treasury_account_id' => $detraccion->id,
                'period' => $periodo,
                'date' => now()->toDateString(),
                'amount' => 80,
            ])
            ->assertSessionHas('error');

        $this->assertSame(50.0, app(TreasuryLedgerService::class)->accountBalance($detraccion));

        // Dentro del saldo, el pago sí se registra.
        $this->actingAs($user)
            ->post(route('treasury_taxes_pay'), [
                'treasury_account_id' => $detraccion->id,
                'period' => $periodo,
                'date' => now()->toDateString(),
                'amount' => 50,
            ])
            ->assertSessionHas('success');

        $this->assertSame(0.0, app(TreasuryLedgerService::class)->accountBalance($detraccion));
    }

    public function test_el_detalle_del_movimiento_muestra_el_total_y_la_detraccion(): void
    {
        $factura = $this->document(['overall_total' => 1500]);
        $this->service->syncDocument($factura);
        $movement = $this->service->registerIncome($factura, now()->toDateString());

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$movement->id])[$movement->id];

        $this->assertSame('Detracción', $detail['tipo']);
        $this->assertSame(1500.0, $detail['total']);
        $this->assertSame(180.0, $detail['detraccion']);
        $this->assertSame(12.0, $detail['detraccion_porcentaje']);
        $this->assertSame('PEN', $detail['moneda']);
        $this->assertFalse($detail['detraccion_pendiente']);
    }

    public function test_el_detalle_de_una_boleta_muestra_su_total_sin_detraccion(): void
    {
        $boleta = $this->document([
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B001',
            'overall_total' => 150,
            'invoice_mto_igv' => 0,
            'detraccion' => false,
            'detraccion_amount' => 0,
        ]);

        $income = app(TreasuryLedgerService::class)->registerIncome(
            $this->cuenta,
            now()->toDateString(),
            150,
            null,
            null,
            null,
            'B001-1',
            'Cobro de boleta',
        );

        TreasurySourceMovement::create([
            'module' => TreasurySourceMovement::MODULE_SALES,
            'source_type' => TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
            'source_id' => $boleta->id,
            'operation' => 'full_payment',
            'type' => TreasurySourceMovement::TYPE_INCOME,
            'status' => TreasurySourceMovement::STATUS_PROCESSED,
            'amount' => 150,
            'currency' => 'PEN',
            'amount_base' => 150,
            'movement_date' => now()->toDateString(),
            'treasury_account_id' => $this->cuenta->id,
            'treasury_transaction_id' => $income->id,
            'idempotency_key' => 'detalle-boleta-' . $income->id,
        ]);

        $detail = app(TreasuryTransactionOriginService::class)->detailsFor([$income->id])[$income->id];

        $this->assertSame(150.0, $detail['total']);
        $this->assertNull($detail['detraccion']);
        $this->assertSame('Boleta', $detail['comprobante_tipo']);
        $this->assertSame('B001-1', trim($detail['comprobante']));
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function document(array $overrides = []): SaleDocument
    {
        $sale = Sale::create([
            'user_id' => auth()->id(),
            'client_id' => 1,
            'local_id' => 1,
            'total' => 1000,
            'advancement' => 0,
            'total_discount' => 0,
            'payments' => null,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
        ]);

        return SaleDocument::create(array_merge([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '1',
            'invoice_broadcast_date' => now()->toDateString(),
            'invoice_type_currency' => 'PEN',
            'invoice_mto_igv' => 180,
            'overall_total' => 1000,
            'invoice_status' => 'Aceptada',
            'status' => 1,
            'forma_pago' => 'Contado',
        ], $overrides));
    }
}
