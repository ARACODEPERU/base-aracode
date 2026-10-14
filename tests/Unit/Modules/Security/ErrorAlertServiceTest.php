<?php

namespace Tests\Unit\Modules\Security;

use Exception;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Entities\SecurityErrorLog;
use Modules\Security\Jobs\SendSecurityErrorAlert;
use Modules\Security\Services\ErrorAlertService;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Puente logs -> alertas de Telegram del módulo Security.
 *
 * Se prueba el contrato del ErrorAlertService: qué niveles disparan aviso, a
 * quién se avisa, la deduplicación por huella, la protección contra la
 * recursión y el envío de prueba.
 */
class ErrorAlertServiceTest extends TestCase
{
    use BuildsSecurityAlertSchema;

    protected function setUp(): void
    {
        parent::setUp();

        // La ventana de anti-duplicados vive en caché (driver array): sin
        // limpiarla, una huella de otra prueba silenciaría la alerta.
        Cache::flush();

        $this->dropSecurityAlertSchema();
        $this->createSecurityAlertSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSecurityAlertSchema();

        parent::tearDown();
    }

    public function test_encola_la_alerta_cuando_hay_error_y_destinatarios_activos(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['name' => 'Ops', 'chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(
            new MessageLogged('error', 'Fallo grave', ['exception' => new RuntimeException('Fallo grave')])
        );

        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
    }

    public function test_no_encola_si_las_alertas_estan_desactivadas(): void
    {
        Queue::fake();

        SecurityAlertSetting::current()->update(['enabled' => false]);
        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged('error', 'Fallo', []));

