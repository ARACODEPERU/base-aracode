<?php

namespace Tests\Unit\Modules\Commercial;

use App\Models\CompanyBilletera;
use App\Models\Person;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Commercial\Emails\CommercialNegotiationDocumentMail;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Commercial\Entities\CommercialNegotiationItem;
use Modules\Commercial\Http\Controllers\CommercialNegotiationProcessController;
use Modules\Commercial\Support\NegotiationWebhookPayload;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Modules\Commercial\Concerns\BuildsCommercialNegotiationSchema;

/**
 * Negociacion gratuita: se entregan cursos o suscripciones que tienen costo, pero el
 * precio acordado es 0, asi que el cliente solo completa sus datos.
 *
 * Lo que se verifica aqui:
 *   - al crear la negociacion, el servidor guarda el medio de pago 'gratis' y descarta
 *     cualquier dato de cobro (cuotas, pago inicial, billeteras, enlace y plazo), aunque
 *     llegue en la peticion;
 *   - una negociacion con cobro sigue exigiendo medio de pago y billetera digital, para
 *     que la regla de "gratis" no haya relajado el alta normal;
 *   - el enlace publico de una negociacion gratuita acepta el envio sin voucher, que
 *     sigue siendo obligatorio cuando hay un pago que acreditar;
 *   - el proceso de aprobacion del administrador recorre todos sus pasos sin comprobante:
 *     el cliente queda matriculado sin deuda, el correo sale sin comprobante y la
 *     negociacion termina completada.
 */
class NegotiationFreeTest extends TestCase
{
    use BuildsCommercialNegotiationSchema;

    private const PASSWORD = 'secreto-comercial';

    /**
     * Migraciones adicionales que la prueba necesita: las columnas que escribe el alta
     * (correo, vigencia del enlace, Mercado Pago, progreso) y el origen del item.
     */
    private const EXTRA_MIGRATIONS = [
        'Modules/Commercial/Database/Migrations/2026_08_17_000000_add_email_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_18_000000_add_email_sent_at_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_18_000001_add_process_progress_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_25_000000_add_mercado_pago_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_25_100000_add_link_expiration_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_25_200000_add_entity_name_product_to_commercial_negotiation_items_table.php',
        // La boleta a nombre de una tercera persona viaja en el mismo envio del formulario
        // publico, asi que la tabla de comprobantes necesita esas columnas.
        'Modules/Commercial/Database/Migrations/2026_09_15_000002_add_boleta_third_party_to_commercial_negotiation_invoices_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropCommercialSchema();
        $this->createCommercialSchema();
        $this->createNegotiationExtraSchema();
        $this->createProcessSchema();
        $this->createRoles();
    }

