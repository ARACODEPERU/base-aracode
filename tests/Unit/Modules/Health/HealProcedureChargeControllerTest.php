<?php

namespace Tests\Unit\Modules\Health;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Health\Entities\HealPatientCharge;
use Modules\Health\Http\Controllers\HealProcedureChargeController;
use Modules\Health\Support\BillingOptions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Tests\Unit\Modules\Health\Concerns\BuildsPatientChargeSchema;

/**
 * Contrato de la pantalla Procedimientos/cobros.
 *
 * Se invocan las acciones del controlador directamente (el permiso y la sesión
 * de la ruta se prueban mirando el middleware registrado). Lo que se cuida aquí
 * es que un cobro no se anule con comprobante vigente, que no se facture dos
 * veces, que el doctor solo vea lo suyo y que un `per_page` manipulado no
 * reviente el listado.
 */
class HealProcedureChargeControllerTest extends TestCase
{
    use BuildsPatientChargeSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropPatientChargeSchema();
        $this->createPatientChargeSchema();
    }

    protected function tearDown(): void
    {
        $this->dropPatientChargeSchema();

        parent::tearDown();
    }

    private function controller(): HealProcedureChargeController
    {
        return new HealProcedureChargeController();
    }

    /**
     * Props de la pantalla, leyendo la respuesta Inertia como JSON.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function indexProps(array $query = []): array
    {
        $request = Request::create('/health/procedures-charges', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');

        $response = $this->controller()->index($request)->toResponse($request);
        $page = json_decode((string) $response->getContent(), true);

        return $page['props'];
    }

    public function test_las_rutas_de_cobros_exigen_el_permiso_de_la_pantalla(): void
    {
        $list = Route::getRoutes()->getByName('heal_procedure_charges_list');
        $this->assertNotNull($list, 'la ruta del listado debe existir');
        $this->assertContains('permission:heal_atenciones_listado', $list->gatherMiddleware());

        // El catálogo cambia precios: además del permiso de la pantalla exige el
        // de configuración del módulo.
        $update = Route::getRoutes()->getByName('heal_procedures_update');
        $this->assertContains('permission:heal_atenciones_listado', $update->gatherMiddleware());
        $this->assertContains('permission:heal_configuracion', $update->gatherMiddleware());
    }

    public function test_un_per_page_manipulado_no_rompe_el_listado(): void
    {
        $this->actingAs($this->adminUser());
        $this->chargeFixture($this->patientFixture());

        // Un valor basura cae al valor por defecto en vez de reventar (antes
        // era un 500 por la division del paginador).
        $this->assertSame(10, $this->indexProps(['per_page' => 'abc'])['charges']['per_page']);
        $this->assertSame(10, $this->indexProps(['per_page' => '0'])['charges']['per_page']);
        $this->assertSame(100, $this->indexProps(['per_page' => '5000'])['charges']['per_page']);
        $this->assertSame(20, $this->indexProps(['per_page' => '20'])['charges']['per_page']);
    }

    public function test_el_administrador_ve_los_cobros_de_todos_los_doctores(): void
    {
        $doctorA = $this->doctorUser('Ana');
        $doctorB = $this->doctorUser('Beto');
        $patient = $this->patientFixture();

        $this->chargeFixture($patient, ['doctor_id' => $doctorA['doctor']->id]);
        $this->chargeFixture($patient, ['doctor_id' => $doctorB['doctor']->id]);

        $this->actingAs($this->adminUser());

        $this->assertSame(2, $this->indexProps()['charges']['total']);
    }

    public function test_el_doctor_solo_ve_sus_propios_cobros(): void
    {
        $doctorA = $this->doctorUser('Ana');
        $doctorB = $this->doctorUser('Beto');
        $patient = $this->patientFixture();

        $this->chargeFixture($patient, ['doctor_id' => $doctorA['doctor']->id]);
        $this->chargeFixture($patient, ['doctor_id' => $doctorB['doctor']->id]);

        $this->actingAs($doctorA['user']);

        $props = $this->indexProps();

        $this->assertSame(1, $props['charges']['total']);
        $this->assertSame($doctorA['doctor']->id, $props['charges']['data'][0]['doctor_id']);
    }

    public function test_el_usuario_sin_doctor_vinculado_no_entra(): void
    {
        // Usuario con el permiso del menú pero sin registro de doctor: la
        // pantalla responde 403 en vez de mostrar cobros de otros.
        $this->actingAs(\App\Models\User::create([
            'name' => 'Recepción',
            'email' => 'recepcion@clinica.test',
            'password' => 'secreto-de-prueba',
        ]));

        $this->expectException(HttpException::class);

        $this->indexProps();
    }

    public function test_no_se_anula_un_cobro_con_comprobante_vigente(): void
    {
        $this->actingAs($this->adminUser());
        $charge = $this->chargeFixture($this->patientFixture(), [
            'status' => 'paid',
            'sale_document_id' => $this->saleDocumentFixture(0),
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->controller()->updateChargeStatus($this->cancelRequest(), $charge);
        } finally {
            $this->assertSame('paid', $charge->fresh()->status);
        }
    }

    public function test_se_anula_cuando_el_comprobante_ya_esta_anulado(): void
    {
        $this->actingAs($this->adminUser());
        $charge = $this->chargeFixture($this->patientFixture(), [
            'status' => 'paid',
            'sale_document_id' => $this->saleDocumentFixture(3),
        ]);

        $this->controller()->updateChargeStatus($this->cancelRequest(), $charge);

        $this->assertSame('cancelled', $charge->fresh()->status);
    }

    public function test_la_anulacion_con_comprobante_puede_habilitarse_en_configuracion(): void
    {
        $this->actingAs($this->adminUser());
        BillingOptions::save(['allow_cancel_with_document' => true]);

        $charge = $this->chargeFixture($this->patientFixture(), [
            'status' => 'paid',
            'sale_document_id' => $this->saleDocumentFixture(0),
        ]);

        $this->controller()->updateChargeStatus($this->cancelRequest(), $charge);

        $this->assertSame('cancelled', $charge->fresh()->status);
    }

    public function test_el_doctor_no_anula_cobros_de_otro_doctor(): void
    {
        $doctorA = $this->doctorUser('Ana');
        $doctorB = $this->doctorUser('Beto');

        $charge = $this->chargeFixture($this->patientFixture(), ['doctor_id' => $doctorB['doctor']->id]);

        $this->actingAs($doctorA['user']);

        $this->expectException(HttpException::class);

        try {
            $this->controller()->updateChargeStatus($this->cancelRequest(), $charge);
        } finally {
            $this->assertSame('pending', $charge->fresh()->status);
        }
    }

    public function test_el_alta_individual_rechaza_una_atencion_de_otro_paciente(): void
    {
        $this->actingAs($this->adminUser());

        $patientA = $this->patientFixture('Ana Pérez');
        $patientB = $this->patientFixture('Beto Ruiz');
        $attentionB = $this->attentionFixture($patientB);
        $procedure = $this->procedureFixture();

        $this->expectException(ValidationException::class);

        try {
            $this->controller()->storeCharge(Request::create('/health/charges/store', 'POST', [
                'patient_id' => $patientA->id,
                'attention_id' => $attentionB->id,
                'procedure_id' => $procedure->id,
                'price' => 50,
                'quantity' => 1,
            ]));
        } finally {
            $this->assertSame(0, HealPatientCharge::count());
        }
    }

    public function test_el_alta_multiple_rechaza_procedimientos_inactivos(): void
    {
        $this->actingAs($this->adminUser());
        $patient = $this->patientFixture();
        $inactive = $this->procedureFixture(['name' => 'Extracción dental', 'is_active' => false]);

        $this->expectException(ValidationException::class);

        try {
            $this->controller()->storeManyCharges(Request::create('/health/charges/store-many', 'POST', [
                'patient_id' => $patient->id,
                'charges' => [
                    ['procedure_id' => $inactive->id, 'price' => 120, 'quantity' => 1],
                ],
            ]));
        } finally {
            $this->assertSame(0, HealPatientCharge::count());
        }
    }

    public function test_preparar_sales_reserva_los_cobros(): void
    {
        $this->actingAs($this->adminUser());
        $charge = $this->chargeFixture($this->patientFixture());

        $this->controller()->prepareSalesReview($this->salesRequest([$charge->id]));

        $charge->refresh();

        $this->assertSame('billed', $charge->status);
        $this->assertNotNull($charge->billed_at);
        $this->assertSame([$charge->id], session('health_billing_draft')['charge_ids']);
    }

    public function test_un_borrador_nuevo_libera_el_anterior(): void
    {
        $this->actingAs($this->adminUser());
        $patient = $this->patientFixture();
        $first = $this->chargeFixture($patient);
        $second = $this->chargeFixture($patient);

        $this->controller()->prepareSalesReview($this->salesRequest([$first->id]));
        $this->controller()->prepareSalesReview($this->salesRequest([$second->id]));

        $this->assertSame('pending', $first->fresh()->status);
        $this->assertSame('billed', $second->fresh()->status);
    }

    public function test_las_reservas_vencidas_vuelven_a_la_lista(): void
    {
        $this->actingAs($this->adminUser());
        $patient = $this->patientFixture();
        $expired = $this->chargeFixture($patient, ['status' => 'billed', 'billed_at' => now()->subMinutes(31)]);
        $current = $this->chargeFixture($patient, ['status' => 'billed', 'billed_at' => now()]);

        $this->indexProps();

        $this->assertSame('pending', $expired->fresh()->status);
        $this->assertNull($expired->fresh()->billed_at);
        $this->assertSame('billed', $current->fresh()->status);
    }

    public function test_liberar_devuelve_el_cobro_a_pendientes(): void
    {
        $this->actingAs($this->adminUser());
        $charge = $this->chargeFixture($this->patientFixture(), ['status' => 'billed', 'billed_at' => now()]);

        $this->controller()->releaseCharge($charge);

        $this->assertSame('pending', $charge->fresh()->status);
        $this->assertNull($charge->fresh()->billed_at);
    }

    public function test_no_se_libera_un_cobro_que_no_esta_reservado(): void
    {
        $this->actingAs($this->adminUser());
        $charge = $this->chargeFixture($this->patientFixture());

        $this->controller()->releaseCharge($charge);

        $this->assertSame('pending', $charge->fresh()->status);
    }

    public function test_el_documento_de_venta_usa_lo_configurado_en_salud(): void
    {
        $this->actingAs($this->adminUser());
        BillingOptions::save([
            'igv_affectation' => '20',
            'unit_type' => 'NIU',
            'billing_description' => 'Cobros de Salud',
        ]);

        $charge = $this->chargeFixture($this->patientFixture());

        $this->controller()->prepareSalesReview($this->salesRequest([$charge->id]));

        $draft = session('health_billing_draft');

        $this->assertSame('20', $draft['items'][0]['afe_igv']);
        $this->assertSame('NIU', $draft['items'][0]['unit_type']);
        $this->assertSame('Cobros de Salud', $draft['additional_description']);
    }

    private function cancelRequest(): Request
    {
        return Request::create('/health/charges/1/status', 'PUT', ['status' => 'cancelled']);
    }

    /**
     * @param array<int, int> $chargeIds
     */
    private function salesRequest(array $chargeIds): Request
    {
        return Request::create('/health/charges/prepare-sales-review', 'POST', ['charge_ids' => $chargeIds]);
    }
}
