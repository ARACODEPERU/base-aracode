<?php

namespace Tests\Unit\Modules\Security;

use Modules\Security\Entities\SecurityErrorLog;
use Modules\Security\Services\ErrorLogService;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Contexto de errores guardado (módulo Security).
 *
 * Se prueba lo que promete el servicio: guardar el detalle completo del error,
 * agrupar las repeticiones en la misma fila, devolver el enlace al detalle y
 * limpiar por retención o a mano.
 */
class ErrorLogServiceTest extends TestCase
{
    use BuildsSecurityAlertSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSecurityAlertSchema();
        $this->createSecurityAlertSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSecurityAlertSchema();

        parent::tearDown();
    }

    private function service(): ErrorLogService
    {
        return app(ErrorLogService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'level' => 'ERROR',
            'message' => 'Undefined variable $alumno',
            'class' => 'ErrorException',
            'file' => 'Modules/Academic/Http/Controllers/AcaStudentController.php',
            'line' => '1518',
            'trace' => 'resumen de la traza',
            'trace_full' => "ModuleAcademico->matricular()\nLaravel\\Router->dispatch()",
            'context' => '{"log":{"sql":"select 1"},"request":{"input":{}}}',
            'environment' => 'production',
            'url' => 'https://escuela.test/aca/students/12/matricular',
            'method' => 'POST',
            'user' => 'Ana Pérez (ana@escuela.test)',
            'user_id' => '7',
            'ip' => '190.117.12.30',
            'agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'date' => now()->format('d/m/Y H:i:s'),
            'reference' => '',
            'link' => '',
            'occurrences' => '1',
        ], $overrides);
    }

    public function test_guarda_el_contexto_completo_del_error(): void
    {
        $payload = $this->payload();
        $log = $this->service()->record($payload, SecurityErrorLog::fingerprint($payload));

        $this->assertInstanceOf(SecurityErrorLog::class, $log);
        $this->assertDatabaseHas('security_error_logs', [
            'id' => $log->id,
            'level' => 'ERROR',
            'exception_class' => 'ErrorException',
            'line' => 1518,
            'environment' => 'production',
            'user_id' => 7,
            'ip' => '190.117.12.30',
            'occurrences' => 1,
        ]);

        $this->assertStringContainsString('matricular', (string) $log->trace);
        $this->assertStringContainsString('select 1', (string) $log->context);
        $this->assertSame('Modules/Academic/Http/Controllers/AcaStudentController.php:1518', $log->origin());
        $this->assertSame('#' . $log->id, $log->reference());
    }

    public function test_el_enlace_apunta_a_la_pantalla_de_detalle(): void
    {
        $link = $this->service()->linkFor(42);

        $this->assertStringContainsString('/security/errors/42', $link);
        $this->assertSame('', $this->service()->linkFor(null));
    }

    public function test_las_repeticiones_suman_en_la_misma_fila(): void
    {
        $payload = $this->payload();
        $service = $this->service();

        $log = $service->record($payload, SecurityErrorLog::fingerprint($payload));
        $service->touch($log, $payload);
        $service->touch($log, $payload);

        $this->assertSame(3, $log->fresh()->occurrences);
        $this->assertSame(1, SecurityErrorLog::query()->count());
        $this->assertNotNull($log->fresh()->last_seen_at);
    }

    public function test_la_traza_que_llega_despues_se_completa(): void
    {
        $payload = $this->payload(['trace_full' => '']);
        $service = $this->service();

        $log = $service->record($payload, SecurityErrorLog::fingerprint($payload));

        $service->touch($log, $this->payload(['trace_full' => 'traza que faltaba']));

        $this->assertStringContainsString('traza que faltaba', (string) $log->fresh()->trace);
    }

    public function test_la_purga_borra_solo_los_vencidos(): void
    {
        $viejo = SecurityErrorLog::create([
            'fingerprint' => 'a',
            'level' => 'ERROR',
            'message' => 'Error viejo',
        ]);
        $viejo->forceFill(['created_at' => now()->subDays(40)])->save();

        SecurityErrorLog::create([
            'fingerprint' => 'b',
            'level' => 'ERROR',
            'message' => 'Error reciente',
        ]);

        $deleted = $this->service()->prune(30);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('security_error_logs', ['id' => $viejo->id]);
        $this->assertDatabaseHas('security_error_logs', ['message' => 'Error reciente']);
    }

    public function test_con_retencion_cero_no_se_borra_nada(): void
    {
        $log = SecurityErrorLog::create([
            'fingerprint' => 'a',
            'level' => 'ERROR',
            'message' => 'Error viejo',
        ]);
        $log->forceFill(['created_at' => now()->subDays(400)])->save();

        $this->assertSame(0, $this->service()->prune(0));
        $this->assertDatabaseHas('security_error_logs', ['id' => $log->id]);
    }

    public function test_se_puede_borrar_uno_o_todo(): void
    {
        SecurityErrorLog::create(['fingerprint' => 'a', 'level' => 'ERROR', 'message' => 'Uno']);
        $otro = SecurityErrorLog::create(['fingerprint' => 'b', 'level' => 'ERROR', 'message' => 'Dos']);

        $this->assertTrue($this->service()->delete((int) $otro->id));
        $this->assertSame(1, SecurityErrorLog::query()->count());

        $this->assertSame(1, $this->service()->clear());
        $this->assertSame(0, SecurityErrorLog::query()->count());
    }

    public function test_las_estadisticas_resumen_lo_guardado(): void
    {
        SecurityErrorLog::create([
            'fingerprint' => 'a',
            'level' => 'ERROR',
            'message' => 'Fallo',
            'context' => str_repeat('x', 100),
        ]);
        SecurityErrorLog::create(['fingerprint' => 'b', 'level' => 'CRITICAL', 'message' => 'Grave']);

        $stats = $this->service()->stats();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['levels']['ERROR'] ?? null);
        $this->assertSame(1, $stats['levels']['CRITICAL'] ?? null);
        $this->assertGreaterThan(100, $stats['size_bytes']);
        $this->assertNotNull($stats['oldest_at']);
    }
}
