<?php

namespace Tests\Feature\Sire;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Services\Sire\SireRvieVentasService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Registro de ventas electrónico (RVIE) del SIRE: el TXT debe tener 33 campos
 * por línea, fechas DD/MM/AAAA, notas de crédito en negativo, anulados en cero
 * y el nombre de archivo de la tabla 6.
 */
class SireRvieVentasTest extends TestCase
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

    public function test_genera_el_txt_con_33_campos_y_las_referencias_de_las_notas(): void
    {
        $factura = $this->document([
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '10',
            'invoice_mto_oper_taxed' => 84.75,
            'invoice_mto_igv' => 15.25,
            'overall_total' => 100,
        ]);

        $this->document([
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B002',
            'invoice_correlative' => '34',
            'client_type_doc' => '1',
            'client_number' => '44556677',
            'client_rzn_social' => 'PEREZ GOMEZ ANA',
            'invoice_mto_oper_taxed' => 42.37,
            'invoice_mto_igv' => 7.63,
            'overall_total' => 50,
        ]);

        $this->document([
            'invoice_type_doc' => '07',
            'invoice_serie' => 'FC01',
            'invoice_correlative' => '5',
            'document_id' => $factura->id,
            'invoice_mto_oper_taxed' => 25.42,
            'invoice_mto_igv' => 4.58,
            'overall_total' => 30,
        ]);

        $this->document([
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '11',
            'invoice_status' => 'Anulada',
            'invoice_mto_oper_taxed' => 169.49,
            'invoice_mto_igv' => 30.51,
            'overall_total' => 200,
        ]);

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertCount(4, $data['rows']);
        $this->assertSame(1, $data['totals']['zeroed']);

        $lineas = explode("\r\n", trim($data['content']));

        foreach ($lineas as $linea) {
            $this->assertCount(33, explode('|', $linea));
        }

        $campos = explode('|', $lineas[0]);
        $this->assertSame(self::RUC, $campos[0]);
        $this->assertSame('ARACODE SMART SOLUTIONS SAC', $campos[1]);
        $this->assertSame(now()->format('Ym'), $campos[2]);
        $this->assertSame('', $campos[3]);
        $this->assertSame(now()->format('d/m/Y'), $campos[4]);
        $this->assertSame('01', $campos[6]);
        $this->assertSame('F001', $campos[7]);
        $this->assertSame('10', $campos[8]);
        $this->assertSame('6', $campos[10]);
        $this->assertSame('20123456789', $campos[11]);
        $this->assertSame('84.75', $campos[14]);
        $this->assertSame('15.25', $campos[16]);
        $this->assertSame('0.00', $campos[17]);
        $this->assertSame('100.00', $campos[25]);
        $this->assertSame('PEN', $campos[26]);
        // En soles el tipo de cambio va vacío: el anexo exige valor positivo
        // solo cuando la moneda es distinta de PEN.
        $this->assertSame('', $campos[27]);

        // La nota de crédito va en negativo y referencia la factura original.
        $nota = explode('|', $lineas[2]);
        $this->assertSame('07', $nota[6]);
        $this->assertSame('-25.42', $nota[14]);
        $this->assertSame('-30.00', $nota[25]);
        $this->assertSame(now()->format('d/m/Y'), $nota[28]);
        $this->assertSame('01', $nota[29]);
        $this->assertSame('F001', $nota[30]);
        $this->assertSame('10', $nota[31]);

        // El comprobante anulado se anota con importe cero.
        $anulada = explode('|', $lineas[3]);
        $this->assertSame('0.00', $anulada[14]);
        $this->assertSame('0.00', $anulada[16]);
        $this->assertSame('0.00', $anulada[25]);

        $this->assertSame(84.75 + 42.37 - 25.42, round($data['totals']['gravada'], 2));
        $this->assertSame(100.0 + 50 - 30, round($data['totals']['total'], 2));
    }

    public function test_nota_de_credito_de_un_periodo_anterior_usa_los_campos_de_descuento(): void
    {
        $periodoAnterior = now()->subMonthsNoOverflow(1);

        $factura = $this->document([
            'invoice_serie' => 'F001',
            'invoice_correlative' => '40',
            'invoice_broadcast_date' => $periodoAnterior->toDateString(),
            'created_at' => $periodoAnterior,
            'updated_at' => $periodoAnterior,
            'invoice_mto_oper_taxed' => 84.75,
            'invoice_mto_igv' => 15.25,
            'overall_total' => 100,
        ]);

        $this->document([
            'invoice_type_doc' => '07',
            'invoice_serie' => 'FC01',
            'invoice_correlative' => '6',
            'document_id' => $factura->id,
            'invoice_mto_oper_taxed' => 42.37,
            'invoice_mto_igv' => 7.63,
            'overall_total' => 50,
        ]);

        $data = $this->service()->build(now()->format('Y-m'));
        // La factura pertenece al período anterior: solo se informa la nota.
        $this->assertCount(1, $data['rows']);
        $lineas = explode("\r\n", trim($data['content']));
        $nota = explode('|', $lineas[0]);

        $this->assertSame('07', $nota[6]);
        $this->assertSame('0.00', $nota[14]);  // base gravada
        $this->assertSame('-42.37', $nota[15]); // descuento de base
        $this->assertSame('0.00', $nota[16]);  // IGV
        $this->assertSame('-7.63', $nota[17]); // descuento de IGV
        $this->assertSame('-50.00', $nota[25]);
    }

    public function test_moneda_extranjera_lleva_tipo_de_cambio_a_tres_decimales(): void
    {
        $this->document([
            'invoice_type_currency' => 'USD',
            'exchange_rate' => 3.7549,
            'overall_total' => 100,
        ]);

        $data = $this->service()->build(now()->format('Y-m'));
        $campos = explode('|', explode("\r\n", trim($data['content']))[0]);

        $this->assertSame('USD', $campos[26]);
        $this->assertSame('3.755', $campos[27]);
    }

    public function test_observa_y_bloquea_comprobantes_incompletos(): void
    {
        $this->document([
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B002',
            'invoice_correlative' => '77',
            'client_type_doc' => null,
            'client_number' => null,
            'invoice_mto_oper_taxed' => 847.46,
            'invoice_mto_igv' => 152.54,
            'overall_total' => 1000,
        ]);

        $this->document([
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '78',
            'invoice_type_currency' => 'USD',
            'exchange_rate' => null,
            'overall_total' => 500,
        ]);

        $this->document([
            'invoice_type_doc' => '07',
            'invoice_serie' => 'FC01',
            'invoice_correlative' => '79',
            'document_id' => null,
            'overall_total' => 20,
        ]);

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertCount(3, $data['rows']);
        // `ready` solo indica que la empresa tiene RUC; el archivo con estos
        // errores queda bloqueado para descarga hasta corregirlos.
        $this->assertTrue($data['ready']);
        $this->assertNotEmpty($data['observations']);
        $this->assertNotEmpty($data['errors']);
        $this->assertFalse($data['canDownload']);

        $texto = implode(' | ', $data['observations']);
        $this->assertStringContainsString('B002-77', $texto);
        $this->assertStringContainsString('documento de identidad', $texto);
        $this->assertStringContainsString('moneda extranjera', $texto);
        $this->assertStringContainsString('comprobante de referencia', $texto);
    }

    public function test_nombre_de_archivo_y_periodo_sin_operaciones(): void
    {
        $periodo = '2026-11';
        $data = $this->service()->build($periodo);

        $this->assertFalse($data['hasOperations']);
        $this->assertSame('', $data['content']);
        $this->assertSame(
            'LE' . self::RUC . '202611' . '00' . '140400' . '02' . '1' . '0' . '1' . '2' . '.TXT',
            $data['fileName']
        );
    }

    public function test_descarga_el_txt_del_periodo(): void
    {
        $this->document(['overall_total' => 100]);

        $periodo = now()->format('Y-m');

        $response = $this->actingAs($this->user)->get(route('sire_rvie_ventas_txt', ['period' => $periodo]));

        $response->assertOk();
        $this->assertStringContainsString(
            'LE' . self::RUC . now()->format('Ym') . '0014040002' . '11' . '12' . '.TXT',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('|', (string) $response->getContent());
    }

    public function test_la_pantalla_del_registro_carga(): void
    {
        $this->document(['overall_total' => 100]);

        $this->actingAs($this->user)
            ->get(route('sire_rvie_ventas_index', ['period' => now()->format('Y-m')]))
            ->assertOk()
            // El buscador de vistas de Inertia no resuelve páginas de módulos
            // en pruebas (`Sales::...`), por eso la existencia no se valida.
            ->assertInertia(fn ($page) => $page
                ->component('Sales::Sire/Registro', false)
                ->where('tipo', 'ventas')
                ->where('ready', true)
                ->where('canDownload', true)
                ->where('rowsTotal', 1)
                ->where('previewLimit', 200)
                ->has('rows', 1)
                // La pantalla no recibe el TXT completo; solo la vista previa.
                ->where('content', null));
    }

    public function test_sin_permiso_no_se_puede_descargar(): void
    {
        $otro = User::factory()->create();

        $this->actingAs($otro)
            ->get(route('sire_rvie_ventas_txt'))
            ->assertForbidden();
    }

    private function service(): SireRvieVentasService
    {
        return app(SireRvieVentasService::class);
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
            // Base e IGV cuadran con el total para que el validador no bloquee
            // las descargas de estas pruebas.
            'invoice_mto_oper_taxed' => 84.75,
            'invoice_mto_igv' => 15.25,
            'overall_total' => 100,
        ], $attributes));
    }
}
