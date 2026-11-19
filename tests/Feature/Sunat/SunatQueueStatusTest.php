<?php

namespace Tests\Feature\Sunat;

use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Cuando SUNAT no responde, el comprobante debe quedar persistido como
 * pendiente de reintento, con su error técnico y disponible para
 * `sunat:retry-queue`.
 */
class SunatQueueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_fallo_de_red_deja_el_comprobante_en_cola_y_reintentable(): void
    {
        $documento = $this->document();

        $resilience = app(SunatResilience::class);
        $resilience->markQueued(
            $documento,
            SunatResilience::KIND_TIMEOUT,
            'cURL error 28: Operation timed out after 30001 milliseconds'
        );

        $documento->invoice_status = 'Pendiente';
        $documento->invoice_response_description = SunatResilience::FRIENDLY_QUEUED;

        // El backoff lo dejaría para dentro de 5 minutos; aquí se adelanta
        // para comprobar que el comando lo selecciona.
        $documento->sunat_next_retry_at = now()->subMinute();
        $documento->save();

        $documento->refresh();

        $this->assertSame('Pendiente', $documento->invoice_status);
        $this->assertSame('reintento', $documento->sunat_send_status);
        $this->assertSame(1, $documento->sunat_retry_attempts);
        $this->assertSame(SunatResilience::KIND_TIMEOUT, $documento->sunat_response_kind);
        $this->assertStringContainsString('cURL error 28', (string) $documento->sunat_last_error);
        $this->assertNotNull($documento->sunat_last_attempt_at);

        // El comando de reenvío lo ve como pendiente y no lo descarta.
        $salida = Artisan::call('sunat:retry-queue', ['--dry-run' => true]);
        $this->assertSame(0, $salida);

        $salidaComando = Artisan::output();
        $this->assertStringContainsString('F001-1', $salidaComando);
        $this->assertStringContainsString('intentos: 1', $salidaComando);

        // Sigue en cola: la simulación no cambia el estado.
        $this->assertSame('reintento', $documento->fresh()->sunat_send_status);
    }

    public function test_un_rechazo_de_negocio_sale_de_la_cola_y_no_se_reintenta(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markQueued($documento, SunatResilience::KIND_SUNAT_UNAVAILABLE, 'Error transitorio');
        $documento->save();

        // SUNAT respondió un rechazo definitivo de negocio (por ejemplo, 2017).
        $resilience->markSent($documento->refresh(), false);
        $documento->save();

        $documento->refresh();

        $this->assertSame('rechazado', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);

        $salida = Artisan::call('sunat:retry-queue', ['--dry-run' => true]);
        $this->assertSame(0, $salida);
        $this->assertStringContainsString('No hay comprobantes pendientes', Artisan::output());
    }

    public function test_el_1033_sale_de_la_cola_como_posiblemente_registrado(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markQueued($documento, SunatResilience::KIND_TIMEOUT, 'timeout');
        $documento->save();

        // En el reintento SUNAT responde 1033: ya estaría registrado.
        $resilience->markDuplicate($documento->refresh(), '1033');
        $documento->invoice_status = 'Posiblemente registrado';
        $documento->save();
        $documento->refresh();

        $this->assertSame('Posiblemente registrado', $documento->invoice_status);
        $this->assertSame('revisar', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertSame(SunatResilience::KIND_DUPLICATE, $documento->sunat_response_kind);
        $this->assertStringContainsString('1033', (string) $documento->sunat_last_error);

        // No vuelve a aparecer en la cola automática.
        $salida = Artisan::call('sunat:retry-queue', ['--dry-run' => true]);
        $this->assertSame(0, $salida);
        $this->assertStringContainsString('No hay comprobantes pendientes', Artisan::output());
    }

    public function test_el_reintento_crece_el_backoff_y_conserva_el_ultimo_error(): void
    {
        $documento = $this->document();
        $resilience = app(SunatResilience::class);

        $resilience->markQueued($documento, SunatResilience::KIND_CURL, 'Connection refused');
        $documento->save();

        $resilience->markQueued($documento->refresh(), SunatResilience::KIND_CURL, 'Connection refused de nuevo');
        $documento->save();

        $documento->refresh();

        $this->assertSame(2, $documento->sunat_retry_attempts);

        $proximo = Carbon::parse($documento->sunat_next_retry_at);
        $this->assertTrue($proximo->greaterThan(now()->addMinutes(9)));
        $this->assertTrue($proximo->lessThanOrEqualTo(now()->addMinutes(10)));
        $this->assertStringContainsString('de nuevo', (string) $documento->sunat_last_error);
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
