<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Helpers\Invoice\Sunat\SunatSendOutcome;
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
            'bad gateway' => ['HTTP', '502 Bad Gateway', SunatResilience::KIND_HTTP],
            'servicio no disponible' => ['0109', 'El servicio no se encuentra disponible', SunatResilience::KIND_SUNAT_UNAVAILABLE],
            'codigo soap' => ['132', 'El sistema no puede responder su solicitud. (No se pudo grabar escribir en el archivo zip)', SunatResilience::KIND_SUNAT_UNAVAILABLE],
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

    /**
     * El 1033 significa que el comprobante ya fue registrado con otros datos:
     * reintentarlo solo repite el rechazo, no es una caída de SUNAT.
     */
    public function test_el_1033_es_un_rechazo_de_negocio_y_no_se_reintenta(): void
    {
        $result = $this->resilience()->classifyError('1033', 'El comprobante fue registrado previamente con otros datos');

        $this->assertFalse($result['retryable']);
        $this->assertSame(SunatResilience::KIND_BUSINESS, $result['kind']);
    }

    public function test_el_1033_se_informa_como_posiblemente_registrado_y_no_se_reintenta(): void
    {
        $resultado = new class
        {
            public function isSuccess(): bool
            {
                return false;
            }

            public function getError(): \Greenter\Model\Response\Error
            {
                return new \Greenter\Model\Response\Error(
                    '1033',
                    'El comprobante fue registrado previamente con otros datos'
                );
            }

            public function getCdrResponse()
            {
                return null;
            }

            public function getCdrZip()
            {
                return null;
            }

            public function getTicket()
            {
                return null;
            }
        };

        $failure = $this->resilience()->describeFailure(SunatSendOutcome::fromResult($resultado));

        $this->assertFalse($failure['retryable']);
        $this->assertSame(SunatResilience::KIND_DUPLICATE, $failure['kind']);
        $this->assertStringContainsString('posiblemente ya fue registrado', $failure['friendly']);
        $this->assertStringContainsString('No se reenviará automáticamente', $failure['friendly']);
        $this->assertStringNotContainsString('SUNAT no está respondiendo', $failure['friendly']);
    }

    public function test_solo_las_fallas_de_red_o_indisponibilidad_dicen_que_sunat_no_responde(): void
    {
        foreach ([
            [SunatResilience::KIND_TIMEOUT, 'cURL error 28: timed out'],
            [SunatResilience::KIND_CURL, 'Connection refused'],
            [SunatResilience::KIND_SUNAT_UNAVAILABLE, 'El servicio no se encuentra disponible'],
            [SunatResilience::KIND_HTTP, 'HTTP 500 Internal Server Error'],
            [SunatResilience::KIND_HTTP, 'HTTP 503 Service Unavailable'],
        ] as [$kind, $technical]) {
            $failure = $this->resilience()->describeFailure($this->exceptionOutcome($kind, $technical));

            $this->assertSame(SunatResilience::FRIENDLY_MANUAL, $failure['friendly'], $kind);
        }

        foreach ([
            [SunatResilience::KIND_SOAP, 'SoapFault: Error interno'],
            [SunatResilience::KIND_HTTP, 'HTTP 400 Bad Request'],
            [SunatResilience::KIND_UNKNOWN, 'Error no identificado'],
        ] as [$kind, $technical]) {
            $failure = $this->resilience()->describeFailure($this->exceptionOutcome($kind, $technical));

            $this->assertSame(SunatResilience::FRIENDLY_UNCONFIRMED, $failure['friendly'], $kind);
            $this->assertStringNotContainsString('SUNAT no está respondiendo', $failure['friendly'], $kind);
        }
    }

    private function exceptionOutcome(string $kind, string $technical): SunatSendOutcome
    {
        return SunatSendOutcome::fromException(
            new \Exception($technical),
            [
                'retryable' => true,
                'kind' => $kind,
                'technical' => $technical,
                'code' => SunatResilience::CODE_CONNECTION,
            ]
        );
    }

    public function test_un_certificado_ausente_avisa_que_el_problema_es_local(): void
    {
        $outcome = SunatSendOutcome::fromException(
            new \Exception('No se pudo cargar el certificado'),
            [
                'retryable' => true,
                'kind' => SunatResilience::KIND_CERTIFICATE,
                'technical' => 'Exception: No se pudo cargar el certificado',
                'code' => SunatResilience::CODE_CONNECTION,
            ]
        );

        $failure = $this->resilience()->describeFailure($outcome);

        $this->assertTrue($failure['retryable']);
        $this->assertStringContainsString('no es SUNAT', $failure['friendly']);
        $this->assertStringContainsString('certificado', $failure['friendly']);
        $this->assertStringNotContainsString('SUNAT no está respondiendo', $failure['friendly']);
    }

    public function test_0109_es_reintentable(): void
    {
        $result = $this->resilience()->classifyError('0109', 'SUNAT no disponible');

        $this->assertTrue($result['retryable']);
    }

    public function test_el_mensaje_amigable_no_expone_errores_tecnicos(): void
    {
        $mensaje = SunatResilience::FRIENDLY_MANUAL;

        $this->assertStringContainsString('se registró correctamente', $mensaje);
        $this->assertStringContainsString('manualmente', $mensaje);
        $this->assertStringNotContainsString('cURL', $mensaje);
        $this->assertStringNotContainsString('Exception', $mensaje);
    }
}
