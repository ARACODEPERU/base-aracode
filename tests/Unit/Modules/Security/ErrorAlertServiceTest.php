<?php

namespace Tests\Unit\Modules\Security;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
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
