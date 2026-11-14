<?php

namespace Tests\Feature\Commercial;

use App\Models\BilleteraDigital;
use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commercial\Entities\CommercialNegotiation;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Reglas de medio de pago al crear una negociación:
 *   - transferencia exige la cuenta bancaria recomendada por el asesor;
 *   - el enlace de pago de la billetera se guarda en la negociación solo para
 *     enlace o billetera digital; con otros medios queda vacío.
 */
class NegotiationPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $asesor = User::factory()->create();
        $asesor->givePermissionTo(Permission::firstOrCreate(['name' => 'comm_negociaciones_nuevo', 'guard_name' => 'web']));
        $this->actingAs($asesor);
    }

    /**
     * Datos mínimos válidos de una negociación con cobro.
     */
    private function datosBase(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Negociacion de prueba',
            'total_price' => 100,
            'currency' => 'PEN',
            'payment_type' => 'single',
            'items' => [[
                'item_type' => 'course',
                'item_id' => null,
                'title' => 'Curso de prueba',
                'price' => 100,
            ]],
            'company_billetera_ids' => [],
        ], $overrides);
    }

    public function test_transferencia_sin_cuenta_recomendada_no_se_guarda(): void
    {
        $respuesta = $this->post(route('comm_negotiations_store'), $this->datosBase([
            'payment_method' => 'transferencia',
        ]));

        $respuesta->assertSessionHasErrors('recommended_bank_account_id');
        $this->assertSame(0, CommercialNegotiation::count());
    }

    public function test_billetera_con_enlace_guarda_el_enlace_en_la_negociacion(): void
    {
        $billetera = BilleteraDigital::create(['short_name' => 'YAPE', 'full_name' => 'Yape', 'status' => true]);
        CompanyBilletera::create([
            'billetera_id' => $billetera->id,
            'account_name' => 'Empresa',
            'account_number' => '999888777',
            'has_payment_link' => true,
            'status' => true,
        ]);

        $this->post(route('comm_negotiations_store'), $this->datosBase([
            'payment_method' => 'enlace',
            'payment_link' => 'https://pagos.example.com/abc123',
        ]))->assertSessionHasNoErrors();

        $negociacion = CommercialNegotiation::firstOrFail();
        $this->assertSame('enlace', $negociacion->payment_method);
        $this->assertSame('https://pagos.example.com/abc123', $negociacion->payment_link);
    }

    public function test_medio_sin_enlace_descarta_el_enlace_aunque_llegue_en_la_peticion(): void
    {
        $this->post(route('comm_negotiations_store'), $this->datosBase([
            'payment_method' => 'efectivo',
            'payment_link' => 'https://pagos.example.com/abc123',
        ]))->assertSessionHasNoErrors();

        $negociacion = CommercialNegotiation::firstOrFail();
        $this->assertSame('efectivo', $negociacion->payment_method);
        $this->assertNull($negociacion->payment_link);
    }
}
