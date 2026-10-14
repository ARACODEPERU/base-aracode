<?php

namespace Tests\Unit\Modules\Security;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Response as InertiaResponse;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Entities\SecurityErrorLog;
use Modules\Security\Http\Controllers\ErrorLogController;
use Tests\TestCase;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Pantalla Errores: listado con filtros, detalle por enlace y limpieza.
 *
 * Los métodos se prueban directamente contra el controlador para no arrastrar
 * el middleware de permisos ni el registro de actividad; los permisos se
 * comprueban aparte sobre las rutas registradas.
 */
class ErrorLogControllerTest extends TestCase
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

    private function controller(): ErrorLogController
    {
        return app(ErrorLogController::class);
    }

    private function makeLog(array $overrides = []): SecurityErrorLog
    {
        return SecurityErrorLog::create(array_merge([
            'fingerprint' => 'fp-' . uniqid(),
            'level' => 'ERROR',
            'message' => 'Fallo al matricular',
            'exception_class' => 'ErrorException',
            'file' => 'Modules/Academic/Http/Controllers/AcaStudentController.php',
            'line' => 1518,
            'trace' => 'traza completa',
            'context' => json_encode(['log' => ['sql' => 'select 1'], 'request' => ['input' => ['id' => 3]]]),
            'url' => 'https://escuela.test/aca/students/12/matricular',
            'method' => 'POST',
            'user_label' => 'Ana Pérez',
            'user_id' => 7,
            'ip' => '190.117.12.30',
        ], $overrides));
    }

    public function test_lista_los_errores_paginados(): void
    {
        $this->makeLog();
        $this->makeLog(['fingerprint' => 'fp-2', 'level' => 'CRITICAL', 'message' => 'Caída del worker']);

        $response = $this->controller()->data(Request::create('/security/errors/data', 'GET'));
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $data['total']);
        $this->assertCount(2, $data['data']);
        $this->assertSame('https://escuela.test/aca/students/12/matricular', $data['data'][0]['url']);
        $this->assertArrayHasKey('reference', $data['data'][0]);
        $this->assertArrayHasKey('occurrences', $data['data'][0]);
    }

    public function test_los_filtros_acotan_el_listado(): void
    {
        $this->makeLog();
        $this->makeLog([
            'fingerprint' => 'fp-2',
            'level' => 'CRITICAL',
            'message' => 'Caída del worker',
            'url' => 'https://escuela.test/aca/students/12/notas',
        ]);

        $porNivel = $this->controller()
            ->data(Request::create('/security/errors/data', 'GET', ['level' => 'CRITICAL']))
            ->getData(true);

        $this->assertSame(1, $porNivel['total']);
        $this->assertSame('Caída del worker', $porNivel['data'][0]['message']);

        $porTexto = $this->controller()
            ->data(Request::create('/security/errors/data', 'GET', ['search' => 'matricular']))
            ->getData(true);

        $this->assertSame(1, $porTexto['total']);
    }

    public function test_el_detalle_responde_con_la_pantalla_del_error(): void
    {
        $log = $this->makeLog();

        // El render completo de Inertia no se prueba aquí: la aplicación comparte
        // props globales (empresa, permisos) que necesitan toda su base de datos.
        $this->assertInstanceOf(InertiaResponse::class, $this->controller()->show((int) $log->id));
    }

    public function test_el_contexto_se_decodifica_para_la_pantalla(): void
    {
        $log = $this->makeLog();

        $context = $log->contextArray();

        $this->assertIsArray($context);
        $this->assertSame('select 1', $context['log']['sql']);
        $this->assertSame(3, $context['request']['input']['id']);

        $log->forceFill(['context' => 'texto no json cortado'])->save();
        $this->assertSame('texto no json cortado', $log->fresh()->contextArray());

        $log->forceFill(['context' => null])->save();
        $this->assertNull($log->fresh()->contextArray());
    }

    public function test_un_id_inexistente_devuelve_404(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->controller()->show(9999);
    }

    public function test_elimina_un_error_con_su_contexto(): void
    {
        $log = $this->makeLog();

        $response = $this->controller()->destroy((int) $log->id);
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['deleted']);
        $this->assertDatabaseMissing('security_error_logs', ['id' => $log->id]);
    }

    public function test_elimina_todos_los_errores(): void
    {
        $this->makeLog();
        $this->makeLog(['fingerprint' => 'fp-2']);

        $data = $this->controller()->clear()->getData(true);

        $this->assertSame(2, $data['deleted']);
        $this->assertSame(0, SecurityErrorLog::query()->count());
    }

    public function test_la_purga_usa_los_dias_indicados(): void
    {
        $viejo = $this->makeLog();
        $viejo->forceFill(['created_at' => now()->subDays(20)])->save();
        $this->makeLog(['fingerprint' => 'fp-2']);

        $data = $this->controller()->prune(Request::create('/security/errors/purge', 'POST', ['days' => 10]))->getData(true);

        $this->assertSame(1, $data['deleted']);
        $this->assertDatabaseMissing('security_error_logs', ['id' => $viejo->id]);
        $this->assertSame(1, SecurityErrorLog::query()->count());
    }

    public function test_la_purga_sin_dias_usa_la_retencion_configurada(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 30, 'purge_enabled' => true]);

        $viejo = $this->makeLog();
        $viejo->forceFill(['created_at' => now()->subDays(45)])->save();

        $data = $this->controller()->prune(Request::create('/security/errors/purge', 'POST'))->getData(true);

        $this->assertSame(1, $data['deleted']);
    }

    public function test_con_retencion_cero_la_purga_no_borra_nada(): void
    {
        SecurityAlertSetting::current()->update(['retention_days' => 0]);

        $log = $this->makeLog();
        $log->forceFill(['created_at' => now()->subDays(400)])->save();

        $data = $this->controller()->prune(Request::create('/security/errors/purge', 'POST'))->getData(true);

        $this->assertSame(0, $data['deleted']);
        $this->assertDatabaseHas('security_error_logs', ['id' => $log->id]);
    }

    public function test_las_rutas_exigen_los_permisos(): void
    {
        $soloVer = [
            'security_errors',
            'security_errors_data',
            'security_errors_show',
        ];

        foreach ($soloVer as $name) {
            $route = $this->findRoute($name);

            $this->assertNotNull($route, "La ruta {$name} no está registrada.");
            $this->assertContains('permission:conf_errores', $route->gatherMiddleware());
        }

        $soloAdmin = [
            'security_errors_destroy',
            'security_errors_clear',
            'security_errors_purge',
        ];

        foreach ($soloAdmin as $name) {
            $route = $this->findRoute($name);

            $this->assertNotNull($route, "La ruta {$name} no está registrada.");
            $this->assertContains('permission:conf_errores_eliminar', $route->gatherMiddleware());
        }
    }

    private function findRoute(string $name): mixed
    {
        return collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === $name);
    }
}
