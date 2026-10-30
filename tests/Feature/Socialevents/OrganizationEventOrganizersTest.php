<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Responsables de un torneo: el organizador queda fijo a su cuenta y solo el
 * superadministrador puede elegir entre todas las personas.
 */
class OrganizationEventOrganizersTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $owner;

    private Person $ownerPerson;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->ownerPerson = Person::create([
            'document_type_id' => '1',
            'number' => '70000001',
            'full_name' => 'Ana Torres Ríos',
            'short_name' => 'Ana Torres',
            'email' => 'ana@example.com',
        ]);

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de Ana',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->owner = User::factory()->create([
            'person_id' => $this->ownerPerson->id,
            'socialevent_organization_id' => $this->organization->id,
            'name' => 'Ana',
            'apellido_paterno' => 'Torres',
            'apellido_materno' => 'Ríos',
            'email' => 'ana@example.com',
        ]);

        $this->organization->forceFill(['created_by' => $this->owner->id])->save();

        DB::table('socialevent_organization_users')->insert([
            'organization_id' => $this->organization->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Permission::firstOrCreate(['name' => 'even_evento_nuevo', 'guard_name' => 'web']);
        $this->owner->givePermissionTo('even_evento_nuevo');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function adminConPermiso(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'even_evento_nuevo', 'guard_name' => 'web']));

        $admin = User::factory()->create();
        $admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    public function test_el_organizador_ve_su_nombre_fijo_y_no_la_lista_de_personas(): void
    {
        $this->actingAs($this->owner)
            ->get('/socialevents/events/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizers.locked', true)
                ->where('organizers.label', 'Ana Torres Ríos')
                ->where('organizers.default.0', 'u:' . $this->owner->id)
                ->has('instructors', 1)
                ->where('instructors.0.value', 'u:' . $this->owner->id));
    }

    public function test_el_organizador_no_puede_asignar_una_persona_ajena_y_el_enlace_es_opcional(): void
    {
        $ajena = Person::create([
            'document_type_id' => '1',
            'number' => '70000009',
            'full_name' => 'Persona ajena',
            'short_name' => 'Persona ajena',
        ]);

        $this->actingAs($this->owner)
            ->post('/socialevents/events/store', [
                'title' => 'Copa de prueba',
                'description' => '<p>Torneo de prueba</p>',
                'tickets_quantity' => 16,
                'date' => [
                    ['$d' => '2026-11-01'],
                    ['$d' => '2026-11-05'],
                ],
                // Intento de asignar a alguien que no pertenece a la organización.
                'exhibitors' => [(string) $ajena->id],
            ])
            ->assertRedirect(route('even_eventos_list'))
            ->assertSessionHasNoErrors();

        $event = EvenEvent::query()->where('title', 'Copa de prueba')->firstOrFail();

        $this->assertSame($this->organization->id, (int) $event->organization_id);
        $this->assertDatabaseHas('even_event_exhibitors', [
            'event_id' => $event->id,
            'exhibitor_id' => $this->ownerPerson->id,
        ]);
        $this->assertDatabaseMissing('even_event_exhibitors', [
            'event_id' => $event->id,
            'exhibitor_id' => $ajena->id,
        ]);
    }

    public function test_al_organizador_sin_persona_se_le_crea_desde_su_cuenta_de_registro(): void
    {
        $nuevo = User::factory()->create([
            'name' => 'Carla',
            'apellido_paterno' => 'Mendoza',
            'apellido_materno' => 'Luna',
            'email' => 'carla@example.com',
            'tipo_identidad' => 'DNI',
            'numero_identidad' => '70000020',
            'telefono' => '999888777',
            'socialevent_organization_id' => $this->organization->id,
        ]);
        $nuevo->givePermissionTo('even_evento_nuevo');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('socialevent_organization_users')->insert([
            'organization_id' => $this->organization->id,
            'user_id' => $nuevo->id,
            'role' => 'member',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->post('/socialevents/events/store', [
                'title' => 'Copa de Carla',
                'description' => '<p>Torneo de prueba</p>',
                'tickets_quantity' => 10,
                'date' => [
                    ['$d' => '2026-11-10'],
                    ['$d' => '2026-11-12'],
                ],
                'exhibitors' => ['u:' . $nuevo->id],
            ])
            ->assertRedirect(route('even_eventos_list'))
            ->assertSessionHasNoErrors();

        $persona = Person::query()->where('number', '70000020')->firstOrFail();
        $this->assertSame('Carla Mendoza Luna', $persona->full_name);
        $this->assertSame('1', $persona->document_type_id);
        $this->assertSame($persona->id, (int) $nuevo->fresh()->person_id);

        $event = EvenEvent::query()->where('title', 'Copa de Carla')->firstOrFail();
        $this->assertDatabaseHas('even_event_exhibitors', [
            'event_id' => $event->id,
            'exhibitor_id' => $persona->id,
        ]);
    }

    public function test_el_superadministrador_puede_elegir_entre_todas_las_personas(): void
    {
        $admin = $this->adminConPermiso();
        $otra = Person::create([
            'document_type_id' => '1',
            'number' => '70000010',
            'full_name' => 'Otra Persona',
            'short_name' => 'Otra Persona',
        ]);

        $this->actingAs($admin)
            ->get('/socialevents/events/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizers.locked', false)
                ->has('instructors', 3));

        $this->actingAs($admin)
            ->post('/socialevents/events/store', [
                'title' => 'Torneo del administrador',
                'description' => '<p>Torneo de prueba</p>',
                'tickets_quantity' => 8,
                'date' => [
                    ['$d' => '2026-12-01'],
                    ['$d' => '2026-12-03'],
                ],
                'exhibitors' => [(string) $otra->id],
            ])
            ->assertRedirect(route('even_eventos_list'))
            ->assertSessionHasNoErrors();

        $event = EvenEvent::query()->where('title', 'Torneo del administrador')->firstOrFail();

        $this->assertDatabaseHas('even_event_exhibitors', [
            'event_id' => $event->id,
            'exhibitor_id' => $otra->id,
        ]);
    }

    public function test_una_organizacion_con_varios_usuarios_habilita_la_seleccion(): void
    {
        $segundaPersona = Person::create([
            'document_type_id' => '1',
            'number' => '70000011',
            'full_name' => 'Bruno Salas',
            'short_name' => 'Bruno Salas',
            'email' => 'bruno@example.com',
        ]);

        $segundoUsuario = User::factory()->create([
            'person_id' => $segundaPersona->id,
            'socialevent_organization_id' => $this->organization->id,
            'name' => 'Bruno',
            'apellido_paterno' => 'Salas',
            'email' => 'bruno@example.com',
        ]);

        DB::table('socialevent_organization_users')->insert([
            'organization_id' => $this->organization->id,
            'user_id' => $segundoUsuario->id,
            'role' => 'member',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get('/socialevents/events/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organizers.locked', false)
                ->has('instructors', 2)
                // El creador de la organización sigue siendo el valor por defecto.
                ->where('organizers.default.0', 'u:' . $this->owner->id));
    }
}