        Queue::assertNothingPushed();
    }

    public function test_no_encola_si_no_hay_destinatarios_activos(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => false]);

        app(ErrorAlertService::class)->handle(new MessageLogged('error', 'Fallo', []));

        Queue::assertNothingPushed();
    }

    public function test_no_encola_por_debajo_del_umbral(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged('warning', 'Solo una advertencia', []));

        Queue::assertNothingPushed();
    }

    public function test_el_umbral_configurable_puede_subir_a_critical(): void
    {
        Queue::fake();

        SecurityAlertSetting::current()->update(['min_level' => 'critical']);
        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        $service = app(ErrorAlertService::class);

        $service->handle(new MessageLogged('error', 'Error normal', []));
        Queue::assertNothingPushed();

        $service->handle(new MessageLogged('critical', 'Error critico', []));
        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
    }

    public function test_deduplica_la_misma_huella(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        $service = app(ErrorAlertService::class);
        $event = new MessageLogged('error', 'Mismo error', ['exception' => new RuntimeException('Mismo error')]);

        $service->handle($event);
        $service->handle($event);

        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
    }

    public function test_ignora_los_logs_internos_de_las_alertas(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged('error', 'Envio fallido', [
            ErrorAlertService::CONTEXT_MARKER => true,
        ]));

        Queue::assertNothingPushed();
    }

    public function test_la_vista_previa_no_deja_variables_sin_reemplazar(): void
    {
        $preview = app(ErrorAlertService::class)->previewMessage();

        $this->assertStringContainsString('ERROR', $preview);
        $this->assertStringNotContainsString('{', $preview);
    }

    public function test_el_envio_de_prueba_llega_solo_a_destinatarios_activos(): void
    {
        SecurityAlertRecipient::create(['name' => 'Activo', 'chat_id' => '111', 'is_active' => true]);
        SecurityAlertRecipient::create(['name' => 'Inactivo', 'chat_id' => '222', 'is_active' => false]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')
            ->once()
            ->with('111', Mockery::type('string'), true)
            ->andReturn([]);

        $result = app(ErrorAlertService::class)->sendTest($bot);

        $this->assertSame(1, $result['sent']);
        $this->assertSame(0, $result['failed']);
        $this->assertCount(1, $result['results']);
        $this->assertTrue($result['results'][0]['ok']);
    }

    public function test_guarda_el_contexto_del_error_y_avisa_con_el_enlace(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged('error', 'Fallo al matricular', [
            'exception' => new RuntimeException('Fallo al matricular'),
        ]));

        $log = SecurityErrorLog::query()->first();

        $this->assertNotNull($log, 'El error no se guardó con su contexto.');
        $this->assertSame('ERROR', $log->level);
        $this->assertSame('RuntimeException', $log->exception_class);
        $this->assertNotSame('', (string) $log->trace);

        Queue::assertPushed(SendSecurityErrorAlert::class, function (SendSecurityErrorAlert $job) use ($log) {
            return str_contains($job->text, '/security/errors/' . $log->id)
                && str_contains($job->text, 'Nivel:')
                && str_contains($job->text, 'RuntimeException');
        });
    }

    public function test_la_repeticion_suma_en_el_mismo_error_sin_volver_a_avisar(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        $service = app(ErrorAlertService::class);
        $event = new MessageLogged('error', 'Error que se repite', [
            'exception' => new RuntimeException('Error que se repite'),
        ]);

        $service->handle($event);
        $service->handle($event);

        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
        $this->assertSame(1, SecurityErrorLog::query()->count());
        $this->assertSame(2, (int) SecurityErrorLog::query()->first()->occurrences);
    }

    public function test_la_repeticion_despues_de_la_ventana_avisa_de_nuevo_pero_no_duplica_la_fila(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        $service = app(ErrorAlertService::class);
        $event = new MessageLogged('error', 'Fallo persistente del planificador', [
            'exception' => new RuntimeException('Fallo persistente del planificador'),
        ]);

        $service->handle($event);

        // Pasa la ventana de anti-duplicados: el admin quiere saber que el
        // error sigue vivo, pero el contexto no se vuelve a guardar.
        $this->travel(10)->minutes();
        $service->handle($event);

        Queue::assertPushed(SendSecurityErrorAlert::class, 2);
        $this->assertSame(1, SecurityErrorLog::query()->count());

        $log = SecurityErrorLog::query()->first();
        $this->assertSame(2, (int) $log->occurrences);

        // El segundo aviso apunta al MISMO contexto y dice que ya van 2.
        $textos = collect(Queue::pushed(SendSecurityErrorAlert::class))->map(fn ($job) => $job->text);
        $this->assertStringContainsString('/security/errors/' . $log->id, $textos->last());
        $this->assertStringContainsString('2', $textos->last());
    }

    public function test_el_fallo_de_un_comando_programado_se_avisa_una_sola_vez(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        $service = app(ErrorAlertService::class);

        // 1) El comando explica el motivo real (es el aviso útil).
        $service->handle(new MessageLogged('error', 'health:sync-google-calendar: la sincronizacion fallo: invalid_grant', [
            ErrorAlertService::SCHEDULED_COMMAND_MARKER => 'health:sync-google-calendar',
            'exception' => new RuntimeException('invalid_grant'),
        ]));

        // 2) El planificador agrega su linea generica, que no dice nada del motivo.
        $service->handle(new MessageLogged(
            'error',
            "Scheduled command ['/usr/bin/php8.3' 'artisan' health:sync-google-calendar] failed with exit code [1].",
            ['exception' => new Exception('Scheduled command [...] failed with exit code [1].')]
        ));

        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
        $this->assertSame(1, SecurityErrorLog::query()->count());
    }

    public function test_la_linea_del_planificador_si_avisa_cuando_nadie_explico_el_fallo(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged(
            'error',
            "Scheduled command ['/usr/bin/php8.3' 'artisan' sales:daily-tasks] failed with exit code [1].",
            ['exception' => new Exception('Scheduled command [...] failed with exit code [1].')]
        ));

        Queue::assertPushed(SendSecurityErrorAlert::class, 1);
    }

    public function test_el_contexto_guardado_oculta_los_datos_sensibles(): void
    {
        $payload = app(ErrorAlertService::class)->buildPayload(new MessageLogged('error', 'Fallo con secreto', [
            'exception' => new RuntimeException('Fallo con secreto'),
            'password' => 'secreta123',
            'datos' => ['access_token' => 'token-abc-123', 'alumno' => 'Ana'],
        ]));

        $this->assertStringNotContainsString('secreta123', $payload['context']);
        $this->assertStringNotContainsString('token-abc-123', $payload['context']);
        $this->assertStringContainsString('oculto', $payload['context']);
        $this->assertStringContainsString('Ana', $payload['context']);

        // La excepción se guarda aparte (clase, archivo, línea y traza).
        $this->assertSame('RuntimeException', $payload['class']);
        $this->assertNotSame('', $payload['trace_full']);
    }

    public function test_el_mensaje_incluye_la_traza_resumida(): void
    {
        config(['security.alerts.trace_lines' => 4]);

        $service = app(ErrorAlertService::class);
        $payload = $service->buildPayload(new MessageLogged('error', 'Error con traza', [
            'exception' => new RuntimeException('Error con traza'),
        ]));

        $message = $service->renderMessage($payload);

        $this->assertStringContainsString('Traza:', $message);
        $this->assertStringNotContainsString('{', $message);
    }

    public function test_sin_guardado_de_contexto_el_aviso_sale_sin_enlace(): void
    {
        Queue::fake();

        SecurityAlertSetting::current()->update(['store_context' => false]);
        SecurityAlertRecipient::create(['chat_id' => '123', 'is_active' => true]);

        app(ErrorAlertService::class)->handle(new MessageLogged('error', 'Fallo sin contexto', [
            'exception' => new RuntimeException('Fallo sin contexto'),
        ]));

        $this->assertSame(0, SecurityErrorLog::query()->count());

        Queue::assertPushed(SendSecurityErrorAlert::class, function (SendSecurityErrorAlert $job) {
            return ! str_contains($job->text, '/security/errors/');
        });
    }

    public function test_el_envio_de_prueba_reporta_los_fallos_por_destinatario(): void
    {
        SecurityAlertRecipient::create(['name' => 'Malo', 'chat_id' => '999', 'is_active' => true]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendFormatted')
            ->once()
            ->andThrow(new RuntimeException('chat no encontrado'));

        $result = app(ErrorAlertService::class)->sendTest($bot);

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['failed']);
        $this->assertFalse($result['results'][0]['ok']);
        $this->assertSame('chat no encontrado', $result['results'][0]['error']);
    }
}
