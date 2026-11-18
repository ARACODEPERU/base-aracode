<?php

namespace Tests\Feature\Sire;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Services\Sire\SireRvieVentasService;
use Modules\Sales\Services\Sire\SireValidador;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Validador interno del SIRE: detecta los errores que SUNAT rechazaría y
 * bloquea la descarga del TXT/ZIP hasta que se corrijan.
 */
class SireValidadorTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('sire_rvie_ventas', 'web');
        $this->user->givePermissionTo('sire_rvie_ventas');

        DB::table('companies')->insert([
            'ruc' => self::RUC,
            'name' => 'ARACODE',
            'business_name' => 'ARACODE SMART SOLUTIONS SAC',
            'tradename' => 'ARACODE',
            'fiscal_address' => 'Av. Los Álamos 123',
            'mode' => 'demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_un_registro_de_ventas_correcto_no_tiene_errores(): void
    {
        $this->assertSame([], $this->validator()->validar('ventas', $this->venta(), self::RUC));
    }

    public function test_la_factura_exige_un_ruc_de_cliente_valido(): void
    {
        $errores = $this->validator()->validar('ventas', $this->venta([
            10 => '1',
            11 => '44556677',
        ]), self::RUC);

        $this->assertStringContainsString('la factura exige el RUC del cliente', implode(' | ', $errores));

        $errores = $this->validator()->validar('ventas', $this->venta([
            11 => '2012345678',
        ]), self::RUC);

        $this->assertStringContainsString('el RUC del cliente debe tener 11 dígitos', implode(' | ', $errores));
    }

    public function test_la_boleta_menor_a_700_puede_ir_sin_documento(): void
    {
        $linea = $this->venta([
            6 => '03',
            7 => 'B002',
            8 => '34',
            10 => '',
            11 => '',
            14 => '42.37',
            16 => '7.63',
            25 => '50.00',
        ]);

        $this->assertSame([], $this->validator()->validar('ventas', $linea, self::RUC));
    }

    public function test_bloquea_importes_que_no_cuadran(): void
    {
        $errores = $this->validator()->validar('ventas', $this->venta([
            14 => '84.75',
            16 => '15.25',
            25 => '120.00',
        ]), self::RUC);

        $this->assertStringContainsString('los importes no cuadran', implode(' | ', $errores));
    }

    public function test_bloquea_una_nota_sin_comprobante_de_referencia(): void
    {
        $errores = $this->validator()->validar('ventas', $this->venta([
            6 => '07',
            7 => 'FC01',
            28 => '',
            29 => '',
            30 => '',
            31 => '',
        ]), self::RUC);

        $this->assertStringContainsString('comprobante de referencia', implode(' | ', $errores));
    }

    public function test_bloquea_moneda_extranjera_sin_tipo_de_cambio(): void
    {
        $errores = $this->validator()->validar('ventas', $this->venta([
            26 => 'USD',
            27 => '',
        ]), self::RUC);

        $this->assertStringContainsString('tipo de cambio positivo', implode(' | ', $errores));
    }

    public function test_bloquea_un_ruc_de_empresa_invalido_y_lineas_mal_formadas(): void
    {
        $errores = $this->validator()->validar('ventas', '20611376031|ARACODE', self::RUC);

        $this->assertStringContainsString('deben ser 33', implode(' | ', $errores));
        $this->assertStringContainsString('RUC de 11 dígitos', implode(' | ', $this->validator()->validar('ventas', '', '123')));
    }

    public function test_valida_el_registro_de_compras_incluyendo_operaciones_gratuitas(): void
    {
        $this->assertSame([], $this->validator()->validar('compras', $this->compra(), self::RUC));

        $errores = $this->validator()->validar('compras', $this->compra([
            12 => '2055667788', // RUC de 10 dígitos
        ]), self::RUC);

        $this->assertStringContainsString('RUC del proveedor debe tener 11 dígitos', implode(' | ', $errores));

        // Operación gratuita: no gravadas por 100 con total 0.
        $gratuita = $this->compra([
            14 => '0.00',
            15 => '0.00',
            20 => '100.00',
            24 => '0.00',
        ]);

        $this->assertSame([], $this->validator()->validar('compras', $gratuita, self::RUC));
    }

    public function test_el_registro_con_errores_no_se_puede_descargar(): void
    {
        $this->document([
            'invoice_mto_oper_taxed' => 0,
            'invoice_mto_igv' => 0,
            'overall_total' => 100,
        ]);

        $periodo = now()->format('Y-m');

        $data = app(SireRvieVentasService::class)->build($periodo);
        $this->assertFalse($data['canDownload']);
        $this->assertNotEmpty($data['errors']);

        // El guardia del servidor impide la descarga aunque se llame directo.
        $this->actingAs($this->user)
            ->get(route('sire_rvie_ventas_txt', ['period' => $periodo]))
            ->assertRedirect(route('sire_rvie_ventas_index', ['period' => $periodo]))
            ->assertSessionHas('error');

        $this->actingAs($this->user)
            ->get(route('sire_rvie_ventas_zip', ['period' => $periodo]))
            ->assertRedirect(route('sire_rvie_ventas_index', ['period' => $periodo]));

        $this->actingAs($this->user)
            ->get(route('sire_rvie_ventas_index', ['period' => $periodo]))
            ->assertInertia(fn ($page) => $page
                ->component('Sales::Sire/Registro', false)
                ->where('canDownload', false)
                ->has('errors'));
    }

    public function test_al_corregir_los_importes_la_descarga_vuelve_a_funcionar(): void
    {
        $documento = $this->document([
            'invoice_mto_oper_taxed' => 0,
            'invoice_mto_igv' => 0,
            'overall_total' => 100,
        ]);

        $documento->update([
            'invoice_mto_oper_taxed' => 84.75,
            'invoice_mto_igv' => 15.25,
        ]);

        $periodo = now()->format('Y-m');

        $this->assertTrue(app(SireRvieVentasService::class)->build($periodo)['canDownload']);

        $this->actingAs($this->user)
            ->get(route('sire_rvie_ventas_txt', ['period' => $periodo]))
            ->assertOk();
    }

    private function validator(): SireValidador
    {
        return app(SireValidador::class);
    }

    /**
     * Línea válida de 33 campos del Anexo N.° 3; cada índice se puede cambiar.
     *
     * @param  array<int, string>  $cambios
     */
    private function venta(array $cambios = []): string
    {
        $campos = [
            self::RUC, 'ARACODE SMART SOLUTIONS SAC', '202611', '', '18/11/2026', '',
            '01', 'F001', '10', '', '6', '20123456789', 'CLIENTE PRUEBA SAC',
            '0.00', '84.75', '0.00', '15.25', '0.00', '0.00', '0.00',
            '0.00', '0.00', '0.00', '0.00', '0.00', '100.00', 'PEN', '',
            '', '', '', '', '',
        ];

        foreach ($cambios as $indice => $valor) {
            $campos[$indice] = $valor;
        }

        return implode('|', $campos);
    }

    /**
     * Línea válida de 37 campos del Anexo N.° 11.
     *
     * @param  array<int, string>  $cambios
     */
    private function compra(array $cambios = []): string
    {
        $campos = array_fill(0, 37, '');

        $campos[0] = self::RUC;
        $campos[1] = 'ARACODE SMART SOLUTIONS SAC';
        $campos[2] = '202611';
        $campos[4] = '18/11/2026';
        $campos[6] = '01';
        $campos[7] = 'F001';
        $campos[9] = '100';
        $campos[11] = '6';
        $campos[12] = '20556677889';
        $campos[13] = 'PROVEEDOR PRUEBA SAC';
        $campos[14] = '84.75';
        $campos[15] = '15.25';

        // Campos 17 a 24 (índices 16 a 23) deben traer un importe numérico.
        for ($i = 16; $i <= 23; $i++) {
            $campos[$i] = '0.00';
        }

        $campos[24] = '100.00';
        $campos[25] = 'PEN';
        $campos[35] = '0.00';

        foreach ($cambios as $indice => $valor) {
            $campos[$indice] = $valor;
        }

        return implode('|', $campos);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function document(array $attributes): SaleDocument
    {
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 0,
            'advancement' => 0,
            'total_discount' => 0,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
            'status' => 1,
        ]);

        return SaleDocument::create(array_merge([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '1',
            'invoice_type_currency' => 'PEN',
            'invoice_broadcast_date' => now()->toDateString(),
            'client_type_doc' => '6',
            'client_number' => '20123456789',
            'client_rzn_social' => 'CLIENTE PRUEBA SAC',
            'invoice_status' => 'Aceptada',
            'status' => 1,
            'overall_total' => 100,
        ], $attributes));
    }
}
