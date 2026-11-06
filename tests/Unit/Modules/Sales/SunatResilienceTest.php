<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Sunat\SunatResilience;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SunatResilienceTest extends TestCase
{
    private function resilience(): SunatResilience
    {
        return new SunatResilience;
    }

    /**
     * @return array<string, array{0: ?string, 1: string, 2: string}>
     */
    public static function fallasReintentables(): array
    {
        return [
            'timeout' => ['HTTP', 'cURL error 28: Operation timed out after 30001 milliseconds', SunatResilience::KIND_TIMEOUT],
            'curl' => [null, 'cURL error 7: Failed to connect to e-factura.sunat.gob.pe: Connection refused', SunatResilience::KIND_CURL],
            'bad gateway' => ['HTTP', 'Bad Request', SunatResilience::KIND_HTTP],
            'servicio no disponible' => ['0109', 'El servicio no se encuentra disponible', SunatResilience::KIND_SUNAT_UNAVAILABLE],
            'codigo soap' => ['1033', 'Error interno del servicio', SunatResilience::KIND_SUNAT_UNAVAILABLE],
            'sin detalle' => [null, '', SunatResilience::KIND_UNKNOWN],
        ];
    }

    #[DataProvider('fallasReintentables')]
    public function test_las_fallas_de_red_son_reintentables(?string $code, string $message, string $expectedKind): void
    {
        $result = $this->resilience()->classifyError($code, $message);

        $this->assertTrue($result['retryable'], 'Se esperaba reintento para: '.$message);
        $this->assertSame($expectedKind, $result['kind']);
    }

    public function test_un_rechazo_de_negocio_no_es_reintentable(): void
    {
        $result = $this->resilience()->classifyError('2017', 'El número de documento de identidad del receptor debe ser RUC.');

        $this->assertFalse($result['retryable']);
        $this->assertSame(SunatResilience::KIND_BUSINESS, $result['kind']);
    }

    public function test_0109_es_reintentable(): void
    {
        $result = $this->resilience()->classifyError('0109', 'SUNAT no disponible');

        $this->assertTrue($result['retryable']);
    }

    public function test_el_mensaje_amigable_no_expone_errores_tecnicos(): void
    {
        $mensaje = SunatResilience::FRIENDLY_QUEUED;

        $this->assertStringContainsString('se registró correctamente', $mensaje);
        $this->assertStringContainsString('cola', $mensaje);
        $this->assertStringNotContainsString('cURL', $mensaje);
        $this->assertStringNotContainsString('Exception', $mensaje);
    }

    public function test_el_backoff_crece_y_se_acota(): void
    {
        $resilience = $this->resilience();

        $this->assertSame(5, $resilience->backoffMinutes(1));
        $this->assertSame(10, $resilience->backoffMinutes(2));
        $this->assertSame(20, $resilience->backoffMinutes(3));
        $this->assertSame(240, $resilience->backoffMinutes(20));
    }
}
