<?php

namespace Tests\Unit\Modules\Commercial;

use App\Models\CompanyBilletera;
use App\Models\Person;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Academic\Entities\AcaStudent;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Commercial\Entities\CommercialNegotiationInvoice;
use Modules\Commercial\Entities\CommercialNegotiationItem;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Modules\Commercial\Concerns\BuildsCommercialNegotiationSchema;

/**
 * Borrado de negociaciones: quién puede, con qué contraseña y qué se conserva.
 *
 * Lo que se verifica aquí es lo que el borrado promete:
 *   - los roles administradores pueden eliminar una negociación en cualquier estado,
 *     incluida una ya aprobada o confirmada;
 *   - un vendedor no puede forzar el borrado de una negociación confirmada ni tocar la
 *     de otro asesor;
 *   - en los estados posteriores a la confirmación del alumno la eliminación exige la
 *     contraseña del usuario que la ejecuta;
 *   - en los estados anteriores se mantiene el borrado simple de siempre;
 *   - se eliminan solo la negociación y sus datos propios: el cliente, su cuenta de
 *     usuario, el alumno, la venta y el comprobante siguen existiendo.
 */
class NegotiationForceDeleteTest extends TestCase
{
    use BuildsCommercialNegotiationSchema;

    private const PASSWORD = 'secreto-comercial';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropCommercialSchema();
        $this->createCommercialSchema();
    }

    protected function tearDown(): void
    {
        $this->dropCommercialSchema();

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Borrado forzado del administrador
    // -----------------------------------------------------------------

    public function test_el_admin_elimina_una_negociacion_aprobada_con_su_contrasena(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-comercial@example.com');
        $negotiation = $this->negotiation('aprobada', $admin);
        $records = $this->processRecords($negotiation, $admin);

        $this->actingAs($admin)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('preserved.sale_id', $records['sale']->id)
            ->assertJsonPath('preserved.sale_document_id', $records['document']->id);

        $this->assertNegotiationGone($negotiation);
        $this->assertRecordsPreserved($negotiation, $records);
    }

    public function test_el_admin_elimina_una_negociacion_confirmada_que_antes_estaba_bloqueada(): void
    {
        $admin = $this->userWithRole('admin', 'admin-bajo@example.com');
        $negotiation = $this->negotiation('confirmada', $admin);

        $this->actingAs($admin)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNegotiationGone($negotiation);
    }

    // -----------------------------------------------------------------
    // La contraseña del que elimina
    // -----------------------------------------------------------------

    public function test_la_contrasena_incorrecta_no_elimina_la_negociacion(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-clave-mala@example.com');
        $negotiation = $this->negotiation('aprobada', $admin);

        $this->actingAs($admin)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => 'no-es-mi-clave'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('commercial_negotiations', ['id' => $negotiation->id]);
    }

    public function test_el_borrado_sensible_sin_contrasena_no_pasa(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-sin-clave@example.com');
        $negotiation = $this->negotiation('completada', $admin);

        $this->actingAs($admin)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('commercial_negotiations', ['id' => $negotiation->id]);
    }

    // -----------------------------------------------------------------
    // Vendedores (rol Ventas)
    // -----------------------------------------------------------------

    public function test_un_vendedor_no_puede_forzar_el_borrado_de_una_negociacion_confirmada(): void
    {
        $ventas = $this->userWithRole('Ventas', 'ventas-comercial@example.com');
        $negotiation = $this->negotiation('confirmada', $ventas);

        $this->actingAs($ventas)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => self::PASSWORD])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('commercial_negotiations', ['id' => $negotiation->id]);
    }

    public function test_un_vendedor_no_puede_eliminar_la_negociacion_de_otro_asesor(): void
    {
        $asesor = $this->userWithRole('Ventas', 'asesor-comercial@example.com');
        $otro = $this->userWithRole('Ventas', 'otro-asesor@example.com');
        $negotiation = $this->negotiation('aprobada', $asesor);

        $this->actingAs($otro)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => self::PASSWORD])
            ->assertForbidden();

        $this->assertDatabaseHas('commercial_negotiations', ['id' => $negotiation->id]);
    }

    public function test_el_asesor_que_creo_la_negociacion_la_elimina_con_su_contrasena(): void
    {
        $asesor = $this->userWithRole('Ventas', 'asesor-aprobada@example.com');
        $negotiation = $this->negotiation('aprobada', $asesor);
        $records = $this->processRecords($negotiation, $asesor);

        $this->actingAs($asesor)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id), ['password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNegotiationGone($negotiation);
        $this->assertRecordsPreserved($negotiation, $records);
    }

    // -----------------------------------------------------------------
    // Estados anteriores: sin contraseña, como siempre
    // -----------------------------------------------------------------

    public function test_un_estado_anterior_al_confirmado_se_elimina_sin_contrasena(): void
    {
        $asesor = $this->userWithRole('Ventas', 'asesor-pendiente@example.com');
        $negotiation = $this->negotiation('pendiente', $asesor);

        $this->actingAs($asesor)
            ->deleteJson(route('comm_negotiations_destroy', $negotiation->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNegotiationGone($negotiation);
    }

    // -----------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------

    private function userWithRole(string $roleName, string $email): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'comm_negociaciones_eliminar',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::create([
            'name' => 'Usuario '.$roleName,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
        ]);

        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * Negociación con sus datos propios: items, datos de facturación y billetera.
     */
    private function negotiation(string $status, ?User $advisor = null): CommercialNegotiation
    {
        $client = Person::create([
            'full_name' => 'Cliente de Prueba',
            'number' => '70123456',
            'email' => 'cliente.comercial@example.com',
        ]);

        $negotiation = CommercialNegotiation::create([
            'token' => (string) Str::uuid(),
            'title' => 'Negociacion de prueba',
            'total_price' => 1200,
            'currency' => 'PEN',
            'payment_type' => 'single',
            'payment_method' => 'transferencia',
            'status' => $status,
            'client_id' => $client->id,
            'created_by' => $advisor?->id,
        ]);

        CommercialNegotiationItem::create([
            'negotiation_id' => $negotiation->id,
            'item_type' => 'course',
            'title' => 'Curso de prueba',
            'price' => 1200,
        ]);

        CommercialNegotiationInvoice::create([
            'negotiation_id' => $negotiation->id,
            'invoice_type' => 'boleta',
        ]);

        $billetera = CompanyBilletera::create([
            'account_name' => 'Empresa de prueba',
            'account_number' => '999888777',
            'status' => true,
        ]);

        $negotiation->companyBilleteras()->attach($billetera->id);

        return $negotiation->fresh();
    }

    /**
     * Registros que el proceso de aprobación crea y que el borrado debe respetar:
     * cuenta de usuario del cliente, alumno, venta y comprobante emitido.
     *
     * @return array{client_user: User, student: AcaStudent, sale: Sale, document: SaleDocument}
     */
    private function processRecords(CommercialNegotiation $negotiation, User $advisor): array
    {
        $clientUser = User::create([
            'name' => 'Cliente de Prueba',
            'email' => 'cliente.cuenta@example.com',
            'person_id' => $negotiation->client_id,
            'password' => Hash::make('clave-cliente'),
        ]);

        $student = AcaStudent::create([
            'student_code' => 'ALU-0001',
            'person_id' => $negotiation->client_id,
        ]);

        $sale = Sale::create([
            'user_id' => $advisor->id,
            'client_id' => $negotiation->client_id,
            'local_id' => 1,
            'total' => 1200,
        ]);

        $document = SaleDocument::create([
            'sale_id' => $sale->id,
            'serie_id' => 1,
            'number' => 'B001-00000001',
            'invoice_type_doc' => '03',
            'invoice_serie' => 'B001',
            'invoice_correlative' => '00000001',
            'invoice_status' => 'Aceptada',
        ]);

        $negotiation->update([
            'sale_id' => $sale->id,
            'sale_document_id' => $document->id,
            'verified_by' => $advisor->id,
            'verified_at' => now(),
        ]);

        return [
            'client_user' => $clientUser,
            'student' => $student,
            'sale' => $sale,
            'document' => $document,
        ];
    }

    /**
     * Se borra la negociación y solo sus datos propios.
     */
    private function assertNegotiationGone(CommercialNegotiation $negotiation): void
    {
        $this->assertDatabaseMissing('commercial_negotiations', ['id' => $negotiation->id]);
        $this->assertDatabaseMissing('commercial_negotiation_items', ['negotiation_id' => $negotiation->id]);
        $this->assertDatabaseMissing('commercial_negotiation_invoices', ['negotiation_id' => $negotiation->id]);
        $this->assertDatabaseMissing('commercial_negotiation_company_billetera', ['negotiation_id' => $negotiation->id]);
    }

    /**
     * Todo lo que el proceso creó sigue en pie.
     *
     * @param  array{client_user: User, student: AcaStudent, sale: Sale, document: SaleDocument}  $records
     */
    private function assertRecordsPreserved(CommercialNegotiation $negotiation, array $records): void
    {
        $this->assertDatabaseHas('people', ['id' => $negotiation->client_id]);
        $this->assertDatabaseHas('users', ['id' => $records['client_user']->id]);
        $this->assertDatabaseHas('aca_students', ['id' => $records['student']->id]);
        $this->assertDatabaseHas('sales', ['id' => $records['sale']->id]);
        $this->assertDatabaseHas('sale_documents', ['id' => $records['document']->id]);
    }
}
