<?php

namespace Tests\Unit\Modules\Security;

use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Entities\SecurityErrorLog;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Comando security:prune-error-logs: borra el contexto vencido para que el
 * disco no se llene, respetando la retención configurada en Seguridad → Alertas.
 */
class PruneErrorLogsCommandTest extends TestCase
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

    private function oldLog(int $days): SecurityErrorLog
    {
        $log = SecurityErrorLog::create([
            'fingerprint' => 'fp-' . uniqid(),
            'level' => 'ERROR',
            'message' => 'Error con ' . $days . ' días',
        ]);

        $log->forceFill(['created_at' => now()->subDays($days)])->save();

        return $log;
    }

    private function freshLog(): SecurityErrorLog
    {
        return SecurityErrorLog::create([
            'fingerprint' => 'fp-' . uniqid(),
            'level' => 'ERROR',
            'message' => 'Error reciente',
        ]);
    }

    public function test_usa_la_retencion_configurada(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 30, 'purge_enabled' => true]);

        $viejo = $this->oldLog(45);
        $this->freshLog();

        $this->artisan('security:prune-error-logs')->assertExitCode(0);

        $this->assertDatabaseMissing('security_error_logs', ['id' => $viejo->id]);
        $this->assertSame(1, SecurityErrorLog::query()->count());
    }

    public function test_los_dias_indicados_mandan_sobre_la_configuracion(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 30, 'purge_enabled' => true]);

        $viejo = $this->oldLog(10);

        $this->artisan('security:prune-error-logs', ['--days' => 5])->assertExitCode(0);

        $this->assertDatabaseMissing('security_error_logs', ['id' => $viejo->id]);
    }

    public function test_no_borra_nada_si_el_borrado_automatico_esta_apagado(): void
    {
        SecurityAlertSetting::current()->update(['purge_enabled' => false]);

        $viejo = $this->oldLog(400);

        $this->artisan('security:prune-error-logs')->assertExitCode(0);

        $this->assertDatabaseHas('security_error_logs', ['id' => $viejo->id]);
    }

    public function test_con_retencion_cero_no_borra_nada_aunque_el_borrado_este_activo(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 0, 'purge_enabled' => true]);

        $viejo = $this->oldLog(400);

        $this->artisan('security:prune-error-logs')->assertExitCode(0);

        $this->assertDatabaseHas('security_error_logs', ['id' => $viejo->id]);
    }

    public function test_all_borra_todo_el_contexto(): void
    {
        $this->oldLog(2);
        $this->freshLog();

        $this->artisan('security:prune-error-logs', ['--all' => true])->assertExitCode(0);

        $this->assertSame(0, SecurityErrorLog::query()->count());
    }

    public function test_la_opcion_logs_borra_los_archivos_de_log_rotados(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 7, 'purge_enabled' => true]);

        $directory = storage_path('logs');
        $rotado = $directory . DIRECTORY_SEPARATOR . 'prueba-purga-' . uniqid() . '.log';

        file_put_contents($rotado, "linea vieja\n");
        touch($rotado, now()->subDays(30)->getTimestamp());

        try {
            $this->artisan('security:prune-error-logs', ['--logs' => true])->assertExitCode(0);

            $this->assertFileDoesNotExist($rotado);
        } finally {
            if (is_file($rotado)) {
                @unlink($rotado);
            }
        }
    }

    public function test_sin_la_opcion_logs_no_toca_los_archivos(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 7, 'purge_enabled' => true]);

        $directory = storage_path('logs');
        $rotado = $directory . DIRECTORY_SEPARATOR . 'prueba-purga-' . uniqid() . '.log';

        file_put_contents($rotado, "linea vieja\n");
        touch($rotado, now()->subDays(30)->getTimestamp());

        try {
            $this->artisan('security:prune-error-logs')->assertExitCode(0);

            $this->assertFileExists($rotado);
        } finally {
            if (is_file($rotado)) {
                @unlink($rotado);
            }
        }
    }
}
