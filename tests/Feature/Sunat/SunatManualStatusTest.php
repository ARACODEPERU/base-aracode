<?php

namespace Tests\Feature\Sunat;

use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estados persistidos del envío manual: sin próximo reintento y con el
 * detalle original guardado.
 */
class SunatManualStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_fallo_manual_no_programa_reintento(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markPendingManual(
            $documento,
            SunatResilience::KIND_TIMEOUT,
            'cURL error 28: Operation timed out after 30001 milliseconds'
        );

        $documento->invoice_status = 'Pendiente';
        $documento->invoice_response_description = SunatResilience::FRIENDLY_MANUAL;
        $documento->save();
        $documento->refresh();

        $this->assertSame('Pendiente', $documento->invoice_status);
        $this->assertSame('manual', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertSame(0, (int) $documento->sunat_retry_attempts);
        $this->assertSame(SunatResilience::KIND_TIMEOUT, $documento->sunat_response_kind);
        $this->assertStringContainsString('cURL error 28', (string) $documento->sunat_last_error);
        $this->assertNotNull($documento->sunat_last_attempt_at);
    }

    public function test_un_rechazo_de_negocio_cierra_la_revision_manual(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markPendingManual($documento, SunatResilience::KIND_SUNAT_UNAVAILABLE, 'Error transitorio');
        $documento->save();

        $resilience->markSent($documento->refresh(), false);
        $documento->save();
        $documento->refresh();

        $this->assertSame('rechazado', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertNull($documento->sunat_last_error);
    }

    public function test_el_1033_queda_para_revisar_sin_reintento(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markPendingManual($documento, SunatResilience::KIND_TIMEOUT, 'timeout');
        $documento->save();

        $resilience->markDuplicate($documento->refresh(), '1033');
        $documento->invoice_status = 'Posiblemente registrado';
        $documento->save();
        $documento->refresh();

        $this->assertSame('Posiblemente registrado', $documento->invoice_status);
        $this->assertSame('revisar', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertSame(SunatResilience::KIND_DUPLICATE, $documento->sunat_response_kind);
        $this->assertStringContainsString('1033', (string) $documento->sunat_last_error);
    }

    private function document(): SaleDocument
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 100,
            'advancement' => 100,
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
            'invoice_broadcast_date' => now()->toDateString(),
            'client_type_doc' => '6',
            'client_number' => '20123456789',
            'client_rzn_social' => 'CLIENTE PRUEBA SAC',
            'invoice_status' => 'Pendiente',
            'status' => 1,
            'overall_total' => 100,
        ]);
    }
}
