<?php

namespace Tests\Feature\Treasury;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Services\PaymentAccountResolver;
use Modules\Treasury\Services\TreasuryAccountSyncService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cuentas Efectivo/Caja y MercadoPago en Tesorería, y medios de pago
 * ofrecidos en factura/boleta.
 */
class TreasuryCashMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_sincronizacion_crea_la_caja_de_efectivo_una_sola_vez(): void
    {
        $sync = app(TreasuryAccountSyncService::class);

        $caja = $sync->ensureCash();
        $sync->ensureCash();

        $this->assertSame(TreasuryAccount::TYPE_CASH, $caja->type);
        $this->assertSame('Efectivo/Caja', $caja->displayName());
        $this->assertSame(1, TreasuryAccount::query()->cash()->count());
    }

    public function test_mercadopago_se_crea_como_cuenta_billetera_sin_numero_de_cuenta(): void
    {
        BilleteraDigital::create(['short_name' => 'MERCADOPAGO', 'full_name' => 'MercadoPago', 'status' => true]);

        $cuenta = app(TreasuryAccountSyncService::class)->ensureMercadoPago();

        $this->assertNotNull($cuenta);
        $this->assertSame(TreasuryAccount::TYPE_WALLET, $cuenta->type);
        $this->assertSame('', $cuenta->companyBilletera->account_number);
        $this->assertSame('MercadoPago', $cuenta->companyBilletera->account_name);
    }

    public function test_efectivo_se_resuelve_a_la_caja(): void
    {
        $sync = app(TreasuryAccountSyncService::class);
        $caja = $sync->ensureCash();
        $efectivo = PaymentMethod::forceCreate(['description' => 'Efectivo', 'number' => null]);

        $resuelta = app(PaymentAccountResolver::class)->resolveForMethodModel($efectivo);

        $this->assertNotNull($resuelta);
        $this->assertSame($caja->id, $resuelta->id);
    }

    public function test_opciones_del_comprobante_solo_muestran_mercadopago_billeteras_bancos_y_efectivo(): void
    {
        $bank = Bank::create(['short_name' => 'BCP', 'full_name' => 'Banco de Crédito del Perú', 'status' => true]);
        $bankAccount = BankAccount::create([
            'bank_id' => $bank->id,
            'description' => 'Cuenta corriente PEN',
            'number' => '193-0001-PEN',
            'currency_type_id' => 'PEN',
            'status' => true,
        ]);

        $yape = BilleteraDigital::create(['short_name' => 'YAPE', 'full_name' => 'Yape', 'status' => true]);
        CompanyBilletera::create([
            'billetera_id' => $yape->id,
            'account_name' => 'ARACODE',
            'account_number' => '999888777',
            'status' => true,
        ]);

        PaymentMethod::forceCreate(['description' => 'Efectivo', 'number' => null]);
        PaymentMethod::forceCreate(['description' => 'Yape', 'number' => null]);
        PaymentMethod::forceCreate(['description' => 'Transferencia BCP', 'number' => null, 'bank_account_id' => $bankAccount->id]);
        PaymentMethod::forceCreate(['description' => 'Tarjeta Visa', 'number' => null]);
        PaymentMethod::forceCreate(['description' => 'Otro medio', 'number' => null]);

        // La vista de comprobantes necesita la empresa con su ubigeo completo.
        // Estos modelos usan claves manuales (incrementing = false).
        // Si el ubigeo ya existe (datos base del sistema) se reutiliza.
        $departamento = \App\Models\Department::firstOrCreate(['id' => '15'], ['name' => 'LIMA']);
        $provincia = \App\Models\Province::firstOrCreate(['id' => '1501'], ['name' => 'LIMA', 'department_id' => $departamento->id]);
        $distrito = \App\Models\District::firstOrCreate(['id' => '150122'], ['name' => 'MIRAFLORES', 'province_id' => $provincia->id]);
        \App\Models\Company::create([
            'ruc' => '20123456789',
            'name' => 'ARACODE',
            'business_name' => 'ARACODE SAC',
            'fiscal_address' => 'Av. Test 123',
            'ubigeo' => $distrito->id,
        ]);

        // La ruta exige el permiso invo_documento_nuevo.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('invo_documento_nuevo', 'web');
        auth()->user()->givePermissionTo('invo_documento_nuevo');

        $response = $this->get(route('saledocuments_create'));

        $response->assertOk();
        $descripciones = collect($response->viewData('page')['props']['payments'] ?? [])
            ->pluck('description')
            ->all();

        $this->assertContains('Efectivo', $descripciones);
        $this->assertContains('Yape', $descripciones);
        $this->assertContains('Transferencia BCP', $descripciones);
        $this->assertNotContains('Tarjeta Visa', $descripciones);
        $this->assertNotContains('Otro medio', $descripciones);
    }
}
