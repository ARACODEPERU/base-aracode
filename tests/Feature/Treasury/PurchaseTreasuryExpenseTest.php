<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\SaleDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Purchases\Entities\PurcDocument;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\PurchaseTreasuryHooks;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;
use Tests\TestCase;

/**
 * Integración Compras → Tesorería.
 *
 * Una compra pagada saca dinero de la cuenta del medio de pago utilizado; el
 * movimiento debe mostrar proveedor, comprobante, concepto y cuenta, y al
 * anular la compra el egreso se revierte.
 */
class PurchaseTreasuryExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PaymentMethod $transferencia;

    private PaymentMethod $efectivo;

    private PaymentMethod $sinCuenta;

    private TreasuryAccount $cuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->transferencia = PaymentMethod::query()->where('description', 'Transferencia BCP')->firstOrFail();
        $this->efectivo = PaymentMethod::query()->where('description', 'Efectivo')->firstOrFail();
        $this->sinCuenta = PaymentMethod::query()->where('description', 'Transferencia BBVA')->firstOrFail();

        $banco = Bank::create([
            'short_name' => 'BCP',
            'full_name' => 'Banco de Crédito del Perú',
            'status' => true,
        ]);

        $cuentaBancaria = BankAccount::create([
            'bank_id' => $banco->id,
            'description' => 'Cuenta corriente PEN',
            'number' => '193-0001-PEN',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        $this->cuenta = TreasuryAccount::create([
            'type' => TreasuryAccount::TYPE_BANK,
            'bank_account_id' => $cuentaBancaria->id,
            'label' => 'BCP PEN',
            'currency_type_id' => 'PEN',
            'opening_balance' => 500,
            'status' => true,
        ]);

        // Solo la transferencia BCP está vinculada a una cuenta de tesorería.
        PaymentMethod::query()->whereKey($this->transferencia->id)->update(['bank_account_id' => $cuentaBancaria->id]);
    }

    private function proveedor(): Person
    {
        return Person::create([
            'document_type_id' => '6',
            'full_name' => 'Distribuidora Andina SAC',
            'number' => (string) random_int(10000000000, 99999999999),
            'is_provider' => true,
        ]);
    }

    private function compra(array $overrides = []): PurcDocument
    {
        $tipo = SaleDocumentType::query()->first()
            ?? SaleDocumentType::create(['description' => 'Factura', 'sunat_id' => '01']);

        return PurcDocument::create(array_merge([
            'user_id' => $this->user->id,
            'provider_id' => $this->proveedor()->id,
            'serie' => 'F001',
            'number' => '123',
            'document_type_id' => $tipo->id,
            'date_of_issue' => now()->toDateString(),
            'date_of_due' => now()->toDateString(),
            'currency_type_id' => 'PEN',
            'descripction' => 'Compra de mercadería para tienda',
            'total' => 300,
            'status' => 'R',
        ], $overrides));
    }

    private function saldo(): float
    {
        return app(TreasuryLedgerService::class)->accountBalance($this->cuenta->refresh());
    }

    public function test_una_compra_pagada_por_transferencia_registra_el_egreso_en_la_cuenta(): void
    {
        $compra = $this->compra();

        $this->assertSame(1, PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->transferencia->id, 'amount' => 300],
        ]));

        $bridge = TreasurySourceMovement::query()->firstOrFail();
        $this->assertSame(TreasurySourceMovement::MODULE_PURCHASES, $bridge->module);
        $this->assertSame(TreasurySourceMovement::SOURCE_PURCHASE_DOCUMENT, $bridge->source_type);
        $this->assertSame(TreasurySourceMovement::TYPE_EXPENSE, $bridge->type);
        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame($this->cuenta->id, (int) $bridge->treasury_account_id);

        $transaction = TreasuryTransaction::query()->firstOrFail();
        $this->assertSame(TreasuryTransaction::TYPE_EXPENSE, $transaction->type);
        $this->assertSame(200.0, $this->saldo(), 'El saldo debe bajar por la compra pagada.');
    }

    public function test_cada_medio_de_pago_de_la_compra_es_un_egreso(): void
    {
        $compra = $this->compra(['total' => 450]);

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->transferencia->id, 'amount' => 300],
            ['type' => $this->efectivo->id, 'amount' => 150],
        ]);

        $this->assertSame(2, TreasurySourceMovement::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()->processed()->count());
        $this->assertSame(1, TreasurySourceMovement::query()
            ->where('status', TreasurySourceMovement::STATUS_IGNORED)
            ->count());
        $this->assertSame(200.0, $this->saldo());
    }

    public function test_el_movimiento_de_compra_muestra_proveedor_comprobante_y_concepto(): void
    {
        $compra = $this->compra();

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->transferencia->id, 'amount' => 300, 'reference' => 'OP-778'],
        ]);

        $transaction = TreasuryTransaction::query()->firstOrFail();

        $detail = app(TreasuryTransactionOriginService::class)
            ->detailsFor([$transaction->id])[$transaction->id] ?? null;

        $this->assertNotNull($detail, 'El egreso de la compra debe tener detalle de origen.');
        $this->assertSame('Pago de compra', $detail['tipo']);
        $this->assertSame('F001-123', $detail['comprobante']);
        $this->assertSame('Distribuidora Andina SAC', $detail['cliente']);
        $this->assertSame('Compra de mercadería para tienda', $detail['concepto']);
        $this->assertSame('Transferencia BCP', $detail['metodo']);
        $this->assertSame('OP-778', $detail['referencia']);
        $this->assertSame(300.0, (float) $detail['total']);
        $this->assertNotEmpty($detail['meta']);
        $this->assertSame(route('purc_documents_list'), $detail['url']);
    }

    public function test_registrar_dos_veces_la_misma_compra_no_duplica_el_egreso(): void
    {
        $compra = $this->compra();
        $pagos = [['type' => $this->transferencia->id, 'amount' => 300]];

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, $pagos);
        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, $pagos);

        $this->assertSame(1, TreasurySourceMovement::query()->count());
        $this->assertSame(1, TreasuryTransaction::query()->count());
        $this->assertSame(200.0, $this->saldo());
    }

    public function test_una_compra_en_efectivo_no_entra_al_libro_bancario(): void
    {
        $compra = $this->compra();

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->efectivo->id, 'amount' => 300],
        ]);

        $this->assertSame(0, TreasuryTransaction::query()->count());
        $this->assertSame(1, TreasurySourceMovement::query()
            ->where('status', TreasurySourceMovement::STATUS_IGNORED)
            ->count());
        $this->assertSame(500.0, $this->saldo());
    }

    public function test_una_compra_con_metodo_sin_cuenta_queda_ignorada(): void
    {
        $compra = $this->compra();

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->sinCuenta->id, 'amount' => 300],
        ]);

        $this->assertSame(0, TreasuryTransaction::query()->count());
        $this->assertSame(TreasurySourceMovement::STATUS_IGNORED, TreasurySourceMovement::query()->firstOrFail()->status);
    }

    public function test_anular_la_compra_revierte_el_egreso_y_devuelve_el_saldo(): void
    {
        $compra = $this->compra();

        PurchaseTreasuryHooks::recordPurchaseExpenses($compra, [
            ['type' => $this->transferencia->id, 'amount' => 300],
        ]);

        PurchaseTreasuryHooks::voidPurchaseExpenses($compra, 'Compra anulada');

        $bridge = TreasurySourceMovement::query()->whereNull('reversal_of_id')->firstOrFail();
        $this->assertSame(TreasurySourceMovement::STATUS_REVERSED, $bridge->status);

        $reversal = TreasurySourceMovement::query()->whereNotNull('reversal_of_id')->firstOrFail();
        $this->assertSame(TreasurySourceMovement::TYPE_INCOME, $reversal->type);
        $this->assertSame($bridge->id, (int) $reversal->reversal_of_id);

        $this->assertNotNull(TreasuryTransaction::query()->firstOrFail()->voided_at);
        $this->assertSame(500.0, $this->saldo());
    }

    /**
     * El alta desde la ruta real del módulo Compras debe dejar el egreso y, al
     * anular el documento, revertirlo.
     */
    public function test_el_alta_y_anulacion_desde_compras_mueven_la_tesoreria(): void
    {
        $proveedor = $this->proveedor();
        $tipo = SaleDocumentType::query()->first()
            ?? SaleDocumentType::create(['description' => 'Factura', 'sunat_id' => '01']);

        $respuesta = $this->post(route('purc_documents_store'), [
            'serie' => 'F001',
            'number' => '9001',
            'date_issue' => now()->toDateString(),
            'date_end' => now()->toDateString(),
            'sale_documenttype_id' => $tipo->id,
            'supplier_id' => $proveedor->id,
            'descripction' => 'Compra desde el formulario',
            'total' => 300,
            'payments' => [['type' => $this->transferencia->id, 'amount' => 300]],
            'items' => [[
                'id' => null,
                'interne' => null,
                'description' => 'Caja de guantes',
                'is_product' => false,
                'unit_type' => 'ZZ',
                'quantity' => 1,
                'unit_price' => 300,
                'discount' => 0,
                'afe_igv' => '10',
                'icbper' => 0,
                'total' => 300,
                'size' => null,
            ]],
        ]);

        $respuesta->assertOk();

        $compra = PurcDocument::query()->latest('id')->firstOrFail();
        $bridge = TreasurySourceMovement::query()->firstOrFail();

        $this->assertSame(TreasurySourceMovement::STATUS_PROCESSED, $bridge->status);
        $this->assertSame(TreasurySourceMovement::TYPE_EXPENSE, $bridge->type);
        $this->assertSame((int) $compra->id, (int) $bridge->source_id);
        $this->assertSame(200.0, $this->saldo());

        $this->get(route('purc_documents_anular', $compra->id))->assertOk();

        $this->assertSame('A', PurcDocument::find($compra->id)->status);
        $this->assertSame(TreasurySourceMovement::STATUS_REVERSED, $bridge->refresh()->status);
        $this->assertSame(500.0, $this->saldo());
    }
}
