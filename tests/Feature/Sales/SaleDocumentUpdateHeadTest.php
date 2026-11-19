<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleDocumentUpdateHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_el_estado_pendiente_desde_el_modal_actualiza_la_cabecera(): void
    {
        $user = User::factory()->create();
        $documento = $this->documento($user, 'Pendiente de Reintento');

        $response = $this->actingAs($user)->post(route('saledocuments_update_head'), $this->payload($documento, false));

        $response->assertOk();

        $documento->refresh();
        $this->assertSame('Pendiente', $documento->invoice_status);
        $this->assertSame('0.00', (string) $documento->detraccion_amount);
    }

    public function test_guardar_una_factura_con_detraccion_no_bloquea_la_actualizacion(): void
    {
        $user = User::factory()->create();
        $documento = $this->documento($user, 'Pendiente');

        $response = $this->actingAs($user)->post(route('saledocuments_update_head'), $this->payload($documento, true));

        $response->assertOk();

        $documento->refresh();
        $this->assertSame('Pendiente', $documento->invoice_status);
        $this->assertTrue((bool) $documento->detraccion);
    }

    private function payload(SaleDocument $documento, bool $detraccion): array
    {
        return [
            'id' => $documento->id,
            'client_number' => '20123456789',
            'client_rzn_social' => 'CLIENTE PRUEBA SAC',
            'client_address' => 'AV. SIEMPRE VIVA 123',
            'client_phone' => '999999999',
            'client_email' => 'cliente@example.com',
            'client_ubigeo_code' => '150101',
            'client_ubigeo_description' => 'LIMA-LIMA-LIMA',
            'invoice_broadcast_date' => '2026-11-19',
            'invoice_due_date' => '2026-11-19',
            'invoice_status' => 'Pendiente',
            'type_operation' => '0101',
            'detraccion' => $detraccion,
        ];
    }

    private function documento(User $user, string $estado): SaleDocument
    {
        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 1000,
            'advancement' => 1000,
            'total_discount' => 0,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
            'status' => 1,
        ]);

        return SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '1',
            'invoice_type_currency' => 'PEN',
            'invoice_broadcast_date' => '2026-11-19',
            'invoice_due_date' => '2026-11-19',
            'client_type_doc' => '6',
            'client_number' => '20123456789',
            'client_rzn_social' => 'CLIENTE PRUEBA SAC',
            'client_address' => 'AV. SIEMPRE VIVA 123',
            'client_ubigeo_code' => '150101',
            'client_ubigeo_description' => 'LIMA-LIMA-LIMA',
            'invoice_status' => $estado,
            'status' => 1,
            'overall_total' => 1000,
            'invoice_type_operation' => '0101',
        ]);
    }
}
