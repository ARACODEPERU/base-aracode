<?php

namespace Tests\Unit\Modules\Commercial;

use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Commercial\Http\Controllers\CommercialNegotiationProcessController;
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
 *     sigue siendo obligatorio cuando hay un pago que acreditar.
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
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropCommercialSchema();
        $this->createCommercialSchema();
        $this->createNegotiationExtraSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('identity_document_type');
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

        $this->assertDatabaseCount('commercial_negotiations', 0);
    }

    // -----------------------------------------------------------------
    // Proceso de aprobacion
    // -----------------------------------------------------------------

    public function test_una_negociacion_gratuita_no_pasa_por_el_paso_del_comprobante(): void
    {
        $gratuita = $this->negotiation(0, 'gratis', 'confirmada');
        $conCobro = $this->negotiation(1200, 'transferencia', 'confirmada');

        $controller = app(CommercialNegotiationProcessController::class);

        $this->assertSame('skipped', $controller->stepStatuses($gratuita)['document']);
        $this->assertSame('pending', $controller->stepStatuses($conCobro)['document']);

        // El paso tampoco se ejecuta si se llega a llamar: no hay venta que registrar ni
        // comprobante que emitir por un importe de 0.
        $respuesta = $controller->processDocument(Request::create('/'), $gratuita->id);

        $this->assertTrue((bool) $respuesta->getData(true)['skipped']);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_documents', 0);
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

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

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
    }
}
