<?php

namespace Tests\Feature\Sire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Purchases\Entities\PurcDocument;
use Modules\Purchases\Entities\PurcDocumentItems;
use Modules\Sales\Services\Sire\SireRceComprasService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Registro de compras electrónico (RCE) del SIRE: 37 campos por línea, importes
 * en el orden del Anexo N.° 11, anulados excluidos y nombre de archivo del libro
 * de compras.
 */
class SireRceComprasTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20611376031';

    private const RUC_PROVEEDOR = '20556677889';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('sire_rce_compras', 'web');
        $this->user->givePermissionTo('sire_rce_compras');

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

        DB::table('people')->insert([
            'number' => self::RUC_PROVEEDOR,
            'full_name' => 'PROVEEDOR PRUEBA SAC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_genera_el_txt_con_37_campos_y_los_importes_del_anexo_11(): void
    {
        $this->purchase();

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertCount(1, $data['rows']);
        $this->assertSame(1, $data['totals']['count']);

        $campos = explode('|', explode("\r\n", trim($data['content']))[0]);
        $this->assertCount(37, $campos);

        $this->assertSame(self::RUC, $campos[0]);
        $this->assertSame('ARACODE SMART SOLUTIONS SAC', $campos[1]);
        $this->assertSame(now()->format('Ym'), $campos[2]);
        $this->assertSame('', $campos[3]);
        $this->assertSame(now()->format('d/m/Y'), $campos[4]);
        $this->assertSame('01', $campos[6]);
        $this->assertSame('F001', $campos[7]);
        $this->assertSame('100', $campos[9]);
        $this->assertSame('6', $campos[11]);
        $this->assertSame(self::RUC_PROVEEDOR, $campos[12]);
        $this->assertSame('PROVEEDOR PRUEBA SAC', $campos[13]);
        $this->assertSame('84.75', $campos[14]);
        $this->assertSame('15.25', $campos[15]);
        $this->assertSame('0.00', $campos[16]);
        $this->assertSame('0.00', $campos[20]);
        $this->assertSame('100.00', $campos[24]);
        $this->assertSame('PEN', $campos[25]);
        $this->assertSame('', $campos[26]);
        $this->assertSame('0.00', $campos[35]);
    }

    public function test_nota_de_credito_de_compra_va_en_negativo_y_referencia_la_factura(): void
    {
        $factura = $this->purchase(['number' => '200']);

        $this->purchase([
            'document_type_id' => $this->documentType('07'),
            'serie' => 'FC01',
            'number' => '1',
            'document_id' => $factura->id,
            'total_taxed' => 42.37,
            'total_igv' => 7.63,
            'total' => 50,
        ]);

        $data = $this->service()->build(now()->format('Y-m'));
        $lineas = explode("\r\n", trim($data['content']));
        $nota = explode('|', $lineas[1]);

        $this->assertSame('07', $nota[6]);
        $this->assertSame('-42.37', $nota[14]);
        $this->assertSame('-7.63', $nota[15]);
        $this->assertSame('-50.00', $nota[24]);
        $this->assertSame(now()->format('d/m/Y'), $nota[27]);
        $this->assertSame('01', $nota[28]);
        $this->assertSame('F001', $nota[29]);
        $this->assertSame('200', $nota[31]);
    }

    public function test_la_anulacion_de_la_compra_la_excluye_del_rce(): void
    {
        $this->purchase();
        $anulada = $this->purchase(['number' => '300']);

        $this->actingAs($this->user)
            ->get(route('purc_documents_anular', $anulada->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        // La pantalla de compras graba 'A' en `status`: el RCE debe mirar ese
        // mismo estado y no una columna aparte.
        $this->assertSame('A', $anulada->refresh()->status);

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertSame(1, $data['rowsTotal']);
        $this->assertSame(1, $data['totals']['count']);
        $this->assertStringNotContainsString('300', $data['content']);
    }

    public function test_la_anulacion_con_detalle_excluye_el_comprobante_del_rce(): void
    {
        $compra = $this->purchase(['number' => '400']);
        $producto = $this->product();

        PurcDocumentItems::create([
            'purchase_id' => $compra->id,
            'product_id' => $producto,
            'date_of_due' => now()->toDateString(),
            'affectation_igv_type_id' => '10',
            'price_type_id' => '01',
            'quantity' => 2,
            'unit_value' => 42.37,
            'total_base_igv' => 84.75,
            'percentage_igv' => 18,
            'total_igv' => 15.25,
            'total_base_isc' => 0,
            'percentage_isc' => 0,
            'total_isc' => 0,
            'total_base_other_taxes' => 0,
            'percentage_other_taxes' => 0,
            'total_other_taxes' => 0,
            'total_taxes' => 15.25,
            'unit_price' => 50,
            'total_value' => 84.75,
            'total_charge' => 0,
            'total_discount' => 0,
            'total' => 100,
            'discounts' => json_encode([]),
            'size' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('purc_documents_anular', $compra->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        // Sin tallas ni presentaciones la anulación antes fallaba y deshacía el
        // cambio de estado; ahora queda grabada y el RCE la excluye.
        $this->assertSame('A', $compra->refresh()->status);
        $this->assertSame(3, (int) DB::table('products')->where('id', $producto)->value('stock'));
        $this->assertNull(DB::table('products')->where('id', $producto)->value('sizes'));

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertSame(0, $data['rowsTotal']);
        $this->assertStringNotContainsString('400', $data['content']);
    }

    public function test_nombre_de_archivo_del_libro_de_compras(): void
    {
        $this->purchase();

        $data = $this->service()->build(now()->format('Y-m'));

        $this->assertSame(
            'LE' . self::RUC . now()->format('Ym') . '00' . '080400' . '02' . '1' . '1' . '1' . '2' . '.TXT',
            $data['fileName']
        );
    }

    public function test_la_pantalla_del_registro_de_compras_carga_sin_el_txt(): void
    {
        $this->purchase();

        $this->actingAs($this->user)
            ->get(route('sire_rce_compras_index', ['period' => now()->format('Y-m')]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sales::Sire/Registro', false)
                ->where('tipo', 'compras')
                ->where('rowsTotal', 1)
                ->where('previewLimit', 200)
                ->where('content', null));
    }

    public function test_sin_permiso_no_se_puede_descargar(): void
    {
        $otro = User::factory()->create();

        $this->actingAs($otro)
            ->get(route('sire_rce_compras_txt'))
            ->assertForbidden();
    }

    private function service(): SireRceComprasService
    {
        return app(SireRceComprasService::class);
    }

    /**
     * Producto comprado sin presentaciones ni tallas, que es el caso que
     * rompía la anulación.
     */
    private function product(): int
    {
        return (int) DB::table('products')->insertGetId([
            'interne' => 'P0001',
            'description' => 'PRODUCTO DE PRUEBA',
            'purchase_prices' => 50,
            'sale_prices' => json_encode(['high' => 60, 'under' => null, 'medium' => null]),
            'type_unit_measure_id' => 'ZZ',
            'stock_min' => 1,
            'stock' => 5,
            'presentations' => false,
            'is_product' => true,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function documentType(string $sunatId): int
    {
        $id = DB::table('sale_document_types')->where('sunat_id', $sunatId)->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('sale_document_types')->insertGetId([
            'description' => $sunatId,
            'sunat_id' => $sunatId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function purchase(array $attributes = []): PurcDocument
    {
        $providerId = (int) DB::table('people')->where('number', self::RUC_PROVEEDOR)->value('id');

        return PurcDocument::create(array_merge([
            'user_id' => $this->user->id,
            'provider_id' => $providerId,
            'serie' => 'F001',
            'number' => '100',
            'document_type_id' => $this->documentType('01'),
            'date_of_issue' => now()->toDateString(),
            'currency_type_id' => 'PEN',
            'total_taxed' => 84.75,
            'total_igv' => 15.25,
            'total_exonerated' => 0,
            'total_unaffected' => 0,
            'total_exportation' => 0,
            'total_free' => 0,
            'total_isc' => 0,
            'total_other_taxes' => 0,
            'total_value' => 84.75,
            'total' => 100,
            'status' => true,
        ], $attributes));
    }
}
