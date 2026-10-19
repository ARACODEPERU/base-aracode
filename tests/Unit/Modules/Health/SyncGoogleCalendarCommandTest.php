<?php

namespace Tests\Unit\Modules\Health;

use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Health\Services\DoctorGoogleCalendarService;
use Modules\Health\Services\GoogleCalendarService;
use Modules\Health\Services\GoogleCalendarSyncService;
use Modules\Health\Support\AppointmentEventMapper;
use Modules\Security\Services\ErrorAlertService;
use RuntimeException;
use Tests\TestCase;

/**
 * Aviso del comando health:sync-google-calendar cuando la sincronizacion falla.
 *
 * El aviso que llega por Telegram (y la pantalla Errores) tiene que traer el
 * motivo REAL y la sugerencia para corregirlo: antes solo se registraba la linea
 * generica del planificador ("failed with exit code [1]"), que no dice nada.
 */
class SyncGoogleCalendarCommandTest extends TestCase
{
    public function test_registra_el_motivo_real_y_la_sugerencia_cuando_falla_la_sincronizacion(): void
    {
        Log::spy();

        $error = new RuntimeException('Google rechazo leer los eventos (HTTP 401): {"error":"invalid_grant"}');

        $sync = Mockery::mock(GoogleCalendarSyncService::class);
        $sync->shouldReceive('tablesReady')->andReturn(true);
        $sync->shouldReceive('ready')->andReturn(true);
        $sync->shouldReceive('inboundEnabled')->andReturn(true);
        $sync->shouldReceive('pull')->andThrow($error);
        $sync->shouldReceive('hintFor')
            ->once()
            ->with(Mockery::on(fn (string $message) => str_contains($message, 'invalid_grant')))
            ->andReturn('La autorizacion caduco o se revoco: vuelve a pulsar "Continuar con Google".');

        $this->instance(GoogleCalendarSyncService::class, $sync);

        $this->artisan('health:sync-google-calendar')->assertExitCode(1);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context = []): bool {
                return str_contains($message, 'health:sync-google-calendar')
                    && str_contains($message, 'invalid_grant')
                    && str_contains($message, 'Como corregirlo')
                    && str_contains($message, 'Continuar con Google')
                    && ($context[ErrorAlertService::SCHEDULED_COMMAND_MARKER] ?? null) === 'health:sync-google-calendar'
                    && ($context['exception'] ?? null) instanceof RuntimeException;
            });
    }

    public function test_el_servicio_ofrece_la_sugerencia_para_los_errores_comunes_de_google(): void
    {
        $sync = new GoogleCalendarSyncService(
            Mockery::mock(GoogleCalendarService::class),
            Mockery::mock(AppointmentEventMapper::class),
            Mockery::mock(DoctorGoogleCalendarService::class),
        );

        $this->assertStringContainsString('caduco o se revoco', $sync->hintFor('invalid_grant'));
        $this->assertStringContainsString('Client ID', $sync->hintFor('invalid_client'));
        $this->assertStringContainsString('conexion a internet', $sync->hintFor('No se pudo conectar con Google Calendar'));
        $this->assertNotSame('', $sync->hintFor('algo raro sin pista conocida'));
    }
}
