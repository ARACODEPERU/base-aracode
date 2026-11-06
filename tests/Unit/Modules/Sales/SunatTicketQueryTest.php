<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Sunat\SunatFileStorage;
use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Helpers\Invoice\Sunat\SunatTicketQuery;
use PHPUnit\Framework\TestCase;

class SunatTicketQueryTest extends TestCase
{
    private function service(): SunatTicketQuery
    {
        return new SunatTicketQuery(
            null,
            new SunatFileStorage(sys_get_temp_dir().DIRECTORY_SEPARATOR.'sunat-ticket-test'),
            new SunatResilience
        );
    }

    private function cdr(int|string $code, string $description = 'Constancia'): object
    {
        return new class($code, $description)
        {
            public function __construct(private $code, private string $description) {}

            public function getCode()
            {
                return $this->code;
            }

            public function getDescription(): string
            {
                return $this->description;
            }

            public function getNotes(): array
            {
                return ['nota'];
            }
        };
    }

    private function response(bool $success, ?object $cdr = null, ?string $errorCode = null, ?string $errorMessage = null, ?string $zip = null): object
    {
        return new class($success, $cdr, $errorCode, $errorMessage, $zip)
        {
            public function __construct(
                private bool $success,
                private ?object $cdr,
                private ?string $errorCode,
                private ?string $errorMessage,
                private ?string $zip,
            ) {}

            public function isSuccess(): bool
            {
                return $this->success;
            }

            public function getCdrResponse(): ?object
            {
                return $this->cdr;
            }

            public function getError(): ?object
            {
                if ($this->errorCode === null && $this->errorMessage === null) {
                    return null;
                }

                return new class($this->errorCode, $this->errorMessage)
                {
                    public function __construct(private ?string $code, private ?string $message) {}

                    public function getCode(): ?string
                    {
                        return $this->code;
                    }

                    public function getMessage(): ?string
                    {
                        return $this->message;
                    }
                };
            }

            public function getCdrZip(): ?string
            {
                return $this->zip;
            }
        };
    }

    public function test_cdr_codigo_cero_es_aceptado(): void
    {
        $result = $this->service()->normalize(
            $this->response(true, $this->cdr(0, 'La boleta ha sido aceptada'), null, null, 'PK'),
            'TICKET-1'
        );

        $this->assertSame(SunatTicketQuery::ACEPTADO, $result['state']);
        $this->assertSame('0', $result['cdr_code']);
        $this->assertSame('La boleta ha sido aceptada', $result['cdr_description']);
        $this->assertSame(['nota'], $result['notes']);
        $this->assertSame('PK', $result['cdr_zip']);
        $this->assertFalse($result['retryable']);
        $this->assertSame('TICKET-1', $result['ticket']);
    }

    public function test_cdr_con_codigo_distinto_es_rechazado(): void
    {
        $result = $this->service()->normalize(
            $this->response(true, $this->cdr(2326, 'El documento no existe')),
            'TICKET-2'
        );

        $this->assertSame(SunatTicketQuery::RECHAZADO, $result['state']);
        $this->assertSame('2326', $result['code']);
        $this->assertSame('El documento no existe', $result['message']);
    }

    public function test_sunat_sin_cdr_es_en_proceso(): void
    {
        $result = $this->service()->normalize($this->response(true, null), 'TICKET-3');

        $this->assertSame(SunatTicketQuery::EN_PROCESO, $result['state']);
        $this->assertTrue($result['retryable']);
    }

    public function test_error_98_es_en_proceso(): void
    {
        $result = $this->service()->normalize(
            $this->response(false, null, '0098', 'El ticket no ha terminado su procesamiento'),
            'TICKET-4'
        );

        $this->assertSame(SunatTicketQuery::EN_PROCESO, $result['state']);
        $this->assertTrue($result['retryable']);
    }

    public function test_ticket_inexistente_0127(): void
    {
        $result = $this->service()->normalize(
            $this->response(false, null, '0127', 'El ticket no existe'),
            'TICKET-5'
        );

        $this->assertSame(SunatTicketQuery::NO_EXISTE, $result['state']);
        $this->assertFalse($result['retryable']);
    }

    public function test_error_de_red_no_es_un_estado_confirmado(): void
    {
        $result = $this->service()->normalize(
            $this->response(false, null, null, 'cURL error 28: Operation timed out'),
            'TICKET-6'
        );

        $this->assertSame(SunatTicketQuery::ERROR, $result['state']);
        $this->assertTrue($result['retryable']);
    }

    public function test_ticket_vacio_no_consulta_la_red(): void
    {
        $result = $this->service()->query('  ');

        $this->assertSame(SunatTicketQuery::ERROR, $result['state']);
        $this->assertStringContainsString('vacío', $result['message']);
    }
}
