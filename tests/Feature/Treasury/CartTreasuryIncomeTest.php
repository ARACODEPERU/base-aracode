<?php

namespace Tests\Feature\Treasury;

use App\Http\Controllers\WebPageController;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Services\TreasuryAccountSyncService;
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
     * Ejecuta el método privado del controlador que registra el ingreso.
     */
    private function registrarIngreso(OnliSale $sale): void
    {
        $metodo = new \ReflectionMethod(WebPageController::class, 'registerCartTreasuryIncome');
        $metodo->setAccessible(true);
        $metodo->invoke(app(WebPageController::class), $sale);
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
