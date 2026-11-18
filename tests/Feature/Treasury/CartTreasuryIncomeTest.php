<?php

namespace Tests\Feature\Treasury;

use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryAccountSyncService;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;
use Tests\TestCase;

/**
 * Ingreso a Tesorería de los carritos aprobados por MercadoPago.
 *
 * El flujo real de pago llama a MercadoPago; aquí se prueba el registro del
 * ingreso directamente sobre una venta ya aprobada, que es lo que hace el
 * controlador tras la aprobación.
 */
class CartTreasuryIncomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * Crea la cuenta billetera de MercadoPago que usa el ingreso del carrito.
     */
    private function cuentaMercadoPago(): TreasuryAccount
    {
        BilleteraDigital::create(['short_name' => 'MERCADOPAGO', 'full_name' => 'MercadoPago', 'status' => true]);

        return app(TreasuryAccountSyncService::class)->ensureMercadoPago();
    }

    private function venta(array $overrides = []): OnliSale
    {
        return OnliSale::create(array_merge([
            'module_name' => 'Onlineshop',
            'clie_full_name' => 'Cliente Prueba',
            'email' => 'cliente@example.com',
            'total' => 120.00,
            'transaction_amount' => 120.00,
            'currency' => 'PEN',
            'response_status' => 'approved',
            'response_date_approved' => now()->format('Y-m-d'),
        ], $overrides));
    }

    /**
     * Registra el ingreso con la misma ruta que usan el carrito web y la
     * suscripción de MercadoPago: TreasuryHooks::recordOnlineSaleIncome().
     */
    private function registrarIngreso(OnliSale $sale): void
    {
        TreasuryHooks::recordOnlineSaleIncome($sale);
    }

    public function test_venta_aprobada_registra_ingreso_en_la_cuenta_de_mercadopago(): void
    {
        $cuenta = $this->cuentaMercadoPago();
        // Igual que el controlador: el id de MercadoPago se asigna y luego se guarda.
        $sale = $this->venta();
        $sale->mercado_payment_id = 987654;
        $sale->save();

        $this->registrarIngreso($sale);

        $movimiento = TreasurySourceMovement::query()
            ->where('source_type', 'onli_sale')
            ->where('source_id', $sale->id)
            ->first();

        $this->assertNotNull($movimiento);
        $this->assertSame(TreasurySourceMovement::TYPE_INCOME, $movimiento->type);
        $this->assertSame(120.0, (float) $movimiento->amount);
        $this->assertSame($cuenta->id, (int) $movimiento->treasury_account_id);
        $this->assertSame('MercadoPago #987654', $movimiento->reference);
    }

    public function test_registrar_dos_veces_la_misma_venta_no_duplica_el_ingreso(): void
    {
        $this->cuentaMercadoPago();
        $sale = $this->venta();

        $this->registrarIngreso($sale);
        $this->registrarIngreso($sale);

        $this->assertSame(
            1,
            TreasurySourceMovement::query()->where('source_type', 'onli_sale')->where('source_id', $sale->id)->count()
        );
    }

    public function test_si_treasury_esta_desactivado_no_registra_ni_falla(): void
    {
        $this->cuentaMercadoPago();
        config(['treasury.auto_income' => false]);
        $sale = $this->venta();

        $this->registrarIngreso($sale);

        $this->assertSame(0, TreasurySourceMovement::query()->where('source_type', 'onli_sale')->count());
        $this->assertNotNull(OnliSale::find($sale->id), 'La venta debe seguir existiendo.');
    }

    public function test_si_no_existe_la_cuenta_de_mercadopago_no_falla(): void
    {
        // Sin billetera MercadoPago creada no hay cuenta destino: se omite el ingreso sin error.
        $sale = $this->venta();

        $this->registrarIngreso($sale);

        $this->assertSame(0, TreasurySourceMovement::query()->where('source_type', 'onli_sale')->count());
        $this->assertNotNull(OnliSale::find($sale->id));
    }

    /**
     * Nota de venta que crea el checkout al finalizar la compra, con el cobro
     * de MercadoPago ya incluido en su JSON de pagos.
     */
    private function notaDeVenta(OnliSale $sale): Sale
    {
        return Sale::create([
            'sale_date' => now()->toDateString(),
            'user_id' => auth()->id(),
            'client_id' => 1,
            'local_id' => 1,
            'total' => $sale->total,
            'advancement' => $sale->total,
            'total_discount' => 0,
            'payments' => [['type' => 6, 'amount' => (float) $sale->total, 'reference' => $sale->mercado_payment_id]],
            'status' => true,
            'physical' => 1,
            'invoice_type' => 2,
        ]);
    }

    public function test_el_movimiento_del_carrito_muestra_comprobante_cliente_y_medio_de_pago(): void
    {
        $this->cuentaMercadoPago();

        $sale = $this->venta();
        $sale->mercado_payment_id = 987654;
        $sale->response_payment_method_id = 'visa';
        $sale->save();

        $this->registrarIngreso($sale);

        $nota = $this->notaDeVenta($sale);
        $sale->nota_sale_id = $nota->id;
        $sale->save();

        // La boleta se emite después del cobro: el detalle debe encontrarla
        // siguiendo la nota de venta enlazada.
        SaleDocument::create([
            'sale_id' => $nota->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_serie' => 'B001',
            'invoice_correlative' => '1',
            'invoice_type_doc' => '03',
            'invoice_type_currency' => 'PEN',
            'client_rzn_social' => 'Cliente Prueba',
            'overall_total' => 120,
            'forma_pago' => 'Contado',
        ]);

        $transaction = TreasuryTransaction::query()
            ->whereIn('id', TreasurySourceMovement::query()
                ->where('source_type', 'onli_sale')
                ->where('source_id', $sale->id)
                ->pluck('treasury_transaction_id'))
            ->firstOrFail();

        $detail = app(TreasuryTransactionOriginService::class)
            ->detailsFor([$transaction->id])[$transaction->id] ?? null;

        $this->assertNotNull($detail, 'El movimiento del carrito debe tener detalle de origen.');
        $this->assertSame('Venta online (carrito)', $detail['tipo']);
        $this->assertSame('Boleta', $detail['comprobante_tipo']);
        $this->assertSame('B001-1', $detail['comprobante']);
        $this->assertSame('Cliente Prueba', $detail['cliente']);
        $this->assertSame('MercadoPago (VISA)', $detail['metodo']);
        $this->assertSame(120.0, (float) $detail['total']);
        $this->assertSame('MercadoPago #987654', $detail['referencia']);
        $this->assertNotEmpty($detail['meta']);
    }

    public function test_emitir_el_comprobante_del_carrito_no_duplica_el_ingreso(): void
    {
        $cuenta = $this->cuentaMercadoPago();

        $sale = $this->venta();
        $this->registrarIngreso($sale);

        $nota = $this->notaDeVenta($sale);
        $sale->nota_sale_id = $nota->id;
        $sale->save();

        // Lo que hace SaleDocumentController al emitir la boleta o factura.
        $this->assertSame(0, TreasuryHooks::recordSaleIncomes($nota));

        $this->assertSame(
            120.0,
            app(TreasuryLedgerService::class)->accountBalance($cuenta),
            'El saldo de la cuenta debe tener un solo cobro de 120.'
        );
        $this->assertSame(1, TreasuryTransaction::query()->whereNull('voided_at')->count());
    }

    public function test_billetera_con_enlace_de_pago_se_guarda_como_flag_booleano(): void
    {
        // El flag de la billetera indica si ofrece enlace de pago; el enlace va en la negociación.
        $billetera = BilleteraDigital::create(['short_name' => 'YAPE', 'full_name' => 'Yape', 'status' => true]);

        $companyBilletera = CompanyBilletera::create([
            'billetera_id' => $billetera->id,
            'account_number' => '999888777',
            'account_name' => 'Empresa',
            'has_payment_link' => true,
        ]);

        $this->assertTrue((bool) $companyBilletera->fresh()->has_payment_link);
    }
}
