<?php

namespace Tests\Unit\Modules\Academic\Services;

use Modules\Academic\Services\GateScanService;
use Modules\Academic\Services\SchoolGateNoticeService;
use Tests\TestCase;

/**
 * La portería ahora depende del servicio de avisos de asistencia.
 *
 * Este test de humo comprueba que el contenedor sigue pudiendo construir
 * GateScanService con su nueva dependencia: es la que encola el SMS al apoderado
 * cuando el escaneo registra la entrada o la salida.
 */
class GateScanServiceTest extends TestCase
{
    public function test_el_contenedor_resuelve_el_servicio_de_porteria_con_su_dependencia(): void
    {
        $service = app(GateScanService::class);

        $this->assertInstanceOf(GateScanService::class, $service);
    }

    public function test_el_servicio_de_avisos_se_construye_sin_dependencias(): void
    {
        $this->assertInstanceOf(SchoolGateNoticeService::class, app(SchoolGateNoticeService::class));
    }
}
