<?php

namespace Tests\Feature\Sunat;

use App\Helpers\Invoice\Documents\Factura;
use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Helpers\Invoice\Sunat\SunatSendOutcome;
use App\Helpers\Invoice\Util;
use App\Models\Company;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\Error;
use Greenter\Model\Sale\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Punta a punta del envío: ruta -> controlador -> Factura::create -> resiliencia
 * -> persistencia. Solo se simula la respuesta externa de SUNAT.
 */
class SunatSendFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_1033_deja_el_comprobante_posiblemente_registrado_sin_encolarse(): void
    {
        $this->empresaDemo();
        $this->reiniciarUtil();

        $user = User::factory()->create();
        $documento = $this->documento($user);

        $this->app->instance(Factura::class, $this->facturaSinPayload());
        $this->app->instance(SunatResilience::class, $this->resilienciaConError(
            '1033',
            'El comprobante fue registrado previamente con otros datos'
        ));

        $response = $this->actingAs($user)->get(route('saledocuments_send', [$documento->id, '01']));

        $response->assertOk()->assertJson([
            'success' => false,
            'queued' => false,
            'duplicate' => true,
            'manual' => false,
            'code' => '1033',
        ]);

        $this->assertStringContainsString('posiblemente', $response->json('message'));

        $documento->refresh();

        $this->assertSame('Posiblemente registrado', $documento->invoice_status);
        $this->assertSame(1, (int) $documento->status);
        $this->assertStringContainsString('posiblemente', (string) $documento->invoice_response_description);
        $this->assertSame('revisar', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertSame('duplicado', $documento->sunat_response_kind);
        $this->assertStringContainsString('1033', (string) $documento->sunat_last_error);
        $this->assertSame(0, (int) $documento->sunat_retry_attempts);

        $this->assertSame(0, Artisan::call('sunat:retry-queue', ['--dry-run' => true]));
        $this->assertStringContainsString('No hay comprobantes pendientes', Artisan::output());
    }

    public function test_un_fallo_de_sunat_queda_pendiente_sin_encolarse_y_muestra_el_error_original(): void
    {
        $this->empresaDemo();
        $this->reiniciarUtil();

        $user = User::factory()->create();
        $documento = $this->documento($user);

        $this->app->instance(Factura::class, $this->facturaSinPayload());
        $this->app->instance(SunatResilience::class, $this->resilienciaConError(
            '0109',
            'El servicio no se encuentra disponible'
        ));

        $response = $this->actingAs($user)->get(route('saledocuments_send', [$documento->id, '01']));

        $response->assertOk()->assertJson([
            'success' => false,
            'queued' => false,
            'manual' => true,
            'code' => '0109',
        ]);

        $this->assertStringContainsString('0109', $response->json('message'));
        $this->assertStringContainsString('no se encuentra disponible', $response->json('message'));

        $documento->refresh();

        $this->assertSame('Pendiente', $documento->invoice_status);
        $this->assertSame(1, (int) $documento->status);
        $this->assertStringContainsString('0109', (string) $documento->invoice_response_description);
        $this->assertSame('manual', $documento->sunat_send_status);
        $this->assertNull($documento->sunat_next_retry_at);
        $this->assertSame('sunat_no_disponible', $documento->sunat_response_kind);
        $this->assertStringContainsString('0109', (string) $documento->sunat_last_error);
        $this->assertSame(0, (int) $documento->sunat_retry_attempts);

        $this->assertSame(0, Artisan::call('sunat:retry-queue', ['--dry-run' => true]));
        $this->assertStringContainsString('No hay comprobantes pendientes', Artisan::output());
    }

    protected function tearDown(): void
    {
        $this->reiniciarUtil();

        parent::tearDown();
    }

    private function empresaDemo(): void
    {
        Company::create([
            'ruc' => '20123456789',
            'name' => 'EMPRESA DE PRUEBA SAC',
            'business_name' => 'EMPRESA DE PRUEBA SAC',
            'mode' => 'demo',
        ]);
    }

    /**
     * Util guarda su instancia en una propiedad estática; se reinicia para que
     * tome la empresa creada dentro de esta prueba.
     */
    private function reiniciarUtil(): void
    {
        $reflexion = new \ReflectionClass(Util::class);
        $propiedad = $reflexion->getProperty('current');
        $propiedad->setAccessible(true);
        $propiedad->setValue(null, null);
    }

    /**
     * Evita construir el XML real: estas pruebas verifican el manejo del
     * resultado de SUNAT, no la generación del comprobante.
     */
    private function facturaSinPayload(): Factura
    {
        return new class extends Factura {
            public function setDocument($document, $tipDet = '022', $ipMeP = '001')
            {
                return new class extends Invoice {
                    public function getName(): string
                    {
                        return 'F001-1';
                    }
                };
            }
        };
    }

    private function resilienciaConError(string $codigo, string $mensaje): SunatResilience
    {
        $resultado = new class($codigo, $mensaje) {
            public function __construct(private string $codigo, private string $mensaje)
            {
            }

            public function isSuccess(): bool
            {
                return false;
            }

            public function getError(): Error
            {
                return new Error($this->codigo, $this->mensaje);
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

        return new class($resultado) extends SunatResilience {
            public function __construct(private object $resultado)
            {
            }

            public function send(Util $util, DocumentInterface $document): array
            {
                return [
                    'outcome' => SunatSendOutcome::fromResult($this->resultado),
                    'xml' => null,
                    'see' => null,
                ];
            }
        };
    }

    private function documento(User $user): SaleDocument
    {
        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => 1,
            'local_id' => 1,
            'total' => 100,
            'advancement' => 100,
            'total_discount' => 0,
            'sale_date' => now()->toDateString(),
            'physical' => 2,
            'status' => 1,
        ]);

        return SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => '1',
            'invoice_type_doc' => '01',
            'invoice_serie' => 'F001',
            'invoice_correlative' => '1',
            'invoice_type_currency' => 'PEN',
            'invoice_broadcast_date' => now()->toDateString(),
            'client_type_doc' => '6',
            'client_number' => '20123456789',
            'client_rzn_social' => 'CLIENTE PRUEBA SAC',
            'invoice_status' => 'Pendiente',
            'status' => 1,
            'overall_total' => 100,
        ]);
    }
}
