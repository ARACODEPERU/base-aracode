<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Locales en modo deportivo (PW00001 = 2): cada organización trabaja con sus propios
 * complejos y el organizador edita o elimina solo los que creó.
 */
class SportsOrganizationLocalTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización de prueba');
    }

    private function crearOrganizacion(string $nombre): SocialeventOrganization
    {
        return SocialeventOrganization::create([
            'name' => $nombre,
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
        ]);
    }

    private function organizador(?SocialeventOrganization $organizacion = null): User
    {
        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        foreach (['even_local_listado', 'even_local_nuevo', 'even_local_editar', 'even_local_eliminar'] as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']));
        }

        $user = User::factory()->create(['socialevent_organization_id' => ($organizacion ?? $this->organization)->id]);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function localDe(?int $creadorId, string $nombre = 'Cancha de prueba'): EvenLocal
    {
        $creador = $creadorId ? User::find($creadorId) : null;

        return EvenLocal::create([
            'names' => $nombre,
            'address' => 'Av. Principal 123',
            'map_code' => '<iframe></iframe>',
            'maximum_capacity' => 200,
            'status' => true,
            'created_by' => $creadorId,
            'socialevent_organization_id' => $creador?->socialevent_organization_id,
        ]);
    }

    private function datosLocal(): array
    {
        return [
            'names' => 'Complejo nuevo',
            'address' => 'Jr. Los Olivos 456',
            'map_code' => '<iframe></iframe>',
            'maximum_capacity' => 150,
            'status' => true,
        ];
    }

    public function test_organizador_crea_local_de_su_organizacion(): void
    {
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post('/socialevents/locales/store', $this->datosLocal())->assertRedirect();

        $this->assertDatabaseHas('even_locals', [
            'names' => 'Complejo nuevo',
            'created_by' => $organizador->id,
            'socialevent_organization_id' => $this->organization->id,
        ]);
    }

    public function test_organizador_edita_y_elimina_solo_los_locales_que_creo(): void
    {
        $organizador = $this->organizador();
        $propio = $this->localDe($organizador->id);

        $this->actingAs($organizador)->post('/socialevents/locales/update', [
            'id' => $propio->id,
            ...$this->datosLocal(),
            'names' => 'Renombrado',
        ])->assertOk(); // update() del controlador no redirige: responde 200 sin cuerpo.
        $this->assertDatabaseHas('even_locals', ['id' => $propio->id, 'names' => 'Renombrado']);

        $this->actingAs($organizador)->delete("/socialevents/locales/destroy/{$propio->id}")
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('even_locals', ['id' => $propio->id]);
    }

    public function test_organizador_no_edita_ni_elimina_locales_ajenos_ni_sin_dueño(): void
    {
        $organizador = $this->organizador();
        $otro = $this->organizador();
        $ajeno = $this->localDe($otro->id);
        $sinDueno = $this->localDe(null);

        $this->actingAs($organizador)->get("/socialevents/locales/{$ajeno->id}/edit")->assertForbidden();
        $this->actingAs($organizador)->post('/socialevents/locales/update', [
            'id' => $ajeno->id,
            ...$this->datosLocal(),
        ])->assertForbidden();
        $this->actingAs($organizador)->delete("/socialevents/locales/destroy/{$ajeno->id}")->assertForbidden();
        $this->actingAs($organizador)->delete("/socialevents/locales/destroy/{$sinDueno->id}")->assertForbidden();

        $this->assertDatabaseHas('even_locals', ['id' => $ajeno->id, 'names' => 'Cancha de prueba']);
        $this->assertDatabaseHas('even_locals', ['id' => $sinDueno->id]);
    }

    public function test_cada_organizacion_ve_solo_sus_locales(): void
    {
        $otraOrganizacion = $this->crearOrganizacion('Otra organización');
        $propio = $this->organizador();
        $ajeno = $this->organizador($otraOrganizacion);

        $this->localDe($propio->id, 'Complejo de la primera');
        $this->localDe($ajeno->id, 'Complejo de la segunda');

        $this->actingAs($propio)->get('/socialevents/locales')
            ->assertInertia(fn ($page) => $page
                ->has('locales.data', 1)
                ->where('locales.data.0.names', 'Complejo de la primera'));

        $this->actingAs($ajeno)->get('/socialevents/locales')
            ->assertInertia(fn ($page) => $page
                ->has('locales.data', 1)
                ->where('locales.data.0.names', 'Complejo de la segunda'));
    }
}
