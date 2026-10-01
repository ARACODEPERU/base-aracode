<?php

namespace Tests\Unit\Modules\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Mockery;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Http\Controllers\SecurityAlertController;
use Modules\Security\Services\ErrorAlertService;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Pantalla Alertas: alta/edición/baja de destinatarios, ajustes y envío de
 * prueba. Los métodos se prueban directamente contra el controlador para no
 * arrastrar el middleware de permisos ni el registro de actividad.
 */
class SecurityAlertControllerTest extends TestCase
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

    private function controller(?TelegramBotService $bot = null): SecurityAlertController
    {
        return new SecurityAlertController(
            app(ErrorAlertService::class),
            $bot ?? app(TelegramBotService::class),
        );
    }

    public function test_registra_un_destinatario(): void
    {
        $response = $this->controller()->storeRecipient(
            Request::create('/security/alerts/recipients', 'POST', [
                'name' => 'Soporte',
                'chat_id' => '-100123456789',
                'is_active' => true,
            ])
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('security_alert_recipients', [
            'name' => 'Soporte',
            'chat_id' => '-100123456789',
            'is_active' => true,
        ]);
    }

    public function test_rechaza_un_chat_id_con_formato_invalido(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->storeRecipient(
            Request::create('/security/alerts/recipients', 'POST', [
                'chat_id' => 'hola mundo',
            ])
        );
    }

    public function test_rechaza_un_chat_id_duplicado(): void
    {
        SecurityAlertRecipient::create(['chat_id' => '12345', 'is_active' => true]);

        $this->expectException(ValidationException::class);

        $this->controller()->storeRecipient(
            Request::create('/security/alerts/recipients', 'POST', [
                'chat_id' => '12345',
            ])
        );
    }

    public function test_actualiza_un_destinatario(): void
    {
        $recipient = SecurityAlertRecipient::create(['name' => 'Viejo', 'chat_id' => '12345', 'is_active' => true]);

        $response = $this->controller()->updateRecipient(
            Request::create('/security/alerts/recipients/' . $recipient->id, 'PUT', [
                'name' => 'Nuevo',
                'chat_id' => '12345',
                'is_active' => false,
            ]),
            $recipient->id
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('security_alert_recipients', [
            'id' => $recipient->id,
            'name' => 'Nuevo',
            'is_active' => false,
        ]);
    }

    public function test_elimina_un_destinatario(): void
    {
        $recipient = SecurityAlertRecipient::create(['chat_id' => '12345', 'is_active' => true]);

        $response = $this->controller()->destroyRecipient($recipient->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseMissing('security_alert_recipients', ['id' => $recipient->id]);
    }

    public function test_guarda_los_ajustes(): void
    {
        $response = $this->controller()->updateSettings(
            Request::create('/security/alerts/settings', 'PUT', [
                'enabled' => false,
                'min_level' => 'critical',
                'cooldown_minutes' => 15,
            ])
        );

        $this->assertSame(200, $response->getStatusCode());

        $settings = SecurityAlertSetting::current();
        $this->assertFalse($settings->isEnabled());
        $this->assertSame('critical', $settings->minLevel());
        $this->assertSame(15, $settings->cooldownMinutes());
    }

    public function test_el_envio_de_prueba_falla_si_el_bot_no_esta_configurado(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('isConfigured')->andReturn(false);

        $this->expectException(ValidationException::class);

        $this->controller($bot)->sendTest();
    }

    public function test_el_envio_de_prueba_falla_sin_destinatarios(): void
    {
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('isConfigured')->andReturn(true);

        $this->expectException(ValidationException::class);

        $this->controller($bot)->sendTest();
    }

    public function test_el_envio_de_prueba_entrega_a_los_destinatarios(): void
    {
        SecurityAlertRecipient::create(['name' => 'Ops', 'chat_id' => '111', 'is_active' => true]);

        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('isConfigured')->andReturn(true);
        $bot->shouldReceive('sendFormatted')->once()->andReturn([]);

        $response = $this->controller($bot)->sendTest();
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['sent']);
        $this->assertSame(0, $data['failed']);
    }

    public function test_las_rutas_de_alertas_exigen_el_permiso(): void
    {
        $names = [
            'security_alerts',
            'security_alerts_recipients_store',
            'security_alerts_recipients_update',
            'security_alerts_recipients_destroy',
            'security_alerts_settings_update',
            'security_alerts_test',
        ];

        foreach ($names as $name) {
            $route = collect(Route::getRoutes()->getRoutes())
                ->first(fn ($route) => $route->getName() === $name);

            $this->assertNotNull($route, "La ruta {$name} no está registrada.");
            $this->assertContains('permission:conf_alertas', $route->gatherMiddleware());
        }
    }
}