    protected function tearDown(): void
    {
        foreach (['aca_student_subscriptions', 'aca_subscription_types', 'aca_cap_registrations', 'aca_courses', 'identity_document_type', 'parameters'] as $table) {
            Schema::dropIfExists($table);
        }

        $this->dropCommercialSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Alta de la negociacion gratuita
    // -----------------------------------------------------------------

    public function test_la_negociacion_gratuita_se_guarda_con_medio_de_pago_gratis_y_sin_datos_de_cobro(): void
    {
        $advisor = $this->advisor();

        $billetera = CompanyBilletera::create([
            'account_name' => 'Empresa de prueba',
            'account_number' => '999888777',
            'status' => true,
        ]);

        $this->actingAs($advisor)
            ->post(route('comm_negotiations_store'), [
                'title' => 'Inscripcion gratuita de prueba',
                'total_price' => 0,
                'currency' => 'PEN',
                'payment_type' => 'installments',
                'initial_amount' => 500,
                'single_payment_days' => 15,
                'link_days' => 2,
                'schedule' => [
                    ['due_date' => now()->addMonth()->toDateString(), 'amount' => 700],
                    ['due_date' => now()->addMonths(2)->toDateString(), 'amount' => 500],
                ],
                'payment_method' => 'billetera_digital',
                'company_billetera_ids' => [$billetera->id],
                'payment_link' => 'https://pagos.example.com/checkout',
                'items' => [
                    ['item_type' => 'course', 'item_id' => null, 'title' => 'Curso de prueba', 'price' => 1200],
                ],
            ])
            ->assertRedirect();

        $negotiation = CommercialNegotiation::latest('id')->firstOrFail();

        $this->assertSame(0.0, (float) $negotiation->total_price);
        $this->assertSame('gratis', $negotiation->payment_method, 'El medio de pago queda identificado como gratis.');
        $this->assertSame('single', $negotiation->payment_type, 'Una negociacion gratuita no se cobra en cuotas.');
        $this->assertNull($negotiation->schedule);
        $this->assertNull($negotiation->initial_amount);
        $this->assertNull($negotiation->single_payment_days);
        $this->assertNull($negotiation->payment_link);
        $this->assertDatabaseMissing('commercial_negotiation_company_billetera', [
            'negotiation_id' => $negotiation->id,
        ]);

        // Los cursos siguen registrados con su costo de catalogo: lo que cambio es el
        // precio acordado de la negociacion, no lo que el cliente recibe.
        $negotiation->load('items');

        $this->assertCount(1, $negotiation->items);
        $this->assertSame('Curso de prueba', $negotiation->items->first()->title);
        $this->assertSame(1200.0, (float) $negotiation->items->first()->price);
    }

    public function test_una_negociacion_con_cobro_sigue_exigiendo_medio_de_pago_y_billetera(): void
    {
        $advisor = $this->advisor();

        $this->actingAs($advisor)
            ->postJson(route('comm_negotiations_store'), [
                'title' => 'Negociacion con cobro',
                'total_price' => 1200,
                'currency' => 'PEN',
                'payment_type' => 'single',
                'items' => [
                    ['item_type' => 'course', 'item_id' => null, 'title' => 'Curso de prueba', 'price' => 1200],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_method');

        // Billetera digital sin ninguna billetera elegida.
        $this->actingAs($advisor)
            ->postJson(route('comm_negotiations_store'), [
                'title' => 'Negociacion con cobro',
                'total_price' => 1200,
                'currency' => 'PEN',
                'payment_type' => 'single',
                'payment_method' => 'billetera_digital',
                'items' => [
                    ['item_type' => 'course', 'item_id' => null, 'title' => 'Curso de prueba', 'price' => 1200],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_billetera_ids');

        // 'gratis' solo identifica a una negociacion sin cobro: no sirve como medio de pago
        // de una negociacion con precio.
        $this->actingAs($advisor)
            ->postJson(route('comm_negotiations_store'), [
                'title' => 'Negociacion con cobro',
                'total_price' => 1200,
                'currency' => 'PEN',
                'payment_type' => 'single',
                'payment_method' => 'gratis',
                'items' => [
                    ['item_type' => 'course', 'item_id' => null, 'title' => 'Curso de prueba', 'price' => 1200],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_method');

        $this->assertDatabaseCount('commercial_negotiations', 0);
    }

    // -----------------------------------------------------------------
    // Enlace publico del cliente
    // -----------------------------------------------------------------

    public function test_el_cliente_de_una_negociacion_gratuita_envia_sus_datos_sin_voucher(): void
    {
        $negotiation = $this->negotiation(0, 'gratis');

        $respuesta = $this->postJson(
            route('comm_negotiations_public_store', $negotiation->token),
            $this->publicPayload()
        );

        // La validacion corre (faltan los datos del cliente) pero nunca pide el voucher.
        $respuesta->assertStatus(422)->assertJsonValidationErrors(['number', 'names', 'father_lastname', 'mother_lastname']);
        $respuesta->assertJsonMissingValidationErrors('voucher');
    }

    public function test_el_cliente_completa_el_envio_de_una_negociacion_gratuita(): void
    {
        Mail::fake();

        $negotiation = $this->negotiation(0, 'gratis');

        $respuesta = $this->postJson(
            route('comm_negotiations_public_store', $negotiation->token),
            $this->fullPublicPayload()
        );

        // El envio completo tiene que pasar sin voucher: es lo que hace el cliente cuando
        // confirma el resumen y presiona "Si, estoy seguro y de acuerdo".
        $respuesta->assertStatus(302);
        $respuesta->assertSessionHasNoErrors();

        $negotiation->refresh();

        $this->assertSame('confirmada', $negotiation->status);
        $this->assertNull($negotiation->voucher_path, 'Sin pago no hay voucher que guardar.');
        $this->assertNotNull($negotiation->client_id);
        $this->assertDatabaseMissing('commercial_negotiation_invoices', ['negotiation_id' => $negotiation->id]);
        $this->assertDatabaseHas('people', ['number' => '70123456', 'email' => 'cliente.nuevo@example.com']);
    }

    public function test_el_envio_gratuito_no_falla_por_el_campo_del_voucher_vacio(): void
    {
        Mail::fake();

        $negotiation = $this->negotiation(0, 'gratis');

        // En el navegador el bloque del voucher esta oculto, pero el campo viaja igual:
        // Inertia manda los null del formulario como cadena vacia, asi que la clave
        // 'voucher' llega al servidor aunque el cliente no adjunte nada. El envio tiene
        // que pasar; si la validacion la rechaza, el cliente no ve ningun mensaje
        // (el bloque que mostraria el error esta oculto) y el formulario se queda igual.
        $respuesta = $this->post(
            route('comm_negotiations_public_store', $negotiation->token),
            array_merge($this->fullPublicPayload(), ['voucher' => ''])
        );

        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertStatus(302);

        $negotiation->refresh();

        $this->assertSame('confirmada', $negotiation->status);
        $this->assertNull($negotiation->voucher_path, 'Sin pago no hay voucher que guardar.');
    }

    public function test_el_cliente_de_una_negociacion_con_cobro_si_debe_adjuntar_el_voucher(): void
    {
        $negotiation = $this->negotiation(1200, 'transferencia');

        $this->postJson(
            route('comm_negotiations_public_store', $negotiation->token),
            $this->publicPayload()
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['voucher', 'number']);
    }

    public function test_el_pago_con_tarjeta_de_mercado_pago_no_falla_por_el_voucher_vacio(): void
    {
        Mail::fake();

        // Pagando con tarjeta el voucher no se pide (lo cobra Mercado Pago), pero el campo
        // del formulario viaja igual: es el mismo caso que la negociacion gratuita.
        $negotiation = $this->negotiation(1200, 'mercadopago');

        $respuesta = $this->post(
            route('comm_negotiations_public_store', $negotiation->token),
            array_merge($this->fullPublicPayload(), ['payment_option' => 'card', 'voucher' => ''])
        );

        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertStatus(302);

        $negotiation->refresh();

        $this->assertSame('confirmada', $negotiation->status);
        $this->assertNull($negotiation->voucher_path, 'Con tarjeta no hay evidencia que guardar.');
        $this->assertFalse($negotiation->client_data['payment_declared'] ?? true);
    }

    public function test_un_voucher_que_no_es_archivo_sigue_siendo_rechazado(): void
    {
        // El campo opcional acepta que no venga nada, pero no que venga cualquier cosa:
        // un envio con cobro y un valor que no es archivo se rechaza igual.
        $negotiation = $this->negotiation(1200, 'transferencia');

        $this->post(
            route('comm_negotiations_public_store', $negotiation->token),
            array_merge($this->fullPublicPayload(), ['voucher' => 'no-es-un-archivo'])
        )
            ->assertStatus(302)
            ->assertSessionHasErrors('voucher');

        $this->assertSame('pendiente', $negotiation->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Proceso de aprobacion del administrador
    // -----------------------------------------------------------------

    public function test_el_proceso_de_una_negociacion_gratuita_completa_sus_pasos_sin_comprobante(): void
    {
        Mail::fake();

        $advisor = $this->advisor();
        $negotiation = $this->negotiationWithClient(0, 'gratis');

        $controller = app(CommercialNegotiationProcessController::class);

        $this->actingAs($advisor);

        // 1) Datos del cliente en people.
        $this->assertTrue((bool) $controller->processPerson(Request::create('/'), $negotiation->id)->getData(true)['success']);

        // 2) Cuenta de usuario con rol Alumno.
        $this->assertTrue((bool) $controller->processUser(Request::create('/'), $negotiation->id)->getData(true)['success']);

        // 3) Ficha de alumno.
        $this->assertTrue((bool) $controller->processStudent(Request::create('/'), $negotiation->id)->getData(true)['success']);

        // 4) Matriculas: el curso se entrega sin deuda (monto pagado 0) y con acceso ilimitado.
        $this->assertTrue((bool) $controller->processRegistrations(Request::create('/'), $negotiation->id)->getData(true)['success']);

        $matricula = AcaCapRegistration::firstOrFail();
        $this->assertSame(0.0, (float) $matricula->amount_paid);
        $this->assertSame(0.0, (float) $matricula->advancement);
        $this->assertTrue((bool) $matricula->unlimited, 'Sin cuotas que condicionen el acceso.');
        $this->assertFalse((bool) $matricula->payment_installments);

        // 5) Suscripciones: tambien sin monto.
        $this->assertTrue((bool) $controller->processSubscriptions(Request::create('/'), $negotiation->id)->getData(true)['success']);
        $this->assertSame(0.0, (float) AcaStudentSubscription::firstOrFail()->amount_paid);

        // 6) Cuotas y comprobante: los dos pasos no aplican y no dejan venta ni documento.
        $this->assertTrue((bool) $controller->processInstallments(Request::create('/'), $negotiation->id)->getData(true)['skipped']);
        $this->assertSame('skipped', $controller->stepStatuses($negotiation)['document']);
        $this->assertTrue((bool) $controller->processDocument(Request::create('/'), $negotiation->id)->getData(true)['skipped']);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_documents', 0);

        // 7) Correo: sale sin comprobante, con las credenciales de acceso.
        $this->assertTrue((bool) $controller->processEmail(Request::create('/'), $negotiation->id)->getData(true)['success']);

        Mail::assertQueued(
            CommercialNegotiationDocumentMail::class,
            fn (CommercialNegotiationDocumentMail $mail) => $mail->document === null
                && ($mail->credentials['username'] ?? null) !== null
        );

        // 8) Payload a n8n: se arma sin comprobante (la llamada externa necesita la
        // integracion configurada, por eso aqui se verifica el contrato del payload).
        $payload = NegotiationWebhookPayload::forNegotiation($negotiation->fresh(), Person::find($negotiation->client_id));

        $this->assertSame(0.0, $payload['negociacion']['total']);
        $this->assertSame('gratis', $payload['negociacion']['metodo_pago']);
        $this->assertNull($payload['comprobante']['tipo']);
        $this->assertNull($payload['comprobante']['pdf']);
        $this->assertCount(2, $payload['items']);

        // 9) Finalizar: la negociacion queda completada.
        $this->assertTrue((bool) $controller->complete(Request::create('/'), $negotiation->id)->getData(true)['success']);
        $this->assertSame('completada', $negotiation->fresh()->status);
    }

    /**
     * Cliente existente que ya tenia el plan: el alumno ya tiene fila en
     * aca_student_subscriptions y el paso tiene que actualizarla, no duplicarla.
     *
     * AcaStudentSubscription usa clave primaria compuesta (student_id + subscription_id),
     * que Eloquent no soporta, por eso el paso no puede guardar el modelo con
     * updateOrCreate(): en la rama de actualizacion PHP lanzaba
     * "Cannot access offset of type array on array" y el paso 5 quedaba en error.
     */
    public function test_el_paso_de_suscripciones_actualiza_la_suscripcion_que_el_alumno_ya_tiene(): void
    {
        $advisor = $this->advisor();
        $negotiation = $this->negotiationWithClient(1200, 'transferencia');

        $controller = app(CommercialNegotiationProcessController::class);

        $this->actingAs($advisor);

        $controller->processStudent(Request::create('/'), $negotiation->id);

        $studentId = (int) AcaStudent::where('person_id', $negotiation->client_id)->value('id');

        // Suscripcion que el alumno ya tenia de una compra anterior y que hoy esta vencida.
        AcaStudentSubscription::create([
            'student_id' => $studentId,
            'subscription_id' => 1,
            'date_start' => '2026-01-01',
            'date_end' => '2026-01-31',
            'status' => false,
            'amount_paid' => 150,
        ]);

        $response = $controller->processSubscriptions(Request::create('/'), $negotiation->id);

        $this->assertTrue((bool) $response->getData(true)['success']);
        // Se actualiza la fila existente en lugar de duplicarla.
        $this->assertDatabaseCount('aca_student_subscriptions', 1);

        $suscripcion = AcaStudentSubscription::where('student_id', $studentId)
            ->where('subscription_id', 1)
            ->firstOrFail();

        $this->assertTrue((bool) $suscripcion->status, 'La suscripcion vencida queda reactivada.');
        $this->assertSame(300.0, (float) $suscripcion->amount_paid);
        $this->assertSame(now()->toDateString(), substr((string) $suscripcion->date_start, 0, 10));
    }

    /**
     * Cuando el item de la negociacion no trae precio propio, el monto sale del precio
     * de catalogo en soles de la suscripcion. prices ya viene decodificado por el cast
     * 'array' del modelo, asi que el paso no puede pasarlo por json_decode().
     */
    public function test_el_paso_de_suscripciones_toma_el_precio_de_catalogo_cuando_el_item_no_trae_precio(): void
    {
        $advisor = $this->advisor();
        $negotiation = $this->negotiationWithClient(1200, 'transferencia');

        CommercialNegotiationItem::where('negotiation_id', $negotiation->id)
            ->where('item_type', 'subscription')
            ->update(['price' => 0]);

        DB::table('aca_subscription_types')->where('id', 1)->update([
            'prices' => json_encode([['currency' => 'PEN', 'amount' => 450]]),
        ]);

        $controller = app(CommercialNegotiationProcessController::class);

        $this->actingAs($advisor);

        $controller->processStudent(Request::create('/'), $negotiation->id);

        $response = $controller->processSubscriptions(Request::create('/'), $negotiation->id);

        $this->assertTrue((bool) $response->getData(true)['success']);
        $this->assertSame(450.0, (float) AcaStudentSubscription::firstOrFail()->amount_paid);
    }

    public function test_el_correo_de_una_inscripcion_gratuita_se_renderiza_sin_comprobante(): void
    {
        $negotiation = $this->negotiationWithClient(0, 'gratis');
        $negotiation->load('items');

        $mail = new CommercialNegotiationDocumentMail(
            $negotiation,
            null,
            [],
            ['username' => 'cliente.gratuito@example.com', 'password' => '70123456'],
        );

        $this->assertSame([], $mail->attachments(), 'Sin comprobante no hay adjuntos.');

        $html = $mail->render();

        $this->assertStringContainsString('GRATIS', $html);
        $this->assertStringContainsString('cliente.gratuito@example.com', $html);
        $this->assertStringNotContainsString('Comprobante:', $html);
        $this->assertStringNotContainsString('TOTAL PAGADO', $html);
    }

    public function test_el_correo_de_una_negociacion_con_cobro_sigue_pidiendo_su_comprobante(): void
    {
        $negotiation = $this->negotiationWithClient(1200, 'transferencia');
        $negotiation->load('items');

        $document = SaleDocument::create([
            'sale_id' => 1,
            'serie_id' => 1,
            'number' => 'B001-00000001',
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B001',
            'invoice_correlative' => '00000001',
            'invoice_status' => 'Aceptada',
        ]);

        // El comprobante sigue siendo obligatorio cuando si hubo un pago: sin el PDF, el
        // correo no se envia. Es el comportamiento de antes y no debe haberse relajado al
        // volver opcional el comprobante para las negociaciones gratuitas.
        $mail = new CommercialNegotiationDocumentMail($negotiation, $document, [], null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El PDF del comprobante es obligatorio');

        $mail->attachments();
    }

    public function test_la_plantilla_del_correo_muestra_el_comprobante_cuando_la_negociacion_tiene_cobro(): void
    {
        $negotiation = $this->negotiationWithClient(1200, 'transferencia');
        $negotiation->load('items');

        $document = SaleDocument::create([
            'sale_id' => 1,
            'serie_id' => 1,
            'number' => 'B001-00000001',
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B001',
            'invoice_correlative' => '00000001',
            'invoice_status' => 'Aceptada',
        ]);

        $html = view('commercial::emails.commercial-negotiation-document', [
            'negotiation' => $negotiation,
            'document' => $document,
            'credentials' => null,
        ])->render();

        $this->assertStringContainsString('Comprobante: BOLETA B001-00000001', $html);
        $this->assertStringContainsString('TOTAL PAGADO', $html);
        $this->assertStringContainsString('PEN 1,200.00', $html, 'El monto real se conserva en el correo con cobro.');
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    /**
     * Roles del sistema que usan el alta y el proceso: el aviso de "negociacion
     * confirmada" resuelve los administradores por nombre, y el paso "crear usuario"
     * asigna el rol Alumno.
     */
    private function createRoles(): void
    {
        foreach (['Alumno', 'Administrador', 'admin', 'Asesor comercial'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    /**
     * Asesor con permiso para registrar negociaciones.
     */
    private function advisor(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'comm_negociaciones_nuevo',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate(['name' => 'Asesor comercial', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::create([
            'name' => 'Asesor de prueba',
            'email' => 'asesor.gratis@example.com',
            'password' => Hash::make(self::PASSWORD),
        ]);

        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * Negociacion ya creada, para probar el formulario publico del cliente.
     */
    private function negotiation(float $totalPrice, string $paymentMethod, string $status = 'pendiente'): CommercialNegotiation
    {
        return CommercialNegotiation::create([
            'token' => (string) Str::uuid(),
            'title' => 'Negociacion de prueba',
            'total_price' => $totalPrice,
            'currency' => 'PEN',
            'payment_type' => 'single',
            'payment_method' => $paymentMethod,
            'status' => $status,
        ]);
    }

    /**
     * Negociacion confirmada con su cliente y sus items (un curso y una suscripcion),
     * lista para recorrer los pasos del proceso de aprobacion.
     */
    private function negotiationWithClient(float $totalPrice, string $paymentMethod): CommercialNegotiation
    {
        $client = Person::create([
            'full_name' => 'Cliente Gratuito',
            'short_name' => 'Cliente Gratuito',
            'number' => '70123456',
            'email' => 'cliente.gratuito@example.com',
        ]);

        $negotiation = CommercialNegotiation::create([
            'token' => (string) Str::uuid(),
            'title' => 'Inscripcion de prueba',
            'total_price' => $totalPrice,
            'currency' => 'PEN',
            'payment_type' => 'single',
            'payment_method' => $paymentMethod,
            'status' => 'confirmada',
            'client_id' => $client->id,
            'client_data' => [
                'full_name' => 'Cliente Gratuito',
                'short_name' => 'Cliente Gratuito',
                'names' => 'Cliente',
                'father_lastname' => 'Gratuito',
                'mother_lastname' => 'Prueba',
                'document_type_id' => '1',
                'number' => '70123456',
                'email' => 'cliente.gratuito@example.com',
            ],
        ]);

        CommercialNegotiationItem::create([
            'negotiation_id' => $negotiation->id,
            'item_type' => 'course',
            'item_id' => 1,
            'title' => 'Curso de prueba',
            'price' => 1200,
        ]);

        CommercialNegotiationItem::create([
            'negotiation_id' => $negotiation->id,
            'item_type' => 'subscription',
            'item_id' => 1,
            'title' => 'Suscripcion de prueba',
            'price' => 300,
        ]);

        return $negotiation->fresh();
    }

    /**
     * Envio minimo del cliente: aceptaciones legales y tipo de documento. A proposito no
     * lleva los datos personales, porque lo unico que se revisa es que errores devuelve la
     * validacion (entre ellos si el voucher es obligatorio o no).
     *
     * @return array<string, mixed>
     */
    private function publicPayload(): array
    {
        return [
            'accepted' => true,
            'accepted_terminos' => true,
            'accepted_devoluciones' => true,
            'accepted_privacidad' => true,
            'invoice_type' => 'boleta',
            'document_type_id' => '1',
        ];
    }

    /**
     * Envio real y completo del formulario publico (boleta a nombre del propio cliente, DNI,
     * ubicacion peruana y las tres aceptaciones legales).
     *
     * @return array<string, mixed>
     */
    private function fullPublicPayload(): array
    {
        return array_merge($this->publicPayload(), [
            'number' => '70123456',
            'names' => 'Cliente',
            'father_lastname' => 'Gratuito',
            'mother_lastname' => 'Prueba',
            'gender' => 'M',
            'email' => 'cliente.nuevo@example.com',
            'telephone' => '999888777',
            'ubigeo' => '021801',
            'ubigeo_description' => 'Lima-Lima-Miraflores',
        ]);
    }

    /**
     * Columnas del alta y catalogo de tipos de documento que usa el formulario publico.
     */
    private function createNegotiationExtraSchema(): void
    {
        foreach (self::EXTRA_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }

        Schema::create('identity_document_type', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('sunat_code', 2)->nullable();
            $table->integer('number_characters')->nullable();
            $table->timestamps();
        });

        DB::table('identity_document_type')->insert([
            'id' => 1,
            'description' => 'DNI',
            'sunat_code' => '01',
            'number_characters' => 8,
        ]);

        // Parametros del sistema: de aqui salen los enlaces legales (PN00002-PN00004)
        // que el alta publica deja como constancia de aceptacion.
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->nullable();
            $table->string('description')->nullable();
            $table->string('control_type')->nullable();
            $table->string('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Esquema que necesitan los pasos del proceso de aprobacion: las columnas de la ficha
     * del cliente que actualiza el paso "persona", las de la cuenta de usuario que crea el
     * paso "usuario" y las matriculas/suscripciones que registran los pasos siguientes.
     */
    private function createProcessSchema(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('document_type_id')->nullable();
            $table->string('short_name')->nullable();
            $table->string('names')->nullable();
            $table->string('father_lastname')->nullable();
            $table->string('mother_lastname')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('ocupacion')->nullable();
            $table->unsignedBigInteger('occupation_id')->nullable();
            $table->string('profession')->nullable();
            $table->unsignedBigInteger('profession_id')->nullable();
            $table->string('company')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->string('industry')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->string('ubigeo')->nullable();
            $table->string('ubigeo_description')->nullable();
            $table->unsignedBigInteger('foreign_country_id')->nullable();
            $table->string('foreign_state')->nullable();
            $table->string('foreign_city')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('is_client')->nullable();
            $table->boolean('is_provider')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('local_id')->nullable();
            $table->boolean('status')->nullable();
        });

        Schema::create('aca_courses', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('type_description')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_cap_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->boolean('status')->default(true);
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('unlimited')->default(true);
            $table->boolean('payment_installments')->default(false);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('advancement', 12, 2)->default(0);
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_subscription_types', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('period')->nullable();
            $table->json('prices')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_student_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id');
            $table->primary(['student_id', 'subscription_id']);
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->integer('renewals')->default(0);
            $table->unsignedBigInteger('registration_user_id')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::table('aca_subscription_types')->insert([
            'id' => 1,
            'title' => 'Suscripcion de prueba',
            'period' => 'Mensual',
            'prices' => json_encode([['currency' => 'PEN', 'amount' => 300]]),
        ]);
    }
}
