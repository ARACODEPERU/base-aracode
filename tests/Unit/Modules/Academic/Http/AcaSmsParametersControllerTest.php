<?php

namespace Tests\Unit\Modules\Academic\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Http\Controllers\AcaSmsParametersController;
use Modules\Academic\Services\SchoolGateNoticeService;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsSchoolNoticeSchema;

/**
 * Contrato de la pantalla Parametros / SMS del Academico.
 *
 * Se invocan las acciones del controlador directamente (el permiso y la sesión
 * viven en las rutas): la vista previa arma el texto con datos de ejemplo y la
 * prueba por SMS exige el canal configurado, activo y con teléfono utilizable.
 */
class AcaSmsParametersControllerTest extends TestCase
{
    use BuildsSchoolNoticeSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSchoolNoticeSchema();
        $this->createSchoolNoticeSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchoolNoticeSchema();

        parent::tearDown();
    }

    public function test_la_vista_previa_reemplaza_las_variables_de_ejemplo(): void
    {
        $response = $this->controller()->preview(Request::create('/academic/school/sms/preview', 'POST', [
            'event' => 'in',
            'message' => 'Hola {alumno} de {seccion}',
        ]));

        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame('Hola Ana Pérez de Primaria 3° A', $data['text']);
    }

    public function test_la_prueba_exige_el_canal_configurado(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->test(Request::create('/academic/school/sms/test', 'POST', [
            'phone' => '987654321',
            'event' => 'in',
        ]));
    }

    public function test_la_prueba_exige_el_interruptor_general(): void
    {
        $this->setChannelParameter('SC-00020', 'https://api.sms-gate.app/mobile/v1');
        $this->setChannelParameter('SC-00021', 'colegio');
        $this->setChannelParameter('SC-00022', 'secreto');
        $this->setChannelParameter('SC-00023', '0');

        $this->expectException(ValidationException::class);

        $this->controller()->test(Request::create('/academic/school/sms/test', 'POST', [
            'phone' => '987654321',
            'event' => 'in',
        ]));
    }

    public function test_la_prueba_envia_el_sms_con_datos_de_ejemplo(): void
    {
        Http::fake(['*' => Http::response(['id' => 'abc'], 200)]);
        $this->enableSchoolNoticeChannel();

        $response = $this->controller()->test(Request::create('/academic/school/sms/test', 'POST', [
            'phone' => '987654321',
            'event' => 'out',
        ]));

        $data = json_decode((string) $response->getContent(), true);

        $this->assertStringContainsString('SALIDA', $data['text']);

        Http::assertSent(fn ($request) => $request['phoneNumbers'][0] === '+51987654321');
    }

    private function controller(): AcaSmsParametersController
    {
        return new AcaSmsParametersController(new SchoolGateNoticeService());
    }
}
